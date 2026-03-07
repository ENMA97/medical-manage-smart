# دليل تثبيت وتشغيل نظام إدارة الموارد البشرية الطبي
# Medical ERP - Installation & Setup Guide

---

## معلومات المشروع

| البند | التفاصيل |
|-------|---------|
| **الواجهة الأمامية** | React 19 + Vite 7 + Tailwind CSS 4 |
| **الواجهة الخلفية** | Laravel 12 (PHP 8.2+) |
| **قاعدة البيانات** | PostgreSQL 16 (أو SQLite للتطوير) |
| **الكاش والطوابير** | Redis 7 |
| **المصادقة** | Laravel Sanctum (Bearer Tokens) |
| **الحاويات** | Docker Compose |

---

## الطريقة الأولى: التثبيت التلقائي بسكربت (الأسهل)

### المتطلبات

1. **Git** — [تحميل Git](https://git-scm.com/downloads)
2. **Node.js 20+** — [تحميل Node.js](https://nodejs.org/) (اختر LTS)
3. **PHP 8.2+** + **Composer** — عبر [Laragon](https://laragon.org/) (يثبّت الكل دفعة واحدة)

### خطوات التثبيت

```bash
git clone https://github.com/ENMA97/medical-manage-smart.git
cd medical-manage-smart
```

**Windows:**
```bash
setup-windows.bat
```

**Linux / macOS:**
```bash
chmod +x setup.sh
./setup.sh
```

السكربت يقوم تلقائياً بـ:
- تثبيت مكتبات PHP و Node.js
- إنشاء ملفات `.env`
- توليد مفتاح التطبيق
- إنشاء قاعدة بيانات SQLite
- تشغيل الهجرات وإدخال البيانات التجريبية

بعد الانتهاء، شغّل في **نافذتين CMD منفصلتين**:

```bash
# النافذة 1: الباكند
cd medical-erp/backend
php artisan serve

# النافذة 2: الفرونتند
cd medical-erp/frontend
npm run dev
```

ثم افتح: **http://localhost:3000**

---

## الطريقة الثانية: التشغيل بـ Docker

### المتطلبات

1. **Git** — [تحميل Git](https://git-scm.com/downloads)
2. **Docker Desktop** — [تحميل Docker Desktop](https://www.docker.com/products/docker-desktop/)

> بعد تثبيت Docker Desktop، تأكد أنه يعمل (أيقونة الحوت في شريط المهام)

### خطوات التثبيت

#### الخطوة 1: نسخ المشروع

```bash
git clone https://github.com/ENMA97/medical-manage-smart.git
cd medical-manage-smart
```

#### الخطوة 2: إعداد ملف البيئة للواجهة الخلفية

```bash
cd medical-erp/backend
copy .env.example .env
```

عدّل ملف `.env` وغيّر إعدادات قاعدة البيانات:

```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=medical_erp
DB_USERNAME=erp_user
DB_PASSWORD=secure_password

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PORT=6379
```

#### الخطوة 3: إعداد ملف البيئة للواجهة الأمامية

```bash
cd ../frontend
copy .env.example .env
```

محتوى `.env`:
```env
VITE_API_URL=
```

> اتركه فارغاً — الـ Proxy في Vite يحوّل الطلبات تلقائياً

#### الخطوة 4: تشغيل Docker

```bash
cd ../docker
docker-compose up -d
```

> الأمر يبني ويشغّل جميع الحاويات (قاعدة البيانات + Redis + Backend + Frontend)

#### الخطوة 5: تهيئة قاعدة البيانات

```bash
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate --seed
```

#### الخطوة 6: افتح المتصفح

| الخدمة | الرابط |
|--------|--------|
| **الواجهة الأمامية** | http://localhost:3000 |
| **API الخلفية** | http://localhost:8000/api |

### أوامر Docker المفيدة

```bash
# إيقاف جميع الحاويات
docker-compose down

# إعادة التشغيل
docker-compose up -d

# عرض سجلات الأخطاء
docker-compose logs -f backend
docker-compose logs -f frontend

# الدخول إلى حاوية الباكند
docker-compose exec backend bash

# إعادة بناء الحاويات (بعد تعديل Dockerfile)
docker-compose up -d --build
```

---

## الطريقة الثالثة: التشغيل المحلي يدوياً (بدون Docker)

### المتطلبات

1. **Git** — [تحميل Git](https://git-scm.com/downloads)
2. **Node.js 20+** — [تحميل Node.js](https://nodejs.org/) (اختر LTS)
3. **PHP 8.2+** — [تحميل PHP](https://windows.php.net/download/) أو عبر [XAMPP](https://www.apachefriends.org/) أو [Laragon](https://laragon.org/)
4. **Composer** — [تحميل Composer](https://getcomposer.org/download/)
5. **PostgreSQL 16** — [تحميل PostgreSQL](https://www.postgresql.org/download/) (اختياري — يمكن استخدام SQLite)

> **نصيحة:** استخدم **Laragon** لأنه يثبّت PHP + Composer + Node.js + PostgreSQL دفعة واحدة

---

### القسم أ: تثبيت الواجهة الأمامية (Frontend)

#### الخطوة 1: نسخ المشروع

```bash
git clone https://github.com/ENMA97/medical-manage-smart.git
cd medical-manage-smart
```

#### الخطوة 2: تثبيت مكتبات الواجهة الأمامية

```bash
cd medical-erp/frontend
npm install
```

#### الخطوة 3: إعداد ملف البيئة

```bash
copy .env.example .env
```

محتوى `.env`:
```env
VITE_API_URL=
```

#### الخطوة 4: تشغيل الواجهة الأمامية

```bash
npm run dev
```

> الواجهة تعمل الآن على: **http://localhost:3000**

---

### القسم ب: تثبيت الواجهة الخلفية (Backend)

> افتح **نافذة CMD/Terminal جديدة** (لا تغلق نافذة الـ Frontend)

#### الخطوة 1: الدخول لمجلد الباكند

```bash
cd medical-manage-smart/medical-erp/backend
```

#### الخطوة 2: تثبيت مكتبات PHP

```bash
composer install
```

#### الخطوة 3: إعداد ملف البيئة

```bash
copy .env.example .env
```

#### الخطوة 4: توليد مفتاح التطبيق

```bash
php artisan key:generate
```

#### الخطوة 5: إعداد قاعدة البيانات

**الخيار أ: استخدام SQLite (الأسهل — بدون تثبيت شيء إضافي)**

عدّل `.env`:
```env
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

> **مهم:** عند استخدام SQLite، يجب تغيير `SESSION_DRIVER` و `CACHE_STORE` إلى `file` و `QUEUE_CONNECTION` إلى `sync`

أنشئ ملف قاعدة البيانات:
```bash
# في Windows CMD:
type nul > database\database.sqlite

# في PowerShell:
New-Item database/database.sqlite -ItemType File
```

**الخيار ب: استخدام PostgreSQL**

1. ثبّت PostgreSQL من الرابط أعلاه
2. أنشئ قاعدة بيانات جديدة:

```sql
-- افتح pgAdmin أو psql واكتب:
CREATE DATABASE medical_erp;
CREATE USER erp_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE medical_erp TO erp_user;
```

3. عدّل `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=medical_erp
DB_USERNAME=erp_user
DB_PASSWORD=secure_password
```

#### الخطوة 6: تشغيل الهجرات (إنشاء الجداول)

```bash
php artisan migrate --seed
```

#### الخطوة 7: تشغيل السيرفر

```bash
php artisan serve
```

> API يعمل الآن على: **http://localhost:8000**

---

## التأكد من أن كل شيء يعمل

### اختبار الواجهة الأمامية
افتح المتصفح:
```
http://localhost:3000
```
يجب أن تظهر صفحة تسجيل الدخول.

### اختبار API
افتح المتصفح:
```
http://localhost:8000/api
```
يجب أن ترجع استجابة JSON.

### حسابات الاختبار

بعد تشغيل `php artisan migrate --seed`، تتوفر الحسابات التالية:

| الدور | الرقم الوظيفي | رقم الهاتف |
|-------|--------------|------------|
| **مدير عام** (Super Admin) | 1001 | 0512345001 |
| **مدير موارد بشرية** (HR Manager) | 1002 | 0512345002 |
| **طبيب** (Doctor) | 2001 | 0512345003 |
| **ممرض** (Nurse) | 3001 | 0512345004 |

> سجّل الدخول باستخدام **الرقم الوظيفي** و **رقم الهاتف** فقط (بدون كلمة مرور)

---

## هيكل المشروع

```
medical-manage-smart/
├── medical-erp/
│   ├── frontend/              ← واجهة React (المنفذ 3000)
│   │   ├── src/
│   │   │   ├── pages/         ← صفحات التطبيق
│   │   │   ├── components/    ← المكونات المشتركة
│   │   │   ├── contexts/      ← إدارة الحالة (Auth)
│   │   │   ├── services/      ← خدمات API
│   │   │   ├── layouts/       ← التخطيطات
│   │   │   └── assets/        ← الصور والأيقونات
│   │   ├── package.json
│   │   └── vite.config.js
│   │
│   ├── backend/               ← خادم Laravel (المنفذ 8000)
│   │   ├── app/
│   │   │   ├── Models/        ← نماذج قاعدة البيانات
│   │   │   ├── Http/Controllers/Api/  ← وحدات التحكم
│   │   │   ├── Services/      ← منطق الأعمال
│   │   │   └── Imports/       ← استيراد Excel
│   │   ├── routes/api.php     ← مسارات API
│   │   ├── database/migrations/ ← هجرات قاعدة البيانات
│   │   ├── composer.json
│   │   └── .env
│   │
│   └── docker/                ← إعداد Docker
│       └── docker-compose.yml
│
└── database/
    └── migrations/            ← ملفات هجرة قاعدة البيانات (8 ملفات)
```

---

## جداول قاعدة البيانات الرئيسية

| المجموعة | الجداول |
|----------|---------|
| **الأساسيات** | users, departments, positions |
| **الموظفين** | employees, employee_profiles |
| **العقود** | contracts, custody_management, resignations |
| **الرواتب** | payroll_records, deductions, allowances, end_of_service |
| **الإجازات** | leave_requests, leave_balances, leave_types |
| **التحليلات** | dashboards, reports, analytics |
| **الذكاء الاصطناعي** | ai_suggestions |
| **النظام** | audit_logs, system_settings |

---

## حل المشاكل الشائعة

### المشكلة: npm install فشل
```bash
# امسح الكاش وأعد المحاولة
npm cache clean --force
rm -rf node_modules
npm install
```

### المشكلة: composer install فشل
```bash
# تأكد أن PHP مثبت
php -v

# تأكد أن Composer مثبت
composer -V

# أعد المحاولة
composer install --no-interaction
```

### المشكلة: خطأ في الهجرات
```bash
# أعد تشغيل الهجرات من الصفر
php artisan migrate:fresh --seed
```

### المشكلة: المنفذ 3000 أو 8000 مستخدم
```bash
# في Windows — اعرف من يستخدم المنفذ
netstat -ano | findstr :3000
netstat -ano | findstr :8000

# أوقف العملية (استبدل PID بالرقم الظاهر)
taskkill /PID <رقم_العملية> /F
```

### المشكلة: CORS Error (خطأ في الطلبات بين السيرفرات)

تأكد من أن `.env` في الباكند يحتوي:
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000
```

---

## أوامر مفيدة للتطوير

### الواجهة الأمامية (Frontend)
```bash
npm run dev        # تشغيل بيئة التطوير
npm run build      # بناء نسخة الإنتاج
npm run preview    # معاينة نسخة الإنتاج
npm run lint       # فحص جودة الكود
```

### الواجهة الخلفية (Backend)
```bash
php artisan serve              # تشغيل السيرفر
php artisan migrate            # تشغيل الهجرات الجديدة
php artisan migrate:fresh      # إعادة بناء قاعدة البيانات
php artisan db:seed            # إدخال بيانات تجريبية
php artisan route:list         # عرض جميع المسارات
php artisan make:model Name -m # إنشاء نموذج مع هجرة
php artisan queue:work         # تشغيل الطوابير
php artisan test               # تشغيل الاختبارات
```

---

## ملاحظات إضافية

- **التطبيق يدعم PWA** — يمكن تثبيته كتطبيق على الجوال من المتصفح
- **الاتجاه RTL** — التطبيق مصمم للغة العربية (من اليمين لليسار)
- **Gemini AI** — لتفعيل اقتراحات الذكاء الاصطناعي، أضف مفتاح API في `.env`:
  ```env
  GEMINI_API_KEY=your_api_key_here
  ```
- **البصمة (ZKTeco)** — لتفعيل جهاز البصمة، شغّل Docker مع:
  ```bash
  docker-compose --profile with-biometric up -d
  ```

---

> **آخر تحديث:** مارس 2026
