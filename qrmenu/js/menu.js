// js/menu.js - Fixed Version

// Global variables
let currentCategory = 'all';
let currentSearch = '';
let menuData = [];
let categoriesData = [];
let isSearchMode = false;

// API Base URL
const API_BASE = '/backend/api';

// DOM Elements
const loadingScreen = document.getElementById('loading-screen');
const searchToggle = document.getElementById('search-toggle');
const searchBar = document.getElementById('search-bar');
const searchInput = document.getElementById('search-input');
const searchClear = document.getElementById('search-clear');
const categoryNav = document.getElementById('category-nav');
const menuItems = document.getElementById('menu-items');
const featuredItems = document.getElementById('featured-items');
const searchResults = document.getElementById('search-results');
const searchItems = document.getElementById('search-items');
const emptyState = document.getElementById('empty-state');
const loadingSkeleton = document.getElementById('loading-skeleton');
const itemModal = document.getElementById('item-modal');
const waiterModal = document.getElementById('waiter-modal');
const waiterCallBtn = document.getElementById('waiter-call-btn');
const toast = document.getElementById('toast');

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadTheme();
});

// Initialize the application
async function initializeApp() {
    try {
        showLoading();
        await Promise.all([
            loadCategories(),
            loadFeaturedItems(),
            loadMenuItems()
        ]);
        hideLoading();
    } catch (error) {
        console.error('Initialization error:', error);
        showToast('Menü yüklenirken hata oluştu', 'error');
        hideLoading();
    }
}

// Setup event listeners
function setupEventListeners() {
    // Search functionality
    if (searchToggle) searchToggle.addEventListener('click', toggleSearch);
    if (searchInput) searchInput.addEventListener('input', debounce(handleSearch, 300));
    if (searchClear) searchClear.addEventListener('click', clearSearch);
    
    // Quick search suggestions
    document.querySelectorAll('#search-suggestions span').forEach(span => {
        span.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = span.textContent;
                handleSearch();
            }
        });
    });
    
    // Modal functionality
    const modalClose = document.getElementById('modal-close');
    if (modalClose) modalClose.addEventListener('click', closeItemModal);
    if (itemModal) {
        itemModal.addEventListener('click', (e) => {
            if (e.target === itemModal) closeItemModal();
        });
    }
    
    // Waiter call functionality
    if (waiterCallBtn) waiterCallBtn.addEventListener('click', openWaiterModal);
    
    const waiterCancel = document.getElementById('waiter-cancel');
    const waiterSend = document.getElementById('waiter-send');
    
    if (waiterCancel) waiterCancel.addEventListener('click', closeWaiterModal);
    if (waiterSend) waiterSend.addEventListener('click', sendWaiterCall);
    
    if (waiterModal) {
        waiterModal.addEventListener('click', (e) => {
            if (e.target === waiterModal) closeWaiterModal();
        });
    }
    
    // Quick message buttons
    document.querySelectorAll('.quick-message').forEach(btn => {
        btn.addEventListener('click', () => {
            const customMessage = document.getElementById('custom-message');
            if (customMessage) {
                customMessage.value = btn.dataset.message || '';
            }
        });
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (itemModal && !itemModal.classList.contains('hidden')) closeItemModal();
            if (waiterModal && !waiterModal.classList.contains('hidden')) closeWaiterModal();
            if (searchBar && searchBar.style.display !== 'none') toggleSearch();
        }
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            toggleSearch();
        }
    });
}

// Load theme
async function loadTheme() {
    try {
        const response = await fetch(`${API_BASE}/themes.php?action=css&restaurant_id=1`);
        if (response.ok) {
            const css = await response.text();
            const style = document.createElement('style');
            style.textContent = css;
            document.head.appendChild(style);
        }
    } catch (error) {
        console.error('Theme loading error:', error);
    }
}

// Load categories
async function loadCategories() {
    try {
        const response = await fetch(`${API_BASE}/categories.php?restaurant_id=1&with_count=true`);
        const result = await response.json();
        
        if (result.success) {
            categoriesData = result.data || [];
            renderCategories();
            updateStats();
        }
    } catch (error) {
        console.error('Categories loading error:', error);
        categoriesData = [];
    }
}

// Load featured items
async function loadFeaturedItems() {
    try {
        const response = await fetch(`${API_BASE}/menu.php?restaurant_id=1&featured=true`);
        const result = await response.json();
        
        if (result.success) {
            renderFeaturedItems(result.data || []);
        }
    } catch (error) {
        console.error('Featured items loading error:', error);
        renderFeaturedItems([]);
    }
}

