#!/bin/bash
#
# MarketPlace Module Deployment Script
# 
# Run on server: bash deploy.sh
# Location: /var/www/dolibarr/htdocs/custom/
#

set -e  # Exit on error

echo "════════════════════════════════════════════════════════"
echo "📦 MarketPlace Module Deployment Script"
echo "════════════════════════════════════════════════════════"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Paths
CUSTOM_DIR="/var/www/dolibarr/htdocs/custom"
REPO_URL="https://github.com/anexys-ops/doliModMarketplace.git"
MODULE_DIR="$CUSTOM_DIR/marketplace_bdc"

echo -e "${YELLOW}Step 1: Cleanup${NC}"
echo "Removing old marketplace_bdc directory..."
if [ -d "$MODULE_DIR" ]; then
    rm -rf "$MODULE_DIR"
    echo -e "${GREEN}✓ Old directory removed${NC}"
else
    echo -e "${GREEN}✓ No existing directory${NC}"
fi

echo ""
echo -e "${YELLOW}Step 2: Clone Repository${NC}"
echo "Cloning from: $REPO_URL"
cd "$CUSTOM_DIR"
git clone "$REPO_URL" marketplace_bdc

if [ -d "$MODULE_DIR" ]; then
    echo -e "${GREEN}✓ Repository cloned successfully${NC}"
else
    echo -e "${RED}✗ Clone failed${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 3: Set Permissions${NC}"
echo "Setting permissions for www-data..."
cd "$MODULE_DIR"
chmod -R 755 .
chown -R www-data:www-data .
echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo -e "${YELLOW}Step 4: Verify Installation${NC}"
echo "Checking key files..."

files=(
    "core/modules/modMarketPlace_BDC.class.php"
    "marketplace/product_tab.php"
    "admin/setup.php"
    "class/marketplace.class.php"
    "class/marketplaceoffer.class.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ $file${NC}"
    else
        echo -e "${RED}✗ $file MISSING${NC}"
    fi
done

echo ""
echo -e "${YELLOW}Step 5: Directory Structure${NC}"
echo "Module directory contents:"
ls -la | head -15

echo ""
echo "════════════════════════════════════════════════════════"
echo -e "${GREEN}✅ DEPLOYMENT COMPLETED!${NC}"
echo "════════════════════════════════════════════════════════"
echo ""
echo "📋 NEXT STEPS:"
echo ""
echo "1. Activate module in Dolibarr:"
echo "   Admin → Modules & Applications"
echo "   Search: 'MarketPlace_BDC'"
echo "   Click: Activate"
echo ""
echo "2. Verify installation:"
echo "   Check Dolibarr logs: tail -50 /var/www/dolibarr/htdocs/dolibarr.log"
echo ""
echo "3. Test product tab:"
echo "   Go to: Products → Select a product"
echo "   Look for: 'Marketplaces' tab"
echo ""
echo "4. Test configuration:"
echo "   Go to: Menu → MarketPlace → Configuration"
echo "   Should see: Marketplace tiles/cards"
echo ""
echo "════════════════════════════════════════════════════════"
echo "✨ Module is ready! 🚀"
echo "════════════════════════════════════════════════════════"
