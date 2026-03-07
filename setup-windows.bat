@echo off
chcp 65001 >nul 2>&1
title Medical ERP - Setup Script

echo ============================================
echo   Medical ERP - Automated Setup (Windows)
echo ============================================
echo.

:: Check PHP
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH.
    echo Please install Laragon from: https://laragon.org/download/
    echo After installing, restart CMD and run this script again.
    pause
    exit /b 1
)
echo [OK] PHP found

:: Check Composer
composer -V >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Composer is not installed or not in PATH.
    echo Please install Composer from: https://getcomposer.org/download/
    pause
    exit /b 1
)
echo [OK] Composer found

:: Check Node.js
node -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Node.js is not installed or not in PATH.
    echo Please install Node.js LTS from: https://nodejs.org/
    pause
    exit /b 1
)
echo [OK] Node.js found

echo.
echo ============================================
echo   Setting up Backend...
echo ============================================
echo.

cd medical-erp\backend

:: Install PHP dependencies
echo Installing PHP dependencies...
call composer install --no-interaction
if %errorlevel% neq 0 (
    echo [ERROR] composer install failed
    pause
    exit /b 1
)
echo [OK] PHP dependencies installed

:: Setup .env
if not exist .env (
    copy .env.example .env
    echo [OK] .env file created
) else (
    echo [OK] .env file already exists
)

:: Generate app key
php artisan key:generate --no-interaction
echo [OK] App key generated

:: Create SQLite database
if not exist database\database.sqlite (
    type nul > database\database.sqlite
    echo [OK] SQLite database file created
) else (
    echo [OK] SQLite database file already exists
)

:: Run migrations and seed
echo Running database migrations...
php artisan migrate --seed --force
if %errorlevel% neq 0 (
    echo [WARNING] Migration failed. Trying fresh migration...
    php artisan migrate:fresh --seed --force
)
echo [OK] Database ready

:: Create storage link
php artisan storage:link >nul 2>&1
echo [OK] Storage linked

cd ..\..

echo.
echo ============================================
echo   Setting up Frontend...
echo ============================================
echo.

cd medical-erp\frontend

:: Install Node dependencies
echo Installing frontend dependencies...
call npm install
if %errorlevel% neq 0 (
    echo [ERROR] npm install failed
    pause
    exit /b 1
)
echo [OK] Frontend dependencies installed

:: Setup .env
if not exist .env (
    if exist .env.example (
        copy .env.example .env
    ) else (
        echo VITE_API_URL=> .env
    )
    echo [OK] Frontend .env file created
)

cd ..\..

echo.
echo ============================================
echo   Setup Complete!
echo ============================================
echo.
echo To start the application, run these commands
echo in TWO SEPARATE CMD windows:
echo.
echo   Window 1 (Backend):
echo     cd medical-erp\backend
echo     php artisan serve
echo.
echo   Window 2 (Frontend):
echo     cd medical-erp\frontend
echo     npm run dev
echo.
echo   Then open: http://localhost:3000
echo.
echo ============================================
echo   Test Accounts:
echo ============================================
echo   Super Admin : 1001 / 0512345001
echo   HR Manager  : 1002 / 0512345002
echo   Doctor      : 2001 / 0512345003
echo   Nurse       : 3001 / 0512345004
echo ============================================
echo.
pause
