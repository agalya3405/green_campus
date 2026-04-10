@echo off
title Campus Green Innovation Portal
echo Starting PHP server...
echo.
echo Open in browser: http://localhost:8080
echo Press Ctrl+C to stop the server.
echo.

REM Try XAMPP PHP first
if exist "C:\xampp\php\php.exe" (
    cd /d "%~dp0"
    "C:\xampp\php\php.exe" -S localhost:8080
    goto :eof
)

REM Try PHP in PATH
php -S localhost:8080 2>nul
if %errorlevel% equ 0 goto :eof

echo PHP not found. Please either:
echo   1. Install XAMPP from https://www.apachefriends.org/ and run this again
echo   2. Or add PHP to your system PATH
pause