// Load menu items
async function loadMenuItems(categoryId = null) {
    try {
        let url = `${API_BASE}/menu.php?restaurant_id=1`;
        if (categoryId) url += `&category_id=${encodeURIComponent(categoryId)}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            menuData = result.data || [];
            renderMenuItems(menuData);
        }
    } catch (error) {
        console.error('Menu items loading error:', error);
        menuData = [];
        renderMenuItems([]);
    }
}

// Render categories
function renderCategories() {
    if (!categoryNav) return;
    
    const categoryButtons = categoriesData.map(category => {
        const safeName = escapeHtml(category.name || 'Kategori');
        const safeIcon = escapeHtml(category.icon || '');
        const availableCount = parseInt(category.available_count) || 0;
        
        return `
            <button class="category-btn px-6 py-2 rounded-full border border-gray-300 whitespace-nowrap transition-all hover:border-primary hover:text-primary" 
                    data-category="${escapeHtml(category.id)}">
                ${safeIcon ? `<span class="mr-1">${safeIcon}</span>` : ''}
                ${safeName}
                <span class="ml-1 text-xs bg-gray-200 px-2 py-1 rounded-full">${availableCount}</span>
            </button>
        `;
    }).join('');
    
    categoryNav.innerHTML = `
        <button class="category-btn active px-6 py-2 rounded-full bg-primary text-white whitespace-nowrap transition-all hover:bg-opacity-90" 
                data-category="all">
            Tümü
        </button>
        ${categoryButtons}
    `;
    
    // Add category click listeners
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', () => handleCategoryClick(btn));
    });
}

// Render featured items
function renderFeaturedItems(items) {
    if (!featuredItems) return;
    
    if (!items || items.length === 0) {
        const featuredSection = document.getElementById('featured-section');
        if (featuredSection) featuredSection.style.display = 'none';
        return;
    }
    
    featuredItems.innerHTML = items.map(item => createItemCard(item, true)).join('');
    addItemClickListeners(featuredItems);
}

// Render menu items
function renderMenuItems(items) {
    if (!menuItems) return;
    
    if (!items || items.length === 0) {
        menuItems.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        return;
    }
    
    menuItems.style.display = 'grid';
    if (emptyState) emptyState.style.display = 'none';
    menuItems.innerHTML = items.map(item => createItemCard(item)).join('');
    addItemClickListeners(menuItems);
}

// Create item card HTML - XSS koruması eklendi
function createItemCard(item, isFeatured = false) {
    if (!item) return '';
    
    // XSS koruması için verileri temizle
    const safeName = escapeHtml(item.name_highlighted || item.name || 'İsimsiz Ürün');
    const safeDescription = escapeHtml(item.description_highlighted || item.description || '');
    const safeCategoryName = escapeHtml(item.category_name || 'Kategori');
    const safePrice = parseFloat(item.price) || 0;
    const safeImageUrl = item.image_url || '/images/no-image.jpg';
    
    const availability = item.is_available ? 
        '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Mevcut</span>' :
        '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Tükendi</span>';
    
    const featuredBadge = isFeatured ? 
        '<div class="absolute top-2 left-2 bg-secondary text-primary px-2 py-1 rounded-full text-xs font-semibold">⭐ Öne Çıkan</div>' : '';
    
    // Safe JSON için item objesini temizle
    const safeItem = {
        id: item.id || 0,
        name: escapeHtml(item.name || ''),
        description: escapeHtml(item.description || ''),
        category_name: escapeHtml(item.category_name || ''),
        ingredients: escapeHtml(item.ingredients || ''),
        allergens: escapeHtml(item.allergens || ''),
        price: safePrice,
        image_url: safeImageUrl,
        is_available: Boolean(item.is_available)
    };
    
    return `
        <div class="menu-item bg-card-bg rounded-xl shadow-sm overflow-hidden card-hover cursor-pointer animate-slide-up ${!item.is_available ? 'opacity-75' : ''}" 
             data-item='${JSON.stringify(safeItem).replace(/'/g, '&#39;')}'>
            <div class="relative">
                <img src="${safeImageUrl}" 
                     alt="${escapeHtml(item.name || 'Ürün resmi')}" 
                     class="w-full h-48 object-cover"
                     loading="lazy"
                     onerror="this.src='/images/no-image.jpg'">
                ${featuredBadge}
                <div class="absolute bottom-2 right-2">
                    ${availability}
                </div>
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-lg mb-2 line-clamp-2">${safeName}</h3>
                <p class="text-gray-600 text-sm mb-3 line-clamp-2">${safeDescription}</p>
                <div class="flex items-center justify-between">
                    <span class="text-xl font-bold text-primary">${safePrice.toFixed(2)} ₺</span>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">${safeCategoryName}</span>
                </div>
            </div>
        </div>
    `;
}

// XSS koruması için helper function
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#x27;',
        "/": '&#x2F;',
    };
    return String(text).replace(/[&<>"'\/]/g, (s) => map[s]);
}

// Add click listeners to item cards
function addItemClickListeners(container) {
    if (!container) return;
    
    container.querySelectorAll('.menu-item').forEach(card => {
        card.addEventListener('click', () => {
            try {
                const item = JSON.parse(card.dataset.item);
                openItemModal(item);
            } catch (e) {
                console.error('Invalid item data:', e);
            }
        });
    });
}

// Handle category click
function handleCategoryClick(button) {
    if (!button) return;
    
    // Update active state
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-primary', 'text-white');
        btn.classList.add('border', 'border-gray-300');
    });
    
    button.classList.add('active', 'bg-primary', 'text-white');
    button.classList.remove('border', 'border-gray-300');
    
    // Update current category and load items
    const categoryId = button.dataset.category;
    currentCategory = categoryId;
    
    const menuTitle = document.getElementById('menu-title');
    
    if (categoryId === 'all') {
        renderMenuItems(menuData);
        if (menuTitle) menuTitle.textContent = 'Tüm Yemekler';
    } else {
        const filteredItems = menuData.filter(item => item.category_id == categoryId);
        renderMenuItems(filteredItems);
        const category = categoriesData.find(cat => cat.id == categoryId);
        if (menuTitle) menuTitle.textContent = category ? category.name : 'Yemekler';
    }
    
    // Hide search results
    isSearchMode = false;
    if (searchResults) searchResults.style.display = 'none';
    
    const menuSection = document.getElementById('menu-section');
    const featuredSection = document.getElementById('featured-section');
    if (menuSection) menuSection.style.display = 'block';
    if (featuredSection) featuredSection.style.display = 'block';
}

// Toggle search
function toggleSearch() {
    if (!searchBar || !searchInput) return;
    
    const isVisible = searchBar.style.display !== 'none';
    
    if (isVisible) {
        searchBar.style.display = 'none';
        searchInput.value = '';
        clearSearch();
    } else {
        searchBar.style.display = 'block';
        searchInput.focus();
        const suggestions = document.getElementById('search-suggestions');
        if (suggestions) suggestions.style.display = 'block';
    }
}

// Handle search
async function handleSearch() {
    if (!searchInput) return;
    
    const query = searchInput.value.trim();
    
    if (query.length < 2) {
        clearSearch();
        return;
    }
    
    // XSS koruması için input'u temizle
    const cleanQuery = query.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    
    if (searchClear) searchClear.style.display = 'block';
    const suggestions = document.getElementById('search-suggestions');
    if (suggestions) suggestions.style.display = 'none';
    
    try {
        showLoading();
        const response = await fetch(`${API_BASE}/search.php?restaurant_id=1&q=${encodeURIComponent(cleanQuery)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            isSearchMode = true;
            const menuSection = document.getElementById('menu-section');
            const featuredSection = document.getElementById('featured-section');
            
            if (menuSection) menuSection.style.display = 'none';
            if (featuredSection) featuredSection.style.display = 'none';
            if (searchResults) searchResults.style.display = 'block';
            
            const searchCount = document.getElementById('search-count');
            if (searchCount) {
                searchCount.textContent = `${result.data.count || 0} sonuç bulundu`;
            }
            
            if (!result.data.items || result.data.items.length === 0) {
                if (searchItems) searchItems.style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
            } else {
                if (searchItems) {
                    searchItems.style.display = 'grid';
                    searchItems.innerHTML = result.data.items.map(item => createItemCard(item)).join('');
                    addItemClickListeners(searchItems);
                }
                if (emptyState) emptyState.style.display = 'none';
            }
        } else {
            throw new Error(result.message || 'Arama başarısız');
        }
    } catch (error) {
        console.error('Search error:', error);
        showToast('Arama sırasında hata oluştu', 'error');
        clearSearch();
    } finally {
        hideLoading();
    }
}

