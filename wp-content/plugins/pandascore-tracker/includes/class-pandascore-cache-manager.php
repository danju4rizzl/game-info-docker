<?php
/**
 * Cache Manager Class for PandaScore Tracker Plugin
 *
 * Handles all caching operations including transient management,
 * cache key generation, and cache clearing functionality.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PandaScore Cache Manager Class
 *
 * Centralizes all caching operations for the plugin
 */
class PandaScore_Cache_Manager extends PandaScore_Base_Component {

    /**
     * Cache key prefix
     *
     * @var string
     */
    private $cache_prefix = 'pandascore_api_';

    /**
     * Default cache expiration time in seconds
     *
     * @var int
     */
    private $default_expiration = 180; // 3 minutes (reduced from 5)

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        // Add cache-busting hooks to prevent conflicts with other caching systems
        add_action( 'wp_login', array( $this, 'clear_user_specific_cache' ) );
        add_action( 'wp_logout', array( $this, 'clear_user_specific_cache' ) );
    }

    /**
     * Clear any user-specific cache (defensive measure)
     * This ensures our plugin cache doesn't interfere with user sessions
     */
    public function clear_user_specific_cache() {
        // Our plugin doesn't cache user-specific data, but this is a defensive measure
        // to ensure we don't accidentally interfere with other caching systems

        // Only clear if we have cached data that might be user-specific
        // (Currently we don't, but this is future-proofing)
        return true;
    }

    /**
     * Generate cache key for API requests
     *
     * @param string $endpoint API endpoint
     * @param array  $query_args Query arguments
     * @return string Generated cache key
     */
    public function generate_api_cache_key( $endpoint, $query_args = array() ) {
        $key_parts = array( $this->cache_prefix );
        
        // Add endpoint parts
        $endpoint_parts = explode( '/', trim( $endpoint, '/' ) );
        $key_parts = array_merge( $key_parts, $endpoint_parts );
        
        // Add query arguments
        if ( ! empty( $query_args ) ) {
            ksort( $query_args ); // Sort for consistent keys
            foreach ( $query_args as $key => $value ) {
                $key_parts[] = sanitize_key( $key );
                $key_parts[] = sanitize_key( $value );
            }
        }
        
        // Create final key and ensure it's within WordPress limits
        $cache_key = implode( '_', $key_parts );
        
        // WordPress transient keys have a 172 character limit
        if ( strlen( $cache_key ) > 172 ) {
            $cache_key = $this->cache_prefix . md5( $cache_key );
        }
        
        return $cache_key;
    }

    /**
     * Get cached data
     *
     * @param string $cache_key Cache key
     * @return mixed Cached data or false if not found
     */
    public function get_cached_data( $cache_key ) {
        return get_transient( $cache_key );
    }

    /**
     * Set cached data
     *
     * @param string $cache_key Cache key
     * @param mixed  $data Data to cache
     * @param int    $expiration Expiration time in seconds
     * @return bool True on success, false on failure
     */
    public function set_cached_data( $cache_key, $data, $expiration = null ) {
        if ( null === $expiration ) {
            $expiration = $this->default_expiration;
        }
        
        return set_transient( $cache_key, $data, $expiration );
    }

    /**
     * Delete cached data
     *
     * @param string $cache_key Cache key
     * @return bool True on success, false on failure
     */
    public function delete_cached_data( $cache_key ) {
        return delete_transient( $cache_key );
    }

    /**
     * Clear all plugin caches
     *
     * @return int Number of cache entries cleared
     */
    public function clear_all_caches() {
        $cleared_count = 0;
        
        // Clear league IDs cache
        if ( $this->delete_cached_data( $this->cache_prefix . 'league_ids' ) ) {
            $cleared_count++;
        }
        
        // Clear match caches for common combinations
        $games = array( 'lol', 'valorant', 'csgo', 'dota2' );
        $endpoints = array( 'upcoming', 'running' );
        $limits = array( 5, 10, 15, 20 );
        
        foreach ( $games as $game ) {
            foreach ( $endpoints as $endpoint ) {
                foreach ( $limits as $limit ) {
                    $cache_key = $this->generate_api_cache_key( 
                        $game . '/matches/' . $endpoint,
                        array( 'page[size]' => $limit )
                    );
                    
                    if ( $this->delete_cached_data( $cache_key ) ) {
                        $cleared_count++;
                    }
                }
            }
        }
        
        // Clear LoL-specific caches with league filtering
        $league_ids = $this->get_cached_data( $this->cache_prefix . 'league_ids' );
        if ( is_array( $league_ids ) ) {
            $league_filter = implode( ',', $league_ids );
            
            foreach ( $endpoints as $endpoint ) {
                foreach ( $limits as $limit ) {
                    $cache_key = $this->generate_api_cache_key(
                        'lol/matches/' . $endpoint,
                        array(
                            'page[size]' => $limit,
                            'filter[league_id]' => $league_filter
                        )
                    );
                    
                    if ( $this->delete_cached_data( $cache_key ) ) {
                        $cleared_count++;
                    }
                }
            }
        }
        
        return $cleared_count;
    }

    /**
     * Clear caches for a specific game
     *
     * @param string $game Game type (e.g., 'lol', 'valorant')
     * @return int Number of cache entries cleared
     */
    public function clear_game_caches( $game ) {
        $cleared_count = 0;
        $endpoints = array( 'upcoming', 'running' );
        $limits = array( 5, 10, 15, 20 );
        
        foreach ( $endpoints as $endpoint ) {
            foreach ( $limits as $limit ) {
                $cache_key = $this->generate_api_cache_key(
                    $game . '/matches/' . $endpoint,
                    array( 'page[size]' => $limit )
                );
                
                if ( $this->delete_cached_data( $cache_key ) ) {
                    $cleared_count++;
                }
            }
        }
        
        return $cleared_count;
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        global $wpdb;
        
        $stats = array(
            'total_transients' => 0,
            'plugin_transients' => 0,
            'estimated_size' => 0
        );
        
        // Count total transients
        $total_transients = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'"
        );
        $stats['total_transients'] = intval( $total_transients );
        
        // Count plugin-specific transients
        $plugin_transients = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        $stats['plugin_transients'] = intval( $plugin_transients );
        
        // Estimate size of plugin transients
        $size_result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        $stats['estimated_size'] = intval( $size_result );
        
        return $stats;
    }

    /**
     * Check if cache is enabled
     *
     * @return bool True if caching is enabled, false otherwise
     */
    public function is_cache_enabled() {
        // Check if object caching is available
        if ( wp_using_ext_object_cache() ) {
            return true;
        }
        
        // Check if transients are working
        $test_key = $this->cache_prefix . 'test_' . time();
        $test_value = 'test_value';
        
        if ( set_transient( $test_key, $test_value, 60 ) ) {
            $retrieved = get_transient( $test_key );
            delete_transient( $test_key );
            
            return $retrieved === $test_value;
        }
        
        return false;
    }

    /**
     * Get cache expiration time for different data types
     * Reduced times to prevent conflicts with site-wide caching
     *
     * @param string $data_type Type of data being cached
     * @return int Expiration time in seconds
     */
    public function get_expiration_time( $data_type ) {
        $expiration_times = array(
            'live_matches'     => 30,           // 30 seconds (reduced from 1 minute)
            'upcoming_matches' => 120,          // 2 minutes (reduced from 5 minutes)
            'league_ids'       => HOUR_IN_SECONDS * 6, // 6 hours (reduced from 24 hours)
            'team_data'        => HOUR_IN_SECONDS / 2,  // 30 minutes (reduced from 1 hour)
            'tournament_data'  => HOUR_IN_SECONDS * 2,  // 2 hours (reduced from 6 hours)
        );

        return isset( $expiration_times[ $data_type ] )
            ? $expiration_times[ $data_type ]
            : $this->default_expiration;
    }

    /**
     * Warm up cache with common data
     *
     * @param array $games Games to warm up cache for
     * @return bool True on success, false on failure
     */
    public function warm_up_cache( $games = array( 'lol', 'valorant', 'csgo' ) ) {
        // This method could be used to pre-populate cache with common requests
        // Implementation would depend on specific requirements
        return true;
    }

    /**
     * Schedule cache cleanup
     */
    public function schedule_cache_cleanup() {
        if ( ! wp_next_scheduled( 'pandascore_cache_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'pandascore_cache_cleanup' );
        }
    }

    /**
     * Unschedule cache cleanup
     */
    public function unschedule_cache_cleanup() {
        wp_clear_scheduled_hook( 'pandascore_cache_cleanup' );
    }
}
