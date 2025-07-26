// admin/js/dashboard-main.js

// Dashboard Ana Sınıfı
class Dashboard {
    constructor() {
        this.refreshIntervals = [];
        this.isLoading = false;
        this.lastUpdate = null;
    }
    
    // Dashboard'u başlat
    async init() {
        try {
            console.log('Dashboard initializing...');
            
            // Auth kontrolü
            if (!await this.checkAuth()) {
                window.location.href = 'login.html';
                return;
            }
            
            // UI bileşenlerini render et
            RENDERER.init();
            
            // Event listener'ları kur
            this.setupEventListeners();
            
            // Verileri yükle
            await this.loadAllData();
            
            // Chart'ları başlat
            CHARTS.init();
            
            // Periyodik güncellemeleri başlat
            this.startPeriodicUpdates();
            
            console.log('Dashboard initialized successfully');
            
        } catch (error) {
            console.error('Dashboard initialization error:', error);
            this.showError('Dashboard yüklenirken hata oluştu');
        } finally {
            this.hideLoading();
        }
    }
    
    // Auth kontrolü
    async checkAuth() {
        try {
            const response = await API.post('/auth.php', { action: 'check' });
            if (response.success && response.data.authenticated) {
                DASHBOARD_STATE.user = response.data.user;
                this.updateUserInfo(response.data.user);
                return true;
            }
            return false;
        } catch (error) {
            console.error('Auth check error:', error);
            return false;
        }
    }
    
