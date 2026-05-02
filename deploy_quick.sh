#!/bin/bash

# Quick deployment script for MarketPlace module
# Usage: ./deploy_quick.sh [file_or_all]

SERVER="root@dlbp150r58.edicloud.app"
PORT="150"
SSH_KEY="$HOME/.ssh/id_rsa_nopass"
REMOTE_PATH="/var/www/dolibarr/htdocs/custom/marketplace_bdc"
LOCAL_PATH="/Users/admin/Documents/ModuleMarketPlace_dolibarr"

SSH_OPTS="-i $SSH_KEY -p $PORT -o StrictHostKeyChecking=no -o ConnectTimeout=10"

echo "🚀 MarketPlace Module Quick Deploy"
echo "======================================"
echo ""

deploy_all() {
    echo "📦 Déploiement de tous les fichiers PHP..."

    # Liste tous les fichiers PHP et autres à déployer
    find "$LOCAL_PATH" \
        -not -path '*/.git/*' \
        -not -name '.version-build' \
        -not -name '*.log' \
        -not -path '*/node_modules/*' \
        -type f | while read -r file; do

        rel="${file#$LOCAL_PATH/}"
        remote_dir="$REMOTE_PATH/$(dirname "$rel")"

        # Crée le dossier distant si nécessaire
        ssh $SSH_OPTS "$SERVER" "mkdir -p '$remote_dir'" 2>/dev/null

        # Copie le fichier
        scp $SSH_OPTS "$file" "$SERVER:$REMOTE_PATH/$rel" 2>/dev/null
        echo "  ✓ $rel"
    done

    echo "✅ Déploiement complet terminé."
}

deploy_file() {
    local rel="$1"
    local full="$LOCAL_PATH/$rel"

    if [ ! -f "$full" ]; then
        echo "❌ Fichier introuvable : $full"
        exit 1
    fi

    echo "📄 Déploiement : $rel"
    ssh $SSH_OPTS "$SERVER" "mkdir -p '$(dirname "$REMOTE_PATH/$rel")'" 2>/dev/null
    scp $SSH_OPTS "$full" "$SERVER:$REMOTE_PATH/$rel"
    echo "✅ Déployé : $rel"
}

if [ "$1" == "all" ] || [ -z "$1" ]; then
    deploy_all
else
    deploy_file "$1"
fi

echo ""
echo "🎉 Déploiement terminé !"
echo "Check: https://dlbp150r58.edicloud.app/custom/marketplace_bdc/admin/setup.php"
