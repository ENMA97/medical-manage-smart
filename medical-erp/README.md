# 🏥 Medical ERP Smart | نظام تخطيط الموارد الطبية الذكي

<div dir="rtl">

## 📋 نظرة عامة

نظام ERP سحابي متكامل لإدارة المنشآت الطبية، يعتمد على الذكاء الاصطناعي في اتخاذ القرار مع فصل تام للصلاحيات وربط محاسبي دقيق.

</div>

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Laravel 11 (PHP 8.2+) |
| **Frontend** | React 18 + Vite + Tailwind CSS |
| **Database** | PostgreSQL 16 |
| **Cache/Queue** | Redis 7 |
| **Infrastructure** | Docker Compose |
| **Desktop** | NativePHP |
| **AI Integration** | Google Gemini API |

---

## 📁 Project Structure

```
medical-erp/
├── backend/                    # Laravel API
│   ├── app/
│   │   ├── Models/            # Eloquent Models
│   │   ├── Http/Controllers/  # API Controllers
│   │   ├── Services/          # Business Logic
│   │   └── Policies/          # Authorization
│   ├── database/
│   │   └── migrations/        # Database Schema
│   └── routes/
│       └── api.php            # API Routes
│
├── frontend/                   # React SPA
│   ├── src/
│   │   ├── components/        # Reusable UI
│   │   ├── pages/             # Route Pages
│   │   ├── hooks/             # Custom Hooks
│   │   ├── contexts/          # React Context
│   │   └── services/          # API Services
│   └── public/
│
├── docker/                     # Docker Configuration
│   ├── docker-compose.yml
│   ├── nginx/
│   └── biometric-agent/
│
└── docs/                       # Documentation
    └── Implementation_Plan_AR.docx
```

---

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Git
- Node.js 20+ (for local development)
- PHP 8.2+ (for local development)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-org/medical-erp.git
cd medical-erp

# 2. Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# 3. Start containers
cd docker
docker-compose up -d

# 4. Run migrations
docker-compose exec backend php artisan migrate --seed

# 5. Access the application
# Frontend: http://localhost:3000
# Backend API: http://localhost:8000/api
```

---

## 📦 Modules

<div dir="rtl">

### 1️⃣ وحدة الموارد البشرية (HR)
- إدارة الموظفين والعقود (دوام كامل، جزئي، تمهير، نسبة، لوكم)
- نظام العهد (Custody) مع منع إخلاء الطرف قبل التسليم
- الرواتب وفق معايير WPS

### 2️⃣ وحدة المستودعات (Smart Inventory)
- مراكز تكلفة دقيقة (ضماد نساء، ضماد رجال، طوارئ...)
- سياسة FEFO (ما ينتهي أولاً يُصرف أولاً)
- نظام الحصص اليومية (Quotas)
- الكراش كار مع محضر Blue Code

### 3️⃣ وحدة الجداول (Smart Rostering)
- أنماط دوام متعددة (Single, Split, On-Call)
- التحقق من تغطية مسؤول التعقيم
- كشف الثغرات (Gap Analysis)
- ربط أجهزة البصمة ZKTeco

### 4️⃣ وحدة المالية (Finance & BI)
- ABC Costing لتوزيع التكاليف
- حساب ربحية العيادات والخدمات
- إدارة التأمين (Claims, Scrubber, Aging)
- نظام Clawback لاسترداد العمولات

### 5️⃣ الذكاء الاصطناعي (Gemini AI)
- المحلل المالي: تقارير ذكية
- مساعد صياغة Medical Justification
- تحليل المخزون واقتراح العروض

</div>

---

## 🔐 Security Features

| Feature | Implementation |
|---------|----------------|
| **Authentication** | JWT / Laravel Sanctum |
| **Authorization** | Role-Based Access Control (RBAC) |
| **Audit Trail** | Immutable logs for all sensitive operations |
| **Concurrency** | Optimistic Locking for inventory |
| **Offline Support** | PWA + IndexedDB |

---

## 🧪 Testing

```bash
# Run backend tests
docker-compose exec backend php artisan test

# Run frontend tests
docker-compose exec frontend npm run test

# Coverage report
docker-compose exec backend php artisan test --coverage
```

**Target: ≥ 80% coverage for financial operations**

---

## 📖 API Documentation

API documentation is available via Swagger UI:

```
http://localhost:8000/api/documentation
```

---

## 🤝 Contributing

1. Create a feature branch: `git checkout -b feature/amazing-feature`
2. Commit changes: `git commit -m 'Add amazing feature'`
3. Push to branch: `git push origin feature/amazing-feature`
4. Open a Pull Request

---

## 📄 License

Proprietary - All rights reserved.

---

## 📞 Support

For technical support, please contact the development team.
