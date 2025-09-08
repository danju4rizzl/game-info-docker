<?php
/**
 * Base Component Class for PandaScore Tracker Plugin
 *
 * This abstract class provides shared functionality for all plugin components,
 * following WordPress coding standards and SOLID principles.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Abstract base class for all PandaScore Tracker components
 *
 * Provides shared functionality including API key management,
 * common utilities, and standardized error handling.
 */
abstract class PandaScore_Base_Component {

    /**
     * Plugin options key
     *
     * @var string
     */
    protected $option_key = 'pandascore_tracker_options';

    /**
     * Constructor
     */
    public function __construct() {
        // Base constructor - can be extended by child classes
    }

    /**
     * Get the PandaScore API key from WordPress options
     *
     * @return string The API key, or empty string if not set
     */
    protected function get_api_key() {
        $opts = get_option( $this->option_key );
        return isset( $opts['api_key'] ) ? trim( $opts['api_key'] ) : '';
    }

    /**
     * Validate API key exists and is not empty
     *
     * @return bool True if API key is valid, false otherwise
     */
    protected function is_api_key_valid() {
        $api_key = $this->get_api_key();
        return ! empty( $api_key );
    }

    /**
     * Get plugin options
     *
     * @return array Plugin options array
     */
    protected function get_plugin_options() {
        return get_option( $this->option_key, array() );
    }

    /**
     * Update plugin options
     *
     * @param array $options Options to update
     * @return bool True on success, false on failure
     */
    protected function update_plugin_options( $options ) {
        return update_option( $this->option_key, $options );
    }

    /**
     * Log error message using WordPress error logging
     *
     * @param string $message Error message to log
     * @param string $context Additional context for the error
     */
    protected function log_error( $message, $context = '' ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $log_message = 'PandaScore Tracker: ' . $message;
            if ( ! empty( $context ) ) {
                $log_message .= ' | Context: ' . $context;
            }
            error_log( $log_message );
        }
    }

    /**
     * Sanitize and validate shortcode attributes
     *
     * @param array $atts Raw shortcode attributes
     * @return array Sanitized attributes
     */
    protected function sanitize_shortcode_attributes( $atts ) {
        $defaults = array(
            'game'  => 'lol',
            'limit' => 5,
            'align' => 'center',
            'type'  => 'mixed'
        );

        $atts = shortcode_atts( $defaults, $atts, 'pandascore_tracker' );

        // Sanitize individual attributes
        $atts['game']  = sanitize_text_field( $atts['game'] );
        $atts['limit'] = absint( $atts['limit'] );
        $atts['align'] = in_array( $atts['align'], array( 'left', 'center', 'right' ) ) ? $atts['align'] : 'center';
        $atts['type']  = in_array( $atts['type'], array( 'live', 'upcoming', 'mixed' ) ) ? $atts['type'] : 'mixed';

        // Ensure reasonable limits
        if ( $atts['limit'] < 1 ) {
            $atts['limit'] = 1;
        } elseif ( $atts['limit'] > 50 ) {
            $atts['limit'] = 50;
        }

        return $atts;
    }

    /**
     * Generate CSS class for alignment
     *
     * @param string $align Alignment value (left, center, right)
     * @return string CSS class name
     */
    protected function get_alignment_class( $align ) {
        return 'align-' . esc_attr( $align );
    }

    /**
     * Check if a string is a valid URL
     *
     * @param string $url URL to validate
     * @return bool True if valid URL, false otherwise
     */
    protected function is_valid_url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    /**
     * Escape HTML attributes safely
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    protected function esc_attr_safe( $text ) {
        return esc_attr( $text );
    }

    /**
     * Escape HTML content safely
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    protected function esc_html_safe( $text ) {
        return esc_html( $text );
    }

    /**
     * Generate a unique cache key for transients
     *
     * @param string $base_key Base key for the cache
     * @param array  $params   Additional parameters to include in key
     * @return string Generated cache key
     */
    protected function generate_cache_key( $base_key, $params = array() ) {
        $key_parts = array( 'pandascore', $base_key );
        
        foreach ( $params as $param ) {
            $key_parts[] = sanitize_key( $param );
        }
        
        return implode( '_', $key_parts );
    }

    /**
     * Get formatted error message for display
     *
     * @param WP_Error $error WordPress error object
     * @return string Formatted error message
     */
    protected function format_error_message( $error ) {
        if ( ! is_wp_error( $error ) ) {
            return 'Unknown error occurred.';
        }

        $message = $error->get_error_message();
        return 'Error: ' . esc_html( $message );
    }

    /**
     * Check if we're in WordPress admin area
     *
     * @return bool True if in admin, false otherwise
     */
    protected function is_admin() {
        return is_admin();
    }

    /**
     * Check if current user has required capability
     *
     * @param string $capability Capability to check
     * @return bool True if user has capability, false otherwise
     */
    protected function current_user_can( $capability ) {
        return current_user_can( $capability );
    }

    /**
     * Get plugin directory URL
     *
     * @return string Plugin directory URL
     */
    protected function get_plugin_url() {
        return plugin_dir_url( dirname( __FILE__ ) );
    }

    /**
     * Get plugin directory path
     *
     * @return string Plugin directory path
     */
    protected function get_plugin_path() {
        return plugin_dir_path( dirname( __FILE__ ) );
    }
}
