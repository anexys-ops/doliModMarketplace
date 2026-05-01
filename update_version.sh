#!/bin/bash

# UPDATE VERSION SCRIPT
# Usage: ./update_version.sh [major|minor|patch]

MODULE_DIR="$(cd "$(dirname "$0")" && pwd)"
VERSION_CLASS="$MODULE_DIR/class/VersionManager.class.php"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== MarketPlace Module Version Manager ===${NC}\n"

# Get current version
CURRENT_VERSION=$(grep "const VERSION = " "$VERSION_CLASS" | sed "s/.*const VERSION = '\([^']*\)'.*/\1/")
echo -e "${YELLOW}Current Version: ${GREEN}$CURRENT_VERSION${NC}"

# Parse version
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

# Determine new version
VERSION_TYPE=${1:-patch}

case $VERSION_TYPE in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch|*)
        PATCH=$((PATCH + 1))
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"
NEW_DATE=$(date '+%Y-%m-%d')

echo -e "${YELLOW}New Version: ${GREEN}$NEW_VERSION${NC}"
echo -e "${YELLOW}Release Date: ${GREEN}$NEW_DATE${NC}\n"

# Update VERSION in VersionManager class
sed -i "" "s/const VERSION = '[^']*'/const VERSION = '$NEW_VERSION'/" "$VERSION_CLASS"
sed -i "" "s/const VERSION_DATE = '[^']*'/const VERSION_DATE = '$NEW_DATE'/" "$VERSION_CLASS"

echo -e "${GREEN}✅ Updated VersionManager.class.php${NC}"

# Update CHANGELOG
if [ -f "$MODULE_DIR/CHANGELOG.md" ]; then
    # Create temp file with new entry
    TEMP_CHANGELOG=$(mktemp)
    echo "## [$NEW_VERSION] - $NEW_DATE" > "$TEMP_CHANGELOG"
    echo "" >> "$TEMP_CHANGELOG"
    echo "### Added" >> "$TEMP_CHANGELOG"
    echo "- " >> "$TEMP_CHANGELOG"
    echo "" >> "$TEMP_CHANGELOG"
    echo "### Fixed" >> "$TEMP_CHANGELOG"
    echo "- " >> "$TEMP_CHANGELOG"
    echo "" >> "$TEMP_CHANGELOG"
    echo "### Changed" >> "$TEMP_CHANGELOG"
    echo "- " >> "$TEMP_CHANGELOG"
    echo "" >> "$TEMP_CHANGELOG"
    cat "$MODULE_DIR/CHANGELOG.md" >> "$TEMP_CHANGELOG"
    mv "$TEMP_CHANGELOG" "$MODULE_DIR/CHANGELOG.md"
    
    echo -e "${GREEN}✅ Updated CHANGELOG.md${NC}"
fi

# Update README version tag
if [ -f "$MODULE_DIR/README.md" ]; then
    sed -i "" "s/Version: [0-9.]\+/Version: $NEW_VERSION/" "$MODULE_DIR/README.md"
    echo -e "${GREEN}✅ Updated README.md${NC}"
fi

# Git operations
echo -e "\n${BLUE}=== Git Operations ===${NC}\n"

# Create git tag
cd "$MODULE_DIR"
git tag -a "v$NEW_VERSION" -m "Release version $NEW_VERSION" 2>/dev/null || true
echo -e "${GREEN}✅ Git tag created: v$NEW_VERSION${NC}"

# Add files to git
git add class/VersionManager.class.php CHANGELOG.md README.md 2>/dev/null || true
echo -e "${GREEN}✅ Files staged for commit${NC}"

echo -e "\n${BLUE}=== Ready for Commit ===${NC}"
echo -e "Branch: ${GREEN}$(git rev-parse --abbrev-ref HEAD)${NC}"
echo -e "Files staged: ${GREEN}$(git diff --cached --name-only | wc -l)${NC}"
echo ""
echo -e "Next steps:"
echo "  1. Review changes: git diff --cached"
echo "  2. Commit: git commit -m \"Release v$NEW_VERSION\""
echo "  3. Push: git push origin --all && git push origin --tags"
echo ""
