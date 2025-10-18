# تغییرات پلاگین Academy Directory Pro

## نسخه 3.2 (Enhanced & Secured) - 2025-10-10

### ✨ ویژگی‌های جدید
- 🔒 **کلاس امنیتی جامع (ADP_Security):** مجموعه کاملی از توابع امنیتی
- 📊 **Structured Logging:** سیستم لاگینگ پیشرفته با context و IP tracking
- 🚦 **Rate Limiting پیشرفته:** الگوریتم Sliding Window برای محدودسازی درخواست‌ها
- 🛡️ **Security Headers:** اضافه شدن CSP, HSTS, و Permissions Policy
- ✅ **اعتبارسنجی پیشرفته:** Sanitization callbacks برای تمام تنظیمات

### 🔒 بهبودهای امنیتی

#### محافظت در برابر SQL Injection
- استفاده از `WP_Query` به جای کوئری‌های مستقیم
- استفاده از `absint()` و `floatval()` برای ورودی‌های عددی
- اعتبارسنجی تمام meta queries

#### محافظت در برابر XSS
- استفاده از `esc_html()`, `esc_attr()`, `esc_url()` در تمام خروجی‌ها
- پاکسازی CSS با `wp_strip_all_tags()`
- حذف JavaScript patterns از ورودی‌های CSS
- اعتبارسنجی تمام ورودی‌ها با `sanitize_text_field()`

#### محافظت در برابر CSRF
- اعتبارسنجی nonce در تمام AJAX requests
- اعتبارسنجی nonce در export/import
- استفاده از `wp_verify_nonce()` در تمام نقاط حساس

#### محافظت از فایل‌ها
- اضافه شدن `.htaccess` به پوشه لاگ‌ها
- محدودیت حجم فایل آپلود (5MB)
- محدودیت تعداد ردیف‌های CSV (1000)
- اعتبارسنجی MIME type فایل‌های آپلود

#### Rate Limiting
- پیاده‌سازی Sliding Window Algorithm
- محدودیت 15 درخواست در 60 ثانیه
- لاگ کردن تلاش‌های مشکوک
- پیام خطای مناسب به کاربر

### 🐛 رفع باگ‌ها

#### باگ‌های کریتیکال
- **[CRITICAL]** رفع مشکل استفاده از `serialize($_POST)` در کش
  - قبل: `md5(serialize($_POST))`
  - بعد: `md5(wp_json_encode($sanitized_params))`
  
- **[HIGH]** رفع مشکل Meta Key اشتباه در فیلترها
  - قبل: `_adp_rating`
  - بعد: `_adp_average_rating`

- **[HIGH]** رفع مشکل wp_kses در فیلدهای CSS
  - قبل: استفاده از iframe allowlist برای CSS
  - بعد: استفاده از `wp_strip_all_tags()` و regex cleaning

#### باگ‌های Medium
- **[MEDIUM]** بهبود تشخیص IP در rate limiting
  - اضافه شدن پشتیبانی از proxy chains
  - اعتبارسنجی با `FILTER_VALIDATE_IP`

- **[MEDIUM]** رفع مشکل اعتبارسنجی JSON در custom fields
  - اضافه شدن بررسی `json_last_error()`
  - نمایش پیام خطای مناسب

- **[MEDIUM]** بهبود sanitization شماره تلفن
  - فیلتر کردن کاراکترهای غیرمجاز
  - اجازه فقط به `0-9`, `+`, `-`, ` `, `(`, `)`

#### بهبودهای کد
- جایگزینی `intval()` با `absint()` در مناسب‌ترین مکان‌ها
- اضافه شدن `strict comparison` (`===`) به جای `==`
- بهبود error handling در CSV import
- اضافه شدن آمار کامل در CSV import (موفق، رد شده، خطا)

### 🚀 بهبودهای عملکرد

#### Caching
- بهبود کلیدهای کش با استفاده از `wp_json_encode()`
- افزایش مدت زمان کش از 300 به `MINUTE_IN_SECONDS * 5`
- اضافه شدن `total` به response های AJAX

