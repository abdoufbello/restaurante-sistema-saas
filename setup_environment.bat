@echo off
echo ========================================
echo  CodeIgniter Environment Setup
echo ========================================
echo.

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo WARNING: PHP is not installed or not in PATH
    echo.
    echo To install PHP:
    echo 1. Download PHP from: https://www.php.net/downloads
    echo 2. Extract to C:\PHP (or another location)
    echo 3. Add PHP directory to your system PATH
    echo 4. Run this script again
    echo.
    pause
    exit /b 1
)

echo ✓ PHP is installed
php --version
echo.

REM Check required directories
if not exist "writable" (
    echo Creating writable directory...
    mkdir writable
)

if not exist "writable\data" (
    echo Creating data directory...
    mkdir writable\data
)

if not exist "writable\logs" (
    echo Creating logs directory...
    mkdir writable\logs
)

if not exist "writable\cache" (
    echo Creating cache directory...
    mkdir writable\cache
)

echo ✓ Directory structure verified
echo.

REM Check if data files exist
set DATA_FILES_MISSING=0

if not exist "writable\data\restaurants.json" (
    echo WARNING: restaurants.json not found
    set DATA_FILES_MISSING=1
)

if not exist "writable\data\employees.json" (
    echo WARNING: employees.json not found
    set DATA_FILES_MISSING=1
)

if not exist "writable\data\categories.json" (
    echo WARNING: categories.json not found
    set DATA_FILES_MISSING=1
)

if not exist "writable\data\dishes.json" (
    echo WARNING: dishes.json not found
    set DATA_FILES_MISSING=1
)

if not exist "writable\data\orders.json" (
    echo WARNING: orders.json not found
    set DATA_FILES_MISSING=1
)

if %DATA_FILES_MISSING%==1 (
    echo.
    echo Some data files are missing. The system may not work properly.
    echo Please ensure all JSON data files are present in writable\data\
    echo.
)

echo ✓ Environment setup complete
echo.
echo You can now run start_server.bat to start the development server
echo.
pause