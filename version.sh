#!/bin/bash

# WP Tracker Version Management Script
# This script handles version increments and commits the changes

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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
    echo -e "${YELLOW}💾 Backup created: wp-tracker.php.backup${NC}"
    
    # Return the new version for commit message
    echo "$new_version"
}

# Commit version change
commit_version_change() {
    local new_version="$1"
    local increment_type="$2"
    
    echo -e "${BLUE}📝 Committing version change...${NC}"
    
    # Add the updated plugin file
    git add wp-tracker.php
    
    # Create commit message
    local commit_message="Bump version to $new_version ($increment_type increment)"
    
    # Commit the change
    git commit -m "$commit_message"
    
    echo -e "${GREEN}✅ Version change committed${NC}"
    echo -e "${YELLOW}📝 Commit message: $commit_message${NC}"
}

# Show usage information
show_usage() {
    echo -e "${BLUE}WP Tracker Version Management Script${NC}"
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  --major                 Increment major version (1.0.0 → 2.0.0) and commit"
    echo "  --minor                 Increment minor version (1.0.0 → 1.1.0) and commit"
    echo "  --patch                 Increment patch version (1.0.0 → 1.0.1) and commit"
    echo "  --no-commit             Update version without committing"
    echo "  --current               Show current version"
    echo "  -h, --help              Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 --current            Show current version"
    echo "  $0 --patch              Increment patch version and commit"
    echo "  $0 --minor --no-commit  Increment minor version without committing"
    echo "  $0 --major              Increment major version and commit"
    echo ""
}

# Main function
main() {
    local increment_type=""
    local no_commit=false
    local show_current=false
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --major|--minor|--patch)
                increment_type="${1#--}"  # Remove the -- prefix
                shift
                ;;
            --no-commit)
                no_commit=true
                shift
                ;;
            --current)
                show_current=true
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
    
    # Show current version if requested
    if [ "$show_current" = true ]; then
        local current_version=$(get_version)
        echo -e "${GREEN}Current version: $current_version${NC}"
        exit 0
    fi
    
    # Check if increment type is specified
    if [ -z "$increment_type" ]; then
        echo -e "${RED}❌ Error: Please specify an increment type (--major, --minor, or --patch)${NC}"
        show_usage
        exit 1
    fi
    
    echo -e "${BLUE}🚀 Starting version management...${NC}"
    echo ""
    
    # Update version
    local new_version=$(update_version "$increment_type")
    
    # Commit if not disabled
    if [ "$no_commit" = false ]; then
        echo ""
        commit_version_change "$new_version" "$increment_type"
    else
        echo -e "${YELLOW}⚠️  Version updated but not committed (use --no-commit)${NC}"
    fi
    
    echo ""
    echo -e "${GREEN}🎉 Version management completed!${NC}"
    echo -e "${YELLOW}📦 Next step: Run './build.sh' to create a package${NC}"
}

# Run main function
main "$@"
