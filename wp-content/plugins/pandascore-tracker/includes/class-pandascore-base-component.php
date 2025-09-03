<?php
/**
 * Base Component Class for PandaScore Tracker Plugin
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Abstract base class for all PandaScore components
 * 
 * Provides shared functionality including API key management,
 * common API requests, and utility methods.
 */
abstract class PandaScore_Base_Component {

    /**
     * Plugin options key
     *
     * @var string
     */
    protected $option_key = 'pandascore_tracker_options';

    /**
     * Cache group for transients
     *
     * @var string
     */
    protected $cache_group = 'pandascore_tracker';

    /**
     * Default cache expiration (5 minutes)
     *
     * @var int
     */
    protected $cache_expiration = 300;

    /**
     * Get cache expiration from settings
     *
     * @return int Cache expiration in seconds
     */
    protected function get_cache_expiration() {
        $opts = get_option( $this->option_key );
        $cache_enabled = isset( $opts['cache_enabled'] ) ? $opts['cache_enabled'] : true;

        if ( ! $cache_enabled ) {
            return 0; // No caching
        }

        $cache_duration = isset( $opts['cache_duration'] ) ? intval( $opts['cache_duration'] ) : 300;
        return max( 60, $cache_duration ); // Minimum 1 minute
    }

    /**
     * Get API key from WordPress options
     *
     * @return string API key or empty string if not set
     */
    protected function get_api_key() {
        $opts = get_option( $this->option_key );
        return isset( $opts['api_key'] ) ? trim( $opts['api_key'] ) : '';
    }

