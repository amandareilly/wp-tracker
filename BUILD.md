# WP Tracker Build Process

This document explains how to build the WP Tracker plugin for distribution.

## Quick Build

To create a plugin package, simply run:

```bash
./build.sh
```

This will:
1. Validate the plugin file
2. Clean previous builds
3. Create a clean build structure
4. Copy all necessary files
5. Create a ZIP package with the current version

## Version Management

The build script can automatically increment the plugin version:

```bash
# Increment patch version (1.0.0 → 1.0.1)
./build.sh --patch

# Increment minor version (1.0.0 → 1.1.0)
./build.sh --minor

# Increment major version (1.0.0 → 2.0.0)
./build.sh --major
```

When using version increments:
- The script automatically updates the version in `wp-tracker.php`
- A backup of the original file is created (`wp-tracker.php.backup`)
- The new package is built with the updated version

## Build Output

The build script creates:
- `build/` - Temporary build directory
- `wp-tracker-v{version}.zip` - Plugin package ready for distribution

## Version Management

The build script automatically reads the version from the plugin header in `wp-tracker.php`:

```php
/**
 * Plugin Name: WP Tracker
 * Version: 1.0.1
 * ...
 */
```

The script supports semantic versioning increments:
- **Patch**: Bug fixes and minor updates (1.0.0 → 1.0.1)
- **Minor**: New features, backward compatible (1.0.0 → 1.1.0)  
- **Major**: Breaking changes (1.0.0 → 2.0.0)

## File Structure

The build includes:
- `wp-tracker.php` - Main plugin file
- `README.md` - Plugin documentation
- `uninstall.php` - Uninstall script (if exists)
- `assets/` - CSS, JS, images (if exists)
- `includes/` - PHP includes (if exists)

## Requirements

- Bash shell
- `zip` command (usually pre-installed on macOS/Linux)
- Git (for version control)

## Troubleshooting

If the build fails:
1. Check that `wp-tracker.php` exists and has a valid plugin header
2. Ensure the version is properly set in the plugin header
3. Verify you have write permissions in the current directory
