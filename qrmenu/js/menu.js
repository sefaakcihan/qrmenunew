// js/menu.js

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
    searchToggle.addEventListener('click', toggleSearch);
    searchInput.addEventListener('input', debounce(handleSearch, 300));
    searchClear.addEventListener('click', clearSearch);
    
    // Quick search suggestions
    document.querySelectorAll('#search-suggestions span').forEach(span => {
        span.addEventListener('click', () => {
            searchInput.value = span.textContent;
            handleSearch();
        });
    });
    
    // Modal functionality
    document.getElementById('modal-close').addEventListener('click', closeItemModal);
    itemModal.addEventListener('click', (e) => {
        if (e.target === itemModal) closeItemModal();
    });
    
    // Waiter call functionality
    waiterCallBtn.addEventListener('click', openWaiterModal);
    document.getElementById('waiter-cancel').addEventListener('click', closeWaiterModal);
    document.getElementById('waiter-send').addEventListener('click', sendWaiterCall);
    waiterModal.addEventListener('click', (e) => {
        if (e.target === waiterModal) closeWaiterModal();
    });
    
    // Quick message buttons
    document.querySelectorAll('.quick-message').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('custom-message').value = btn.dataset.message;
        });
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (itemModal.style.display !== 'none') closeItemModal();
            if (waiterModal.style.display !== 'none') closeWaiterModal();
            if (searchBar.style.display !== 'none') toggleSearch();
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
            categoriesData = result.data;
            renderCategories();
            updateStats();
        }
    } catch (error) {
        console.error('Categories loading error:', error);
    }
}

// Load featured items
async function loadFeaturedItems() {
    try {
        const response = await fetch(`${API_BASE}/menu.php?restaurant_id=1&featured=true`);
        const result = await response.json();
        
        if (result.success) {
            renderFeaturedItems(result.data);
        }
    } catch (error) {
        console.error('Featured items loading error:', error);
    }
}

// Load menu items
async function loadMenuItems(categoryId = null) {
    try {
        let url = `${API_BASE}/menu.php?restaurant_id=1`;
        if (categoryId) url += `&category_id=${categoryId}`;
        
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            menuData = result.data;
            renderMenuItems(result.data);
        }
    } catch (error) {
        console.error('Menu items loading error:', error);
    }
}

// Render categories
function renderCategories() {
    const categoryButtons = categoriesData.map(category => `
        <button class="category-btn px-6 py-2 rounded-full border border-gray-300 whitespace-nowrap transition-all hover:border-primary hover:text-primary" 
                data-category="${category.id}">
            ${category.icon ? `<span class="mr-1">${category.icon}</span>` : ''}
            ${escapeHtml(category.name)}
            <span class="ml-1 text-xs bg-gray-200 px-2 py-1 rounded-full">${category.available_count}</span>
        </button>
    `).join('');
    
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
    if (items.length === 0) {
        document.getElementById('featured-section').style.display = 'none';
        return;
    }
    
    featuredItems.innerHTML = items.map(item => createItemCard(item, true)).join('');
    addItemClickListeners();
}

