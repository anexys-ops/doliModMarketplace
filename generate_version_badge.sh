#!/bin/bash

# Generate Version Badge
# Creates an HTML version badge to display in admin pages

MODULE_DIR="$(cd "$(dirname "$0")" && pwd)"
VERSION_MANAGER="$MODULE_DIR/class/VersionManager.class.php"
OUTPUT_FILE="$MODULE_DIR/admin/version_badge.html"

echo "🎨 Generating version badge..."

# Extract version and date
VERSION=$(grep "const VERSION = " "$VERSION_MANAGER" | sed "s/.*const VERSION = '\([^']*\)'.*/\1/")
VERSION_DATE=$(grep "const VERSION_DATE = " "$VERSION_MANAGER" | sed "s/.*const VERSION_DATE = '\([^']*\)'.*/\1/")

if [ -z "$VERSION" ]; then
    echo "❌ Could not extract version"
    exit 1
fi

# Get git info
cd "$MODULE_DIR"
GIT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "N/A")
GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "N/A")

# Create HTML badge
cat > "$OUTPUT_FILE" << EOF
<!-- MarketPlace Module Version Badge -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 15px; border-radius: 8px; color: white; margin: 15px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 13px;">
        <div>
            <div style="opacity: 0.9; margin-bottom: 4px;">Version</div>
            <div style="font-size: 24px; font-weight: bold;">v$VERSION</div>
            <div style="opacity: 0.8; font-size: 11px; margin-top: 4px;">Released: $VERSION_DATE</div>
        </div>
        <div>
            <div style="opacity: 0.9; margin-bottom: 4px;">Build Info</div>
            <div style="font-family: monospace; font-size: 12px;">
                <div>🔗 Commit: $GIT_COMMIT</div>
                <div>📍 Branch: $GIT_BRANCH</div>
                <div>⏱️ Updated: $(date '+%Y-%m-%d %H:%M')</div>
            </div>
        </div>
    </div>
</div>
<!-- End Version Badge -->
EOF

echo "✅ Badge generated: $OUTPUT_FILE"
echo ""
echo "Version: v$VERSION"
echo "Commit: $GIT_COMMIT"
echo "Branch: $GIT_BRANCH"
echo ""
echo "Badge created successfully!"