    // Event listener'ları kur
    setupEventListeners() {
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });
        }
        
        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            });
        }
        
        // Logout
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', this.handleLogout.bind(this));
        }
        
        // Quick actions dropdown
        const quickActionsBtn = document.getElementById('quick-actions-btn');
        const dropdown = document.getElementById('quick-actions-dropdown');
        
        if (quickActionsBtn && dropdown) {
            quickActionsBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            
            document.addEventListener('click', () => {
                dropdown.classList.add('hidden');
            });
        }
        
        // Notifications
        const notificationsBtn = document.getElementById('notifications-btn');
        if (notificationsBtn) {
            notificationsBtn.addEventListener('click', this.showNotifications.bind(this));
        }
        
        // Window resize
        window.addEventListener('resize', UTILS.debounce(() => {
            CHARTS.resize();
        }, 250));
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.refreshDashboard();
            }
        });
    }
    
    // Tüm verileri yükle
    async loadAllData() {
        const promises = [
            this.loadStats(),
            this.loadWaiterCalls(),
            this.loadRecentActivities(),
            CHARTS.loadData()
        ];
        
        await Promise.allSettled(promises);
        this.lastUpdate = new Date();
    }
    
    // İstatistikleri yükle
    async loadStats() {
        try {
            const [menuResponse, categoryResponse, waiterResponse] = await Promise.all([
                API.get('/menu.php', { restaurant_id: DASHBOARD_CONFIG.RESTAURANT_ID }),
                API.get('/categories.php', { restaurant_id: DASHBOARD_CONFIG.RESTAURANT_ID, with_count: true }),
                API.get('/waiter-call.php', { restaurant_id: DASHBOARD_CONFIG.RESTAURANT_ID, action: 'pending' })
            ]);
            
            // İstatistikleri güncelle
            if (menuResponse.success) {
                document.getElementById('total-items').textContent = menuResponse.data.length;
            }
            
            if (categoryResponse.success) {
                document.getElementById('total-categories').textContent = categoryResponse.data.length;
            }
            
            if (waiterResponse.success) {
                const pendingCount = waiterResponse.data.length;
                const urgentCount = waiterResponse.data.filter(call => call.priority === 'high').length;
                
                document.getElementById('pending-calls').textContent = pendingCount;
                document.getElementById('urgent-calls').textContent = urgentCount;
                
                // Badge'leri güncelle
                this.updateCallsBadges(pendingCount);
            }
            
            DASHBOARD_STATE.stats = {
                totalItems: menuResponse.data?.length || 0,
                totalCategories: categoryResponse.data?.length || 0,
                pendingCalls: waiterResponse.data?.length || 0
            };
            
        } catch (error) {
            console.error('Stats loading error:', error);
        }
    }
    
    // Garson çağrılarını yükle
    async loadWaiterCalls() {
        try {
            const response = await API.get('/waiter-call.php', {
                restaurant_id: DASHBOARD_CONFIG.RESTAURANT_ID,
                action: 'pending'
            });
            
            if (response.success) {
                DASHBOARD_STATE.waiterCalls = response.data;
                this.renderWaiterCalls(response.data);
                this.updateCallsBadges(response.data.length);
            }
        } catch (error) {
            console.error('Waiter calls loading error:', error);
        }
    }
    
    // Son aktiviteleri yükle (mock data)
    async loadRecentActivities() {
        // Bu normalde bir API'den gelir
        const activities = [
            { type: 'info', title: 'Sistem başlatıldı', description: 'Dashboard aktif - ' + UTILS.getCurrentDate(), time: new Date() },
            { type: 'success', title: 'Veriler yüklendi', description: 'Tüm veriler başarıyla yüklendi', time: new Date(Date.now() - 300000) }
        ];
        
        this.renderActivities(activities);
    }
    
    // Garson çağrılarını render et
    renderWaiterCalls(calls) {
        const container = document.getElementById('waiter-calls-list');
        if (!container) return;
        
        if (calls.length === 0) {
            container.innerHTML = COMPONENTS.emptyState('garson');
            return;
        }
        
        container.innerHTML = calls.map(call => COMPONENTS.waiterCallItem(call)).join('');
    }
    
    // Aktiviteleri render et
    renderActivities(activities) {
        const container = document.getElementById('recent-activities');
        if (!container) return;
        
        if (activities.length === 0) {
            container.innerHTML = COMPONENTS.emptyState('activities');
            return;
        }
        
        container.innerHTML = activities.map(activity => 
            COMPONENTS.activityItem(activity.title, activity.description, activity.type)
        ).join('');
    }
    
        // Çağrı badge'lerini güncelle
    updateCallsBadges(count) {
        // Sidebar badge
        const sidebarBadge = document.getElementById('waiter-calls-badge');
        if (sidebarBadge) {
            if (count > 0) {
                sidebarBadge.textContent = count;
                sidebarBadge.classList.remove('hidden');
            } else {
                sidebarBadge.classList.add('hidden');
            }
        }
        
        // Header notification badge
        const notificationBadge = document.getElementById('notification-badge');
        if (notificationBadge) {
            if (count > 0) {
                notificationBadge.textContent = count;
                notificationBadge.classList.remove('hidden');
            } else {
                notificationBadge.classList.add('hidden');
            }
        }
        
        // Calls count display
        const callsCount = document.getElementById('calls-count');
        if (callsCount) {
            callsCount.textContent = `${count} bekleyen`;
        }
    }
    
    // Kullanıcı bilgilerini güncelle
    updateUserInfo(user) {
        const userName = document.getElementById('user-name');
        const userAvatar = document.getElementById('user-avatar');
        
        if (userName) {
            userName.textContent = user.full_name || user.username;
        }
        
        if (userAvatar) {
            userAvatar.textContent = (user.full_name || user.username).charAt(0).toUpperCase();
        }
    }
    
    // Periyodik güncellemeleri başlat
    startPeriodicUpdates() {
        // Garson çağrıları - 30 saniyede bir
        this.refreshIntervals.push(
            setInterval(() => {
                this.loadWaiterCalls();
            }, DASHBOARD_CONFIG.WAITER_CALLS_REFRESH)
        );
        
        // İstatistikler - 5 dakikada bir
        this.refreshIntervals.push(
            setInterval(() => {
                this.loadStats();
            }, DASHBOARD_CONFIG.STATS_REFRESH)
        );
        
        // Chart'lar - 10 dakikada bir
        this.refreshIntervals.push(
            setInterval(() => {
                CHARTS.loadData();
            }, DASHBOARD_CONFIG.CHARTS_REFRESH)
        );
    }
    
    // Periyodik güncellemeleri durdur
    stopPeriodicUpdates() {
        this.refreshIntervals.forEach(interval => clearInterval(interval));
        this.refreshIntervals = [];
    }
    
    // Dashboard'u yenile
    async refreshDashboard() {
        if (this.isLoading) return;
        
        this.showLoading();
        await this.loadAllData();
        this.hideLoading();
        
        this.showToast('Dashboard güncellendi', 'success');
    }
    
    // Çıkış işlemi
    async handleLogout() {
        if (!confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
            return;
        }
        
        try {
            await API.post('/auth.php', { action: 'logout' });
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Local storage'ı temizle
            localStorage.removeItem('admin_user');
            
            // Interval'ları durdur
            this.stopPeriodicUpdates();
            
            // Login sayfasına yönlendir
            window.location.href = 'login.html';
        }
    }
    
    // Bildirimleri göster
    showNotifications() {
        const notifications = DASHBOARD_STATE.waiterCalls.map(call => ({
            id: call.id,
            title: `Masa ${call.table_number}`,
            message: call.message,
            time: call.created_at,
            type: 'waiter-call'
        }));
        
        // Notification modal'ı göster (implementasyon gerekli)
        console.log('Notifications:', notifications);
        this.showToast(`${notifications.length} bildirim var`, 'info');
    }
    
    // Loading göster
    showLoading() {
        const loadingScreen = document.getElementById('loading-screen');
        if (loadingScreen) {
            loadingScreen.classList.remove('hidden');
            this.isLoading = true;
        }
    }
    
    // Loading gizle
    hideLoading() {
        const loadingScreen = document.getElementById('loading-screen');
        if (loadingScreen) {
            loadingScreen.classList.add('hidden');
            this.isLoading = false;
        }
    }
    
    // Toast mesajı göster
    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.innerHTML = COMPONENTS.toast(message, type);
        document.body.appendChild(toast);
        
        const toastEl = toast.querySelector('div');
        setTimeout(() => {
            toastEl.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            toastEl.style.transform = 'translateX(100%)';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Hata mesajı göster
    showError(message) {
        this.showToast(message, 'error');
    }
}

// Global fonksiyonlar - garson çağrı işlemleri
window.acknowledgeCall = async function(callId) {
    try {
        const response = await API.post('/waiter-call.php', {
            action: 'update_status',
            id: callId,
            status: 'acknowledged'
        });
        
        if (response.success) {
            dashboard.showToast('Çağrı kabul edildi', 'success');
            dashboard.loadWaiterCalls();
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        console.error('Acknowledge call error:', error);
        dashboard.showError('Çağrı kabul edilemedi');
    }
};

window.completeCall = async function(callId) {
    try {
        const response = await API.post('/waiter-call.php', {
            action: 'update_status',
            id: callId,
            status: 'completed'
        });
        
        if (response.success) {
            dashboard.showToast('Çağrı tamamlandı', 'success');
            dashboard.loadWaiterCalls();
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        console.error('Complete call error:', error);
        dashboard.showError('Çağrı tamamlanamadı');
    }
};

// Dashboard instance'ı oluştur
const dashboard = new Dashboard();

// DOM yüklendiğinde dashboard'u başlat
document.addEventListener('DOMContentLoaded', () => {
    dashboard.init();
});

// Sayfa kapatılırken cleanup
window.addEventListener('beforeunload', () => {
    dashboard.stopPeriodicUpdates();
});

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl+R: Refresh dashboard
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        dashboard.refreshDashboard();
    }
    
    // Ctrl+L: Logout
    if (e.ctrlKey && e.key === 'l') {
        e.preventDefault();
        dashboard.handleLogout();
    }
    
    // F5: Refresh page
    if (e.key === 'F5') {
        e.preventDefault();
        window.location.reload();
    }
});

// Service Worker güncellemelerini dinle
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data && event.data.type === 'CACHE_UPDATED') {
            dashboard.showToast('Uygulama güncellendi', 'info');
        }
    });
}

// Online/offline durumu
window.addEventListener('online', () => {
    dashboard.showToast('İnternet bağlantısı geri geldi', 'success');
    dashboard.refreshDashboard();
});

window.addEventListener('offline', () => {
    dashboard.showToast('İnternet bağlantısı kesildi', 'error');
});

// Export dashboard instance for global access
window.dashboard = dashboard;