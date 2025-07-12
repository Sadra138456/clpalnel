# 🐾 پنل مدیریت کلینیک دامپزشکی پارسیان - Backend

FastAPI backend برای سیستم مدیریت رزرواسیون و ارسال پیامک کلینیک دامپزشکی

## ✨ ویژگی‌ها

- 🔐 **احراز هویت کامل**: JWT + OAuth Google
- 📅 **مدیریت رزروها**: CRUD کامل با فیلتر و جستجو
- 📱 **سیستم پیامک**: ارسال خودکار یادآور + آرشیو
- 👥 **مدیریت کاربران**: نقش‌های مختلف و مجوزها
- 📊 **آمار و گزارش**: Dashboard آماری کامل
- 🏗️ **معماری مدرن**: FastAPI + PostgreSQL + Redis
- 📚 **مستندات خودکار**: Swagger/OpenAPI
- 🐳 **Docker Ready**: آماده برای production

## 🛠️ تکنولوژی‌ها

- **FastAPI** 0.104+ - Modern Python web framework
- **SQLAlchemy** 2.0+ - ORM قدرتمند
- **PostgreSQL** 15+ - Database اصلی
- **Redis** 7+ - Cache و session management
- **Pydantic** 2.0+ - Data validation
- **JWT** - Authentication
- **Alembic** - Database migrations
- **Kavenegar** - SMS provider ایرانی

## 📦 نصب و راه‌اندازی

### 🚀 روش سریع با Docker

```bash
# کلون کردن پروژه
git clone https://github.com/your-repo/parsian-clinic.git
cd parsian-clinic

# تنظیم متغیرهای محیطی
cp backend/.env.example backend/.env
# ویرایش فایل .env و تنظیم مقادیر

# اجرای سیستم کامل
docker-compose up -d

# مشاهده logs
docker-compose logs -f backend
```

### 💻 روش دستی (Development)

```bash
# ایجاد virtual environment
python -m venv venv
source venv/bin/activate  # Linux/Mac
# venv\Scripts\activate   # Windows

# نصب dependencies
cd backend
pip install -r requirements.txt

# تنظیم database
# ابتدا PostgreSQL و Redis را راه‌اندازی کنید

# اجرای migrations
alembic upgrade head

# اجرای server
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
```

## ⚙️ تنظیمات

### 📧 SMS Configuration (کاوه‌نگار)

```bash
# در فایل .env
SMS_API_KEY=your-kavenegar-api-key
SMS_SENDER=10004346
```

### 🗄️ Database Configuration

```bash
# PostgreSQL
POSTGRES_SERVER=localhost
POSTGRES_USER=postgres
POSTGRES_PASSWORD=your-password
POSTGRES_DB=parsian_clinic

# Redis
REDIS_URL=redis://localhost:6379
```

### 🔐 Security

```bash
# تولید SECRET_KEY قوی
SECRET_KEY=your-super-secret-key-minimum-32-characters-long
ACCESS_TOKEN_EXPIRE_MINUTES=10080  # 7 days
```

## 📋 API Documentation

بعد از اجرای سرور، مستندات API در آدرس‌های زیر در دسترس است:

- **Swagger UI**: http://localhost:8000/docs
- **ReDoc**: http://localhost:8000/redoc

### 🔗 API Endpoints اصلی

#### Authentication
```
POST /api/v1/auth/login          # ورود با ایمیل/پسورد
POST /api/v1/auth/register       # ثبت نام کاربر جدید
POST /api/v1/auth/google         # ورود با Google OAuth
GET  /api/v1/auth/me             # اطلاعات کاربر فعلی
```

#### Reservations
```
GET    /api/v1/reservations      # لیست رزروها + فیلتر
POST   /api/v1/reservations      # ایجاد رزرو جدید
PUT    /api/v1/reservations/{id} # ویرایش رزرو
DELETE /api/v1/reservations/{id} # حذف رزرو
GET    /api/v1/reservations/statistics/overview  # آمار
```

#### SMS
```
POST /api/v1/sms/send            # ارسال پیامک تکی
POST /api/v1/sms/send-bulk       # ارسال پیامک گروهی
GET  /api/v1/sms/archive         # آرشیو پیامک‌ها
GET  /api/v1/sms/statistics      # آمار پیامک
GET  /api/v1/sms/templates       # قالب‌های پیامک
```

#### Users
```
GET  /api/v1/users/me            # پروفایل کاربر
PUT  /api/v1/users/me            # ویرایش پروفایل
POST /api/v1/users/me/upload-avatar  # آپلود آواتار
```

## 🗄️ Database Schema

### 📊 جداول اصلی

```sql
-- کاربران
users (
    id, email, full_name, hashed_password,
    is_active, is_superuser, profile_picture,
    google_id, oauth_provider, created_at, updated_at
)

-- رزروها
reservations (
    id, pet_name, breed, weight, birth_year,
    owner_name, phone_number, visit_date, next_visit_date,
    vaccine_type, price, notes, is_active, reminder_sent,
    created_by, created_at, updated_at
)

-- آرشیو پیامک
sms_archive (
    id, message, recipient_phone, recipient_name,
    sms_type, status, provider_message_id,
    provider_status, cost, reservation_id, sent_by,
    sent_at, delivered_at
)
```

## 🔧 Development

### 🧪 تست‌ها

```bash
# اجرای تست‌ها
pytest

# تست با coverage
pytest --cov=app --cov-report=html
```

### 📦 Database Migrations

```bash
# ایجاد migration جدید
alembic revision --autogenerate -m "توضیح تغییرات"

# اعمال migrations
alembic upgrade head

# برگشت migration
alembic downgrade -1
```

### 🔍 Code Quality

```bash
# Format کردن کد
black app/
isort app/

# بررسی کیفیت کد
flake8 app/
mypy app/
```

## 🚀 Production Deployment

### 🐳 Docker Production

```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  backend:
    build: 
      context: ./backend
      target: production
    environment:
      - ENVIRONMENT=production
    deploy:
      replicas: 3
      resources:
        limits:
          memory: 512M
        reservations:
          memory: 256M
```

### 🔒 Security Checklist

- [ ] SECRET_KEY قوی و یکتا
- [ ] SSL/HTTPS فعال
- [ ] Database credentials امن
- [ ] CORS صحیح تنظیم شده
- [ ] Rate limiting فعال
- [ ] Input validation کامل
- [ ] Error handling امن

### 📊 Monitoring

```bash
# Health check
curl http://localhost:8000/health

# Metrics
GET /api/v1/reservations/statistics/overview
GET /api/v1/sms/statistics
```

## 🐛 Troubleshooting

### مشکلات رایج

1. **Database Connection Error**
   ```bash
   # بررسی connection string در .env
   # اطمینان از اجرای PostgreSQL
   docker-compose logs db
   ```

2. **SMS Sending Failed**
   ```bash
   # بررسی API key کاوه‌نگار
   # بررسی credit حساب
   GET /api/v1/sms/test
   ```

3. **Authentication Issues**
   ```bash
   # بررسی SECRET_KEY
   # چک کردن token expiry
   POST /api/v1/auth/test-token
   ```

## 📞 پشتیبانی

- **توسعه‌دهنده**: صدرا
- **تلفن**: 09307398501
- **GitHub Issues**: [لینک مخزن]

## 📄 License

این پروژه برای استفاده شخصی کلینیک پارسیان توسعه داده شده است.

---

**ساخته شده با ❤️ برای کلینیک دامپزشکی پارسیان**