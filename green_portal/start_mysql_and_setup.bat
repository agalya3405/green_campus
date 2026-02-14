@echo off
echo ========================================
echo Green Campus Portal - MySQL Setup
echo ========================================
echo.

echo Step 1: Starting MySQL...
cd /d C:\xampp
start /B mysql\bin\mysqld.exe --console

echo Waiting for MySQL to start (10 seconds)...
timeout /t 10 /nobreak > nul

echo.
echo Step 2: Creating database...
mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS green_innovation;"

echo.
echo Step 3: Running database setup...
mysql\bin\mysql.exe -u root green_innovation < "%~dp0database\setup.sql"

echo.
echo Step 4: Checking database...
mysql\bin\mysql.exe -u root -e "USE green_innovation; SHOW TABLES;"

echo.
echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo The application should now be accessible at:
echo http://localhost:8080
echo.
pause
