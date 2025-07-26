// admin/js/dashboard-charts.js

// Chart Manager
const CHARTS = {
    // Chart instances
    instances: {},
    
    // Chart configurations
    configs: {
        popularItems: {
            type: 'bar',
            data: {
                labels: ['Adana Kebap', 'Urfa Kebap', 'Künefe', 'Çoban Salata', 'Humus'],
                datasets: [{
                    label: 'Görüntülenme',
                    data: [120, 95, 80, 65, 45],
                    backgroundColor: DASHBOARD_CONFIG.CHART_COLORS.primary,
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: false },
                        ticks: { color: '#6B7280' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#6B7280' }
                    }
                },
                animation: {
                    delay: (context) => context.dataIndex * 100
                }
            }
        },
        
        categoryDistribution: {
            type: 'doughnut',
            data: {
                labels: ['Kebaplar', 'Ana Yemekler', 'Başlangıçlar', 'Tatlılar', 'İçecekler'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        DASHBOARD_CONFIG.CHART_COLORS.primary,
                        DASHBOARD_CONFIG.CHART_COLORS.secondary,
                        DASHBOARD_CONFIG.CHART_COLORS.accent,
                        DASHBOARD_CONFIG.CHART_COLORS.purple,
                        DASHBOARD_CONFIG.CHART_COLORS.blue
                    ],
                    borderWidth: 0,
                    hoverBorderWidth: 3,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            color: '#6B7280'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.formattedValue;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.parsed / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 1000
                }
            }
        }
    },
    
    // Initialize all charts
    init() {
        this.createChart('popular-items-chart', 'popularItems');
        this.createChart('category-chart', 'categoryDistribution');
    },
    
    // Create individual chart
    createChart(canvasId, configKey) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn(`Canvas with id '${canvasId}' not found`);
            return;
        }
        
        const ctx = canvas.getContext('2d');
        const config = this.configs[configKey];
        
        if (!config) {
            console.warn(`Chart config '${configKey}' not found`);
            return;
        }
        
        try {
            this.instances[configKey] = new Chart(ctx, config);
        } catch (error) {
            console.error(`Error creating chart '${configKey}':`, error);
        }
    },
    
    // Update chart data
    updateChart(configKey, newData) {
        const chart = this.instances[configKey];
        if (!chart) return;
        
        chart.data = { ...chart.data, ...newData };
        chart.update('active');
    },
    
    // Destroy all charts
    destroy() {
        Object.values(this.instances).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.instances = {};
    },
    
    // Resize all charts
    resize() {
        Object.values(this.instances).forEach(chart => {
            if (chart && typeof chart.resize === 'function') {
                chart.resize();
            }
        });
    },
    
    // Load real data and update charts
    async loadData() {
        try {
            // Load popular items data
            const popularData = await this.getPopularItemsData();
            if (popularData) {
                this.updateChart('popularItems', {
                    labels: popularData.labels,
                    datasets: [{
                        ...this.configs.popularItems.data.datasets[0],
                        data: popularData.data
                    }]
                });
            }
            
            // Load category distribution data  
            const categoryData = await this.getCategoryDistributionData();
            if (categoryData) {
                this.updateChart('categoryDistribution', {
                    labels: categoryData.labels,
                    datasets: [{
                        ...this.configs.categoryDistribution.data.datasets[0],
                        data: categoryData.data
                    }]
                });
            }
            
        } catch (error) {
            console.error('Error loading chart data:', error);
        }
    },
    
    // Get popular items data from API
    async getPopularItemsData() {
        try {
            const response = await API.get('/menu.php', {
                restaurant_id: DASHBOARD_CONFIG.RESTAURANT_ID
            });
            
            if (response.success && response.data) {
                // Sort by some popularity metric (using ID as placeholder)
                const sorted = response.data
                    .sort((a, b) => b.id - a.id)
                    .slice(0, 5);
                
                return {
                    labels: sorted.map(item => item.name),
                    data: sorted.map(() => Math.floor(Math.random() * 100) + 20) // Mock data
                };
            }
        } catch (error) {
            console.error('Error fetching popular items:', error);
        }
        return null;
    },
    
    // Get category distribution data from API
    async getCategoryDistributionData() {
        try {
            const response = await API.get('/categories.php', {
                restaurant_id: DASHBOARD_CONFIG.RESTAURANT_ID,
                with_count: true
            });
            
            if (response.success && response.data) {
                return {
                    labels: response.data.map(cat => cat.name),
                    data: response.data.map(cat => parseInt(cat.item_count) || 0)
                };
            }
        } catch (error) {
            console.error('Error fetching category distribution:', error);
        }
        return null;
    }
};

// Chart utilities
const CHART_UTILS = {
    // Generate random color
    randomColor() {
        const colors = Object.values(DASHBOARD_CONFIG.CHART_COLORS);
        return colors[Math.floor(Math.random() * colors.length)];
    },
    
    // Generate color palette
    generatePalette(count) {
        const colors = Object.values(DASHBOARD_CONFIG.CHART_COLORS);
        const palette = [];
        
        for (let i = 0; i < count; i++) {
            palette.push(colors[i % colors.length]);
        }
        
        return palette;
    },
    
    // Format chart data for display
    formatChartData(data, type = 'number') {
        switch (type) {
            case 'currency':
                return data.map(value => UTILS.formatCurrency(value));
            case 'percentage':
                return data.map(value => `${value}%`);
            default:
                return data.map(value => UTILS.formatNumber(value));
        }
    }
};