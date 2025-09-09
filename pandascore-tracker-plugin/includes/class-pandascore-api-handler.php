<?php
/**
 * API Handler Class for PandaScore Tracker Plugin
 *
 * Handles all PandaScore API interactions including authentication,
 * request handling, and response processing.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PandaScore API Handler Class
 *
 * Centralizes all API interactions with PandaScore service
 */
class PandaScore_API_Handler extends PandaScore_Base_Component {

    /**
     * PandaScore API base URL
     *
     * @var string
     */
    private $api_base_url = 'https://api.pandascore.co';

    /**
     * Default request timeout in seconds
     *
     * @var int
     */
    private $request_timeout = 15;

    /**
     * Cache manager instance
     *
     * @var PandaScore_Cache_Manager
     */
    private $cache_manager;

    /**
     * Constructor
     *
     * @param PandaScore_Cache_Manager $cache_manager Cache manager instance
     */
    public function __construct( $cache_manager = null ) {
        parent::__construct();
        $this->cache_manager = $cache_manager;
    }

    /**
     * Make authenticated API request to PandaScore
     *
     * @param string $endpoint API endpoint (without base URL)
     * @param array  $query_args Query arguments for the request
     * @param bool   $use_cache Whether to use caching for this request
     * @return array|WP_Error API response data or WP_Error on failure
     */
    public function make_api_request( $endpoint, $query_args = array(), $use_cache = true ) {
        // Validate API key
        if ( ! $this->is_api_key_valid() ) {
            $this->log_error( 'API request failed: No API key set' );
            return new WP_Error( 'no_api_key', 'PandaScore API key not set' );
        }

        // Generate cache key if caching is enabled
        $cache_key = null;
        if ( $use_cache && $this->cache_manager ) {
            $cache_key = $this->cache_manager->generate_api_cache_key( $endpoint, $query_args );
            $cached_data = $this->cache_manager->get_cached_data( $cache_key );
            
            if ( false !== $cached_data ) {
                return $cached_data;
            }
        }

        // Build request URL
        $url = $this->build_request_url( $endpoint, $query_args );

        // Make the request
        $response = wp_remote_get( $url, $this->get_request_args() );

        // Handle request errors
        if ( is_wp_error( $response ) ) {
            $this->log_error( 'API request failed', $response->get_error_message() );
            return $response;
        }

        // Check response code
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $response_code ) {
            $error_message = sprintf( 'PandaScore API returned code %d', $response_code );
            $this->log_error( $error_message, $endpoint );
            return new WP_Error( 'api_error', $error_message );
        }

        // Parse response body
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( JSON_ERROR_NONE !== json_last_error() ) {
            $this->log_error( 'Invalid JSON response from API', $endpoint );
            return new WP_Error( 'json_error', 'Invalid JSON from API' );
        }

        // Cache the successful response
        if ( $use_cache && $this->cache_manager && $cache_key ) {
            $expiration = $this->get_cache_expiration( $endpoint );
            $this->cache_manager->set_cached_data( $cache_key, $data, $expiration );
        }

