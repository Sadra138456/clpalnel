# 🏥 سیستم رزرو کلینیک دامپزشکی
## سیستم مدیریت نوبت‌های دامپزشکی با قابلیت ارسال SMS

### 📋 ویژگی‌ها
- ✅ **مدیریت کاربران**: ثبت نام، ورود، اعتبارسنجی JWT
- ✅ **تقویم رزرو**: مدیریت کامل نوبت‌های دامپزشکی
- ✅ **ارسال SMS**: ادغام کاوه‌نگار برای پیام‌های خودکار
- ✅ **آرشیو SMS**: ذخیره و مدیریت تمام پیام‌های ارسالی
- ✅ **پنل مدیریت**: رابط کاربری مدرن و کاربرپسند
- ✅ **Google OAuth**: ورود آسان با حساب Google
- ✅ **طراحی ریسپانسیو**: سازگار با تمام دستگاه‌ها
- ✅ **تم‌های زیبا**: 6 تم بنفش متنوع

---

## 🚀 نصب روی هاست اشتراکی

### 1️⃣ **پیش‌نیازها**
- PHP 7.4 یا بالاتر
- MySQL 5.7 یا بالاتر
- cPanel یا phpMyAdmin
- اکانت کاوه‌نگار SMS

### 2️⃣ **آپلود فایل‌ها**
```bash
# کل پروژه را در پوشه public_html یا subdomain آپلود کنید
public_html/
├── index.php
├── .htaccess
├── config/
├── api/
├── classes/
├── database/
├── css/
├── js/
└── uploads/
```

### 3️⃣ **تنظیم دیتابیس**
1. **ایجاد دیتابیس**:
   - در cPanel > MySQL Databases
   - یک دیتابیس جدید بسازید
   - کاربر MySQL ایجاد کنید و به دیتابیس اختصاص دهید

2. **اجرای Schema**:
   ```sql
   -- در phpMyAdmin، فایل database/schema.sql را اجرا کنید
   ```

### 4️⃣ **پیکربندی**
فایل `config/database.php` را ویرایش کنید:
```php
// Database Configuration
private $host = 'localhost';
private $db_name = 'your_database_name';  // نام دیتابیس خود
private $username = 'your_username';       // نام کاربری MySQL
private $password = 'your_password';       // رمز عبور MySQL

// SMS Configuration (Kavenegar)
define('SMS_API_KEY', 'your-kavenegar-api-key');
define('SMS_SENDER', '10004346'); // شماره پنل کاوه‌نگار

// Site Configuration
define('SITE_URL', 'https://yourdomain.com');
```

### 5️⃣ **تنظیم مجوزها**
```bash
# در cPanel File Manager یا FTP
chmod 755 uploads/
chmod 644 config/database.php
```

---

## 🔧 تنظیمات کاوه‌نگار SMS

### 1️⃣ **دریافت API Key**
1. وارد پنل کاوه‌نگار شوید
2. از منوی **تنظیمات** > **API** کلید خود را کپی کنید
3. شماره ارسال (پیش‌فرض: 10004346) را یادداشت کنید

### 2️⃣ **تست اتصال**
```php
// تست API در settings بخش سیستم
curl -X POST "https://api.kavenegar.com/v1/YOUR_API_KEY/sms/send.json" \
  -d "receptor=09123456789&message=تست&sender=10004346"
```

---

## 📊 API Endpoints

### 🔐 **Authentication**
```bash
POST /api/auth/login
POST /api/auth/register
POST /api/auth/google-login
GET  /api/auth/verify-token
```

### 📅 **Reservations**
```bash
GET    /api/reservations          # لیست رزروها
POST   /api/reservations          # ایجاد رزرو
PUT    /api/reservations/{id}     # ویرایش رزرو
DELETE /api/reservations/{id}     # حذف رزرو
GET    /api/reservations/statistics # آمار
```

### 📱 **SMS**
```bash
POST /api/sms/send               # ارسال تکی
POST /api/sms/send-bulk          # ارسال گروهی
GET  /api/sms/archive            # آرشیو پیام‌ها
GET  /api/sms/statistics         # آمار SMS
GET  /api/sms/templates          # قالب‌های پیام
```

---

## 🎨 تم‌های موجود

1. **بنفش مرموز** - تم کلاسیک بنفش
2. **آبی شاهانه** - ترکیب آبی و بنفش
3. **صورتی رمانتیک** - تم صورتی ملایم
4. **سبز طبیعی** - تم سبز آرامش‌بخش
5. **نارنجی انرژی** - تم نارنجی پرانرژی
6. **قرمز قدرت** - تم قرمز قدرتمند

---

## 🔒 امنیت

### Headers امنیتی
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- CORS Headers برای API

### محافظت از فایل‌ها
- فایل‌های .env, .sql, .log محافظت شده
- Directory browsing غیرفعال
- JWT Token برای احراز هویت

---

## 📱 نحوه استفاده

### 1️⃣ **ورود اولیه**
- حساب مدیر پیش‌فرض:
  - ایمیل: `admin@vet.com`
  - رمز: `admin123`

### 2️⃣ **ایجاد رزرو**
1. از منوی **رزروها** > **افزودن رزرو**
2. اطلاعات حیوان و صاحب را وارد کنید
3. تاریخ و ساعت مناسب انتخاب کنید
4. پیام تأیید خودکار ارسال می‌شود

### 3️⃣ **ارسال SMS**
1. از منوی **ارسال پیام**
2. شماره و متن پیام را وارد کنید
3. برای ارسال گروهی از **ارسال گروهی** استفاده کنید

### 4️⃣ **مشاهده آرشیو**
- تمام پیام‌های ارسالی در **آرشیو پیام‌ها** قابل مشاهده
- امکان فیلتر و جستجو موجود است

---

## 🐛 رفع مشکلات

### مشکل اتصال به دیتابیس
```php
// بررسی اطلاعات در config/database.php
// اطمینان از درستی نام دیتابیس، کاربر و رمز
```

### خطای 500 در API
```bash
# بررسی error_log در cPanel
# فعال‌سازی error reporting در PHP
```

### مشکل ارسال SMS
```php
// بررسی API Key و شماره ارسال در config
// تست اتصال با کاوه‌نگار
```

### مشکل Google OAuth
```javascript
// بررسی تنظیمات Google Console
// اطمینان از درستی Client ID
```

---

## 📞 پشتیبانی

برای مشکلات فنی:
- 📧 ایمیل: support@yourvet.com
- 🌐 وب‌سایت: https://yourvet.com
- 📱 تلگرام: @yourvet_support

---

## 🔄 نسخه‌ها

### v1.0 (فعلی)
- ✅ سیستم رزرو پایه
- ✅ ارسال SMS
- ✅ پنل مدیریت
- ✅ Google OAuth
- ✅ 6 تم زیبا

### v1.1 (آینده)
- 🔄 تقویم تعاملی
- 🔄 گزارش‌های پیشرفته
- 🔄 نوتیفیکیشن‌های Push
- 🔄 پشتیبانی چندزبانه

---

## 📄 مجوز

این پروژه تحت مجوز MIT منتشر شده است.

---

**🎉 موفق باشید! سیستم شما آماده استفاده است.**