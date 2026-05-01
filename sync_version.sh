#!/bin/bash

# Sync Version Script
# Synchronizes version between VersionManager.class.php and modMarketPlace_BDC.class.php

MODULE_DIR="$(cd "$(dirname "$0")" && pwd)"
VERSION_MANAGER="$MODULE_DIR/class/VersionManager.class.php"
MODULE_CLASS="$MODULE_DIR/core/modules/modMarketPlace_BDC.class.php"

echo "🔄 Synchronizing versions..."
echo ""

# Extract version from VersionManager
VERSION=$(grep "const VERSION = " "$VERSION_MANAGER" | sed "s/.*const VERSION = '\([^']*\)'.*/\1/")

if [ -z "$VERSION" ]; then
    echo "❌ Could not extract version from $VERSION_MANAGER"
    exit 1
fi

echo "📦 Version from VersionManager: $VERSION"

# Update module version
sed -i "" "s/\$this->version = '[^']*'/\$this->version = '$VERSION'/" "$MODULE_CLASS"

echo "✅ Updated module version to: $VERSION"
echo ""
echo "Files synchronized:"
echo "  ✅ $VERSION_MANAGER"
echo "  ✅ $MODULE_CLASS"
echo ""
echo "Version sync complete!"
