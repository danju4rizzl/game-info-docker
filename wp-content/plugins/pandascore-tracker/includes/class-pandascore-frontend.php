<?php
/**
 * Frontend Display Class for PandaScore Tracker Plugin
 *
 * Handles shortcode processing, asset enqueuing, and frontend
 * display logic for the plugin.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PandaScore Frontend Display Class
 *
 * Handles all frontend functionality including shortcodes
 */
class PandaScore_Frontend extends PandaScore_Base_Component {

    /**
     * API handler instance
     *
     * @var PandaScore_API_Handler
     */
    private $api_handler;

    /**
     * Match renderer instance
     *
     * @var PandaScore_Match_Renderer
     */
    private $match_renderer;

    /**
     * Asset manager instance
     *
     * @var PandaScore_Asset_Manager
     */
    private $asset_manager;

    /**
     * Live match IDs for current request
     *
     * @var array
     */
    private $live_match_ids = array();

    /**
     * Constructor
     *
     * @param PandaScore_API_Handler    $api_handler API handler instance
     * @param PandaScore_Match_Renderer $match_renderer Match renderer instance
     * @param PandaScore_Asset_Manager  $asset_manager Asset manager instance
     */
    public function __construct( $api_handler, $match_renderer, $asset_manager ) {
        parent::__construct();
        $this->api_handler = $api_handler;
        $this->match_renderer = $match_renderer;
        $this->asset_manager = $asset_manager;
    }

    /**
     * Initialize frontend functionality
     */
    public function init() {
        add_shortcode( 'pandascore_tracker', array( $this, 'handle_shortcode' ) );
    }

    /**
     * Handle the pandascore_tracker shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output for the shortcode
     */
    public function handle_shortcode( $atts ) {
        // Sanitize and validate attributes
        $atts = $this->sanitize_shortcode_attributes( $atts );

        // Check if API key is configured
        if ( ! $this->is_api_key_valid() ) {
            return $this->render_error_message( 'PandaScore API key not configured. Please check plugin settings.' );
        }

        // Reset live match IDs for this shortcode instance
        $this->live_match_ids = array();

        // Enqueue frontend styles
        $this->asset_manager->enqueue_frontend_styles();

        // Build HTML output
        $html = $this->build_shortcode_output( $atts );

        // Handle live tracking if needed
        $this->handle_live_tracking();

        // Always enqueue timezone converter
        $this->asset_manager->enqueue_timezone_converter();

        return $html;
    }

