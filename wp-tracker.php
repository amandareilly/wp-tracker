<?php
/**
 * Plugin Name: WP Tracker
 * Plugin URI: https://github.com/yourusername/wp-tracker
 * Description: Create tracker links that count clicks and redirect to destination URLs
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-tracker
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_TRACKER_VERSION', '1.0.0');
define('WP_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_TRACKER_PLUGIN_PATH', plugin_dir_path(__FILE__));

class WP_Tracker {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'register_tracker_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_create_tracker_link', array($this, 'create_tracker_link'));
        add_action('wp_ajax_get_tracker_stats', array($this, 'get_tracker_stats'));
        add_action('wp_ajax_delete_tracker_link', array($this, 'delete_tracker_link'));
        add_action('wp_ajax_save_tracking_settings', array($this, 'save_tracking_settings'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('template_redirect', array($this, 'handle_tracker_redirect'));
    }
    
    public function init() {
        // Initialize any necessary functionality
    }
    
    public function register_tracker_post_type() {
        $tracking_path = get_option('wp_tracker_path', 'trackers');
        
        $labels = array(
            'name'               => 'Tracker Links',
            'singular_name'      => 'Tracker Link',
            'menu_name'          => 'Tracker Links',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Tracker Link',
            'edit_item'          => 'Edit Tracker Link',
            'new_item'           => 'New Tracker Link',
            'view_item'          => 'View Tracker Link',
            'search_items'       => 'Search Tracker Links',
            'not_found'          => 'No tracker links found',
            'not_found_in_trash' => 'No tracker links found in trash'
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => false, // We'll use our custom admin interface
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'show_in_rest'        => false,
            'query_var'           => true,
            'rewrite'             => array('slug' => $tracking_path),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'custom-fields'),
            'can_export'          => false,
            'delete_with_user'    => false
        );
        
        register_post_type('tracker_link', $args);
    }
    
    public function activate() {
        // Set default tracking path
        if (!get_option('wp_tracker_path')) {
            update_option('wp_tracker_path', 'trackers');
        }
        
        // Flush rewrite rules to register the custom post type
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'WP Tracker',
            'WP Tracker',
            'manage_options',
            'wp-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'wp-tracker',
            'Settings',
            'Settings',
            'manage_options',
            'wp-tracker-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Tracker</h1>
            
            <div class="card">
                <h2>Settings</h2>
                <p>Configure your tracking path in the <a href="<?php echo admin_url('admin.php?page=wp-tracker-settings'); ?>">Settings</a> page.</p>
            </div>
            
            <div class="card">
                <h2>Create New Tracker Link</h2>
                <form id="create-tracker-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="destination_url">Destination URL</label>
                            </th>
                            <td>
                                <input type="url" id="destination_url" name="destination_url" class="regular-text" required>
                                <p class="description">Enter the URL you want to redirect to</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary">Create Tracker Link</button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>Tracker Links</h2>
                <div id="tracker-links-list">
                    <?php $this->display_tracker_links(); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#create-tracker-form').on('submit', function(e) {
                e.preventDefault();
                
                var destinationUrl = $('#destination_url').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'create_tracker_link',
                        destination_url: destinationUrl,
                        nonce: '<?php echo wp_create_nonce('wp_tracker_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Tracker link created successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
            
            $('.delete-tracker').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to delete this tracker link?')) {
                    return;
                }
                
                var postId = $(this).data('post-id');
                var row = $(this).closest('tr');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_tracker_link',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce('wp_tracker_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            row.remove();
                            alert('Tracker link deleted successfully!');
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function display_tracker_links() {
        $args = array(
            'post_type' => 'tracker_link',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $links = get_posts($args);
        
        if (empty($links)) {
            echo '<p>No tracker links created yet.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Tracker ID</th>';
        echo '<th>Destination URL</th>';
        echo '<th>Clicks</th>';
        echo '<th>Created</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($links as $link) {
            $tracker_id = get_post_meta($link->ID, '_tracker_id', true);
            $destination_url = get_post_meta($link->ID, '_destination_url', true);
            $click_count = get_post_meta($link->ID, '_click_count', true);
            $tracking_path = get_option('wp_tracker_path', 'trackers');
            $tracker_url = home_url($tracking_path . '/' . $tracker_id);
            
            echo '<tr>';
            echo '<td><code>' . esc_html($tracker_id) . '</code></td>';
            echo '<td><a href="' . esc_url($destination_url) . '" target="_blank">' . esc_html($destination_url) . '</a></td>';
            echo '<td>' . intval($click_count) . '</td>';
            echo '<td>' . esc_html(get_the_date('Y-m-d H:i:s', $link->ID)) . '</td>';
            echo '<td>';
            echo '<button class="button copy-tracker" data-url="' . esc_attr($tracker_url) . '">Copy URL</button> ';
            echo '<button class="button delete-tracker" data-post-id="' . esc_attr($link->ID) . '">Delete</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<script>
        jQuery(document).ready(function($) {
            $(".copy-tracker").on("click", function() {
                var url = $(this).data("url");
                navigator.clipboard.writeText(url).then(function() {
                    alert("Tracker URL copied to clipboard!");
                });
            });
        });
        </script>';
    }
    
    public function create_tracker_link() {
        check_ajax_referer('wp_tracker_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $destination_url = sanitize_url($_POST['destination_url']);
        
        if (empty($destination_url)) {
            wp_send_json_error('Invalid destination URL');
        }
        
        // Generate unique tracker ID
        do {
            $tracker_id = wp_generate_password(8, false);
        } while (get_page_by_path($tracker_id, OBJECT, 'tracker_link'));
        
        // Create the post
        $post_data = array(
            'post_title'    => 'Tracker: ' . $tracker_id,
            'post_name'     => $tracker_id,
            'post_status'   => 'publish',
            'post_type'     => 'tracker_link',
            'post_author'   => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to create tracker link: ' . $post_id->get_error_message());
        }
        
        // Add post meta
        update_post_meta($post_id, '_destination_url', $destination_url);
        update_post_meta($post_id, '_click_count', 0);
        update_post_meta($post_id, '_tracker_id', $tracker_id);
        
        wp_send_json_success('Tracker link created successfully');
    }
    
    public function delete_tracker_link() {
        check_ajax_referer('wp_tracker_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'tracker_link') {
            wp_send_json_error('Tracker link not found');
        }
        
        $result = wp_delete_post($post_id, true);
        
        if (!$result) {
            wp_send_json_error('Failed to delete tracker link');
        }
        
        wp_send_json_success('Tracker link deleted successfully');
    }
    
    public function settings_page() {
        $tracking_path = get_option('wp_tracker_path', 'trackers');
        $site_url = home_url();
        ?>
        <div class="wrap">
            <h1>WP Tracker Settings</h1>
            
            <div class="card">
                <h2>Tracking Path Configuration</h2>
                <form id="tracking-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="tracking_path">Tracking Path</label>
                            </th>
                            <td>
                                <input type="text" id="tracking_path" name="tracking_path" value="<?php echo esc_attr($tracking_path); ?>" class="regular-text" required>
                                <p class="description">Enter the path for your tracker URLs (e.g., "trackers", "links", "go")</p>
                                <p class="description">Your tracker URLs will be: <code><?php echo esc_url($site_url . '/' . $tracking_path . '/{tracker_id}'); ?></code></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary">Save Settings</button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>Important Note</h2>
                <p>After changing the tracking path, you may need to:</p>
                <ol>
                    <li>Go to <strong>Settings > Permalinks</strong> and click "Save Changes" to flush rewrite rules</li>
                    <li>Update any existing tracker links you've shared</li>
                </ol>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#tracking-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                var trackingPath = $('#tracking_path').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'save_tracking_settings',
                        tracking_path: trackingPath,
                        nonce: '<?php echo wp_create_nonce('wp_tracker_settings_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Settings saved successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function save_tracking_settings() {
        check_ajax_referer('wp_tracker_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $tracking_path = sanitize_title($_POST['tracking_path']);
        
        if (empty($tracking_path)) {
            wp_send_json_error('Tracking path cannot be empty');
        }
        
        // Check if path conflicts with existing post types or pages
        $existing_post = get_page_by_path($tracking_path);
        if ($existing_post) {
            wp_send_json_error('This path conflicts with an existing page or post. Please choose a different path.');
        }
        
        update_option('wp_tracker_path', $tracking_path);
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        wp_send_json_success('Settings saved successfully');
    }
    
    public function handle_tracker_redirect() {
        // Only handle tracker_link post type requests
        if (!is_singular('tracker_link')) {
            return;
        }
        
        global $post;
        
        if (!$post || $post->post_type !== 'tracker_link') {
            return;
        }
        
        // Get the destination URL from post meta
        $destination_url = get_post_meta($post->ID, '_destination_url', true);
        $click_count = get_post_meta($post->ID, '_click_count', true);
        
        if (empty($destination_url)) {
            wp_die('Tracker link not found or invalid');
        }
        
        // Increment click count
        update_post_meta($post->ID, '_click_count', intval($click_count) + 1);
        
        // Redirect to destination
        wp_redirect($destination_url);
        exit;
    }
}

// Initialize the plugin
new WP_Tracker();
