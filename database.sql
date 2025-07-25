-- Restaurant Dijital Menü Sistemi - Veritabanı Şeması
-- MySQL/PostgreSQL uyumlu SQL

-- Restoran bilgileri tablosu
CREATE TABLE restaurants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    logo VARCHAR(500),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Temalar tablosu
CREATE TABLE themes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    primary_color VARCHAR(7) DEFAULT '#C8102E',
    secondary_color VARCHAR(7) DEFAULT '#FFD700',
    accent_color VARCHAR(7) DEFAULT '#2E8B57',
    bg_color VARCHAR(7) DEFAULT '#F8FAFC',
    text_color VARCHAR(7) DEFAULT '#1E293B',
    card_bg VARCHAR(7) DEFAULT '#FFFFFF',
    css_variables TEXT,
    thumbnail VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Restoran tema ilişkisi
CREATE TABLE restaurant_themes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT,
    theme_id INT,
    custom_css TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE
);

-- Kategoriler tablosu
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Menü öğeleri tablosu
CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(500),
    ingredients TEXT,
    allergens VARCHAR(500),
    nutritional_info TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Masalar tablosu
CREATE TABLE tables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT,
    table_number VARCHAR(50) NOT NULL,
    qr_code VARCHAR(500),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Garson çağrıları tablosu
CREATE TABLE waiter_calls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_id INT,
    message TEXT,
    status ENUM('pending', 'acknowledged', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

-- Admin kullanıcıları tablosu
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(255),
    role ENUM('super_admin', 'admin', 'manager') DEFAULT 'admin',
    restaurant_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL
);

-- Sistem ayarları tablosu
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    is_global BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_setting (restaurant_id, setting_key)
);

-- Önceden tanımlı temalar
INSERT INTO themes (name, slug, primary_color, secondary_color, accent_color, bg_color, text_color, card_bg, css_variables) VALUES
('Classic Restaurant', 'classic', '#C8102E', '#FFD700', '#2E8B57', '#F8FAFC', '#1E293B', '#FFFFFF', 
 '{"shadow": "0 4px 6px -1px rgba(0, 0, 0, 0.1)", "border_radius": "0.5rem", "font_family": "Inter"}'),

('Modern Minimalist', 'modern', '#667EEA', '#764BA2', '#F093FB', '#F7FAFC', '#1A202C', '#FFFFFF',
 '{"shadow": "0 10px 25px -3px rgba(0, 0, 0, 0.1)", "border_radius": "1rem", "font_family": "Inter"}'),

('Dark Elegance', 'dark-elegance', '#D4AF37', '#C0392B', '#2ECC71', '#0D1117', '#F8F9FA', '#21262D',
 '{"shadow": "0 4px 6px -1px rgba(255, 255, 255, 0.1)", "border_radius": "0.75rem", "font_family": "Inter"}'),

('Colorful Cafe', 'colorful', '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFF8E1', '#2C3E50', '#FFFFFF',
 '{"shadow": "0 8px 32px rgba(31, 38, 135, 0.37)", "border_radius": "1.25rem", "font_family": "Inter"}'),

('Fine Dining', 'fine-dining', '#8B4513', '#D2691E', '#DAA520', '#FDF6E3', '#2F1B14', '#FAF0E6',
 '{"shadow": "0 2px 10px rgba(139, 69, 19, 0.2)", "border_radius": "0.25rem", "font_family": "Playfair Display"}'),

('Seafood Fresh', 'seafood', '#0077BE', '#20B2AA', '#FF7F50', '#F0F8FF', '#1C3A3A', '#FFFFFF',
 '{"shadow": "0 6px 20px rgba(0, 119, 190, 0.3)", "border_radius": "1rem", "font_family": "Inter"}'),

('Italian Rustic', 'italian', '#228B22', '#DC143C', '#FFD700', '#FFF8DC', '#2F4F2F', '#FFFAF0',
 '{"shadow": "0 4px 15px rgba(34, 139, 34, 0.2)", "border_radius": "0.5rem", "font_family": "Inter"}'),

