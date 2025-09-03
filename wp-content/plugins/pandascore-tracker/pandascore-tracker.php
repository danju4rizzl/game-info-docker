<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode with component-based architecture.
Version: 2.0.0
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'PANDASCORE_TRACKER_VERSION', '2.0.0' );
define( 'PANDASCORE_TRACKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PANDASCORE_TRACKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class - Coordinates components and handles WordPress integration
 */
class PandaScore_Tracker_Plugin {

    /**
     * Component instances
     */
    private $api_handler;
    private $renderer;
    private $settings;
    private $live_scores;
    private $upcoming_matches;

    /**
     * Constructor - Initialize plugin
     */
    public function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Load required component files
     */
    private function load_dependencies() {
        require_once PANDASCORE_TRACKER_PLUGIN_DIR . 'includes/class-pandascore-base-component.php';
        require_once PANDASCORE_TRACKER_PLUGIN_DIR . 'includes/class-pandascore-api-handler.php';
        require_once PANDASCORE_TRACKER_PLUGIN_DIR . 'includes/class-pandascore-renderer.php';
        require_once PANDASCORE_TRACKER_PLUGIN_DIR . 'includes/class-pandascore-settings.php';
        require_once PANDASCORE_TRACKER_PLUGIN_DIR . 'includes/class-pandascore-live-scores.php';
        require_once PANDASCORE_TRACKER_PLUGIN_DIR . 'includes/class-pandascore-upcoming-matches.php';
    }

    /**
     * Initialize component instances
     */
    private function init_components() {
        $this->api_handler = new PandaScore_API_Handler();
        $this->renderer = new PandaScore_Renderer();
        $this->settings = new PandaScore_Settings();
        $this->live_scores = new PandaScore_Live_Scores();
        $this->upcoming_matches = new PandaScore_Upcoming_Matches();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_shortcode( 'pandascore_tracker', array( $this, 'shortcode_handler' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Plugin lifecycle hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Load plugin text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'pandascore-tracker',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Register base CSS
        wp_register_style(
            'pandascore-base-style',
            PANDASCORE_TRACKER_PLUGIN_URL . 'assets/css/pandascore-base.css',
            array(),
            PANDASCORE_TRACKER_VERSION
        );

        // Register JavaScript for live tracking
        wp_register_script(
            'pandascore-live-tracker',
            PANDASCORE_TRACKER_PLUGIN_URL . 'assets/js/live-tracker.js',
            array(),
            PANDASCORE_TRACKER_VERSION,
            true
        );
    }

    /**
     * Get default shortcode attributes from settings
     *
     * @return array Default attributes
     */
    private function get_default_shortcode_attributes() {
        return array(
            'game'  => $this->settings->get_setting( 'default_game', 'valorant' ),
            'limit' => $this->settings->get_setting( 'default_limit', 5 ),
            'align' => $this->settings->get_setting( 'default_align', 'center' ),
            'type'  => $this->settings->get_setting( 'default_type', 'mixed' )
        );
    }

    /**
     * Handle shortcode rendering
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function shortcode_handler( $atts ) {
        // Get default attributes from settings
        $defaults = $this->get_default_shortcode_attributes();

        $atts = shortcode_atts( $defaults, $atts, 'pandascore_tracker' );

        // Sanitize attributes
        $game = sanitize_text_field( $atts['game'] );
        $limit = max( 1, min( 50, intval( $atts['limit'] ) ) );
        $align = in_array( $atts['align'], array( 'left', 'center', 'right' ) ) ? $atts['align'] : 'center';
        $type = in_array( $atts['type'], array( 'live', 'upcoming', 'mixed' ) ) ? $atts['type'] : 'mixed';

        // Debug output (only if WP_DEBUG is enabled)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $debug_info = array(
                'defaults_from_settings' => $defaults,
                'final_attributes' => array(
                    'game' => $game,
                    'limit' => $limit,
                    'align' => $align,
                    'type' => $type
                )
            );
            error_log( '[PandaScore Debug] Shortcode attributes: ' . wp_json_encode( $debug_info ) );
        }

        // Enqueue base styles
        wp_enqueue_style( 'pandascore-base-style' );

        // Reset live data for this instance
        $this->live_scores->reset_live_data();

        // Build container
        $container_style = "text-align: {$align};";
        $html = '<div class="pandascore-tracker" style="' . esc_attr( $container_style ) . '">';

        // Render sections based on type
        if ( $type === 'live' || $type === 'mixed' ) {
            $html .= $this->live_scores->render_live_matches( $limit, $game );
        }

        if ( $type === 'upcoming' || $type === 'mixed' ) {
            $html .= $this->upcoming_matches->render_upcoming_matches( $game, $limit );
        }

        // Handle JavaScript for live matches
        if ( $this->live_scores->has_live_matches() ) {
            wp_enqueue_script( 'pandascore-live-tracker' );
            wp_localize_script( 'pandascore-live-tracker', 'pandaScoreLiveTracker', array(
                'apiKey'    => $this->settings->get_setting( 'api_key', '' ),
                'matchIds'  => $this->live_scores->get_live_match_ids(),
                'wsMatches' => $this->live_scores->get_websocket_matches(),
            ) );
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Plugin activation hook
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'api_key'        => '',
            'default_game'   => 'valorant',
            'default_limit'  => 5,
            'default_type'   => 'mixed',
            'default_align'  => 'center',
            'cache_enabled'  => true,
            'cache_duration' => 300,
        );

        add_option( 'pandascore_tracker_options', $default_options );

        // Clear any existing cache
        if ( $this->api_handler ) {
            $this->api_handler->clear_all_cache();
        }
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate() {
        // Clear cache on deactivation
        $this->api_handler->clear_all_cache();
    }

    /**
     * Get plugin instance (singleton pattern)
     *
     * @return PandaScore_Tracker_Plugin
     */
    public static function get_instance() {
        static $instance = null;

        if ( null === $instance ) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * Get component instance
     *
     * @param string $component Component name
     * @return object|null Component instance or null if not found
     */
    public function get_component( $component ) {
        switch ( $component ) {
            case 'api_handler':
                return $this->api_handler;
            case 'renderer':
                return $this->renderer;
            case 'settings':
                return $this->settings;
            case 'live_scores':
                return $this->live_scores;
            case 'upcoming_matches':
                return $this->upcoming_matches;
            default:
                return null;
        }
    }

}

// Initialize the plugin at the right time
add_action( 'plugins_loaded', array( 'PandaScore_Tracker_Plugin', 'get_instance' ) );