// Render menu items
function renderMenuItems(items) {
    if (items.length === 0) {
        menuItems.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    menuItems.style.display = 'grid';
    emptyState.style.display = 'none';
    menuItems.innerHTML = items.map(item => createItemCard(item)).join('');
    addItemClickListeners();
}

// Create item card HTML - XSS koruması eklendi
function createItemCard(item, isFeatured = false) {
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
        ...item,
        name: escapeHtml(item.name || ''),
        description: escapeHtml(item.description || ''),
        category_name: escapeHtml(item.category_name || ''),
        ingredients: escapeHtml(item.ingredients || ''),
        allergens: escapeHtml(item.allergens || ''),
        price: safePrice
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
function addItemClickListeners() {
    document.querySelectorAll('.menu-item').forEach(card => {
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
    
    if (categoryId === 'all') {
        renderMenuItems(menuData);
        document.getElementById('menu-title').textContent = 'Tüm Yemekler';
    } else {
        const filteredItems = menuData.filter(item => item.category_id == categoryId);
        renderMenuItems(filteredItems);
        const category = categoriesData.find(cat => cat.id == categoryId);
        document.getElementById('menu-title').textContent = category ? category.name : 'Yemekler';
    }
    
    // Hide search results
    isSearchMode = false;
    searchResults.style.display = 'none';
    document.getElementById('menu-section').style.display = 'block';
    document.getElementById('featured-section').style.display = 'block';
}

// Toggle search
function toggleSearch() {
    const isVisible = searchBar.style.display !== 'none';
    
    if (isVisible) {
        searchBar.style.display = 'none';
        searchInput.value = '';
        clearSearch();
    } else {
        searchBar.style.display = 'block';
        searchInput.focus();
        document.getElementById('search-suggestions').style.display = 'block';
    }
}

// Handle search
async function handleSearch() {
    const query = searchInput.value.trim();
    
    if (query.length < 2) {
        clearSearch();
        return;
    }
    
    // XSS koruması için input'u temizle
    const cleanQuery = query.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    
    searchClear.style.display = 'block';
    document.getElementById('search-suggestions').style.display = 'none';
    
    try {
        showLoading();
        const response = await fetch(`${API_BASE}/search.php?restaurant_id=1&q=${encodeURIComponent(cleanQuery)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            isSearchMode = true;
            document.getElementById('menu-section').style.display = 'none';
            document.getElementById('featured-section').style.display = 'none';
            searchResults.style.display = 'block';
            
            document.getElementById('search-count').textContent = `${result.data.count} sonuç bulundu`;
            
            if (result.data.items.length === 0) {
                searchItems.style.display = 'none';
                emptyState.style.display = 'block';
            } else {
                searchItems.style.display = 'grid';
                emptyState.style.display = 'none';
                searchItems.innerHTML = result.data.items.map(item => createItemCard(item)).join('');
                
                // Add click listeners to search results
                document.querySelectorAll('#search-items .menu-item').forEach(card => {
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
    searchInput.value = '';
    searchClear.style.display = 'none';
    isSearchMode = false;
    searchResults.style.display = 'none';
    document.getElementById('menu-section').style.display = 'block';
    document.getElementById('featured-section').style.display = 'block';
    emptyState.style.display = 'none';
    document.getElementById('search-suggestions').style.display = 'block';
}

// Open item modal - güvenlik iyileştirmesi
function openItemModal(item) {
    // Güvenli veri kontrolü
    if (!item || typeof item !== 'object') {
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
    
    document.getElementById('modal-image').src = safeImageUrl;
    document.getElementById('modal-image').onerror = function() { this.src = '/images/no-image.jpg'; };
    document.getElementById('modal-title').textContent = safeName;
    document.getElementById('modal-price').textContent = `${safePrice.toFixed(2)} ₺`;
    document.getElementById('modal-description').textContent = safeDescription;
    
    const ingredientsDiv = document.getElementById('modal-ingredients');
    if (safeIngredients && safeIngredients.trim()) {
        ingredientsDiv.style.display = 'block';
        ingredientsDiv.querySelector('p').textContent = safeIngredients;
    } else {
        ingredientsDiv.style.display = 'none';
    }
    
    const allergensDiv = document.getElementById('modal-allergens');
    if (safeAllergens && safeAllergens.trim() && safeAllergens !== 'Yok') {
        allergensDiv.style.display = 'block';
        allergensDiv.querySelector('p').textContent = safeAllergens;
    } else {
        allergensDiv.style.display = 'none';
    }
    
    document.getElementById('modal-category').textContent = safeCategoryName;
    
    const availabilitySpan = document.getElementById('modal-availability');
    if (item.is_available) {
        availabilitySpan.textContent = 'Mevcut';
        availabilitySpan.className = 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm';
    } else {
        availabilitySpan.textContent = 'Tükendi';
        availabilitySpan.className = 'px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm';
    }
    
    itemModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Modal açılma animasyonu
    itemModal.classList.add('animate-fade-in');
}

// Close item modal
function closeItemModal() {
    itemModal.style.display = 'none';
    document.body.style.overflow = 'auto';
    itemModal.classList.remove('animate-fade-in');
}

// Open waiter modal
function openWaiterModal() {
    waiterModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Close waiter modal
function closeWaiterModal() {
    waiterModal.style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('custom-message').value = '';
}

// Send waiter call - güvenlik ve hata yönetimi iyileştirmesi
async function sendWaiterCall() {
    const message = document.getElementById('custom-message').value.trim();
    
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
    const urlParams = new URLSearchParams(window.location.search);
    let tableId = urlParams.get('table') || urlParams.get('qr') || '1';
    
    // Basit validasyon
    if (!/^\d+$/.test(tableId)) {
        console.warn('Invalid table ID, using default');
        return '1';
    }
    
    return tableId;
}

// Show/hide loading
function showLoading() {
    loadingScreen.style.display = 'flex';
    loadingSkeleton.style.display = 'grid';
    menuItems.style.display = 'none';
}

function hideLoading() {
    loadingScreen.style.display = 'none';
    loadingSkeleton.style.display = 'none';
    menuItems.style.display = 'grid';
}

// Show toast notification
function showToast(message, type = 'success') {
    const toastEl = document.getElementById('toast');
    const messageEl = document.getElementById('toast-message');
    
    messageEl.textContent = message;
    
    if (type === 'error') {
        toastEl.className = toastEl.className.replace('bg-green-500', 'bg-red-500');
    } else {
        toastEl.className = toastEl.className.replace('bg-red-500', 'bg-green-500');
    }
    
    toastEl.style.transform = 'translateX(0)';
    
    setTimeout(() => {
        toastEl.style.transform = 'translateX(100%)';
    }, 3000);
}

// Update stats
function updateStats() {
    const totalItems = menuData.length;
    const totalCategories = categoriesData.length;
    
    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-categories').textContent = totalCategories;
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