('Asian Zen', 'asian', '#8B0000', '#FFD700', '#32CD32', '#F5F5DC', '#2F4F2F', '#FFFFFF',
 '{"shadow": "0 3px 12px rgba(139, 0, 0, 0.25)", "border_radius": "0.375rem", "font_family": "Inter"}');

-- Örnek restoran verisi
INSERT INTO restaurants (name, logo, address, phone, email, description) VALUES
('Lezzet Restaurant', '/uploads/logos/lezzet-logo.png', 'Atatürk Caddesi No:123, Seyhan/Adana', '+90 322 123 45 67', 'info@lezzetrestaurant.com', 'Adana\'nın en lezzetli mekânı');

-- Örnek kategoriler
INSERT INTO categories (restaurant_id, name, description, icon, sort_order) VALUES
(1, 'Başlangıçlar', 'Aperatifler ve meze çeşitleri', 'appetizer', 1),
(1, 'Ana Yemekler', 'Et, tavuk ve balık yemekleri', 'main-course', 2),
(1, 'Kebaplar', 'Adana\'nın meşhur kebap çeşitleri', 'kebab', 3),
(1, 'Salatalar', 'Taze ve sağlıklı salata çeşitleri', 'salad', 4),
(1, 'Tatlılar', 'Ev yapımı tatlılar', 'dessert', 5),
(1, 'İçecekler', 'Sıcak ve soğuk içecekler', 'beverage', 6);

-- Örnek menü öğeleri
INSERT INTO menu_items (category_id, name, description, price, ingredients, allergens, is_featured) VALUES
(3, 'Adana Kebap', 'Özel baharatlarla hazırlanmış geleneksel Adana kebabı', 45.00, 'Dana kıyma, özel baharat karışımı, soğan', 'Yok', TRUE),
(3, 'Urfa Kebap', 'Acısız, lezzetli Urfa kebabı', 42.00, 'Dana kıyma, Urfa biberi, soğan', 'Yok', TRUE),
(1, 'Humus', 'Tahin ve limon ile hazırlanmış ev yapımı humus', 18.00, 'Nohut, tahin, limon, sarımsak, zeytinyağı', 'Susam', FALSE),
(2, 'Izgara Levrek', 'Taze deniz levreği ızgara', 65.00, 'Levrek balığı, zeytinyağı, limon, tuz', 'Balık', TRUE),
(4, 'Çoban Salata', 'Domates, salatalık, soğan, maydanoz', 22.00, 'Domates, salatalık, soğan, maydanoz, zeytinyağı, limon', 'Yok', FALSE),
(5, 'Künefe', 'Peynirli tel kadayıf tatlısı', 28.00, 'Tel kadayıf, lor peyniri, şerbet, fıstık', 'Gluten, Süt', TRUE),
(6, 'Türk Kahvesi', 'Geleneksel Türk kahvesi', 12.00, 'Kahve', 'Yok', FALSE);

-- Örnek masa
INSERT INTO tables (restaurant_id, table_number, qr_code) VALUES
(1, '1', 'QR_TABLE_001'),
(1, '2', 'QR_TABLE_002'),
(1, '3', 'QR_TABLE_003');

-- Örnek admin kullanıcısı (şifre: admin123)
INSERT INTO admin_users (username, password, email, full_name, role, restaurant_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@lezzetrestaurant.com', 'Sistem Yöneticisi', 'super_admin', 1);

-- Örnek sistem ayarları
INSERT INTO settings (restaurant_id, setting_key, setting_value, setting_type) VALUES
(1, 'restaurant_open_hours', '{"monday": "09:00-23:00", "tuesday": "09:00-23:00", "wednesday": "09:00-23:00", "thursday": "09:00-23:00", "friday": "09:00-24:00", "saturday": "09:00-24:00", "sunday": "10:00-22:00"}', 'json'),
(1, 'default_currency', 'TL', 'string'),
(1, 'tax_rate', '18', 'number'),
(1, 'service_charge', '10', 'number'),
(1, 'enable_waiter_call', 'true', 'boolean'),
(1, 'max_waiter_calls_per_hour', '10', 'number');

-- Default tema ataması
INSERT INTO restaurant_themes (restaurant_id, theme_id, is_default) VALUES (1, 1, TRUE);