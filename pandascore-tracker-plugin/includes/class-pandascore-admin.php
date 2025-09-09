<?php
/**
 * Admin Interface Class for PandaScore Tracker Plugin
 *
 * Handles all WordPress admin functionality including settings pages,
 * menu registration, cache management, and admin notices.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PandaScore Admin Interface Class
 *
 * Handles all admin-related functionality
 */
class PandaScore_Admin extends PandaScore_Base_Component {

    /**
     * Cache manager instance
     *
     * @var PandaScore_Cache_Manager
     */
    private $cache_manager;

    /**
     * Asset manager instance
     *
     * @var PandaScore_Asset_Manager
     */
    private $asset_manager;

    /**
     * Constructor
     *
     * @param PandaScore_Cache_Manager $cache_manager Cache manager instance
     * @param PandaScore_Asset_Manager $asset_manager Asset manager instance
     */
    public function __construct( $cache_manager = null, $asset_manager = null ) {
        parent::__construct();
        $this->cache_manager = $cache_manager;
        $this->asset_manager = $asset_manager;
    }

    /**
     * Initialize admin functionality
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_cache_clear' ) );
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            'PandaScore Tracker',
            'PandaScore Tracker',
            'manage_options',
            'pandascore-tracker',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( $this->option_key, $this->option_key, array( $this, 'sanitize_settings' ) );
        
        add_settings_section(
            'pandascore_main',
            'PandaScore Settings',
            array( $this, 'render_main_section' ),
            'pandascore-tracker'
        );
        
        add_settings_field(
            'api_key',
            'API Key',
            array( $this, 'render_api_key_field' ),
            'pandascore-tracker',
            'pandascore_main'
        );
    }

    /**
     * Sanitize settings input
     *
     * @param array $input Raw input data
     * @return array Sanitized settings
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        
        if ( isset( $input['api_key'] ) ) {
            $sanitized['api_key'] = sanitize_text_field( trim( $input['api_key'] ) );
        }
        
        return $sanitized;
    }

    /**
     * Handle cache clear requests
     */
    public function handle_cache_clear() {
        if ( ! isset( $_POST['pandascore_clear_cache'] ) ||
             ! isset( $_POST['pandascore_cache_nonce'] ) ||
             ! wp_verify_nonce( $_POST['pandascore_cache_nonce'], 'pandascore_clear_cache' ) ||
             ! $this->current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( $this->cache_manager ) {
            $cleared_count = $this->cache_manager->clear_all_caches();
            
            // Store success message for display
            set_transient( 'pandascore_admin_notice', array(
                'type' => 'success',
                'message' => sprintf( 'Cache cleared successfully! Removed %d cached entries.', $cleared_count )
            ), 30 );
        } else {
            set_transient( 'pandascore_admin_notice', array(
                'type' => 'error',
                'message' => 'Cache manager not available.'
            ), 30 );
        }

        // Redirect to prevent resubmission
        wp_redirect( admin_url( 'options-general.php?page=pandascore-tracker' ) );
        exit;
    }

    /**
     * Display admin notices
     */
    public function display_admin_notices() {
        $notice = get_transient( 'pandascore_admin_notice' );
        
        if ( $notice && is_array( $notice ) ) {
            $type = isset( $notice['type'] ) ? $notice['type'] : 'info';
            $message = isset( $notice['message'] ) ? $notice['message'] : '';
            
            if ( ! empty( $message ) ) {
                printf(
                    '<div class="notice notice-%s is-dismissible"><p><strong>PandaScore Tracker:</strong> %s</p></div>',
                    esc_attr( $type ),
                    esc_html( $message )
                );
            }
            
            delete_transient( 'pandascore_admin_notice' );
        }
    }

    /**
     * Render main settings section
     */
    public function render_main_section() {
        echo '<p>Configure your PandaScore API settings below.</p>';
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $options = $this->get_plugin_options();
        $value = isset( $options['api_key'] ) ? $options['api_key'] : '';
        
        printf(
            '<input type="text" name="%s[api_key]" value="%s" class="pandascore-api-key-input" placeholder="Enter your PandaScore API key">',
            esc_attr( $this->option_key ),
            esc_attr( $value )
        );
        
        echo '<p class="description">Get your API key from <a href="https://app.pandascore.co/dashboard/main" target="_blank">PandaScore</a>.</p>';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! $this->current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        // Enqueue admin styles
        if ( $this->asset_manager ) {
            $this->asset_manager->enqueue_admin_styles();
        }

        ?>
        <div class="wrap">
            <h1>PandaScore Tracker</h1>
            
            <?php $this->render_settings_form(); ?>
            <?php $this->render_cache_management(); ?>
            <?php $this->render_usage_documentation(); ?>
            <?php $this->render_system_info(); ?>
        </div>
        <?php
    }

    /**
     * Render settings form
     */
    private function render_settings_form() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( $this->option_key );
            do_settings_sections( 'pandascore-tracker' );
            submit_button();
            ?>
        </form>
        <?php
    }

