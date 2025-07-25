# 🍽️ Restaurant Dijital Menü Sistemi

Modern, kullanıcı dostu ve tam özellikli bir restaurant dijital menü sistemi. Vanilla PHP backend ve modern frontend teknolojileri kullanarak geliştirilmiştir.

## ✨ Özellikler

### 🎯 Müşteri Tarafı
- **Responsive Tasarım**: Mobile-first yaklaşımla tüm cihazlarda mükemmel görünüm
- **Canlı Arama**: Instant search ile ürünlerde anlık arama
- **Kategori Filtreleme**: Smooth animasyonlarla kategori geçişleri
- **Ürün Detayları**: Modal ile detaylı ürün bilgileri
- **Garson Çağırma**: QR kod entegrasyonu ile garson çağırma sistemi
- **Multi-Theme**: 8 farklı önceden tasarlanmış tema
- **PWA Desteği**: Offline çalışma ve app-like deneyim

### ⚙️ Admin Paneli
- **Dashboard**: Gerçek zamanlı istatistikler ve grafikler
- **Menü Yönetimi**: Drag & drop ile ürün/kategori yönetimi
- **Tema Editörü**: Renk paleti ve stil özelleştirmesi
- **Garson Çağrıları**: Real-time bildirimler ve durum takibi
- **Dosya Yükleme**: Otomatik boyutlandırma ve optimizasyon
- **Güvenlik**: XSS koruması, CSRF token, rate limiting

### 🔧 Teknik Özellikler
- **Backend**: Vanilla PHP 8.0+ (MVC pattern)
- **Frontend**: HTML5, CSS3, Vanilla JavaScript, Tailwind CSS
- **Veritabanı**: MySQL/PostgreSQL
- **API**: RESTful architecture
- **Güvenlik**: SQL injection koruması, input sanitization
- **Performance**: Lazy loading, image optimization, caching

## 🚀 Kurulum

### Gereksinimler
- PHP 8.0 veya üzeri
- MySQL 5.7+ veya PostgreSQL 10+
- Apache/Nginx web server
- GD extension (görsel işleme için)

### 1. Projeyi İndirin
```bash
git clone https://github.com/your-username/restaurant-menu.git
cd restaurant-menu
```

### 2. Veritabanını Kurun
```bash
# MySQL için
mysql -u root -p < database.sql

# PostgreSQL için  
psql -U postgres -d restaurant_menu -f database.sql
```

### 3. Konfigürasyonu Yapın
`backend/config/config.php` dosyasını düzenleyin:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_menu');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('SITE_URL', 'http://your-domain.com');
```

### 4. Dizin İzinlerini Ayarlayın
```bash
chmod 755 backend/uploads/
chmod 644 backend/uploads/images/
```

### 5. Virtual Host Kurulumu

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^backend/api/(.*)$ backend/api/$1 [L]
```

#### Nginx
```nginx
location /backend/api/ {
    try_files $uri $uri/ @api;
}

location @api {
    rewrite ^/backend/api/(.*)$ /backend/api/$1 last;
}
```

## 📁 Proje Yapısı

```
restaurant-menu/
├── 📄 index.html                 # Ana menü sayfası
├── 📄 manifest.json             # PWA manifest
├── 📄 sw.js                     # Service Worker
├── 📄 offline.html              # Offline sayfası
├── 📁 js/
│   └── 📄 menu.js               # Ana sayfa JavaScript
├── 📁 admin/
│   ├── 📄 login.html            # Admin giriş
│   ├── 📄 dashboard.html        # Admin dashboard
│   └── 📁 js/
│       └── 📄 dashboard.js      # Dashboard JavaScript
├── 📁 backend/
│   ├── 📁 config/
│   │   ├── 📄 config.php        # Ana konfigürasyon
│   │   ├── 📄 database.php      # Veritabanı sınıfı
│   │   ├── 📄 utils.php         # Yardımcı fonksiyonlar
│   │   └── 📄 session_manager.php # Session yönetimi
│   ├── 📁 models/
│   │   ├── 📄 MenuItem.php      # Menü modeli
│   │   ├── 📄 Category.php      # Kategori modeli
│   │   ├── 📄 Theme.php         # Tema modeli
│   │   ├── 📄 WaiterCall.php    # Garson çağrı modeli
│   │   ├── 📄 Table.php         # Masa modeli
│   │   └── 📄 Admin.php         # Admin modeli
│   ├── 📁 api/
│   │   ├── 📄 menu.php          # Menü API
│   │   ├── 📄 categories.php    # Kategori API
│   │   ├── 📄 themes.php        # Tema API
│   │   ├── 📄 waiter-call.php   # Garson çağrı API
│   │   ├── 📄 search.php        # Arama API
│   │   ├── 📄 upload.php        # Dosya yükleme API
│   │   └── 📄 auth.php          # Authentication API
│   └── 📁 uploads/              # Yüklenen dosyalar
├── 📁 icons/                    # PWA ikonları
├── 📁 images/                   # Statik görseller
└── 📄 database.sql             # Veritabanı şeması
```

## 🎨 Tema Sistemi

Sistem 8 önceden tasarlanmış tema ile birlikte gelir:

1. **Classic Restaurant** - Geleneksel restaurant teması
2. **Modern Minimalist** - Minimalist tasarım  
3. **Dark Elegance** - Lüks koyu tema
4. **Colorful Cafe** - Renkli, enerjik tema
5. **Fine Dining** - Şık, premium tema
6. **Seafood Fresh** - Deniz ürünleri teması
7. **Italian Rustic** - İtalyan mutfağı teması
8. **Asian Zen** - Asya mutfağı teması

