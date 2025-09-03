<?php
/**
 * API Handler Component for PandaScore Tracker Plugin
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles all PandaScore API interactions
 * 
 * Provides methods for fetching live matches, upcoming matches,
 * with built-in caching, rate limiting, and error handling.
 */
class PandaScore_API_Handler extends PandaScore_Base_Component {

    /**
     * Rate limiting: max requests per minute
     *
     * @var int
     */
    private $rate_limit = 60;

    /**
     * Rate limiting: current request count
     *
     * @var int
     */
    private $request_count = 0;

    /**
     * Rate limiting: window start time
     *
     * @var int
     */
    private $rate_window_start = 0;

    /**
     * Constructor
     */
    public function __construct() {
        $this->rate_window_start = time();
    }

    /**
     * Fetch live matches from PandaScore API
     *
     * @param int    $limit Maximum number of matches to fetch
     * @param string $game Optional. Filter by specific game
     * @return array|WP_Error Array of live matches or error
     */
    public function fetch_live_matches( $limit = 10, $game = '' ) {
        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limit', __( 'API rate limit exceeded. Please try again later.', 'pandascore-tracker' ) );
        }

        $query_args = array(
            'page[size]' => min( intval( $limit ), 50 ), // Cap at 50
        );

        // Add game filter if specified
        if ( ! empty( $game ) ) {
            $query_args['filter[videogame]'] = sanitize_text_field( $game );
        }

        $data = $this->make_api_request( 'lives', $query_args, true );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        // Process and sanitize the data
        $processed_matches = array();
        if ( is_array( $data ) ) {
            foreach ( $data as $live_data ) {
                if ( isset( $live_data['match'] ) && is_array( $live_data['match'] ) ) {
                    $match = $this->sanitize_match_data( $live_data['match'] );
                    if ( ! empty( $match['id'] ) ) {
                        // Add live-specific data
                        $match['is_live'] = true;
                        $match['websocket_url'] = $this->get_websocket_url( $live_data );
                        $processed_matches[] = $match;
                    }
                }
            }
        }