#### Database
- بهینه‌سازی meta queries
- استفاده بهتر از `orderby` و `order`
- کاهش تعداد کوئری‌ها با استفاده از transients

#### Logging
- Structured logging با context
- اضافه شدن User ID و IP به لاگ‌ها
- چرخش خودکار فایل‌های لاگ (5MB)
- پاکسازی خودکار لاگ‌های 30 روز گذشته

### 📝 تغییرات API

#### کلاس‌های جدید
```php
ADP_Security::sanitize_url( $url )
ADP_Security::sanitize_phone( $phone )
ADP_Security::validate_json( $json )
ADP_Security::verify_nonce( $nonce, $action )
ADP_Security::sanitize_html( $html, $allowed_tags )
ADP_Security::encrypt_data( $data )
ADP_Security::decrypt_data( $encrypted_data )
ADP_Security::check_permission( $capability )
ADP_Security::create_hash( $data )
ADP_Security::safe_compare( $a, $b )
ADP_Security::prevent_xss( $data )
ADP_Security::validate_email( $email )
ADP_Security::limit_string_length( $string, $max_length )
```

#### توابع به‌روز شده
```php
// قبل
ADP_Logger::log( $message, $level )

// بعد
ADP_Logger::log( $message, $level, $context )
```

### 🔧 تغییرات تنظیمات

#### Sanitization Callbacks
- `adp_per_page`: `absint`
- `adp_cols`: `sanitize_cols` (1-4)
- `adp_per_page_ajax`: `absint`
- `adp_enable_ajax`: `absint`
- `adp_custom_fields`: `sanitize_json`
- `adp_custom_css`: `sanitize_css`
- `adp_enable_logging`: `absint`
- `adp_google_maps_api`: `sanitize_text_field`
- `adp_theme`: `sanitize_theme`

### 📋 فایل‌های جدید
- `includes/adp-security.php` - کلاس امنیتی جامع
- `SECURITY.md` - مستندات امنیتی
- `CHANGELOG.md` - این فایل

### 🔄 فایل‌های تغییر یافته
- `academy-directory-pro.php`
- `includes/adp-core.php`
- `includes/adp-ajax.php`
- `includes/adp-settings.php`
- `includes/adp-helpers.php`
- `includes/adp-export-import.php`
- `includes/adp-logger.php`
- `includes/adp-metabox.php`
- `includes/adp-shortcode.php`

### 📊 آمار کد

#### خلاصه تغییرات
- **خطوط اضافه شده:** ~500
- **خطوط حذف شده:** ~100
- **فایل‌های تغییر یافته:** 9
- **فایل‌های جدید:** 3
- **کلاس‌های جدید:** 1
- **توابع جدید:** 15+

#### بهبودهای امنیتی
- **آسیب‌پذیری‌های رفع شده:** 10+
- **بهبودهای امنیتی:** 30+
- **توابع sanitization جدید:** 13

### ⚠️ Breaking Changes
این نسخه هیچ breaking change ندارد و با نسخه‌های قبلی سازگار است.

### 🔜 برنامه آینده

#### نسخه 3.3 (برنامه‌ریزی شده)
- [ ] پشتیبانی از REST API
- [ ] اضافه کردن Unit Tests
- [ ] بهینه‌سازی بیشتر database queries
- [ ] اضافه کردن dashboard analytics
- [ ] پشتیبانی از چند زبانه

#### نسخه 4.0 (آینده دور)
- [ ] معماری مبتنی بر Microservices
- [ ] پشتیبانی از PWA
- [ ] اضافه کردن GraphQL API
- [ ] سیستم notification پیشرفته

### 📖 مستندات
- برای جزئیات امنیتی، `SECURITY.md` را مطالعه کنید
- برای راهنمای توسعه، `README.md` را مطالعه کنید

### 🙏 تشکر
از تمامی کسانی که در بهبود این پلاگین مشارکت کرده‌اند، تشکر می‌کنیم.

---

## نسخه 3.1 (قبلی)
تغییرات نسخه‌های قبلی در اینجا لیست نمی‌شوند. برای تاریخچه کامل، به repository مراجعه کنید.