### Özel Tema Oluşturma
```php
// Yeni tema ekleme
$themeData = [
    'name' => 'Özel Tema',
    'slug' => 'custom-theme',
    'primary_color' => '#FF6B6B',
    'secondary_color' => '#4ECDC4',
    'accent_color' => '#45B7D1',
    'bg_color' => '#F8F9FA',
    'text_color' => '#2C3E50',
    'card_bg' => '#FFFFFF'
];
```

## 🔒 Güvenlik

### Uygulanmış Güvenlik Önlemleri
- **SQL Injection**: Prepared statements
- **XSS Koruması**: Input/output sanitization
- **CSRF Koruması**: Token validation
- **Rate Limiting**: API abuse önleme
- **Session Güvenliği**: Secure session management
- **File Upload**: Type ve size validation
- **Input Validation**: Comprehensive validation

### Güvenlik Ayarları
```php
// Rate limiting örneği
Utils::rateLimit("login_{$clientIp}", 5, 900); // 5 deneme / 15 dakika

// XSS koruması
$cleanData = Database::escape($userInput);

// CSRF token
$token = Database::generateCSRFToken();
```

## 📱 PWA (Progressive Web App)

### Özellikler
- **Offline Support**: Service Worker ile offline çalışma
- **App-like Experience**: Native app benzeri deneyim
- **Push Notifications**: Garson çağrıları için bildirimler
- **Background Sync**: Offline işlemlerin senkronizasyonu
- **Add to Home Screen**: Ana ekrana ekleme

### Service Worker Konfigürasyonu
```javascript
// Cache stratejileri
const CACHE_STRATEGIES = {
    static: 'cache-first',
    api: 'network-first', 
    images: 'cache-first'
};
```

## 🔧 API Dokümantasyonu

### Menu API
```http
GET /backend/api/menu.php?restaurant_id=1
GET /backend/api/menu.php?restaurant_id=1&featured=true
GET /backend/api/menu.php?restaurant_id=1&category_id=3
POST /backend/api/menu.php (Admin only)
PUT /backend/api/menu.php (Admin only)
DELETE /backend/api/menu.php (Admin only)
```

### Search API
```http
GET /backend/api/search.php?restaurant_id=1&q=kebap
```

### Waiter Call API
```http
GET /backend/api/waiter-call.php?restaurant_id=1&action=pending
POST /backend/api/waiter-call.php
```

### Response Format
```json
{
    "success": true,
    "message": "Success",
    "data": [...],
    "timestamp": "2025-01-20T10:30:00Z"
}
```

## 🎯 Performans Optimizasyonları

### Frontend
- **Lazy Loading**: Görseller için lazy loading
- **Code Splitting**: Modüler JavaScript
- **CSS Optimization**: Tailwind purging
- **Image Optimization**: WebP format, compression
- **Caching**: Browser caching ve Service Worker

### Backend
- **Database**: Query optimization, indexing
- **API Rate Limiting**: Abuse önleme
- **File Caching**: Statik dosya caching
- **Compression**: Gzip response compression

## 🔍 Test Etme

### Örnek Test Verileri
```sql
-- Test restoranı
INSERT INTO restaurants (name, description) VALUES 
('Test Restaurant', 'Test açıklaması');

-- Test kategorileri
INSERT INTO categories (restaurant_id, name, icon) VALUES 
(1, 'Başlangıçlar', '🥗'),
(1, 'Ana Yemekler', '🍖'),
(1, 'Tatlılar', '🍰');
```

### Demo Giriş Bilgileri
- **Kullanıcı Adı**: admin
- **Şifre**: admin123

## 🐛 Hata Giderme

### Yaygın Sorunlar

#### 1. Veritabanı Bağlantı Hatası
```
Database connection failed: SQLSTATE[HY000] [2002]
```
**Çözüm**: `config.php` dosyasındaki veritabanı ayarlarını kontrol edin.

#### 2. Dosya Yükleme Hatası
```
Dosya yükleme başarısız
```
**Çözüm**: `uploads/` dizin izinlerini kontrol edin (755).

#### 3. Session Hatası
```
Authentication required
```
**Çözüm**: PHP session ayarlarını ve cookie settings kontrol edin.

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request açın

## 📋 ToDo

- [ ] Multi-language support (i18n)
- [ ] Payment integration
- [ ] QR menu generator
- [ ] Analytics dashboard
- [ ] Email notifications
- [ ] SMS integration
- [ ] Social media sharing
- [ ] Customer reviews
- [ ] Loyalty program
- [ ] Inventory management

## 📝 Changelog

### v1.0.0 (2025-01-20)
- ✅ İlk sürüm yayınlandı
- ✅ Temel menü sistemi
- ✅ Admin paneli
- ✅ PWA desteği
- ✅ Tema sistemi
- ✅ Garson çağırma sistemi

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için [LICENSE](LICENSE) dosyasına bakın.

## 📞 İletişim

- **Proje**: Restaurant Digital Menu System
- **E-mail**: info@lezzetrestaurant.com
- **Website**: [https://lezzetrestaurant.com](https://lezzetrestaurant.com)

## 🙏 Teşekkürler

- [Tailwind CSS](https://tailwindcss.com/) - CSS framework
- [Chart.js](https://www.chartjs.org/) - Grafik library
- [Heroicons](https://heroicons.com/) - Icon set

---

⭐ Bu projeyi beğendiyseniz, lütfen yıldız verin!

📢 Sorularınız için issue açabilir veya pull request gönderebilirsiniz.