    /**
     * Render cache management section
     */
    private function render_cache_management() {
        ?>
        <h3>Cache Management</h3>
        <p>Clear cached data to refresh league information and match data immediately.</p>
        
        <?php if ( $this->cache_manager ): ?>
            <?php $stats = $this->cache_manager->get_cache_stats(); ?>
            <p><strong>Cache Statistics:</strong></p>
            <ul>
                <li>Plugin cache entries: <?php echo intval( $stats['plugin_transients'] ); ?></li>
                <li>Estimated size: <?php echo size_format( $stats['estimated_size'] ); ?></li>
                <li>Cache enabled: <?php echo $this->cache_manager->is_cache_enabled() ? 'Yes' : 'No'; ?></li>
            </ul>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field( 'pandascore_clear_cache', 'pandascore_cache_nonce' ); ?>
            <input type="submit" 
                   name="pandascore_clear_cache" 
                   class="button button-secondary" 
                   value="Clear Cache" 
                   onclick="return confirm('Are you sure you want to clear all cached data? This will refresh league and match information.');">
        </form>
        <?php
    }

    /**
     * Render usage documentation
     */
    private function render_usage_documentation() {
        ?>
        <h3>Shortcode Usage</h3>
        <p><strong>Basic usage:</strong> <code>[pandascore_tracker game="valorant" limit="5" align="right"]</code></p>
        <p><strong>Live matches:</strong> <code>[pandascore_tracker type="live" limit="5"]</code></p>
        <p><strong>Mixed (live + upcoming):</strong> <code>[pandascore_tracker type="mixed" game="valorant" limit="10"]</code></p>

        <h4>Parameters:</h4>
        <ul>
            <li><strong>game:</strong> Game type (valorant, lol, csgo, dota2, etc.)</li>
            <li><strong>limit:</strong> Number of matches to display (default: 5, max: 50)</li>
            <li><strong>align:</strong> Text alignment (left, center, right) (default: center)</li>
            <li><strong>type:</strong> Match type - "upcoming", "live", or "mixed" (default: mixed)</li>
        </ul>

        <h4>League Filtering (LoL only):</h4>
        <p>When using <code>game="lol"</code>, matches are automatically filtered to show only these leagues:</p>
        <ul>
            <li><strong>LCK</strong> - League of Legends Champions Korea</li>
            <li><strong>LTA North</strong> - League of Legends EMEA Championship North</li>
            <li><strong>LTA South</strong> - League of Legends EMEA Championship South</li>
            <li><strong>LPL</strong> - League of Legends Pro League</li>
            <li><strong>LEC</strong> - League of Legends European Championship</li>
        </ul>
        <p><em>Note: League IDs are fetched dynamically from PandaScore API and cached for 24 hours. Use "Clear Cache" above to refresh immediately.</em></p>
        <?php
    }

    /**
     * Render system information
     */
    private function render_system_info() {
        ?>
        <h3>System Information</h3>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong>Plugin Version:</strong></td>
                    <td>1.2.0</td>
                </tr>
                <tr>
                    <td><strong>WordPress Version:</strong></td>
                    <td><?php echo get_bloginfo( 'version' ); ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>API Key Status:</strong></td>
                    <td><?php echo $this->is_api_key_valid() ? '<span style="color: green;">✓ Set</span>' : '<span style="color: red;">✗ Not Set</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong>Object Cache:</strong></td>
                    <td><?php echo wp_using_ext_object_cache() ? 'Enabled' : 'Disabled'; ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}