    /**
     * Build the complete HTML output for the shortcode
     *
     * @param array $atts Sanitized shortcode attributes
     * @return string Complete HTML output
     */
    private function build_shortcode_output( $atts ) {
        $align_class = $this->get_alignment_class( $atts['align'] );
        
        // Start with internal styles and main container
        $html = $this->asset_manager->get_internal_styles();
        $html .= '<div class="pandascore-tracker ' . $align_class . '">';

        // Add content based on type
        if ( 'live' === $atts['type'] || 'mixed' === $atts['type'] ) {
            $html .= $this->render_live_matches( $atts['game'], $atts['limit'] );
        }

        if ( 'upcoming' === $atts['type'] || 'mixed' === $atts['type'] ) {
            $html .= $this->render_upcoming_matches( $atts['game'], $atts['limit'] );
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render live matches section
     *
     * @param string $game Game type
     * @param int    $limit Number of matches to display
     * @return string HTML for live matches
     */
    private function render_live_matches( $game, $limit ) {
        $live_matches = $this->api_handler->fetch_live_matches( $game, $limit );

        if ( is_wp_error( $live_matches ) ) {
            $this->log_error( 'Failed to fetch live matches', $live_matches->get_error_message() );
            return '';
        }

        if ( empty( $live_matches ) ) {
            return '';
        }

        // Store live match IDs for WebSocket tracking
        foreach ( $live_matches as $match ) {
            if ( isset( $match['id'] ) ) {
                $this->live_match_ids[] = $match['id'];
            }
        }

        return $this->match_renderer->render_live_matches_section( $live_matches );
    }

    /**
     * Render upcoming matches section
     *
     * @param string $game Game type
     * @param int    $limit Number of matches to display
     * @return string HTML for upcoming matches
     */
    private function render_upcoming_matches( $game, $limit ) {
        $upcoming_matches = $this->api_handler->fetch_upcoming_matches( $game, $limit );

        if ( is_wp_error( $upcoming_matches ) ) {
            $this->log_error( 'Failed to fetch upcoming matches', $upcoming_matches->get_error_message() );
            return $this->match_renderer->render_error( $upcoming_matches );
        }

        return $this->match_renderer->render_upcoming_matches_section( $upcoming_matches );
    }

    /**
     * Handle live tracking setup
     */
    private function handle_live_tracking() {
        if ( ! empty( $this->live_match_ids ) ) {
            $api_key = $this->get_api_key();
            $this->asset_manager->enqueue_live_tracker( $this->live_match_ids, $api_key );
        }
    }

    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string HTML for error display
     */
    private function render_error_message( $message ) {
        return '<div class="pandascore-error">' . $this->esc_html_safe( $message ) . '</div>';
    }

    /**
     * Get live match IDs from current request
     *
     * @return array Array of live match IDs
     */
    public function get_live_match_ids() {
        return $this->live_match_ids;
    }

    /**
     * Reset live match IDs
     */
    public function reset_live_match_ids() {
        $this->live_match_ids = array();
    }

    /**
     * Check if shortcode should display content
     *
     * @param array $atts Shortcode attributes
     * @return bool True if content should be displayed
     */
    private function should_display_content( $atts ) {
        // Always display unless there are specific conditions to hide
        return true;
    }

    /**
     * Get shortcode cache key
     *
     * @param array $atts Shortcode attributes
     * @return string Cache key for shortcode output
     */
    private function get_shortcode_cache_key( $atts ) {
        return $this->generate_cache_key( 'shortcode', array(
            $atts['game'],
            $atts['type'],
            $atts['limit'],
            $atts['align']
        ) );
    }

    /**
     * Validate game parameter
     *
     * @param string $game Game parameter
     * @return bool True if valid game, false otherwise
     */
    private function is_valid_game( $game ) {
        $valid_games = array( 'lol', 'valorant', 'csgo', 'dota2', 'overwatch', 'rocket-league' );
        return in_array( strtolower( $game ), $valid_games, true );
    }

    /**
     * Get default shortcode attributes
     *
     * @return array Default attributes
     */
    public function get_default_attributes() {
        return array(
            'game'  => 'lol',
            'limit' => 5,
            'align' => 'center',
            'type'  => 'mixed'
        );
    }

    /**
     * Process shortcode attributes with validation
     *
     * @param array $atts Raw shortcode attributes
     * @return array Processed attributes
     */
    private function process_shortcode_attributes( $atts ) {
        $defaults = $this->get_default_attributes();
        $atts = shortcode_atts( $defaults, $atts, 'pandascore_tracker' );

        // Additional validation
        if ( ! $this->is_valid_game( $atts['game'] ) ) {
            $atts['game'] = $defaults['game'];
        }

        return $atts;
    }

    /**
     * Add debug information to output (if WP_DEBUG is enabled)
     *
     * @param array $atts Shortcode attributes
     * @return string Debug HTML
     */
    private function get_debug_info( $atts ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return '';
        }

        $debug_info = array(
            'Game' => $atts['game'],
            'Type' => $atts['type'],
            'Limit' => $atts['limit'],
            'Align' => $atts['align'],
            'API Key Set' => $this->is_api_key_valid() ? 'Yes' : 'No',
            'Live Matches' => count( $this->live_match_ids )
        );

        $html = '<!-- PandaScore Tracker Debug Info: ';
        foreach ( $debug_info as $key => $value ) {
            $html .= $key . ': ' . $value . '; ';
        }
        $html .= '-->';

        return $html;
    }

    /**
     * Handle AJAX requests for live updates
     */
    public function handle_ajax_live_update() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'pandascore_live_tracker' ) ) {
            wp_die( 'Security check failed' );
        }

        $match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : 0;
        
        if ( ! $match_id ) {
            wp_send_json_error( 'Invalid match ID' );
        }

        // This would typically fetch updated match data
        // For now, just return success
        wp_send_json_success( array( 'match_id' => $match_id ) );
    }

    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        add_action( 'wp_ajax_pandascore_live_update', array( $this, 'handle_ajax_live_update' ) );
        add_action( 'wp_ajax_nopriv_pandascore_live_update', array( $this, 'handle_ajax_live_update' ) );
    }
}
