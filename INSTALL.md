# 🚀 راهنمای سریع نصب - هاست دیتاسنتر المان

## مرحله 1: آپلود فایل‌ها
1. تمام فایل‌ها را از پوشه پروژه در `public_html` آپلود کنید
2. ساختار فایل‌ها باید اینگونه باشد:
```
public_html/
├── index.php
├── .htaccess
├── config/
│   └── database.php
├── api/
│   ├── auth.php
│   ├── reservations.php
│   └── sms.php
├── classes/
│   ├── JWT.php
│   └── SMS.php
├── database/
│   └── schema.sql
├── css/
│   └── style.css
├── js/
│   └── main.js
└── uploads/
    └── .htaccess
```

## مرحله 2: ایجاد دیتابیس
1. در cPanel > MySQL Databases
2. دیتابیس جدید بسازید (مثلاً: `username_vet`)
3. کاربر MySQL ایجاد کنید
4. کاربر را به دیتابیس assign کنید

## مرحله 3: اجرای SQL
1. در phpMyAdmin وارد شوید
2. دیتابیس خود را انتخاب کنید
3. فایل `database/schema.sql` را import کنید

## مرحله 4: تنظیم کانفیگ
فایل `config/database.php` را ویرایش کنید:
```php
// خط 4-7 را تغییر دهید:
private $db_name = 'username_vet';     // نام دیتابیس
private $username = 'username_user';   // نام کاربری MySQL
private $password = 'your_password';   // رمز عبور MySQL

// خط 33-34 را تغییر دهید:
define('SMS_API_KEY', 'your-kavenegar-api-key');
define('SMS_SENDER', '10004346');

// خط 36 را تغییر دهید:
define('SITE_URL', 'https://yourdomain.com');
```

## مرحله 5: تست سیستم
1. به آدرس سایت بروید
2. با اطلاعات زیر وارد شوید:
   - **ایمیل**: `admin@vet.com`
   - **رمز**: `admin123`

## مرحله 6: تنظیم SMS (اختیاری)
1. در بخش **تنظیمات** > **تنظیمات SMS**
2. API Key کاوه‌نگار را وارد کنید
3. شماره ارسال را تنظیم کنید

---

## ⚠️ نکات مهم:
- حتماً رمز admin را تغییر دهید
- API Key کاوه‌نگار را محرمانه نگه دارید
- برای تست SMS، یک شماره معتبر استفاده کنید

## 📞 پشتیبانی:
اگر مشکلی داشتید، بررسی کنید:
1. آیا تمام فایل‌ها آپلود شده؟
2. آیا اطلاعات دیتابیس درست است؟
3. آیا Schema اجرا شده؟

---

**🎉 تبریک! سیستم شما آماده استفاده است!**