    /**
     * Make authenticated API request to PandaScore
     *
     * @param string $endpoint API endpoint (without base URL)
     * @param array  $query_args Query parameters
     * @param bool   $use_cache Whether to use caching
     * @return array|WP_Error API response data or error
     */
    protected function make_api_request( $endpoint, $query_args = array(), $use_cache = true ) {
        $api_key = $this->get_api_key();
        if ( ! $api_key ) {
            return new WP_Error( 'no_api_key', __( 'PandaScore API key not configured.', 'pandascore-tracker' ) );
        }

        // Create cache key
        $cache_key = $this->get_cache_key( $endpoint, $query_args );
        
        // Try to get from cache first
        if ( $use_cache ) {
            $cached_data = get_transient( $cache_key );
            if ( false !== $cached_data ) {
                return $cached_data;
            }
        }

        // Build URL
        $url = add_query_arg( $query_args, "https://api.pandascore.co/{$endpoint}" );

        // Make request
        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Accept'        => 'application/json',
            ),
        ) );

        // Handle errors
        if ( is_wp_error( $response ) ) {
            $this->log_error( 'API Request Failed', array(
                'endpoint' => $endpoint,
                'error'    => $response->get_error_message(),
            ) );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            $error_message = sprintf( 
                __( 'PandaScore API returned HTTP %d', 'pandascore-tracker' ), 
                $code 
            );
            $this->log_error( 'API HTTP Error', array(
                'endpoint' => $endpoint,
                'code'     => $code,
                'response' => wp_remote_retrieve_body( $response ),
            ) );
            return new WP_Error( 'api_error', $error_message );
        }

        // Parse JSON
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->log_error( 'JSON Parse Error', array(
                'endpoint' => $endpoint,
                'error'    => json_last_error_msg(),
                'body'     => substr( $body, 0, 500 ),
            ) );
            return new WP_Error( 'json_error', __( 'Invalid JSON response from API', 'pandascore-tracker' ) );
        }

        // Cache successful response
        if ( $use_cache && ! empty( $data ) ) {
            $cache_expiration = $this->get_cache_expiration();
            if ( $cache_expiration > 0 ) {
                set_transient( $cache_key, $data, $cache_expiration );
            }
        }

        return $data;
    }

    /**
     * Generate cache key for API requests
     *
     * @param string $endpoint API endpoint
     * @param array  $query_args Query parameters
     * @return string Cache key
     */
    protected function get_cache_key( $endpoint, $query_args = array() ) {
        $key_data = array(
            'endpoint' => $endpoint,
            'args'     => $query_args,
            'version'  => '2.0',
        );
        return $this->cache_group . '_' . md5( serialize( $key_data ) );
    }

    /**
     * Clear cache for specific endpoint or all cache
     *
     * @param string $endpoint Optional. Specific endpoint to clear
     * @param array  $query_args Optional. Query args for specific cache key
     */
    protected function clear_cache( $endpoint = null, $query_args = array() ) {
        if ( $endpoint ) {
            $cache_key = $this->get_cache_key( $endpoint, $query_args );
            delete_transient( $cache_key );
        } else {
            // Clear all plugin transients (WordPress doesn't have a direct way)
            global $wpdb;
            $wpdb->query( 
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_' . $this->cache_group . '_%'
                )
            );
            $wpdb->query( 
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_timeout_' . $this->cache_group . '_%'
                )
            );
        }
    }

    /**
     * Log error for debugging
     *
     * @param string $message Error message
     * @param array  $context Additional context data
     */
    protected function log_error( $message, $context = array() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf(
                '[PandaScore Tracker] %s: %s',
                $message,
                wp_json_encode( $context )
            ) );
        }
    }

    /**
     * Sanitize and validate match data
     *
     * @param array $match Raw match data from API
     * @return array Sanitized match data
     */
    protected function sanitize_match_data( $match ) {
        if ( ! is_array( $match ) ) {
            return array();
        }

        $sanitized = array(
            'id'           => isset( $match['id'] ) ? intval( $match['id'] ) : 0,
            'name'         => isset( $match['name'] ) ? sanitize_text_field( $match['name'] ) : '',
            'scheduled_at' => isset( $match['scheduled_at'] ) ? sanitize_text_field( $match['scheduled_at'] ) : '',
            'status'       => isset( $match['status'] ) ? sanitize_text_field( $match['status'] ) : '',
            'opponents'    => array(),
            'results'      => array(),
            'league'       => array(),
        );

        // Sanitize opponents
        if ( isset( $match['opponents'] ) && is_array( $match['opponents'] ) ) {
            foreach ( $match['opponents'] as $opponent ) {
                if ( isset( $opponent['opponent'] ) && is_array( $opponent['opponent'] ) ) {
                    $sanitized['opponents'][] = array(
                        'id'        => isset( $opponent['opponent']['id'] ) ? intval( $opponent['opponent']['id'] ) : 0,
                        'name'      => isset( $opponent['opponent']['name'] ) ? sanitize_text_field( $opponent['opponent']['name'] ) : '',
                        'acronym'   => isset( $opponent['opponent']['acronym'] ) ? sanitize_text_field( $opponent['opponent']['acronym'] ) : '',
                        'image_url' => isset( $opponent['opponent']['image_url'] ) ? esc_url_raw( $opponent['opponent']['image_url'] ) : '',
                    );
                }
            }
        }

        // Sanitize results
        if ( isset( $match['results'] ) && is_array( $match['results'] ) ) {
            foreach ( $match['results'] as $result ) {
                $sanitized['results'][] = array(
                    'team_id' => isset( $result['team_id'] ) ? intval( $result['team_id'] ) : 0,
                    'score'   => isset( $result['score'] ) ? intval( $result['score'] ) : 0,
                );
            }
        }

        // Sanitize league
        if ( isset( $match['league'] ) && is_array( $match['league'] ) ) {
            $sanitized['league'] = array(
                'id'        => isset( $match['league']['id'] ) ? intval( $match['league']['id'] ) : 0,
                'name'      => isset( $match['league']['name'] ) ? sanitize_text_field( $match['league']['name'] ) : '',
                'image_url' => isset( $match['league']['image_url'] ) ? esc_url_raw( $match['league']['image_url'] ) : '',
            );
        }

        return $sanitized;
    }

    /**
     * Format match time for display
     *
     * @param string $scheduled_at ISO 8601 timestamp
     * @return array Array with 'time' and 'day' keys
     */
    protected function format_match_time( $scheduled_at ) {
        if ( empty( $scheduled_at ) ) {
            return array( 'time' => '', 'day' => '' );
        }

        $timestamp = strtotime( $scheduled_at );
        if ( ! $timestamp ) {
            return array( 'time' => '', 'day' => '' );
        }

        $time = date( 'H:i', $timestamp );
        $day = $this->get_match_day_display( $timestamp );

        return array( 'time' => $time, 'day' => $day );
    }

    /**
     * Get human-readable day display for match
     *
     * @param int $timestamp Unix timestamp
     * @return string Day display (Today, Tomorrow, or date)
     */
    protected function get_match_day_display( $timestamp ) {
        $today = strtotime( 'today' );
        $tomorrow = strtotime( 'tomorrow' );
        $match_date = strtotime( date( 'Y-m-d', $timestamp ) );

        if ( $match_date == $today ) {
            return __( 'Today', 'pandascore-tracker' );
        } elseif ( $match_date == $tomorrow ) {
            return __( 'Tomorrow', 'pandascore-tracker' );
        } else {
            return date( 'M j', $timestamp );
        }
    }

    /**
     * Get plugin version for cache busting
     *
     * @return string Plugin version
     */
    protected function get_plugin_version() {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_file = dirname( dirname( __FILE__ ) ) . '/pandascore-tracker.php';
        $plugin_data = get_plugin_data( $plugin_file );
        
        return $plugin_data['Version'] ?? '2.0.0';
    }
}
