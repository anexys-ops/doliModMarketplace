#!/bin/bash

# Quick deployment script for MarketPlace module
# Usage: ./deploy_quick.sh [file_or_all]

SERVER="root@dlbp150r58.edicloud.app"
PORT="150"
REMOTE_PATH="/var/www/dolibarr/htdocs/custom/marketplace_bdc"
LOCAL_PATH="/Users/admin/Documents/ModuleMarketPlace_dolibarr"

echo "🚀 MarketPlace Module Quick Deploy"
echo "======================================"
echo ""

# Check if file argument provided
if [ "$1" == "all" ] || [ -z "$1" ]; then
    echo "📦 Deploying ALL files..."
    
    # Using rsync for full sync
    rsync -avz -e "ssh -p $PORT" \
        --exclude='.git' \
        --exclude='.version-build' \
        --exclude='node_modules' \
        --exclude='*.log' \
        "$LOCAL_PATH/" "$SERVER:$REMOTE_PATH/"
    
    echo "✅ Full deployment complete!"
else
    echo "📄 Deploying single file: $1"
    
    # Deploy specific file
    if [ -f "$LOCAL_PATH/$1" ]; then
        scp -P $PORT "$LOCAL_PATH/$1" "$SERVER:$REMOTE_PATH/$1"
        echo "✅ File deployed: $1"
    else
        echo "❌ File not found: $LOCAL_PATH/$1"
        exit 1
    fi
fi

echo ""
echo "🎉 Deployment finished!"
echo "Check: https://dlbp150r58.edicloud.app/custom/marketplace_bdc/marketplace/product_tab.php?id=108"
