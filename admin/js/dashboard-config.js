// admin/js/dashboard-config.js

// Global Configuration
const DASHBOARD_CONFIG = {
    // API Configuration
    API_BASE: '/backend/api',
    RESTAURANT_ID: 1,
    
    // Update Intervals (milliseconds)
    WAITER_CALLS_REFRESH: 30000,  // 30 seconds
    STATS_REFRESH: 300000,        // 5 minutes
    CHARTS_REFRESH: 600000,       // 10 minutes
    
    // Timeouts
    API_TIMEOUT: 10000,           // 10 seconds
    
    // Animation Delays
    CARD_ANIMATION_DELAY: 100,    // Stagger animation
    TOAST_DURATION: 3000,         // Toast display time
    
    // Chart Colors
    CHART_COLORS: {
        primary: '#C8102E',
        secondary: '#FFD700',
        accent: '#2E8B57',
        blue: '#3B82F6',
        green: '#10B981',
        yellow: '#F59E0B',
        purple: '#8B5CF6',
        red: '#EF4444'
    },
    
    // Status Classes
    STATUS_CLASSES: {
        pending: 'status-pending',
        acknowledged: 'status-acknowledged', 
        completed: 'status-completed',
        cancelled: 'status-cancelled'
    },
    
    // Priority Classes
    PRIORITY_CLASSES: {
        high: 'priority-high',
        medium: 'priority-medium',
        low: 'priority-low'
    }
};

// Global State
const DASHBOARD_STATE = {
    user: null,
    stats: {},
    waiterCalls: [],
    charts: {},
    isLoading: false,
    lastUpdate: null
};

// Utility Functions
const UTILS = {
    // Format time relative to now
    formatTime(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'Az önce';
        if (diffMins < 60) return `${diffMins} dakika önce`;
        
        const diffHours = Math.floor(diffMins / 60);
        if (diffHours < 24) return `${diffHours} saat önce`;
        
        const diffDays = Math.floor(diffHours / 24);
        return `${diffDays} gün önce`;
    },
    
    // Escape HTML to prevent XSS
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#x27;',
            "/": '&#x2F;',
        };
        return String(text).replace(/[&<>"'\/]/g, (s) => map[s]);
    },
    
    // Format currency
    formatCurrency(amount) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
            minimumFractionDigits: 2
        }).format(amount);
    },
    
    // Format number with thousands separator
    formatNumber(number) {
        return new Intl.NumberFormat('tr-TR').format(number);
    },
    
    // Get current date formatted
    getCurrentDate() {
        return new Intl.DateTimeFormat('tr-TR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            weekday: 'long'
        }).format(new Date());
    },
    
    // Debounce function
    debounce(func, wait) {
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
};

// API Helper Functions
const API = {
    // Make API request with timeout
    async request(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), DASHBOARD_CONFIG.API_TIMEOUT);
        
        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    },
    
    // GET request
    async get(endpoint, params = {}) {
        const url = new URL(`${DASHBOARD_CONFIG.API_BASE}${endpoint}`, window.location.origin);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        
        return this.request(url.toString());
    },
    
    // POST request
    async post(endpoint, data = {}) {
        return this.request(`${DASHBOARD_CONFIG.API_BASE}${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
    }
};