        return $data;
    }

    /**
     * Fetch upcoming matches for a specific game
     *
     * @param string $game Game type (e.g., 'lol', 'valorant', 'csgo')
     * @param int    $limit Number of matches to fetch
     * @return array|WP_Error Match data or WP_Error on failure
     */
    public function fetch_upcoming_matches( $game, $limit ) {
        $endpoint = $game . '/matches/upcoming';
        $query_args = array(
            'page[size]' => intval( $limit )
        );

        // Add league filtering for LoL
        if ( 'lol' === strtolower( $game ) ) {
            $league_ids = $this->get_league_ids();
            if ( is_wp_error( $league_ids ) ) {
                return $league_ids;
            }
            $query_args['filter[league_id]'] = implode( ',', $league_ids );
        }

        return $this->make_api_request( $endpoint, $query_args );
    }

    /**
     * Fetch live/running matches for a specific game
     *
     * @param string $game Game type (e.g., 'lol', 'valorant', 'csgo')
     * @param int    $limit Number of matches to fetch
     * @return array|WP_Error Match data or WP_Error on failure
     */
    public function fetch_live_matches( $game, $limit ) {
        $endpoint = $game . '/matches/running';
        $query_args = array(
            'page[size]' => intval( $limit )
        );

        // Add league filtering for LoL
        if ( 'lol' === strtolower( $game ) ) {
            $league_ids = $this->get_league_ids();
            if ( is_wp_error( $league_ids ) ) {
                return $league_ids;
            }
            $query_args['filter[league_id]'] = implode( ',', $league_ids );
        }

        return $this->make_api_request( $endpoint, $query_args );
    }

    /**
     * Get league IDs for LoL filtering
     *
     * @return array|WP_Error Array of league IDs or WP_Error on failure
     */
    public function get_league_ids() {
        $cache_key = 'league_ids';
        
        if ( $this->cache_manager ) {
            $league_ids = $this->cache_manager->get_cached_data( $cache_key );
            if ( false !== $league_ids ) {
                return $league_ids;
            }
        }

        // Fetch league IDs from API
        $endpoint = 'leagues';
        $query_args = array(
            'filter[name]' => 'LCK,LTA North,LTA South,LPL,LEC'
        );

        $response = $this->make_api_request( $endpoint, $query_args, false );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Extract league IDs
        $league_ids = array();
        $target_leagues = array( 'LCK', 'LTA North', 'LTA South', 'LPL', 'LEC' );

        if ( is_array( $response ) ) {
            foreach ( $response as $league ) {
                if ( isset( $league['name'], $league['id'] ) && 
                     in_array( $league['name'], $target_leagues, true ) ) {
                    $league_ids[] = $league['id'];
                }
            }
        }

        if ( empty( $league_ids ) ) {
            $error_message = 'Could not fetch league IDs from PandaScore API';
            $this->log_error( $error_message );
            return new WP_Error( 'no_leagues', $error_message );
        }

        // Cache the league IDs
        $league_ids = array_unique( $league_ids );
        if ( $this->cache_manager ) {
            $this->cache_manager->set_cached_data( $cache_key, $league_ids, DAY_IN_SECONDS );
        }

        return $league_ids;
    }

    /**
     * Build complete request URL
     *
     * @param string $endpoint API endpoint
     * @param array  $query_args Query arguments
     * @return string Complete URL
     */
    private function build_request_url( $endpoint, $query_args ) {
        $url = trailingslashit( $this->api_base_url ) . ltrim( $endpoint, '/' );
        
        if ( ! empty( $query_args ) ) {
            $url = add_query_arg( $query_args, $url );
        }

        return $url;
    }

    /**
     * Get request arguments for wp_remote_get
     *
     * @return array Request arguments
     */
    private function get_request_args() {
        return array(
            'timeout' => $this->request_timeout,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->get_api_key(),
                'User-Agent'    => 'PandaScore-Tracker-WordPress-Plugin/1.2.0'
            )
        );
    }

    /**
     * Get cache expiration time based on endpoint
     * Reduced times to prevent conflicts with site-wide caching
     *
     * @param string $endpoint API endpoint
     * @return int Cache expiration in seconds
     */
    private function get_cache_expiration( $endpoint ) {
        // Live/running matches have shorter cache time
        if ( strpos( $endpoint, '/running' ) !== false ) {
            return 30; // 30 seconds (reduced from 1 minute)
        }

        // League data can be cached longer but not too long
        if ( strpos( $endpoint, 'leagues' ) !== false ) {
            return 3600 * 6; // 6 hours (reduced from 24 hours)
        }

        // Default cache time for upcoming matches
        return 120; // 2 minutes (reduced from 5 minutes)
    }
}
