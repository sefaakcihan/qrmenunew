// admin/js/dashboard-components.js

// Component Templates
const COMPONENTS = {
    
    // Sidebar HTML
    sidebar: () => `
        <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 gradient-bg">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-white text-xl font-bold">Admin Panel</h1>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="mt-8">
                <a href="#dashboard" class="sidebar-item active flex items-center px-6 py-3 text-gray-700">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="menu-manager.html" class="sidebar-item flex items-center px-6 py-3 text-gray-700">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    Menü Yönetimi
                </a>
                <a href="theme-manager.html" class="sidebar-item flex items-center px-6 py-3 text-gray-700">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                    </svg>
                    Tema Yönetimi
                </a>
                <a href="#waiter-calls" class="sidebar-item flex items-center px-6 py-3 text-gray-700">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Garson Çağrıları
                    <span id="waiter-calls-badge" class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full hidden notification-dot">0</span>
                </a>
                <a href="#settings" class="sidebar-item flex items-center px-6 py-3 text-gray-700">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Ayarlar
                </a>
            </nav>
            
            <!-- User Info -->
            <div class="absolute bottom-0 w-full p-4 border-t bg-gray-50">
                <div class="flex items-center">
                    <div class="w-10 h-10 gradient-bg rounded-full flex items-center justify-center">
                        <span id="user-avatar" class="text-white text-sm font-bold">A</span>
                    </div>
                    <div class="ml-3 flex-1">
                        <p id="user-name" class="text-sm font-medium text-gray-900">Admin</p>
                        <p class="text-xs text-gray-500">Sistem Yöneticisi</p>
                    </div>
                    <button id="logout-btn" class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `,
    
    // Header HTML
    header: () => `
        <header class="bg-white shadow-sm border-b sticky top-0 z-30">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="lg:hidden mr-4 text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Dashboard</h2>
                        <p class="text-sm text-gray-500">Lezzet Restaurant Yönetim Paneli</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button id="notifications-btn" class="relative p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-4.03-4.03a9.953 9.953 0 00-.97-1.45L15 11.5V6a3 3 0 10-6 0v5.5l.03.03a9.953 9.953 0 00-.97 1.45L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span id="notification-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center hidden notification-dot">0</span>
                    </button>
                    
                    <div class="relative">
                        <button id="quick-actions-btn" class="gradient-bg text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>Hızlı İşlem</span>
                        </button>
                        <div id="quick-actions-dropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border hidden z-50">
                            <div class="py-2">
                                <a href="menu-manager.html?action=add" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Yeni Ürün Ekle
                                </a>
                                <a href="../index.html" target="_blank" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Menüyü Görüntüle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
    `,
    
    // Welcome Section
    welcome: () => `
        <div class="mb-8">
            <div class="gradient-bg rounded-xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold mb-2">Hoş Geldiniz! 👋</h1>
                        <p class="opacity-90">Lezzet Restaurant yönetim paneline hoş geldiniz.</p>
                    </div>
                    <div class="hidden md:block">
                        <div class="text-right">
                            <p class="text-sm opacity-75">Bugünün Tarihi</p>
                            <p id="current-date" class="text-lg font-semibold">${UTILS.getCurrentDate()}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    
    // Stats Cards
    statsCards: () => `
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover animate-slide-up">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 font-medium">Toplam Ürün</p>
                        <p id="total-items" class="text-2xl font-bold text-gray-900">0</p>
                        <p class="text-xs text-green-600">+3 bu hafta</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover animate-slide-up" style="animation-delay: 0.1s">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 font-medium">Kategoriler</p>
                        <p id="total-categories" class="text-2xl font-bold text-gray-900">0</p>
                        <p class="text-xs text-blue-600">Aktif kategoriler</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover animate-slide-up" style="animation-delay: 0.2s">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 font-medium">Bekleyen Çağrılar</p>
                        <p id="pending-calls" class="text-2xl font-bold text-gray-900">0</p>
                        <p class="text-xs text-orange-600">Acil: <span id="urgent-calls">0</span></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover animate-slide-up" style="animation-delay: 0.3s">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 font-medium">Aktif Masalar</p>
                        <p id="active-tables" class="text-2xl font-bold text-gray-900">12</p>
                        <p class="text-xs text-purple-600">Toplam: 24</p>
                    </div>
                </div>
            </div>
        </div>
    `,
    
    // Charts Section
    charts: () => `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Popüler Ürünler</h3>
                    <div class="flex space-x-2">
                        <button class="text-xs px-3 py-1 bg-gray-100 rounded-full hover:bg-gray-200">7 Gün</button>
                        <button class="text-xs px-3 py-1 bg-primary text-white rounded-full">30 Gün</button>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="popular-items-chart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Kategori Dağılımı</h3>
                <div class="h-64">
                    <canvas id="category-chart"></canvas>
                </div>
            </div>
        </div>
    `,
    
    // Activities Section
    activities: () => `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Son Aktiviteler</h3>
                    <button class="text-primary hover:text-opacity-80 text-sm">Tümünü Gör</button>
                </div>
                <div id="recent-activities" class="space-y-4">
                    ${COMPONENTS.activityItem('Yeni ürün eklendi', 'Adana Kebap - 2 dakika önce', 'success')}
                    ${COMPONENTS.activityItem('Tema güncellendi', 'Modern tema aktif edildi - 5 dakika önce', 'info')}
                    ${COMPONENTS.activityItem('Ürün stokta tükendi', 'Künefe - 10 dakika önce', 'warning')}
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Garson Çağrıları</h3>
                    <span id="calls-count" class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">0 bekleyen</span>
                </div>
                <div id="waiter-calls-list" class="space-y-3 max-h-64 overflow-y-auto">
                    ${COMPONENTS.emptyState('garson')}
                </div>
            </div>
        </div>
    `,
    
    // Activity Item
    activityItem: (title, description, type) => {
        const icons = {
            success: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>',
            info: '<path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>',
            warning: '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>'
        };
        
        const colors = {
            success: 'bg-green-100 text-green-600',
            info: 'bg-blue-100 text-blue-600',
            warning: 'bg-yellow-100 text-yellow-600'
        };
        
        return `
            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 ${colors[type]} rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">${icons[type]}</svg>
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">${UTILS.escapeHtml(title)}</p>
                    <p class="text-xs text-gray-500">${UTILS.escapeHtml(description)}</p>
                </div>
            </div>
        `;
    },
    
    // Waiter Call Item
    waiterCallItem: (call) => `
        <div class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex-1">
                <div class="flex items-center">
                    <span class="font-medium text-gray-900">Masa ${UTILS.escapeHtml(call.table_number)}</span>
                    <span class="ml-2 px-2 py-1 text-xs rounded-full ${DASHBOARD_CONFIG.PRIORITY_CLASSES[call.priority]}">
                        ${call.priority === 'high' ? 'Acil' : call.priority === 'medium' ? 'Normal' : 'Düşük'}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mt-1">${UTILS.escapeHtml(call.message)}</p>
                <p class="text-xs text-gray-500">${UTILS.formatTime(call.created_at)}</p>
            </div>
            <div class="flex space-x-2">
                <button onclick="acknowledgeCall(${call.id})" 
                        class="px-3 py-1 bg-yellow-500 text-white text-xs rounded hover:bg-yellow-600 transition-colors">
                    Kabul
                </button>
                <button onclick="completeCall(${call.id})" 
                        class="px-3 py-1 bg-green-500 text-white text-xs rounded hover:bg-green-600 transition-colors">
                    Tamamla
                </button>
            </div>
        </div>
    `,
    
    // Empty State
    emptyState: (type) => {
        const states = {
            garson: {
                icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                text: 'Bekleyen garson çağrısı yok'
            },
            activities: {
                icon: 'M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h6a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                text: 'Henüz aktivite yok'
            }
        };
        
        const state = states[type] || states.garson;
        
        return `
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${state.icon}"></path>
                </svg>
                <p>${state.text}</p>
            </div>
        `;
    },
    
    // Toast Notification
    toast: (message, type = 'success') => {
        const icon = type === 'success' 
            ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>'
            : '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>';
        
        const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        
        return `
            <div class="fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">${icon}</svg>
                    <span>${UTILS.escapeHtml(message)}</span>
                </div>
            </div>
        `;
    }
};

// Component Renderer
const RENDERER = {
    // Render component to container
    render(containerId, component) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = component;
        }
    },
    
    // Initialize all components
    init() {
        this.render('sidebar-container', COMPONENTS.sidebar());
        this.render('header-container', COMPONENTS.header());
        this.render('welcome-section', COMPONENTS.welcome());
        this.render('stats-cards', COMPONENTS.statsCards());
        this.render('charts-section', COMPONENTS.charts());
        this.render('activities-section', COMPONENTS.activities());
    }
};