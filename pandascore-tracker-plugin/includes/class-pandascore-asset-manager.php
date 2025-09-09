<?php
/**
 * Asset Manager Class for PandaScore Tracker Plugin
 *
 * Handles CSS and JavaScript asset registration, enqueuing,
 * and localization for the plugin.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PandaScore Asset Manager Class
 *
 * Manages all CSS and JavaScript assets for the plugin
 */
class PandaScore_Asset_Manager extends PandaScore_Base_Component {

    /**
     * Plugin version for cache busting
     *
     * @var string
     */
    private $version = '1.2.0';

    /**
     * Registered assets
     *
     * @var array
     */
    private $registered_assets = array();

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ) );
    }

    /**
     * Register frontend assets
     */
    public function register_frontend_assets() {
        $this->register_styles();
        $this->register_scripts();
    }

    /**
     * Register admin assets
     */
    public function register_admin_assets() {
        $this->register_admin_styles();
    }

    /**
     * Register CSS styles
     */
    private function register_styles() {
        // Main plugin styles
        wp_register_style(
            'pandascore-custom-style',
            $this->get_asset_url( 'css/index.css' ),
            array(),
            $this->version
        );

        $this->registered_assets['styles']['pandascore-custom-style'] = array(
            'handle' => 'pandascore-custom-style',
            'type' => 'frontend',
            'dependencies' => array()
        );
    }

    /**
     * Register JavaScript files
     */
    private function register_scripts() {
        // Live tracker WebSocket script
        wp_register_script(
            'pandascore-live-tracker-js',
            $this->get_asset_url( 'js/live-tracker.js' ),
            array(),
            $this->version,
            true
        );

        // Timezone converter script
        wp_register_script(
            'pandascore-timezone-js',
            $this->get_asset_url( 'js/timezone-converter.js' ),
            array(),
            $this->version,
            true
        );

        $this->registered_assets['scripts']['pandascore-live-tracker-js'] = array(
            'handle' => 'pandascore-live-tracker-js',
            'type' => 'frontend',
            'dependencies' => array()
        );

        $this->registered_assets['scripts']['pandascore-timezone-js'] = array(
            'handle' => 'pandascore-timezone-js',
            'type' => 'frontend',
            'dependencies' => array()
        );
    }

    /**
     * Register admin styles
     */
    private function register_admin_styles() {
        // Admin-specific styles (if needed)
        wp_register_style(
            'pandascore-admin-style',
            $this->get_asset_url( 'css/admin.css' ),
            array(),
            $this->version
        );

        $this->registered_assets['styles']['pandascore-admin-style'] = array(
            'handle' => 'pandascore-admin-style',
            'type' => 'admin',
            'dependencies' => array()
        );
    }

    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style( 'pandascore-custom-style' );
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles() {
        wp_enqueue_style( 'pandascore-admin-style' );
    }

    /**
     * Enqueue live tracker script with localized data
     *
     * @param array $live_match_ids Array of live match IDs
     * @param string $api_key PandaScore API key
     */
    public function enqueue_live_tracker( $live_match_ids, $api_key ) {
        if ( empty( $live_match_ids ) ) {
            return;
        }

        wp_enqueue_script( 'pandascore-live-tracker-js' );
        
        wp_localize_script( 'pandascore-live-tracker-js', 'pandaScoreLiveTracker', array(
            'apiKey'       => $api_key,
            'matchIds'     => array_unique( $live_match_ids ),
            'websocketUrl' => 'wss://websocket.pandascore.co/ws',
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'pandascore_live_tracker' )
        ) );
    }

    /**
     * Enqueue timezone converter script
     */
    public function enqueue_timezone_converter() {
        wp_enqueue_script( 'pandascore-timezone-js' );
    }

    /**
     * Get internal CSS styles for inline output
     *
     * @return string CSS styles wrapped in <style> tags
     */
    public function get_internal_styles() {
        $css_file_path = $this->get_plugin_path() . 'css/index.css';

        if ( ! file_exists( $css_file_path ) ) {
            return '<style>/* PandaScore Tracker: CSS file not found */</style>';
        }

        $css_content = file_get_contents( $css_file_path );

        if ( false === $css_content ) {
            return '<style>/* PandaScore Tracker: Could not read CSS file */</style>';
        }

        return '<style>' . $css_content . '</style>';
    }

    /**
     * Check if asset is registered
     *
     * @param string $handle Asset handle
     * @param string $type Asset type ('styles' or 'scripts')
     * @return bool True if registered, false otherwise
     */
    public function is_asset_registered( $handle, $type = 'styles' ) {
        return isset( $this->registered_assets[ $type ][ $handle ] );
    }

    /**
     * Get registered assets
     *
     * @param string $type Optional. Asset type ('styles' or 'scripts')
     * @return array Registered assets
     */
    public function get_registered_assets( $type = null ) {
        if ( null === $type ) {
            return $this->registered_assets;
        }

        return isset( $this->registered_assets[ $type ] ) ? $this->registered_assets[ $type ] : array();
    }

    /**
     * Deregister asset
     *
     * @param string $handle Asset handle
     * @param string $type Asset type ('styles' or 'scripts')
     */
    public function deregister_asset( $handle, $type = 'styles' ) {
        if ( 'styles' === $type ) {
            wp_deregister_style( $handle );
        } elseif ( 'scripts' === $type ) {
            wp_deregister_script( $handle );
        }

        unset( $this->registered_assets[ $type ][ $handle ] );
    }

    /**
     * Add inline style to registered stylesheet
     *
     * @param string $handle Stylesheet handle
     * @param string $css CSS code to add
     */
    public function add_inline_style( $handle, $css ) {
        wp_add_inline_style( $handle, $css );
    }

    /**
     * Add inline script to registered script
     *
     * @param string $handle Script handle
     * @param string $js JavaScript code to add
     * @param string $position Position ('before' or 'after')
     */
    public function add_inline_script( $handle, $js, $position = 'after' ) {
        wp_add_inline_script( $handle, $js, $position );
    }

    /**
     * Get asset URL
     *
     * @param string $asset_path Relative path to asset
     * @return string Full URL to asset
     */
    private function get_asset_url( $asset_path ) {
        return $this->get_plugin_url() . ltrim( $asset_path, '/' );
    }

    /**
     * Check if we're on a page that needs plugin assets
     *
     * @return bool True if assets are needed, false otherwise
     */
    public function should_load_assets() {
        global $post;

        // Always load in admin
        if ( $this->is_admin() ) {
            return true;
        }

        // Check if current post/page contains the shortcode
        if ( $post && has_shortcode( $post->post_content, 'pandascore_tracker' ) ) {
            return true;
        }

        // Check if any widget contains the shortcode
        if ( $this->check_widgets_for_shortcode() ) {
            return true;
        }

        return false;
    }

    /**
     * Check if any active widget contains the shortcode
     *
     * @return bool True if shortcode found in widgets, false otherwise
     */
    private function check_widgets_for_shortcode() {
        $sidebars = wp_get_sidebars_widgets();

        if ( empty( $sidebars ) ) {
            return false;
        }

        foreach ( $sidebars as $sidebar_id => $widget_ids ) {
            if ( empty( $widget_ids ) || 'wp_inactive_widgets' === $sidebar_id ) {
                continue;
            }

            foreach ( $widget_ids as $widget_id ) {
                $widget_content = $this->get_widget_content( $widget_id );
                if ( $widget_content && has_shortcode( $widget_content, 'pandascore_tracker' ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get widget content for shortcode checking
     *
     * @param string $widget_id Widget ID
     * @return string Widget content
     */
    private function get_widget_content( $widget_id ) {
        // This is a simplified check - in a real implementation,
        // you might need to check specific widget types
        $widget_options = get_option( 'widget_text' );
        
        if ( is_array( $widget_options ) ) {
            foreach ( $widget_options as $widget_data ) {
                if ( isset( $widget_data['text'] ) ) {
                    return $widget_data['text'];
                }
            }
        }

        return '';
    }

    /**
     * Get asset dependencies
     *
     * @param string $handle Asset handle
     * @param string $type Asset type ('styles' or 'scripts')
     * @return array Asset dependencies
     */
    public function get_asset_dependencies( $handle, $type = 'styles' ) {
        if ( isset( $this->registered_assets[ $type ][ $handle ]['dependencies'] ) ) {
            return $this->registered_assets[ $type ][ $handle ]['dependencies'];
        }

        return array();
    }

    /**
     * Set asset dependencies
     *
     * @param string $handle Asset handle
     * @param array  $dependencies Asset dependencies
     * @param string $type Asset type ('styles' or 'scripts')
     */
    public function set_asset_dependencies( $handle, $dependencies, $type = 'styles' ) {
        if ( isset( $this->registered_assets[ $type ][ $handle ] ) ) {
            $this->registered_assets[ $type ][ $handle ]['dependencies'] = $dependencies;
        }
    }
}
