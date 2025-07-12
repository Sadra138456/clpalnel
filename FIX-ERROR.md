# 🚨 رفع خطای JSON Parse Error

خطای `JSON.parse: unexpected end of data` معمولاً به این دلایل اتفاق می‌افتد:

## 🔍 مرحله 1: Debug اولیه

1. **فایل `debug.php` را آپلود و اجرا کنید:**
   ```
   https://yourdomain.com/debug.php
   ```

2. **بررسی کنید:**
   - ✅ اتصال دیتابیس موفق است؟
   - ✅ تمام فایل‌ها موجود هستند؟
   - ✅ PHP version مناسب است؟

---

## 🔧 مرحله 2: تست API مستقیم

1. **فایل `test-api.php` را اجرا کنید:**
   ```
   https://yourdomain.com/test-api.php
   ```

2. **اگر خطای HTML دیدید:**
   - مشکل در path های API
   - `.htaccess` کار نمی‌کند

3. **اگر پاسخ خالی دیدید:**
   - مشکل در کانفیگ دیتابیس
   - فایل‌های API ناقص

---

## 💾 مرحله 3: تنظیم Database

1. **فایل `config/database.example.php` را کپی کنید:**
   ```bash
   cp config/database.example.php config/database.php
   ```

2. **اطلاعات زیر را در `config/database.php` تغییر دهید:**
   ```php
   private $db_name = 'username_vet';      // نام دیتابیس
   private $username = 'username_user';    // کاربر MySQL  
   private $password = 'your_password';    // رمز MySQL
   ```

3. **آدرس سایت را تنظیم کنید:**
   ```php
   define('SITE_URL', 'https://yourdomain.com');
   ```

---

## ⚙️ مرحله 4: تست .htaccess

اگر مشکل همچنان ادامه دارد، `.htaccess` اصلی را rename کنید:

1. **نام فایل `.htaccess` را تغییر دهید:**
   ```bash
   mv .htaccess .htaccess.backup
   ```

2. **فایل `.htaccess.simple` را کپی کنید:**
   ```bash
   cp .htaccess.simple .htaccess
   ```

---

## 🌐 مرحله 5: تست Browser Console

1. **Developer Tools را باز کنید** (F12)
2. **Tab Console را انتخاب کنید**  
3. **دوباره لاگین کنید**
4. **Log های زیر را بررسی کنید:**
   - 🚀 API Request
   - 📡 Response Status  
   - 📝 Raw Response

---

## 🎯 راه حل‌های سریع:

### اگر دیتابیس خطا می‌دهد:
```php
// در config/database.php
private $host = 'localhost';        // یا IP سرور MySQL
private $db_name = 'دقیقاً_نام_دیتابیس';
private $username = 'دقیقاً_نام_کاربری';
private $password = 'دقیقاً_رمز_عبور';
```

### اگر API پیدا نمی‌شود:
```bash
# بررسی کنید این فایل‌ها موجود باشند:
api/auth.php
api/reservations.php  
api/sms.php
classes/JWT.php
classes/SMS.php
```

### اگر 404 می‌گیرید:
```apache
# در .htaccess اضافه کنید:
RewriteEngine On
RewriteRule ^api/auth/(.*)$ api/auth.php [QSA,L]
```

---

## 📞 اگر باز هم مشکل دارید:

1. **نتیجه `debug.php` را کپی کنید**
2. **Screenshot از Browser Console بگیرید**  
3. **نتیجه `test-api.php` را نشان دهید**

**🎉 بعد از رفع مشکل، فایل‌های debug را حذف کنید!**