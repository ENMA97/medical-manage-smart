# خطة تفعيل نظام الموارد البشرية - HRMS Activation Plan

## المرحلة 1: إعداد البيئة (Environment Setup)

### 1.1 متطلبات الخادم (Server Requirements)
- PHP >= 8.2 with extensions: `intl`, `mbstring`, `pdo_mysql`, `openssl`, `fileinfo`, `gd`
- MySQL 8.0+ or MariaDB 10.6+
- Node.js >= 18 LTS
- Composer >= 2.5
- Nginx or Apache with mod_rewrite
- Redis (optional, for caching/queues)
- SSL Certificate (required for production)

### 1.2 إعداد Backend
```bash
cd medical-erp/backend

# نسخ ملف البيئة
cp .env.example .env

# تعديل الإعدادات الأساسية في .env:
# - DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - APP_URL (رابط الموقع)
# - APP_ENV=production
# - APP_DEBUG=false
# - SANCTUM_STATEFUL_DOMAINS (رابط الفرونت إند)

# تثبيت المكتبات
composer install --no-dev --optimize-autoloader

# توليد مفتاح التطبيق
php artisan key:generate

# تشغيل قاعدة البيانات
php artisan migrate --force

# تشغيل البيانات الأولية
php artisan db:seed --class=FoundationSeeder
php artisan db:seed --class=ViolationTypesSeeder

# تحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 1.3 إعداد Frontend
```bash
cd medical-erp/frontend

# نسخ ملف البيئة
cp .env.example .env

# تعديل VITE_API_URL ليشير للباك إند
# VITE_API_URL=https://api.yourdomain.com

# تثبيت المكتبات والبناء
npm ci
npm run build
```

### 1.4 إعداد Nginx
```nginx
# Backend API
server {
    listen 443 ssl;
    server_name api.yourdomain.com;
    root /var/www/medical-erp/backend/public;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}

