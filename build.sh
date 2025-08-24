#!/bin/bash

# WP Tracker Plugin Build Script
# This script creates a clean plugin package for distribution

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PLUGIN_NAME="wp-tracker"
BUILD_DIR="build"
PLUGIN_DIR="$BUILD_DIR/$PLUGIN_NAME"

# Get version from plugin file
get_version() {
    local version=$(grep "Version:" wp-tracker.php | sed 's/.*Version: *//' | tr -d ' ')
    echo "$version"
}

# Calculate new version based on increment type
calculate_new_version() {
    local current_version="$1"
    local increment_type="$2"
    
    # Parse current version (assuming format: major.minor.patch)
    IFS='.' read -ra VERSION_PARTS <<< "$current_version"
    local major="${VERSION_PARTS[0]:-0}"
    local minor="${VERSION_PARTS[1]:-0}"
    local patch="${VERSION_PARTS[2]:-0}"
    
    case "$increment_type" in
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        minor)
            minor=$((minor + 1))
            patch=0
            ;;
        patch)
            patch=$((patch + 1))
            ;;
        *)
            echo -e "${RED}❌ Error: Invalid increment type. Use 'major', 'minor', or 'patch'${NC}"
            exit 1
            ;;
    esac
    
    echo "${major}.${minor}.${patch}"
}

# Update version in plugin file
update_version() {
    local increment_type="$1"
    local current_version=$(get_version)
    local new_version=$(calculate_new_version "$current_version" "$increment_type")
    local temp_file="wp-tracker.php.tmp"
    
    echo -e "${BLUE}📝 Updating version from $current_version to $new_version ($increment_type increment)${NC}"
    
    # Create backup
    cp wp-tracker.php "wp-tracker.php.backup"
    
    # Update version in file
    sed "s/Version: [0-9.]*/Version: $new_version/" wp-tracker.php > "$temp_file"
    mv "$temp_file" wp-tracker.php
    
    echo -e "${GREEN}✅ Version updated successfully${NC}"
}

# Show usage information
show_usage() {
    echo -e "${BLUE}WP Tracker Build Script${NC}"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --major                 Increment major version (1.0.0 → 2.0.0)"
    echo "  --minor                 Increment minor version (1.0.0 → 1.1.0)"
    echo "  --patch                 Increment patch version (1.0.0 → 1.0.1)"
    echo "  -h, --help              Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                      Build with current version"
    echo "  $0 --patch              Increment patch version and build"
    echo "  $0 --minor              Increment minor version and build"
    echo "  $0 --major              Increment major version and build"
    echo ""
}

# Clean previous builds
clean_build() {
    echo -e "${BLUE}🧹 Cleaning previous builds...${NC}"
    if [ -d "$BUILD_DIR" ]; then
        rm -rf "$BUILD_DIR"
    fi
    if [ -f "${PLUGIN_NAME}-v*.zip" ]; then
        rm -f "${PLUGIN_NAME}-v*.zip"
    fi
}

# Create build directory structure
create_build_structure() {
    echo -e "${BLUE}📁 Creating build structure...${NC}"
    mkdir -p "$PLUGIN_DIR"
}

# Copy plugin files
copy_files() {
    echo -e "${BLUE}📋 Copying plugin files...${NC}"
    cp wp-tracker.php "$PLUGIN_DIR/"
    cp README.md "$PLUGIN_DIR/"
    
    # Copy any additional files if they exist
    if [ -f "uninstall.php" ]; then
        cp uninstall.php "$PLUGIN_DIR/"
    fi
    
    if [ -d "assets" ]; then
        cp -r assets "$PLUGIN_DIR/"
    fi
    
    if [ -d "includes" ]; then
        cp -r includes "$PLUGIN_DIR/"
    fi
}

# Create ZIP package
create_package() {
    local version=$(get_version)
    local zip_name="${PLUGIN_NAME}-v${version}.zip"
    
    echo -e "${BLUE}📦 Creating package: $zip_name${NC}"
    
    cd "$BUILD_DIR"
    zip -r "../$zip_name" "$PLUGIN_NAME" -x "*.DS_Store" "*/.*"
    cd ..
    
    echo -e "${GREEN}✅ Package created: $zip_name${NC}"
    echo -e "${YELLOW}📊 Package size: $(du -h "$zip_name" | cut -f1)${NC}"
}

# Validate plugin file
validate_plugin() {
    echo -e "${BLUE}🔍 Validating plugin file...${NC}"
    
    if [ ! -f "wp-tracker.php" ]; then
        echo -e "${RED}❌ Error: wp-tracker.php not found${NC}"
        exit 1
    fi
    
    if ! grep -q "Plugin Name:" wp-tracker.php; then
        echo -e "${RED}❌ Error: Plugin header not found in wp-tracker.php${NC}"
        exit 1
    fi
    
    local version=$(get_version)
    if [ -z "$version" ]; then
        echo -e "${RED}❌ Error: Version not found in plugin header${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✅ Plugin validation passed (Version: $version)${NC}"
}

# Main build process
main() {
    local increment_type=""
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --major|--minor|--patch)
                increment_type="${1#--}"  # Remove the -- prefix
                shift
                ;;
            -h|--help)
                show_usage
                exit 0
                ;;
            *)
                echo -e "${RED}❌ Unknown option: $1${NC}"
                show_usage
                exit 1
                ;;
        esac
    done
    
    echo -e "${BLUE}🚀 Starting WP Tracker build process...${NC}"
    echo ""
    
    # Update version if specified
    if [ -n "$increment_type" ]; then
        update_version "$increment_type"
        echo ""
    fi
    
    validate_plugin
    clean_build
    create_build_structure
    copy_files
    create_package
    
    echo ""
    echo -e "${GREEN}🎉 Build completed successfully!${NC}"
    echo -e "${YELLOW}📁 Build directory: $BUILD_DIR${NC}"
    echo -e "${YELLOW}📦 Package: $(ls ${PLUGIN_NAME}-v*.zip)${NC}"
    
    # Show backup info if version was updated
    if [ -n "$increment_type" ]; then
        echo -e "${YELLOW}💾 Backup created: wp-tracker.php.backup${NC}"
    fi
}

# Run main function
main "$@"
