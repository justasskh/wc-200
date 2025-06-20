<?php
/**
 * WooCommerce Gifting Flow Admin Tools
 * Database management and debugging tools
 */

if (!defined('ABSPATH')) exit;

class WCFlow_Admin_Tools {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_wcflow_test_connection', array($this, 'test_database_connection'));
        add_action('wp_ajax_wcflow_import_sample_data', array($this, 'import_sample_data'));
        add_action('wp_ajax_wcflow_export_data', array($this, 'export_data'));
        add_action('wp_ajax_wcflow_clear_all_cache', array($this, 'clear_all_cache'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=wcflow_card',
            'Database Tools',
            'Database Tools',
            'manage_options',
            'wcflow-database-tools',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wcflow-database-tools') !== false) {
            wp_enqueue_script('wcflow-admin-tools', WCFLOW_URL . 'assets/admin-tools.js', array('jquery'), WCFLOW_VERSION, true);
            wp_localize_script('wcflow-admin-tools', 'wcflow_admin_tools', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wcflow_admin_tools_nonce')
            ));
        }
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        global $wpdb;
        
        // Get current database stats
        $stats = array(
            'total_cards' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card'"),
            'published_cards' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'"),
            'total_categories' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'"),
            'relationships' => $wpdb->get_var("
                SELECT COUNT(*) 
                FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                WHERE tt.taxonomy = 'wcflow_card_category' 
                AND p.post_type = 'wcflow_card'
                AND p.post_status = 'publish'
            ")
        );
        
        ?>
        <div class="wrap">
            <h1>🎯 Greeting Cards Database Tools</h1>
            
            <div class="wcflow-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                
                <!-- Database Status -->
                <div class="postbox">
                    <h2 class="hndle">📊 Database Status</h2>
                    <div class="inside">
                        <table class="widefat">
                            <tr>
                                <td><strong>Total Cards:</strong></td>
                                <td><?php echo $stats['total_cards']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Published Cards:</strong></td>
                                <td><?php echo $stats['published_cards']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Categories:</strong></td>
                                <td><?php echo $stats['total_categories']; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Card-Category Links:</strong></td>
                                <td><?php echo $stats['relationships']; ?></td>
                            </tr>
                        </table>
                        
                        <p style="margin-top: 15px;">
                            <button type="button" class="button button-primary" onclick="testDatabaseConnection()">
                                🔍 Test Database Connection
                            </button>
                        </p>
                        
                        <div id="connection-test-results" style="margin-top: 10px;"></div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="postbox">
                    <h2 class="hndle">⚡ Quick Actions</h2>
                    <div class="inside">
                        <p>
                            <button type="button" class="button button-secondary" onclick="clearAllCache()">
                                🧹 Clear All Cache
                            </button>
                        </p>
                        
                        <p>
                            <button type="button" class="button button-secondary" onclick="importSampleData()">
                                📥 Import Sample Data
                            </button>
                        </p>
                        
                        <p>
                            <button type="button" class="button button-secondary" onclick="exportData()">
                                📤 Export Current Data
                            </button>
                        </p>
                        
                        <p>
                            <a href="<?php echo admin_url('edit.php?post_type=wcflow_card'); ?>" class="button">
                                🎴 Manage Cards
                            </a>
                        </p>
                        
                        <p>
                            <a href="<?php echo admin_url('edit-tags.php?taxonomy=wcflow_card_category&post_type=wcflow_card'); ?>" class="button">
                                📂 Manage Categories
                            </a>
                        </p>
                        
                        <div id="quick-actions-results" style="margin-top: 10px;"></div>
                    </div>
                </div>
                
                <!-- Recent Cards -->
                <div class="postbox">
                    <h2 class="hndle">🎴 Recent Cards</h2>
                    <div class="inside">
                        <?php
                        $recent_cards = $wpdb->get_results("
                            SELECT p.ID, p.post_title, p.post_status, pm.meta_value as price
                            FROM {$wpdb->posts} p
                            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wcflow_price'
                            WHERE p.post_type = 'wcflow_card' 
                            ORDER BY p.ID DESC 
                            LIMIT 5
                        ");
                        
                        if ($recent_cards) {
                            echo '<ul>';
                            foreach ($recent_cards as $card) {
                                $price = $card->price ? get_woocommerce_currency_symbol() . $card->price : 'FREE';
                                echo '<li>';
                                echo '<strong>' . esc_html($card->post_title) . '</strong> ';
                                echo '<span style="color: #666;">(' . $price . ')</span> ';
                                echo '<span class="status-' . $card->post_status . '">' . ucfirst($card->post_status) . '</span>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>No cards found.</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Categories Overview -->
                <div class="postbox">
                    <h2 class="hndle">📂 Categories Overview</h2>
                    <div class="inside">
                        <?php
                        $categories = $wpdb->get_results("
                            SELECT t.term_id, t.name, tt.count
                            FROM {$wpdb->terms} t 
                            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                            WHERE tt.taxonomy = 'wcflow_card_category' 
                            ORDER BY t.name ASC
                        ");
                        
                        if ($categories) {
                            echo '<ul>';
                            foreach ($categories as $category) {
                                echo '<li>';
                                echo '<strong>' . esc_html($category->name) . '</strong> ';
                                echo '<span style="color: #666;">(' . $category->count . ' cards)</span>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>No categories found.</p>';
                        }
                        ?>
                    </div>
                </div>
                
            </div>
            
            <!-- Debug Console -->
            <div class="postbox" style="margin-top: 20px;">
                <h2 class="hndle">🔧 Debug Console</h2>
                <div class="inside">
                    <textarea id="debug-console" readonly style="width: 100%; height: 200px; font-family: monospace; background: #f1f1f1; border: 1px solid #ddd; padding: 10px;"></textarea>
                    <p>
                        <button type="button" class="button" onclick="clearDebugConsole()">Clear Console</button>
                        <button type="button" class="button" onclick="runFullDiagnostic()">🔍 Run Full Diagnostic</button>
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        function logToConsole(message) {
            const console = document.getElementById('debug-console');
            const timestamp = new Date().toLocaleTimeString();
            console.value += '[' + timestamp + '] ' + message + '\n';
            console.scrollTop = console.scrollHeight;
        }
        
        function clearDebugConsole() {
            document.getElementById('debug-console').value = '';
        }
        
        function testDatabaseConnection() {
            logToConsole('🔍 Testing database connection...');
            
            jQuery.post(ajaxurl, {
                action: 'wcflow_test_connection',
                nonce: wcflow_admin_tools.nonce
            }, function(response) {
                if (response.success) {
                    logToConsole('✅ Database connection successful');
                    logToConsole('📊 Found ' + response.data.categories + ' categories, ' + response.data.cards + ' cards');
                    document.getElementById('connection-test-results').innerHTML = 
                        '<div class="notice notice-success"><p>✅ Connection successful! Found ' + 
                        response.data.categories + ' categories and ' + response.data.cards + ' cards.</p></div>';
                } else {
                    logToConsole('❌ Database connection failed: ' + response.data);
                    document.getElementById('connection-test-results').innerHTML = 
                        '<div class="notice notice-error"><p>❌ Connection failed: ' + response.data + '</p></div>';
                }
            });
        }
        
        function clearAllCache() {
            logToConsole('🧹 Clearing all cache...');
            
            jQuery.post(ajaxurl, {
                action: 'wcflow_clear_all_cache',
                nonce: wcflow_admin_tools.nonce
            }, function(response) {
                if (response.success) {
                    logToConsole('✅ Cache cleared successfully');
                    document.getElementById('quick-actions-results').innerHTML = 
                        '<div class="notice notice-success"><p>✅ Cache cleared successfully!</p></div>';
                } else {
                    logToConsole('❌ Failed to clear cache');
                }
            });
        }
        
        function importSampleData() {
            if (!confirm('This will create sample greeting cards and categories. Continue?')) {
                return;
            }
            
            logToConsole('📥 Importing sample data...');
            
            jQuery.post(ajaxurl, {
                action: 'wcflow_import_sample_data',
                nonce: wcflow_admin_tools.nonce
            }, function(response) {
                if (response.success) {
                    logToConsole('✅ Sample data imported: ' + response.data.message);
                    document.getElementById('quick-actions-results').innerHTML = 
                        '<div class="notice notice-success"><p>✅ ' + response.data.message + '</p></div>';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    logToConsole('❌ Failed to import sample data');
                }
            });
        }
        
        function exportData() {
            logToConsole('📤 Exporting data...');
            
            jQuery.post(ajaxurl, {
                action: 'wcflow_export_data',
                nonce: wcflow_admin_tools.nonce
            }, function(response) {
                if (response.success) {
                    logToConsole('✅ Data exported successfully');
                    
                    // Create download link
                    const blob = new Blob([JSON.stringify(response.data, null, 2)], {type: 'application/json'});
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'wcflow-cards-export-' + new Date().toISOString().split('T')[0] + '.json';
                    a.click();
                    window.URL.revokeObjectURL(url);
                    
                    document.getElementById('quick-actions-results').innerHTML = 
                        '<div class="notice notice-success"><p>✅ Data exported and downloaded!</p></div>';
                } else {
                    logToConsole('❌ Failed to export data');
                }
            });
        }
        
        function runFullDiagnostic() {
            logToConsole('🔍 Running full diagnostic...');
            logToConsole('📊 Database Status: <?php echo $stats['total_cards']; ?> cards, <?php echo $stats['total_categories']; ?> categories');
            logToConsole('🔗 Relationships: <?php echo $stats['relationships']; ?> card-category links');
            logToConsole('📝 Post Type Exists: <?php echo post_type_exists('wcflow_card') ? 'Yes' : 'No'; ?>');
            logToConsole('📂 Taxonomy Exists: <?php echo taxonomy_exists('wcflow_card_category') ? 'Yes' : 'No'; ?>');
            logToConsole('🎯 Plugin Version: <?php echo WCFLOW_VERSION; ?>');
            logToConsole('🌐 WordPress Version: <?php echo get_bloginfo('version'); ?>');
            logToConsole('🛒 WooCommerce Version: <?php echo defined('WC_VERSION') ? WC_VERSION : 'Not installed'; ?>');
            logToConsole('✅ Full diagnostic complete');
        }
        
        // Auto-run diagnostic on page load
        jQuery(document).ready(function() {
            logToConsole('🎯 WCFlow Database Tools loaded');
            logToConsole('📊 Current status: <?php echo $stats['published_cards']; ?> published cards in <?php echo $stats['total_categories']; ?> categories');
        });
        </script>
        
        <style>
        .wcflow-admin-grid .postbox {
            margin-bottom: 0;
        }
        .wcflow-admin-grid .postbox .hndle {
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
        }
        .wcflow-admin-grid .inside {
            padding: 12px;
        }
        .status-publish { color: #46b450; }
        .status-draft { color: #ffb900; }
        .status-private { color: #dc3232; }
        </style>
        <?php
    }
    
    /**
     * Test database connection
     */
    public function test_database_connection() {
        check_ajax_referer('wcflow_admin_tools_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        try {
            $categories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'wcflow_card_category'");
            $cards = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wcflow_card' AND post_status = 'publish'");
            
            wp_send_json_success(array(
                'categories' => $categories,
                'cards' => $cards,
                'message' => 'Database connection successful'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Import sample data
     */
    public function import_sample_data() {
        check_ajax_referer('wcflow_admin_tools_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Force recreation of default data
        delete_option('wcflow_default_data_created');
        
        // Trigger creation
        if (function_exists('wcflow_create_default_categories_and_cards')) {
            wcflow_create_default_categories_and_cards();
            update_option('wcflow_default_data_created', 'yes');
        }
        
        // Clear cache
        delete_transient('wcflow_cards_cache');
        
        wp_send_json_success(array(
            'message' => 'Sample data imported successfully!'
        ));
    }
    
    /**
     * Export data
     */
    public function export_data() {
        check_ajax_referer('wcflow_admin_tools_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        // Get all cards with their categories
        $cards = $wpdb->get_results("
            SELECT p.ID, p.post_title, p.post_content, p.post_status, p.menu_order,
                   pm_price.meta_value as price,
                   GROUP_CONCAT(t.name) as categories
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_wcflow_price'
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'wcflow_card_category'
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = 'wcflow_card'
            GROUP BY p.ID
            ORDER BY p.menu_order ASC, p.post_title ASC
        ");
        
        // Get all categories
        $categories = $wpdb->get_results("
            SELECT t.term_id, t.name, t.slug, tt.description, tt.count,
                   tm_order.meta_value as display_order,
                   tm_desc.meta_value as category_description
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            LEFT JOIN {$wpdb->termmeta} tm_order ON t.term_id = tm_order.term_id AND tm_order.meta_key = '_wcflow_category_order'
            LEFT JOIN {$wpdb->termmeta} tm_desc ON t.term_id = tm_desc.term_id AND tm_desc.meta_key = '_wcflow_category_description'
            WHERE tt.taxonomy = 'wcflow_card_category'
            ORDER BY CAST(tm_order.meta_value AS UNSIGNED) ASC, t.name ASC
        ");
        
        $export_data = array(
            'export_date' => current_time('mysql'),
            'plugin_version' => WCFLOW_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : null,
            'categories' => $categories,
            'cards' => $cards,
            'stats' => array(
                'total_categories' => count($categories),
                'total_cards' => count($cards)
            )
        );
        
        wp_send_json_success($export_data);
    }
    
    /**
     * Clear all cache
     */
    public function clear_all_cache() {
        check_ajax_referer('wcflow_admin_tools_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Clear WCFlow cache
        delete_transient('wcflow_cards_cache');
        
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear any other relevant caches
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        wp_send_json_success(array(
            'message' => 'All cache cleared successfully'
        ));
    }
}

// Initialize admin tools
new WCFlow_Admin_Tools();