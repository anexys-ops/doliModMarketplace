@echo off
REM MarketPlace Module Deployment Script (Windows/Putty)
REM
REM Instructions:
REM 1. Open PuTTY and connect to: dlbp150r58.edicloud.app:150 (root)
REM 2. Copy-paste these commands one by one:
REM

echo.
echo ========================================================
echo  MarketPlace Module Deployment (Manual Steps)
echo ========================================================
echo.

REM Step 1: Go to custom directory
cd /var/www/dolibarr/htdocs/custom

REM Step 2: Remove old directory
echo [Step 1] Cleaning up old installation...
rm -rf marketplace_bdc

REM Step 3: Clone repository
echo [Step 2] Cloning repository from GitHub...
git clone https://github.com/anexys-ops/doliModMarketplace.git marketplace_bdc

REM Step 4: Go to module directory
cd marketplace_bdc

REM Step 5: Set permissions
echo [Step 3] Setting permissions...
chmod -R 755 .
chown -R www-data:www-data .

REM Step 6: Verify
echo [Step 4] Verifying installation...
ls -la

echo.
echo ========================================================
echo  Deployment completed!
echo ========================================================
echo.
echo Next steps:
echo 1. Go to Dolibarr Admin > Modules
echo 2. Search for "MarketPlace_BDC"
echo 3. Click "Activate"
echo.
echo Done!
echo ========================================================