// Clear search
function clearSearch() {
    if (searchInput) searchInput.value = '';
    if (searchClear) searchClear.style.display = 'none';
    
    isSearchMode = false;
    if (searchResults) searchResults.style.display = 'none';
    
    const menuSection = document.getElementById('menu-section');
    const featuredSection = document.getElementById('featured-section');
    if (menuSection) menuSection.style.display = 'block';
    if (featuredSection) featuredSection.style.display = 'block';
    if (emptyState) emptyState.style.display = 'none';
    
    const suggestions = document.getElementById('search-suggestions');
    if (suggestions) suggestions.style.display = 'block';
}

// Open item modal - güvenlik iyileştirmesi
function openItemModal(item) {
    if (!item || typeof item !== 'object' || !itemModal) {
        console.error('Invalid item data for modal');
        return;
    }
    
    const safeImageUrl = item.image_url || '/images/no-image.jpg';
    const safeName = escapeHtml(item.name || 'İsimsiz Ürün');
    const safePrice = parseFloat(item.price) || 0;
    const safeDescription = escapeHtml(item.description || 'Açıklama bulunmuyor.');
    const safeIngredients = escapeHtml(item.ingredients || '');
    const safeAllergens = escapeHtml(item.allergens || '');
    const safeCategoryName = escapeHtml(item.category_name || 'Kategori');
    
    const modalImage = document.getElementById('modal-image');
    const modalTitle = document.getElementById('modal-title');
    const modalPrice = document.getElementById('modal-price');
    const modalDescription = document.getElementById('modal-description');
    const modalCategory = document.getElementById('modal-category');
    const modalAvailability = document.getElementById('modal-availability');
    
    if (modalImage) {
        modalImage.src = safeImageUrl;
        modalImage.onerror = function() { this.src = '/images/no-image.jpg'; };
    }
    if (modalTitle) modalTitle.textContent = safeName;
    if (modalPrice) modalPrice.textContent = `${safePrice.toFixed(2)} ₺`;
    if (modalDescription) modalDescription.textContent = safeDescription;
    if (modalCategory) modalCategory.textContent = safeCategoryName;
    
    const ingredientsDiv = document.getElementById('modal-ingredients');
    if (ingredientsDiv) {
        if (safeIngredients && safeIngredients.trim()) {
            ingredientsDiv.style.display = 'block';
            const ingredientsText = ingredientsDiv.querySelector('p');
            if (ingredientsText) ingredientsText.textContent = safeIngredients;
        } else {
            ingredientsDiv.style.display = 'none';
        }
    }
    
    const allergensDiv = document.getElementById('modal-allergens');
    if (allergensDiv) {
        if (safeAllergens && safeAllergens.trim() && safeAllergens !== 'Yok') {
            allergensDiv.style.display = 'block';
            const allergensText = allergensDiv.querySelector('p');
            if (allergensText) allergensText.textContent = safeAllergens;
        } else {
            allergensDiv.style.display = 'none';
        }
    }
    
    if (modalAvailability) {
        if (item.is_available) {
            modalAvailability.textContent = 'Mevcut';
            modalAvailability.className = 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm';
        } else {
            modalAvailability.textContent = 'Tükendi';
            modalAvailability.className = 'px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm';
        }
    }
    
    itemModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Modal açılma animasyonu
    itemModal.classList.add('animate-fade-in');
}

