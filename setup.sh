#!/bin/bash
set -e

echo "============================================"
echo "  Medical ERP - Automated Setup (Linux/Mac)"
echo "============================================"
echo ""

# Check PHP
if ! command -v php &> /dev/null; then
    echo "[ERROR] PHP is not installed."
    echo "  Ubuntu/Debian: sudo apt install php php-mbstring php-xml php-sqlite3 php-curl"
    echo "  macOS:         brew install php"
    exit 1
fi
echo "[OK] PHP found: $(php -r 'echo PHP_VERSION;')"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo "[ERROR] Composer is not installed."
    echo "  Install from: https://getcomposer.org/download/"
    exit 1
fi
echo "[OK] Composer found"

# Check Node.js
if ! command -v node &> /dev/null; then
    echo "[ERROR] Node.js is not installed."
    echo "  Install from: https://nodejs.org/"
    exit 1
fi
echo "[OK] Node.js found: $(node -v)"

echo ""
echo "============================================"
echo "  Setting up Backend..."
echo "============================================"
echo ""

cd medical-erp/backend

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --no-interaction
echo "[OK] PHP dependencies installed"

# Setup .env
if [ ! -f .env ]; then
    cp .env.example .env
    echo "[OK] .env file created"
else
    echo "[OK] .env file already exists"
fi

# Generate app key
php artisan key:generate --no-interaction
echo "[OK] App key generated"

# Create SQLite database
if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    echo "[OK] SQLite database file created"
else
    echo "[OK] SQLite database file already exists"
fi

# Run migrations and seed
echo "Running database migrations..."
php artisan migrate --seed --force || php artisan migrate:fresh --seed --force
echo "[OK] Database ready"

# Create storage link
php artisan storage:link 2>/dev/null || true
echo "[OK] Storage linked"

cd ../..

echo ""
echo "============================================"
echo "  Setting up Frontend..."
echo "============================================"
echo ""

cd medical-erp/frontend

# Install Node dependencies
echo "Installing frontend dependencies..."
npm install
echo "[OK] Frontend dependencies installed"

# Setup .env
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "VITE_API_URL=" > .env
    fi
    echo "[OK] Frontend .env file created"
fi

cd ../..

echo ""
echo "============================================"
echo "  Setup Complete!"
echo "============================================"
echo ""
echo "To start the application, run in TWO terminals:"
echo ""
echo "  Terminal 1 (Backend):"
echo "    cd medical-erp/backend && php artisan serve"
echo ""
echo "  Terminal 2 (Frontend):"
echo "    cd medical-erp/frontend && npm run dev"
echo ""
echo "  Then open: http://localhost:3000"
echo ""
echo "============================================"
echo "  Test Accounts:"
echo "============================================"
echo "  Super Admin : 1001 / 0512345001"
echo "  HR Manager  : 1002 / 0512345002"
echo "  Doctor      : 2001 / 0512345003"
echo "  Nurse       : 3001 / 0512345004"
echo "============================================"