# Frontend SPA
server {
    listen 443 ssl;
    server_name hr.yourdomain.com;
    root /var/www/medical-erp/frontend/dist;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

---

## المرحلة 2: البيانات الأولية (Initial Data Setup)

### 2.1 إنشاء المستخدم المدير (Super Admin)
يتم إنشاؤه تلقائياً من FoundationSeeder:
- **رقم الموظف:** `ADMIN001`
- **كلمة المرور:** `ADMIN001` (يجب تغييرها فوراً)

### 2.2 إعداد الأقسام والمسميات الوظيفية
1. تسجيل الدخول كمدير
2. الذهاب إلى **الأقسام** → إضافة جميع الأقسام
3. الذهاب إلى **المسميات الوظيفية** → إضافة المسميات لكل قسم

### 2.3 إعداد أنواع الإجازات
- الإجازات الأساسية تم إنشاؤها من الـ Seeder (سنوية، مرضية، طارئة)
- يمكن إضافة أنواع إضافية من **إعدادات الإجازات**

### 2.4 إعداد قوالب الخطابات
- إنشاء قوالب: تعريف بالراتب، شهادة خبرة، إخلاء طرف
- استخدام المتغيرات: `{employee_name}`, `{employee_number}`, `{department}`, `{hijri_date}`

### 2.5 استيراد بيانات الموظفين
```
1. تحميل قالب الاستيراد من: الإعدادات → استيراد → تحميل القالب
2. تعبئة البيانات في ملف Excel
3. رفع الملف عبر صفحة الاستيراد
```

---

## المرحلة 3: الاختبار (Testing & QA)

### 3.1 اختبارات آلية
```bash
cd medical-erp/backend

# تشغيل جميع الاختبارات
php artisan test

# تشغيل اختبار محدد
php artisan test --filter=AuthTest
php artisan test --filter=EmployeeTest
php artisan test --filter=RbacTest
```

### 3.2 اختبار يدوي - قائمة التحقق

#### المصادقة (Authentication)
- [ ] تسجيل دخول برقم الموظف + رقم الجوال
- [ ] تسجيل خروج
- [ ] حماية الصفحات (redirect لصفحة الدخول)

#### إدارة الموظفين
- [ ] إضافة موظف جديد
- [ ] تعديل بيانات موظف
- [ ] البحث عن موظف
- [ ] عرض تفاصيل موظف
- [ ] استيراد موظفين من Excel
- [ ] عرض مستندات الموظف

#### العقود
- [ ] إنشاء عقد جديد
- [ ] تجديد عقد
- [ ] تنبيهات العقود المنتهية

#### الإجازات
- [ ] تقديم طلب إجازة (موظف)
- [ ] اعتماد/رفض طلب إجازة (مدير)
- [ ] عرض رصيد الإجازات
- [ ] إلغاء طلب إجازة

#### الرواتب
- [ ] إنشاء مسير رواتب شهري
- [ ] اعتماد المسير
- [ ] تصدير المسير إلى Excel

#### القروض
- [ ] تقديم طلب قرض
- [ ] اعتماد/رفض القرض
- [ ] متابعة الأقساط

#### العهد
- [ ] تسليم عهدة
- [ ] استلام عهدة (إرجاع)

#### الخطابات
- [ ] إنشاء خطاب من قالب
- [ ] اعتماد خطاب
- [ ] التحقق من التاريخ الهجري

#### المخالفات
- [ ] تسجيل مخالفة
- [ ] تشكيل لجنة تحقيق
- [ ] إصدار قرار تأديبي

#### الاستقالات
- [ ] تقديم استقالة
- [ ] اعتماد/رفض استقالة

#### لوحة التحكم
- [ ] عرض الإحصائيات
- [ ] عرض التنبيهات
- [ ] عرض الموظفين في إجازة

#### صلاحيات الأدوار (RBAC)
- [ ] Super Admin: وصول كامل
- [ ] HR Manager: إدارة موظفين + عقود + رواتب
- [ ] Employee: طلبات إجازة + استقالة + عرض بياناتي فقط

---

## المرحلة 4: التفعيل المباشر (Go-Live)

### 4.1 خطوات ما قبل التفعيل
1. ✅ اكتمال جميع الاختبارات
2. تأمين الخادم (Firewall, SSH keys, fail2ban)
3. إعداد النسخ الاحتياطي التلقائي (Database + Storage)
4. إعداد مراقبة الأداء (Monitoring)
5. تدريب المستخدمين

### 4.2 خطة التفعيل التدريجي
| الأسبوع | المرحلة | المستخدمون |
|---------|---------|-----------|
| 1 | تجريبي | فريق IT + مدير HR |
| 2 | محدود | قسم HR كامل |
| 3 | موسع | مدراء الأقسام |
| 4 | كامل | جميع الموظفين |

### 4.3 خطة الطوارئ (Rollback Plan)
```bash
# استعادة قاعدة البيانات من النسخة الاحتياطية
mysql -u root -p hrms_db < /backups/hrms_backup_YYYYMMDD.sql

# العودة للإصدار السابق
cd /var/www/medical-erp
git checkout previous-release-tag
composer install --no-dev
php artisan migrate
php artisan config:cache
```

---

## المرحلة 5: ما بعد التفعيل (Post Go-Live)

### 5.1 مهام الصيانة الدورية
```bash
# نسخ احتياطي يومي
mysqldump -u root -p hrms_db > /backups/hrms_$(date +%Y%m%d).sql

# تنظيف الكاش أسبوعياً
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# مراجعة سجل التدقيق
# من لوحة التحكم → الإعدادات → سجل التدقيق
```

### 5.2 مؤشرات الأداء (KPIs)
- زمن استجابة API < 500ms
- وقت التشغيل (Uptime) > 99.5%
- عدد الأخطاء اليومية < 5
- رضا المستخدمين > 80%

### 5.3 تحسينات مستقبلية
- [ ] تكامل مع نظام حضور وانصراف (Biometric)
- [ ] تطبيق جوال (Mobile App)
- [ ] تقارير BI متقدمة
- [ ] تكامل مع نظام مُدد (GOSI)
- [ ] إشعارات SMS/WhatsApp
- [ ] نظام تقييم أداء الموظفين
- [ ] بوابة الخدمة الذاتية للموظف
