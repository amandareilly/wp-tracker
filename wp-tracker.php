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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_create_tracker_link', array($this, 'create_tracker_link'));
        add_action('wp_ajax_get_tracker_stats', array($this, 'get_tracker_stats'));
        add_action('wp_ajax_delete_tracker_link', array($this, 'delete_tracker_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Handle tracker redirects
        if (isset($_GET['tracker_id'])) {
            $this->handle_tracker_redirect();
        }
    }
    
    public function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tracker_links';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            destination_url varchar(500) NOT NULL,
            tracker_id varchar(32) NOT NULL,
            click_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY tracker_id (tracker_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Tracker</h1>
            
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
                
                var trackerId = $(this).data('tracker-id');
                var row = $(this).closest('tr');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_tracker_link',
                        tracker_id: trackerId,
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
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tracker_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
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
            $tracker_url = home_url('?tracker_id=' . $link->tracker_id);
            
            echo '<tr>';
            echo '<td><code>' . esc_html($link->tracker_id) . '</code></td>';
            echo '<td><a href="' . esc_url($link->destination_url) . '" target="_blank">' . esc_html($link->destination_url) . '</a></td>';
            echo '<td>' . intval($link->click_count) . '</td>';
            echo '<td>' . esc_html($link->created_at) . '</td>';
            echo '<td>';
            echo '<button class="button copy-tracker" data-url="' . esc_attr($tracker_url) . '">Copy URL</button> ';
            echo '<button class="button delete-tracker" data-tracker-id="' . esc_attr($link->tracker_id) . '">Delete</button>';
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
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tracker_links';
        
        // Generate unique tracker ID
        do {
            $tracker_id = wp_generate_password(8, false);
        } while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE tracker_id = %s", $tracker_id)));
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'destination_url' => $destination_url,
                'tracker_id' => $tracker_id
            ),
            array('%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to create tracker link');
        }
        
        wp_send_json_success('Tracker link created successfully');
    }
    
    public function delete_tracker_link() {
        check_ajax_referer('wp_tracker_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $tracker_id = sanitize_text_field($_POST['tracker_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tracker_links';
        
        $result = $wpdb->delete(
            $table_name,
            array('tracker_id' => $tracker_id),
            array('%s')
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to delete tracker link');
        }
        
        wp_send_json_success('Tracker link deleted successfully');
    }
    
    public function handle_tracker_redirect() {
        $tracker_id = sanitize_text_field($_GET['tracker_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'tracker_links';
        
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE tracker_id = %s",
            $tracker_id
        ));
        
        if (!$link) {
            wp_die('Tracker link not found');
        }
        
        // Increment click count
        $wpdb->update(
            $table_name,
            array('click_count' => $link->click_count + 1),
            array('tracker_id' => $tracker_id),
            array('%d'),
            array('%s')
        );
        
        // Redirect to destination
        wp_redirect($link->destination_url);
        exit;
    }
}

// Initialize the plugin
new WP_Tracker();
