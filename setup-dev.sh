#!/bin/bash
set -e

echo "============================================"
echo "  Smart Medical ERP - Local Dev Setup"
echo "============================================"
echo ""

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
ERP_DIR="$PROJECT_ROOT/medical-erp"

# ---- Backend Setup ----
echo "[1/6] Creating Laravel backend..."
if [ ! -d "$PROJECT_ROOT/backend-app/vendor" ]; then
    composer create-project laravel/laravel "$PROJECT_ROOT/backend-app" --prefer-dist --quiet
fi

BACKEND="$PROJECT_ROOT/backend-app"

echo "[2/6] Copying backend files..."
# Models
cp "$ERP_DIR/backend/app/Models/Region.php" "$BACKEND/app/Models/"
cp "$ERP_DIR/backend/app/Models/County.php" "$BACKEND/app/Models/"
cp "$ERP_DIR/backend/app/Models/Employee.php" "$BACKEND/app/Models/"

# Controllers
mkdir -p "$BACKEND/app/Http/Controllers/Api"
cp "$ERP_DIR/backend/app/Http/Controllers/Api/"*.php "$BACKEND/app/Http/Controllers/Api/"

# Requests & Resources
mkdir -p "$BACKEND/app/Http/Requests" "$BACKEND/app/Http/Resources"
cp "$ERP_DIR/backend/app/Http/Requests/"*.php "$BACKEND/app/Http/Requests/"
cp "$ERP_DIR/backend/app/Http/Resources/"*.php "$BACKEND/app/Http/Resources/"

# Migrations & Seeders
cp "$ERP_DIR/backend/database/migrations/2026_01_24_000008_create_regions_and_counties_tables.php" "$BACKEND/database/migrations/"
cp "$ERP_DIR/backend/database/seeders/RegionsAndCountiesSeeder.php" "$BACKEND/database/seeders/"

# Install API support if not already
if [ ! -f "$BACKEND/routes/api.php" ]; then
    cd "$BACKEND" && php artisan install:api --no-interaction
    cd "$PROJECT_ROOT"
fi

# Write API routes
cat > "$BACKEND/routes/api.php" << 'ROUTES'
<?php

use App\Http\Controllers\Api\CountyController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\RegionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('regions', RegionController::class);
Route::apiResource('counties', CountyController::class);
Route::apiResource('employees', EmployeeController::class);
ROUTES

echo "[3/6] Running migrations and seeding..."
cd "$BACKEND"
touch database/database.sqlite
php artisan migrate --force 2>/dev/null || true
php artisan db:seed --class=RegionsAndCountiesSeeder --force 2>/dev/null || true

# ---- Frontend Setup ----
echo "[4/6] Creating React frontend..."
cd "$PROJECT_ROOT"
if [ ! -d "$PROJECT_ROOT/frontend-app/node_modules" ]; then
    npm create vite@latest frontend-app -- --template react 2>/dev/null
    cd frontend-app
    npm install
    npm install @tanstack/react-query axios react-router-dom react-hot-toast
    npm install -D tailwindcss @tailwindcss/vite
else
    cd frontend-app
fi

FRONTEND="$PROJECT_ROOT/frontend-app"

echo "[5/6] Copying frontend files..."
mkdir -p "$FRONTEND/src/services" "$FRONTEND/src/hooks" "$FRONTEND/src/components/common" "$FRONTEND/src/pages/hr" "$FRONTEND/src/layouts"

cp "$ERP_DIR/frontend/src/services/api.js" "$FRONTEND/src/services/"
cp "$ERP_DIR/frontend/src/services/locationService.js" "$FRONTEND/src/services/"
cp "$ERP_DIR/frontend/src/hooks/useLocations.js" "$FRONTEND/src/hooks/"
cp "$ERP_DIR/frontend/src/components/common/RegionCountySelect.jsx" "$FRONTEND/src/components/common/"
cp "$ERP_DIR/frontend/src/pages/hr/EmployeeForm.jsx" "$FRONTEND/src/pages/hr/"
cp "$ERP_DIR/frontend/src/pages/hr/EmployeeList.jsx" "$FRONTEND/src/pages/hr/"
cp "$ERP_DIR/frontend/src/layouts/MainLayout.jsx" "$FRONTEND/src/layouts/"

# Vite config with proxy and tailwind
cat > "$FRONTEND/vite.config.js" << 'VITECONF'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    port: 5173,
    proxy: {
      '/api': 'http://localhost:8000',
    },
  },
})
VITECONF

# Tailwind CSS
echo '@import "tailwindcss";' > "$FRONTEND/src/index.css"

# App.jsx - working version with implemented pages only
cat > "$FRONTEND/src/App.jsx" << 'APPJSX'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import MainLayout from './layouts/MainLayout';
import EmployeeList from './pages/hr/EmployeeList';
import EmployeeForm from './pages/hr/EmployeeForm';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { staleTime: 5 * 60 * 1000, retry: 1 },
  },
});

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <Toaster position="top-left" reverseOrder={false} />
        <Routes>
          <Route element={<MainLayout />}>
            <Route path="/" element={<Navigate to="/hr/employees" replace />} />
            <Route path="/hr/employees" element={<EmployeeList />} />
            <Route path="/hr/employees/new" element={<EmployeeForm />} />
            <Route path="/hr/employees/:id" element={<EmployeeForm />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </QueryClientProvider>
  );
}

export default App;
APPJSX

echo "[6/6] Setup complete!"
echo ""
echo "============================================"
echo "  To start the app, run these in 2 terminals:"
echo ""
echo "  Terminal 1 (Backend):"
echo "    cd backend-app && php artisan serve"
echo ""
echo "  Terminal 2 (Frontend):"
echo "    cd frontend-app && npm run dev"
echo ""
echo "  Then open: http://localhost:5173"
echo "============================================"
