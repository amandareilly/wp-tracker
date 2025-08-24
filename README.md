# WP Tracker - WordPress Link Tracking Plugin

A simple WordPress plugin that creates tracker links to monitor click counts and redirect to destination URLs.

## Features

- **Create Tracker Links**: Generate unique tracker URLs for any destination link
- **Click Counting**: Automatically track and count clicks on tracker links
- **Transparent Redirects**: Seamlessly redirect users to the original destination
- **Admin Interface**: Easy-to-use WordPress admin panel
- **Statistics**: View click counts and manage tracker links
- **Copy URLs**: One-click copying of tracker URLs to clipboard

## Installation

1. Upload the `wp-tracker.php` file to your WordPress site's `/wp-content/plugins/wp-tracker/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The plugin will automatically create the necessary database table

## Usage

### Creating a Tracker Link

1. Go to **WP Tracker** in your WordPress admin menu
2. Enter the destination URL you want to track
3. Click "Create Tracker Link"
4. The plugin will generate a unique tracker URL

### Tracker URL Format

Tracker URLs follow this format:
```
https://yoursite.com/trackers/ABC123XY
```

Where `ABC123XY` is a unique 8-character identifier.

**Custom Tracking Path**: You can customize the tracking path in the Settings page. For example, you could use:
- `https://yoursite.com/links/ABC123XY`
- `https://yoursite.com/go/ABC123XY`
- `https://yoursite.com/redirect/ABC123XY`

### How It Works

1. When someone visits a tracker URL, the plugin:
   - Increments the click counter
   - Immediately redirects to the destination URL
   - The redirect is transparent to the user

2. You can view click statistics in the admin panel

### Managing Tracker Links

- **View Statistics**: See click counts for all tracker links
- **Copy URLs**: Click "Copy URL" to copy tracker URLs to clipboard
- **Delete Links**: Remove tracker links you no longer need

## Implementation

The plugin uses WordPress custom post types to store tracker links, which provides:

- **WordPress Native**: Uses WordPress's built-in post system
- **No Database Conflicts**: Avoids potential conflicts with other plugins
- **Better Performance**: Leverages WordPress's optimized post queries
- **Automatic Cleanup**: Posts are automatically cleaned up when the plugin is deactivated

Each tracker link is stored as a `tracker_link` post type with the following meta fields:
- `_destination_url`: The URL to redirect to
- `_click_count`: Number of clicks
- `_tracker_id`: Unique 8-character identifier

## Security Features

- Nonce verification for all AJAX requests
- Capability checks for admin functions
- Input sanitization and validation
- SQL prepared statements to prevent injection

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## License

GPL v2 or later

## Support

For support or feature requests, please create an issue on the GitHub repository.

## Changelog

### Version 1.0.0
- Initial release
- Basic tracker link creation and management
- Click counting functionality
- Admin interface
- Database table creation