// Close item modal
function closeItemModal() {
    if (!itemModal) return;
    
    itemModal.style.display = 'none';
    document.body.style.overflow = 'auto';
    itemModal.classList.remove('animate-fade-in');
}

// Open waiter modal
function openWaiterModal() {
    if (!waiterModal) return;
    
    waiterModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Close waiter modal
function closeWaiterModal() {
    if (!waiterModal) return;
    
    waiterModal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    const customMessage = document.getElementById('custom-message');
    if (customMessage) customMessage.value = '';
}

// Send waiter call - güvenlik ve hata yönetimi iyileştirmesi
async function sendWaiterCall() {
    const customMessage = document.getElementById('custom-message');
    if (!customMessage) {
        showToast('Mesaj alanı bulunamadı', 'error');
        return;
    }
    
    const message = customMessage.value.trim();
    
    if (!message) {
        showToast('Lütfen bir mesaj girin', 'error');
        return;
    }
    
    if (message.length > 500) {
        showToast('Mesaj çok uzun. Maksimum 500 karakter.', 'error');
        return;
    }
    
    // Basit XSS koruması
    const cleanMessage = message.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    
    const sendButton = document.getElementById('waiter-send');
    if (!sendButton) return;
    
    const originalText = sendButton.textContent;
    
    try {
        // Loading state
        sendButton.disabled = true;
        sendButton.textContent = 'Gönderiliyor...';
        
        const response = await fetch(`${API_BASE}/waiter-call.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create',
                table_id: getTableId(),
                message: cleanMessage,
                priority: 'medium'
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Garson çağrısı gönderildi! Kısa sürede yanınızda olacağız.', 'success');
            closeWaiterModal();
        } else {
            throw new Error(result.message || 'Garson çağrısı gönderilemedi');
        }
    } catch (error) {
        console.error('Waiter call error:', error);
        let errorMessage = 'Bağlantı hatası oluştu';
        
        if (error.message.includes('Çok fazla çağrı')) {
            errorMessage = 'Çok fazla çağrı yapıyorsunuz. Lütfen bekleyin.';
        } else if (error.message.includes('bekleyen bir çağrı')) {
            errorMessage = 'Bu masa için zaten bekleyen bir çağrı bulunuyor.';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        showToast(errorMessage, 'error');
    } finally {
        // Reset button state
        sendButton.disabled = false;
        sendButton.textContent = originalText;
    }
}

// Get table ID - URL validation eklendi
function getTableId() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        let tableId = urlParams.get('table') || urlParams.get('qr') || '1';
        
        // Basit validasyon
        if (!/^\d+$/.test(tableId)) {
            console.warn('Invalid table ID, using default');
            return '1';
        }
        
        return tableId;
    } catch (error) {
        console.error('Error getting table ID:', error);
        return '1';
    }
}

// Show/hide loading
function showLoading() {
    if (loadingScreen) loadingScreen.style.display = 'flex';
    if (loadingSkeleton) loadingSkeleton.style.display = 'grid';
    if (menuItems) menuItems.style.display = 'none';
}

function hideLoading() {
    if (loadingScreen) loadingScreen.style.display = 'none';
    if (loadingSkeleton) loadingSkeleton.style.display = 'none';
    if (menuItems) menuItems.style.display = 'grid';
}

// Show toast notification
function showToast(message, type = 'success') {
    if (!toast) return;
    
    const toastMessage = document.getElementById('toast-message');
    if (!toastMessage) return;
    
    toastMessage.textContent = message;
    
    // Update toast color based on type
    toast.className = toast.className.replace(/bg-(green|red)-500/g, '');
    if (type === 'error') {
        toast.classList.add('bg-red-500');
    } else {
        toast.classList.add('bg-green-500');
    }
    
    toast.style.transform = 'translateX(0)';
    
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
    }, 3000);
}

// Update stats
function updateStats() {
    const totalItems = menuData.length;
    const totalCategories = categoriesData.length;
    
    const totalItemsEl = document.getElementById('total-items');
    const totalCategoriesEl = document.getElementById('total-categories');
    
    if (totalItemsEl) totalItemsEl.textContent = totalItems;
    if (totalCategoriesEl) totalCategoriesEl.textContent = totalCategories;
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Service Worker Registration (PWA)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

// Error handling for uncaught errors
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    showToast('Beklenmeyen bir hata oluştu', 'error');
});

// Handle promise rejections
window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    showToast('İşlem başarısız oldu', 'error');
    event.preventDefault();
});

// Online/offline status
window.addEventListener('online', () => {
    showToast('İnternet bağlantısı geri geldi', 'success');
    // Reload data when back online
    initializeApp();
});

window.addEventListener('offline', () => {
    showToast('İnternet bağlantısı kesildi', 'error');
});

// Page visibility API for performance
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        // Page became visible, refresh data if needed
        const lastUpdate = localStorage.getItem('menu_last_update');
        const fiveMinutesAgo = Date.now() - 5 * 60 * 1000;
        
        if (!lastUpdate || parseInt(lastUpdate) < fiveMinutesAgo) {
            initializeApp();
            localStorage.setItem('menu_last_update', Date.now().toString());
        }
    }
});

// Performance monitoring
const perfObserver = new PerformanceObserver((list) => {
    list.getEntries().forEach((entry) => {
        if (entry.entryType === 'navigation') {
            console.log(`Page load time: ${entry.loadEventEnd - entry.loadEventStart}ms`);
        }
    });
});

if ('PerformanceObserver' in window) {
    perfObserver.observe({ entryTypes: ['navigation'] });
}