        return $processed_matches;
    }

    /**
     * Fetch upcoming matches for a specific game
     *
     * @param string $game Game identifier (e.g., 'valorant', 'lol', 'csgo')
     * @param int    $limit Maximum number of matches to fetch
     * @return array|WP_Error Array of upcoming matches or error
     */
    public function fetch_upcoming_matches( $game, $limit = 10 ) {
        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limit', __( 'API rate limit exceeded. Please try again later.', 'pandascore-tracker' ) );
        }

        $game = sanitize_text_field( $game );
        if ( empty( $game ) ) {
            return new WP_Error( 'invalid_game', __( 'Game parameter is required.', 'pandascore-tracker' ) );
        }

        $query_args = array(
            'page[size]' => min( intval( $limit ), 50 ), // Cap at 50
            'sort'       => 'begin_at', // Upcoming matches sorted by start time
            'filter[status]' => 'not_started',
        );

        $endpoint = "{$game}/matches";
        $data = $this->make_api_request( $endpoint, $query_args, true );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        // Process and sanitize the data
        $processed_matches = array();
        if ( is_array( $data ) ) {
            foreach ( $data as $match_data ) {
                $match = $this->sanitize_match_data( $match_data );
                if ( ! empty( $match['id'] ) ) {
                    $match['is_live'] = false;
                    $processed_matches[] = $match;
                }
            }
        }

        return $processed_matches;
    }

    /**
     * Fetch specific match details
     *
     * @param int $match_id Match ID
     * @return array|WP_Error Match data or error
     */
    public function fetch_match_details( $match_id ) {
        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limit', __( 'API rate limit exceeded. Please try again later.', 'pandascore-tracker' ) );
        }

        $match_id = intval( $match_id );
        if ( $match_id <= 0 ) {
            return new WP_Error( 'invalid_match_id', __( 'Invalid match ID.', 'pandascore-tracker' ) );
        }

        $endpoint = "matches/{$match_id}";
        $data = $this->make_api_request( $endpoint, array(), true );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return $this->sanitize_match_data( $data );
    }

    /**
     * Get available games from PandaScore
     *
     * @return array|WP_Error Array of games or error
     */
    public function fetch_available_games() {
        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limit', __( 'API rate limit exceeded. Please try again later.', 'pandascore-tracker' ) );
        }

        // Use longer cache for games list (1 hour)
        $cache_key = $this->cache_group . '_games_list';
        $cached_games = get_transient( $cache_key );
        
        if ( false !== $cached_games ) {
            return $cached_games;
        }

        $data = $this->make_api_request( 'videogames', array(), false );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $games = array();
        if ( is_array( $data ) ) {
            foreach ( $data as $game ) {
                if ( isset( $game['slug'] ) && isset( $game['name'] ) ) {
                    $games[] = array(
                        'slug' => sanitize_text_field( $game['slug'] ),
                        'name' => sanitize_text_field( $game['name'] ),
                    );
                }
            }
        }

        // Cache for 1 hour
        set_transient( $cache_key, $games, 3600 );

        return $games;
    }

    /**
     * Extract WebSocket URL from live match data
     *
     * @param array $live_data Live match data from API
     * @return string WebSocket URL or empty string
     */
    private function get_websocket_url( $live_data ) {
        // Try to get WebSocket URL from various possible fields
        $possible_fields = array( 'websocket_url', 'frames_url', 'events_url' );
        
        foreach ( $possible_fields as $field ) {
            if ( isset( $live_data[$field] ) && ! empty( $live_data[$field] ) ) {
                return esc_url_raw( $live_data[$field] );
            }
        }

        // Fallback: construct URL from match ID
        if ( isset( $live_data['match']['id'] ) ) {
            return "wss://live.pandascore.co/matches/{$live_data['match']['id']}";
        }

        return '';
    }

    /**
     * Check if we're within rate limits
     *
     * @return bool True if within limits, false otherwise
     */
    private function check_rate_limit() {
        $current_time = time();
        
        // Reset counter if we're in a new minute
        if ( $current_time - $this->rate_window_start >= 60 ) {
            $this->request_count = 0;
            $this->rate_window_start = $current_time;
        }

        // Check if we've exceeded the limit
        if ( $this->request_count >= $this->rate_limit ) {
            $this->log_error( 'Rate Limit Exceeded', array(
                'requests' => $this->request_count,
                'limit'    => $this->rate_limit,
                'window'   => $this->rate_window_start,
            ) );
            return false;
        }

        $this->request_count++;
        return true;
    }

    /**
     * Get API status and health information
     *
     * @return array API status information
     */
    public function get_api_status() {
        $api_key = $this->get_api_key();
        
        $status = array(
            'api_key_configured' => ! empty( $api_key ),
            'rate_limit_status'  => array(
                'requests_made'    => $this->request_count,
                'limit'           => $this->rate_limit,
                'window_start'    => $this->rate_window_start,
                'remaining'       => max( 0, $this->rate_limit - $this->request_count ),
            ),
            'cache_status'       => array(
                'enabled'    => true,
                'expiration' => $this->cache_expiration,
            ),
        );

        // Test API connectivity if key is configured
        if ( $status['api_key_configured'] ) {
            $test_response = $this->make_api_request( 'videogames', array( 'page[size]' => 1 ), false );
            $status['api_connectivity'] = ! is_wp_error( $test_response );
            
            if ( is_wp_error( $test_response ) ) {
                $status['api_error'] = $test_response->get_error_message();
            }
        } else {
            $status['api_connectivity'] = false;
            $status['api_error'] = __( 'API key not configured', 'pandascore-tracker' );
        }

        return $status;
    }

    /**
     * Clear all API-related cache
     */
    public function clear_all_cache() {
        $this->clear_cache();
        
        // Also clear games list cache
        delete_transient( $this->cache_group . '_games_list' );
    }
}
