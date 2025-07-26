-- Restaurant Dijital Menü Sistemi - FIXED Veritabanı Şeması
-- MySQL 8.0+ uyumlu SQL

-- Set charset and collation
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS restaurant_menu 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE restaurant_menu;

-- Enable strict mode and disable zero dates
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- Restoran bilgileri tablosu - Enhanced
CREATE TABLE restaurants (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    logo VARCHAR(500),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    description TEXT,
    business_hours JSON,
    social_media JSON,
    settings JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Temalar tablosu - Enhanced
CREATE TABLE themes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    primary_color VARCHAR(7) DEFAULT '#C8102E',
    secondary_color VARCHAR(7) DEFAULT '#FFD700',
    accent_color VARCHAR(7) DEFAULT '#2E8B57',
    bg_color VARCHAR(7) DEFAULT '#F8FAFC',
    text_color VARCHAR(7) DEFAULT '#1E293B',
    card_bg VARCHAR(7) DEFAULT '#FFFFFF',
    css_variables JSON,
    custom_css TEXT,
    thumbnail VARCHAR(500),
    preview_images JSON,
    is_active BOOLEAN DEFAULT TRUE,
    is_premium BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restoran tema ilişkisi - Enhanced
CREATE TABLE restaurant_themes (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT UNSIGNED NOT NULL,
    theme_id INT UNSIGNED NOT NULL,
    custom_css TEXT,
    custom_variables JSON,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (theme_id) REFERENCES themes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_restaurant_theme (restaurant_id, theme_id),
    INDEX idx_default (restaurant_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kategoriler tablosu - Enhanced
CREATE TABLE categories (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    image VARCHAR(500),
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_restaurant_slug (restaurant_id, slug),
    INDEX idx_restaurant_status (restaurant_id, status),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menü öğeleri tablosu - Enhanced
CREATE TABLE menu_items (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2) NULL,
    image VARCHAR(500),
    gallery JSON,
    ingredients TEXT,
    allergens VARCHAR(500),
    nutritional_info JSON,
    calories INT UNSIGNED,
    prep_time INT UNSIGNED COMMENT 'Preparation time in minutes',
    spice_level ENUM('none', 'mild', 'medium', 'hot', 'very_hot') DEFAULT 'none',
    dietary_info SET('vegetarian', 'vegan', 'gluten_free', 'dairy_free', 'halal', 'kosher') DEFAULT '',
    is_available BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_popular BOOLEAN DEFAULT FALSE,
    is_new BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    view_count INT UNSIGNED DEFAULT 0,
    meta_title VARCHAR(255),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category_available (category_id, is_available),
    INDEX idx_featured (is_featured),
    INDEX idx_popular (is_popular),
    INDEX idx_sort_order (sort_order),
    FULLTEXT idx_search (name, description, ingredients)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Masalar tablosu - Enhanced
CREATE TABLE tables (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT UNSIGNED NOT NULL,
    table_number VARCHAR(50) NOT NULL,
    table_name VARCHAR(100),
    qr_code VARCHAR(500) UNIQUE NOT NULL,
    capacity INT UNSIGNED DEFAULT 4,
    location VARCHAR(100) COMMENT 'Indoor, Outdoor, VIP, etc.',
    status ENUM('active', 'inactive', 'maintenance', 'reserved') DEFAULT 'active',
    notes TEXT,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_restaurant_table (restaurant_id, table_number),
    UNIQUE KEY unique_qr_code (qr_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Garson çağrıları tablosu - Enhanced
CREATE TABLE waiter_calls (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    table_id INT UNSIGNED NOT NULL,
    message TEXT,
    call_type ENUM('service', 'bill', 'complaint', 'assistance', 'other') DEFAULT 'service',
    status ENUM('pending', 'acknowledged', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    assigned_to INT UNSIGNED NULL COMMENT 'Admin ID who acknowledged the call',
    response_message TEXT,
    customer_rating TINYINT UNSIGNED NULL COMMENT '1-5 rating',
    customer_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_table_status (table_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin kullanıcıları tablosu - Fixed and Enhanced
CREATE TABLE admin_users (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT UNSIGNED,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    avatar VARCHAR(500),
    role ENUM('super_admin', 'admin', 'manager', 'staff') DEFAULT 'admin',
    permissions JSON,
    phone VARCHAR(50),
    last_login TIMESTAMP NULL,
    last_ip VARCHAR(45),
    login_attempts TINYINT UNSIGNED DEFAULT 0,
    locked_until TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    two_factor_secret VARCHAR(32),
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_restaurant_active (restaurant_id, is_active),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistem ayarları tablosu - Enhanced
CREATE TABLE settings (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT UNSIGNED,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json', 'text', 'email', 'url') DEFAULT 'string',
    is_global BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT FALSE COMMENT 'Can be accessed by frontend',
    description TEXT,
    validation_rules JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_setting (restaurant_id, setting_key),
    INDEX idx_global (is_global),
    INDEX idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log tablosu - New
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED,
    user_type ENUM('admin', 'customer') DEFAULT 'admin',
    action VARCHAR(100) NOT NULL,
    subject_type VARCHAR(100),
    subject_id INT UNSIGNED,
    description TEXT,
    properties JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id, user_type),
    INDEX idx_action (action),
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- File uploads tablosu - New
CREATE TABLE file_uploads (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500),
    alt_text VARCHAR(255),
    uploaded_by INT UNSIGNED,
    entity_type VARCHAR(100) COMMENT 'menu_item, category, restaurant, etc.',
    entity_id INT UNSIGNED,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (uploaded_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu analytics tablosu - New
CREATE TABLE menu_analytics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED,
    category_id INT UNSIGNED,
    table_id INT UNSIGNED,
    event_type ENUM('view', 'click', 'search', 'share') NOT NULL,
    session_id VARCHAR(128),
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer VARCHAR(500),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE SET NULL,
    INDEX idx_restaurant_date (restaurant_id, created_at),
    INDEX idx_menu_item (menu_item_id),
    INDEX idx_event_type (event_type),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================
-- DATA INSERTION
-- ================================

-- Önceden tanımlı temalar
INSERT INTO themes (name, slug, description, primary_color, secondary_color, accent_color, bg_color, text_color, card_bg, css_variables) VALUES
('Classic Restaurant', 'classic', 'Geleneksel ve sıcak restoran teması', '#C8102E', '#FFD700', '#2E8B57', '#F8FAFC', '#1E293B', '#FFFFFF', 
 '{"shadow": "0 4px 6px -1px rgba(0, 0, 0, 0.1)", "border_radius": "0.5rem", "font_family": "Inter, sans-serif"}'),

('Modern Minimalist', 'modern', 'Modern ve minimalist tasarım', '#667EEA', '#764BA2', '#F093FB', '#F7FAFC', '#1A202C', '#FFFFFF',
 '{"shadow": "0 10px 25px -3px rgba(0, 0, 0, 0.1)", "border_radius": "1rem", "font_family": "Inter, sans-serif"}'),

('Dark Elegance', 'dark-elegance', 'Lüks ve koyu tema', '#D4AF37', '#C0392B', '#2ECC71', '#0D1117', '#F8F9FA', '#21262D',
 '{"shadow": "0 4px 6px -1px rgba(255, 255, 255, 0.1)", "border_radius": "0.75rem", "font_family": "Inter, sans-serif"}'),

('Colorful Cafe', 'colorful', 'Renkli ve enerjik kafe teması', '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFF8E1', '#2C3E50', '#FFFFFF',
 '{"shadow": "0 8px 32px rgba(31, 38, 135, 0.37)", "border_radius": "1.25rem", "font_family": "Inter, sans-serif"}'),

('Fine Dining', 'fine-dining', 'Şık ve premium restoran teması', '#8B4513', '#D2691E', '#DAA520', '#FDF6E3', '#2F1B14', '#FAF0E6',
 '{"shadow": "0 2px 10px rgba(139, 69, 19, 0.2)", "border_radius": "0.25rem", "font_family": "Playfair Display, serif"}'),

('Seafood Fresh', 'seafood', 'Deniz ürünleri restoranı teması', '#0077BE', '#20B2AA', '#FF7F50', '#F0F8FF', '#1C3A3A', '#FFFFFF',
 '{"shadow": "0 6px 20px rgba(0, 119, 190, 0.3)", "border_radius": "1rem", "font_family": "Inter, sans-serif"}'),

('Italian Rustic', 'italian', 'İtalyan mutfağı teması', '#228B22', '#DC143C', '#FFD700', '#FFF8DC', '#2F4F2F', '#FFFAF0',
 '{"shadow": "0 4px 15px rgba(34, 139, 34, 0.2)", "border_radius": "0.5rem", "font_family": "Inter, sans-serif"}'),

('Asian Zen', 'asian', 'Asya mutfağı zen teması', '#8B0000', '#FFD700', '#32CD32', '#F5F5DC', '#2F4F2F', '#FFFFFF',
 '{"shadow": "0 3px 12px rgba(139, 0, 0, 0.25)", "border_radius": "0.375rem", "font_family": "Inter, sans-serif"}');

-- Örnek restoran verisi
INSERT INTO restaurants (name, slug, logo, address, phone, email, website, description, business_hours, social_media) VALUES
('Lezzet Restaurant', 'lezzet-restaurant', '/uploads/logos/lezzet-logo.png', 
 'Atatürk Caddesi No:123, Seyhan/Adana', '+90 322 123 45 67', 'info@lezzetrestaurant.com', 
 'https://lezzetrestaurant.com', 'Adana\'nın en lezzetli mekânı', 
 '{"monday": "09:00-23:00", "tuesday": "09:00-23:00", "wednesday": "09:00-23:00", "thursday": "09:00-23:00", "friday": "09:00-24:00", "saturday": "09:00-24:00", "sunday": "10:00-22:00"}',
 '{"facebook": "https://facebook.com/lezzetrestaurant", "instagram": "@lezzetrestaurant", "twitter": "@lezzetrestaurant"}');

-- Örnek kategoriler
INSERT INTO categories (restaurant_id, name, slug, description, icon, sort_order) VALUES
(1, 'Başlangıçlar', 'baslangiclar', 'Aperatifler ve meze çeşitleri', '🥗', 1),
(1, 'Çorbalar', 'corbalar', 'Sıcak ve doyurucu çorba çeşitleri', '🍲', 2),
(1, 'Ana Yemekler', 'ana-yemekler', 'Et, tavuk ve balık yemekleri', '🍖', 3),
(1, 'Kebaplar', 'kebaplar', 'Adana\'nın meşhur kebap çeşitleri', '🥙', 4),
(1, 'Pideler', 'pideler', 'Fırından çıkmış sıcak pideler', '🍕', 5),
(1, 'Salatalar', 'salatalar', 'Taze ve sağlıklı salata çeşitleri', '🥗', 6),
(1, 'Tatlılar', 'tatlilar', 'Ev yapımı tatlılar', '🍰', 7),
(1, 'Sıcak İçecekler', 'sicak-icecekler', 'Çay, kahve ve sıcak içecekler', '☕', 8),
(1, 'Soğuk İçecekler', 'soguk-icecekler', 'Meşrubat, meyve suları ve soğuk içecekler', '🥤', 9);

-- Örnek menü öğeleri
INSERT INTO menu_items (category_id, name, slug, description, short_description, price, image, ingredients, allergens, spice_level, dietary_info, is_featured, is_popular) VALUES
-- Başlangıçlar
(1, 'Humus', 'humus', 'Tahin ve limon ile hazırlanmış ev yapımı humus. Zeytinyağı ve kırmızı pul biber ile servis edilir.', 'Ev yapımı humus', 18.00, '/uploads/images/humus.jpg', 'Nohut, tahin, limon, sarımsak, zeytinyağı, kırmızı pul biber', 'Susam', 'none', 'vegetarian,vegan,gluten_free', FALSE, TRUE),
(1, 'Cacık', 'cacik', 'Yoğurt, salatalık, sarımsak ve nane ile hazırlanmış serinletici cacık.', 'Geleneksel cacık', 15.00, '/uploads/images/cacik.jpg', 'Yoğurt, salatalık, sarımsak, nane, zeytinyağı', 'Süt', 'none', 'vegetarian,gluten_free', FALSE, FALSE),
(1, 'Atom', 'atom', 'Acı domates sosu, sarımsak ve baharatlarla hazırlanmış Antakya usulü atom.', 'Antakya usulü atom', 20.00, '/uploads/images/atom.jpg', 'Domates, sarımsak, acı biber, baharatlar', 'Yok', 'hot', 'vegetarian,vegan', TRUE, TRUE),

-- Çorbalar
(2, 'Mercimek Çorbası', 'mercimek-corbasi', 'Kırmızı mercimek, havuç ve soğan ile hazırlanmış besleyici çorba.', 'Klasik mercimek çorbası', 12.00, '/uploads/images/mercimek-corbasi.jpg', 'Kırmızı mercimek, havuç, soğan, tereyağı, baharatlar', 'Süt', 'none', 'vegetarian', FALSE, TRUE),
(2, 'İşkembe Çorbası', 'iskembe-corbasi', 'Geleneksel işkembe çorbası, sarımsaklı sirke ile servis edilir.', 'Geleneksel işkembe çorbası', 25.00, '/uploads/images/iskembe-corbasi.jpg', 'İşkembe, tereyağı, un, yumurta, limon, sarımsak', 'Gluten,Yumurta', 'none', '', FALSE, FALSE),

-- Ana Yemekler
(3, 'Izgara Levrek', 'izgara-levrek', 'Taze deniz levreği ızgara, zeytinyağı ve limon ile marine edilmiş.', 'Taze ızgara levrek', 65.00, '/uploads/images/izgara-levrek.jpg', 'Levrek balığı, zeytinyağı, limon, tuz, karabiber', 'Balık', 'none', '', TRUE, TRUE),
(3, 'Kuzu Tandır', 'kuzu-tandir', 'Yavaş pişirilmiş kuzu tandır, özel baharatlar ile lezzetlendirilmiş.', 'Yavaş pişirilmiş kuzu tandır', 85.00, '/uploads/images/kuzu-tandir.jpg', 'Kuzu eti, soğan, domates, baharatlar', 'Yok', 'mild', 'halal', TRUE, TRUE),
(3, 'Tavuk Şiş', 'tavuk-sis', 'Marine edilmiş tavuk göğsü şiş kebap, ızgara sebze ile servis.', 'Marine tavuk şiş', 45.00, '/uploads/images/tavuk-sis.jpg', 'Tavuk göğsü, zeytinyağı, baharatlar, sebzeler', 'Yok', 'mild', 'halal', FALSE, TRUE),

-- Kebaplar
(4, 'Adana Kebap', 'adana-kebap', 'Özel baharatlarla hazırlanmış geleneksel Adana kebabı. Bulgur pilavı ve közlenmiş domates ile servis edilir.', 'Geleneksel Adana kebabı', 45.00, '/uploads/images/adana-kebap.jpg', 'Dana kıyma, kuyruk yağı, özel baharat karışımı, soğan', 'Yok', 'hot', 'halal', TRUE, TRUE),
(4, 'Urfa Kebap', 'urfa-kebap', 'Acısız, lezzetli Urfa kebabı. Bulgur pilavı ve salata ile servis edilir.', 'Acısız Urfa kebabı', 42.00, '/uploads/images/urfa-kebap.jpg', 'Dana kıyma, Urfa biberi, soğan, baharatlar', 'Yok', 'mild', 'halal', TRUE, TRUE),
(4, 'Şiş Kebap', 'sis-kebap', 'Marine edilmiş dana eti şiş kebap, ızgara sebze ile.', 'Dana şiş kebap', 55.00, '/uploads/images/sis-kebap.jpg', 'Dana eti, zeytinyağı, soğan, baharatlar', 'Yok', 'mild', 'halal', FALSE, TRUE),

-- Pideler
(5, 'Kıymalı Pide', 'kiymali-pide', 'El açması hamur üzerine baharatlı kıyma, domates ve biber.', 'Baharatlı kıymalı pide', 35.00, '/uploads/images/kiymali-pide.jpg', 'Hamur, dana kıyma, domates, biber, baharatlar', 'Gluten', 'mild', 'halal', FALSE, TRUE),
(5, 'Karışık Pide', 'karisik-pide', 'Kıyma, sucuk, kaşar peyniri ve yumurta ile karışık pide.', 'Karışık malzemeli pide', 40.00, '/uploads/images/karisik-pide.jpg', 'Hamur, kıyma, sucuk, kaşar, yumurta', 'Gluten,Süt,Yumurta', 'mild', '', FALSE, FALSE),

-- Salatalar
(6, 'Çoban Salata', 'coban-salata', 'Domates, salatalık, soğan ve maydanoz ile hazırlanmış taze salata.', 'Taze çoban salatası', 22.00, '/uploads/images/coban-salata.jpg', 'Domates, salatalık, soğan, maydanoz, zeytinyağı, limon', 'Yok', 'none', 'vegetarian,vegan,gluten_free', FALSE, TRUE),
(6, 'Mevsim Salata', 'mevsim-salata', 'Mevsim yeşillikleri, ceviz ve nar ekşisi ile.', 'Mevsim yeşillikleri salatası', 28.00, '/uploads/images/mevsim-salata.jpg', 'Karışık yeşillik, ceviz, nar ekşisi, zeytinyağı', 'Ceviz', 'none', 'vegetarian,gluten_free', FALSE, FALSE),

-- Tatlılar
(7, 'Künefe', 'kunefe', 'Peynirli tel kadayıf tatlısı, şerbet ve fıstık ile servis edilir.', 'Peynirli künefe', 28.00, '/uploads/images/kunefe.jpg', 'Tel kadayıf, lor peyniri, şerbet, antep fıstığı', 'Gluten,Süt', 'none', 'vegetarian', TRUE, TRUE),
(7, 'Baklava', 'baklava', 'Fıstıklı baklava, ince yufka aralarında antep fıstığı.', 'Antep fıstıklı baklava', 32.00, '/uploads/images/baklava.jpg', 'Yufka, antep fıstığı, şerbet, tereyağı', 'Gluten,Süt,Fıstık', 'none', 'vegetarian', FALSE, TRUE),
(7, 'Sütlaç', 'sutlac', 'Fırında pişirilmiş geleneksel sütlaç, tarçın ile.', 'Fırında sütlaç', 18.00, '/uploads/images/sutlac.jpg', 'Süt, pirinç, şeker, tarçın', 'Süt', 'none', 'vegetarian,gluten_free', FALSE, FALSE),

-- Sıcak İçecekler
(8, 'Türk Kahvesi', 'turk-kahvesi', 'Geleneksel Türk kahvesi, şekerli veya sade.', 'Geleneksel Türk kahvesi', 12.00, '/uploads/images/turk-kahvesi.jpg', 'Kahve, şeker (isteğe bağlı)', 'Yok', 'none', '', FALSE, TRUE),
(8, 'Çay', 'cay', 'Türk çayı, ince belli bardakta servis.', 'Türk çayı', 5.00, '/uploads/images/cay.jpg', 'Çay', 'Yok', 'none', '', FALSE, TRUE),
(8, 'Nescafe', 'nescafe', 'Sıcak Nescafe, süt ve şeker ile.', 'Sıcak Nescafe', 15.00, '/uploads/images/nescafe.jpg', 'İnstant kahve, süt, şeker', 'Süt', 'none', 'vegetarian', FALSE, FALSE),

-- Soğuk İçecekler
(9, 'Ayran', 'ayran', 'Ev yapımı ayran, tuz ile çırpılmış.', 'Ev yapımı ayran', 8.00, '/uploads/images/ayran.jpg', 'Yoğurt, su, tuz', 'Süt', 'none', 'vegetarian,gluten_free', FALSE, TRUE),
(9, 'Cola', 'cola', 'Soğuk cola, buzlu servis.', 'Soğuk cola', 10.00, '/uploads/images/cola.jpg', 'Kola', 'Yok', 'none', '', FALSE, FALSE),
(9, 'Portakal Suyu', 'portakal-suyu', 'Taze sıkılmış portakal suyu.', 'Taze portakal suyu', 18.00, '/uploads/images/portakal-suyu.jpg', 'Portakal', 'Yok', 'none', 'vegetarian,vegan,gluten_free', FALSE, FALSE);

-- Örnek masalar
INSERT INTO tables (restaurant_id, table_number, table_name, qr_code, capacity, location) VALUES
(1, '1', 'Masa 1', 'QR_TABLE_001_' || UNIX_TIMESTAMP(), 4, 'Indoor'),
(1, '2', 'Masa 2', 'QR_TABLE_002_' || UNIX_TIMESTAMP(), 2, 'Indoor'),
(1, '3', 'Masa 3', 'QR_TABLE_003_' || UNIX_TIMESTAMP(), 6, 'Indoor'),
(1, '4', 'Masa 4', 'QR_TABLE_004_' || UNIX_TIMESTAMP(), 4, 'Outdoor'),
(1, '5', 'Masa 5', 'QR_TABLE_005_' || UNIX_TIMESTAMP(), 8, 'VIP'),
(1, '6', 'Masa 6', 'QR_TABLE_006_' || UNIX_TIMESTAMP(), 4, 'Indoor'),
(1, '7', 'Masa 7', 'QR_TABLE_007_' || UNIX_TIMESTAMP(), 2, 'Outdoor'),
(1, '8', 'Masa 8', 'QR_TABLE_008_' || UNIX_TIMESTAMP(), 4, 'Indoor');

-- Örnek admin kullanıcısı (şifre: Admin123!)
INSERT INTO admin_users (restaurant_id, username, password, email, full_name, role, is_active) VALUES
(1, 'admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@lezzetrestaurant.com', 'Sistem Yöneticisi', 'super_admin', TRUE),
(1, 'manager', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@lezzetrestaurant.com', 'Restoran Müdürü', 'manager', TRUE),
(1, 'staff', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff@lezzetrestaurant.com', 'Garson', 'staff', TRUE);

-- Örnek sistem ayarları
INSERT INTO settings (restaurant_id, setting_key, setting_value, setting_type, is_public, description) VALUES
(1, 'restaurant_open_hours', '{"monday": "09:00-23:00", "tuesday": "09:00-23:00", "wednesday": "09:00-23:00", "thursday": "09:00-23:00", "friday": "09:00-24:00", "saturday": "09:00-24:00", "sunday": "10:00-22:00"}', 'json', TRUE, 'Restaurant opening hours'),
(1, 'default_currency', 'TL', 'string', TRUE, 'Default currency symbol'),
(1, 'currency_symbol', '₺', 'string', TRUE, 'Currency symbol to display'),
(1, 'tax_rate', '18', 'number', FALSE, 'Tax rate percentage'),
(1, 'service_charge', '10', 'number', FALSE, 'Service charge percentage'),
(1, 'enable_waiter_call', 'true', 'boolean', TRUE, 'Enable waiter call feature'),
(1, 'max_waiter_calls_per_hour', '10', 'number', FALSE, 'Maximum waiter calls per hour per table'),
(1, 'contact_phone', '+90 322 123 45 67', 'string', TRUE, 'Restaurant contact phone'),
(1, 'contact_email', 'info@lezzetrestaurant.com', 'email', TRUE, 'Restaurant contact email'),
(1, 'contact_address', 'Atatürk Caddesi No:123, Seyhan/Adana', 'text', TRUE, 'Restaurant address'),
(1, 'social_facebook', 'https://facebook.com/lezzetrestaurant', 'url', TRUE, 'Facebook page URL'),
(1, 'social_instagram', 'https://instagram.com/lezzetrestaurant', 'url', TRUE, 'Instagram profile URL'),
(1, 'social_twitter', 'https://twitter.com/lezzetrestaurant', 'url', TRUE, 'Twitter profile URL'),
(1, 'enable_analytics', 'true', 'boolean', FALSE, 'Enable menu analytics'),
(1, 'analytics_retention_days', '90', 'number', FALSE, 'Days to retain analytics data'),
(1, 'maintenance_mode', 'false', 'boolean', FALSE, 'Enable maintenance mode'),
(1, 'featured_items_limit', '6', 'number', TRUE, 'Number of featured items to display'),
(1, 'menu_cache_duration', '3600', 'number', FALSE, 'Menu cache duration in seconds');

-- Default tema ataması
INSERT INTO restaurant_themes (restaurant_id, theme_id, is_default) VALUES (1, 1, TRUE);

-- ================================
-- INDEXES AND OPTIMIZATIONS
-- ================================

-- Additional indexes for performance
CREATE INDEX idx_menu_items_featured_available ON menu_items(is_featured, is_available);
CREATE INDEX idx_menu_items_popular_available ON menu_items(is_popular, is_available);
CREATE INDEX idx_categories_restaurant_status_sort ON categories(restaurant_id, status, sort_order);
CREATE INDEX idx_waiter_calls_table_created ON waiter_calls(table_id, created_at);

-- Full-text search indexes
ALTER TABLE menu_items ADD FULLTEXT(name, description, ingredients);
ALTER TABLE categories ADD FULLTEXT(name, description);

-- ================================
-- STORED PROCEDURES
-- ================================

DELIMITER //

-- Get restaurant menu with categories
CREATE PROCEDURE GetRestaurantMenu(IN restaurantId INT UNSIGNED)
BEGIN
    SELECT 
        c.id as category_id,
        c.name as category_name,
        c.slug as category_slug,
        c.description as category_description,
        c.icon as category_icon,
        c.sort_order as category_sort_order,
        mi.id,
        mi.name,
        mi.slug,
        mi.description,
        mi.short_description,
        mi.price,
        mi.old_price,
        mi.image,
        mi.ingredients,
        mi.allergens,
        mi.spice_level,
        mi.dietary_info,
        mi.is_available,
        mi.is_featured,
        mi.is_popular,
        mi.is_new,
        mi.calories,
        mi.prep_time
    FROM categories c
    LEFT JOIN menu_items mi ON c.id = mi.category_id AND mi.is_available = TRUE
    WHERE c.restaurant_id = restaurantId 
    AND c.status = 'active'
    ORDER BY c.sort_order, mi.sort_order;
END //

-- Get popular menu items
CREATE PROCEDURE GetPopularItems(IN restaurantId INT UNSIGNED, IN itemLimit INT DEFAULT 10)
BEGIN
    SELECT 
        mi.*,
        c.name as category_name,
        c.slug as category_slug
    FROM menu_items mi
    JOIN categories c ON mi.category_id = c.id
    WHERE c.restaurant_id = restaurantId
    AND mi.is_available = TRUE
    AND mi.is_popular = TRUE
    ORDER BY mi.view_count DESC, mi.sort_order
    LIMIT itemLimit;
END //

-- Search menu items
CREATE PROCEDURE SearchMenuItems(IN restaurantId INT UNSIGNED, IN searchQuery VARCHAR(255))
BEGIN
    SELECT 
        mi.*,
        c.name as category_name,
        c.slug as category_slug,
        MATCH(mi.name, mi.description, mi.ingredients) AGAINST(searchQuery IN NATURAL LANGUAGE MODE) as relevance
    FROM menu_items mi
    JOIN categories c ON mi.category_id = c.id
    WHERE c.restaurant_id = restaurantId
    AND mi.is_available = TRUE
    AND (
        MATCH(mi.name, mi.description, mi.ingredients) AGAINST(searchQuery IN NATURAL LANGUAGE MODE)
        OR mi.name LIKE CONCAT('%', searchQuery, '%')
        OR mi.description LIKE CONCAT('%', searchQuery, '%')
        OR mi.ingredients LIKE CONCAT('%', searchQuery, '%')
    )
    ORDER BY relevance DESC, mi.sort_order
    LIMIT 50;
END //

DELIMITER ;

-- ================================
-- VIEWS
-- ================================

-- Restaurant overview view
CREATE VIEW restaurant_overview AS
SELECT 
    r.id,
    r.name,
    r.slug,
    r.address,
    r.phone,
    r.email,
    COUNT(DISTINCT c.id) as total_categories,
    COUNT(DISTINCT mi.id) as total_menu_items,
    COUNT(DISTINCT CASE WHEN mi.is_available = TRUE THEN mi.id END) as available_items,
    COUNT(DISTINCT t.id) as total_tables,
    COUNT(DISTINCT CASE WHEN wc.status = 'pending' THEN wc.id END) as pending_calls
FROM restaurants r
LEFT JOIN categories c ON r.id = c.restaurant_id AND c.status = 'active'
LEFT JOIN menu_items mi ON c.id = mi.category_id
LEFT JOIN tables t ON r.id = t.restaurant_id AND t.status = 'active'
LEFT JOIN waiter_calls wc ON t.id = wc.table_id
WHERE r.is_active = TRUE
GROUP BY r.id;

-- Menu item analytics view
CREATE VIEW menu_analytics_summary AS
SELECT 
    mi.id as menu_item_id,
    mi.name,
    c.name as category_name,
    COUNT(ma.id) as total_views,
    COUNT(CASE WHEN ma.event_type = 'view' THEN 1 END) as page_views,
    COUNT(CASE WHEN ma.event_type = 'click' THEN 1 END) as clicks,
    COUNT(CASE WHEN ma.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as views_last_7_days,
    COUNT(CASE WHEN ma.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as views_last_30_days
FROM menu_items mi
JOIN categories c ON mi.category_id = c.id
LEFT JOIN menu_analytics ma ON mi.id = ma.menu_item_id
GROUP BY mi.id, mi.name, c.name;

-- ================================
-- TRIGGERS
-- ================================

DELIMITER //

-- Update menu item view count
CREATE TRIGGER update_menu_item_view_count
AFTER INSERT ON menu_analytics
FOR EACH ROW
BEGIN
    IF NEW.event_type = 'view' AND NEW.menu_item_id IS NOT NULL THEN
        UPDATE menu_items 
        SET view_count = view_count + 1 
        WHERE id = NEW.menu_item_id;
    END IF;
END //

-- Log admin activities
CREATE TRIGGER log_admin_login
AFTER UPDATE ON admin_users
FOR EACH ROW
BEGIN
    IF OLD.last_login != NEW.last_login THEN
        INSERT INTO activity_logs (user_id, user_type, action, description, ip_address, created_at)
        VALUES (NEW.id, 'admin', 'login', CONCAT('Admin login: ', NEW.username), NEW.last_ip, NOW());
    END IF;
END //

DELIMITER ;

-- ================================
-- DATA CLEANUP AND MAINTENANCE
-- ================================

-- Create event scheduler for cleanup (if enabled)
-- SET GLOBAL event_scheduler = ON;

DELIMITER //

-- Clean old analytics data
CREATE EVENT IF NOT EXISTS cleanup_old_analytics
ON SCHEDULE EVERY 1 DAY
STARTS '2025-01-01 02:00:00'
DO
BEGIN
    DELETE FROM menu_analytics 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    DELETE FROM activity_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
END //

DELIMITER ;

-- ================================
-- FINAL SETTINGS
-- ================================

-- Optimize tables
OPTIMIZE TABLE restaurants, categories, menu_items, tables, waiter_calls, admin_users, settings;

-- Update table statistics
ANALYZE TABLE restaurants, categories, menu_items, tables, waiter_calls, admin_users, settings;

-- Show database info
SELECT 
    'Database created successfully!' as status,
    COUNT(*) as total_themes FROM themes
UNION ALL
SELECT 
    'Sample restaurant data inserted' as status,
    COUNT(*) as count FROM restaurants
UNION ALL
SELECT 
    'Categories created' as status,
    COUNT(*) as count FROM categories
UNION ALL
SELECT 
    'Menu items inserted' as status,
    COUNT(*) as count FROM menu_items
UNION ALL
SELECT 
    'Tables created' as status,
    COUNT(*) as count FROM tables
UNION ALL
SELECT 
    'Admin users created' as status,
    COUNT(*) as count FROM admin_users;