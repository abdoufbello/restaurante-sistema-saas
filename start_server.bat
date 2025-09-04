@echo off
echo ========================================
echo  CodeIgniter Development Server Starter
echo ========================================
echo.

REM Check if PHP is installed and accessible
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP and add it to your system PATH
    echo.
    echo You can download PHP from: https://www.php.net/downloads
    echo After installation, add PHP to your PATH environment variable
    pause
    exit /b 1
)

echo PHP is installed and accessible
php --version
echo.

REM Check if we're in the correct directory
if not exist "public\index.php" (
    echo ERROR: This script must be run from the CodeIgniter root directory
    echo Current directory: %CD%
    echo Expected to find: public\index.php
    pause
    exit /b 1
)

echo Starting PHP development server...
echo Server will be available at: http://localhost:8080
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

REM Change to public directory and start server
cd public
php -S localhost:8080

REM If we get here, the server has stopped
echo.
echo Server has stopped.
pause