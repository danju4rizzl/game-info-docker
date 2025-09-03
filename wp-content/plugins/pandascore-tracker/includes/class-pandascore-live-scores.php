<?php
/**
 * Live Scores Component for PandaScore Tracker Plugin
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles live match functionality and WebSocket management
 * 
 * Provides methods for fetching live matches, managing WebSocket connections,
 * and handling real-time updates.
 */
class PandaScore_Live_Scores extends PandaScore_Base_Component {

    /**
     * API handler instance
     *
     * @var PandaScore_API_Handler
     */
    private $api_handler;

    /**
     * Renderer instance
     *
     * @var PandaScore_Renderer
     */
    private $renderer;

    /**
     * Live match IDs for WebSocket tracking
     *
     * @var array
     */
    private $live_match_ids = array();

    /**
     * WebSocket match data
     *
     * @var array
     */
    private $websocket_matches = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_handler = new PandaScore_API_Handler();
        $this->renderer = new PandaScore_Renderer();
    }

    /**
     * Fetch and render live matches section
     *
     * @param int    $limit Maximum number of matches to display
     * @param string $game Optional. Filter by specific game
     * @param array  $args Display arguments
     * @return string HTML output
     */
    public function render_live_matches( $limit = 10, $game = '', $args = array() ) {
        // Fetch live matches
        $matches = $this->api_handler->fetch_live_matches( $limit, $game );

        if ( is_wp_error( $matches ) ) {
            return $this->renderer->render_error_message( $matches->get_error_message() );
        }

        if ( empty( $matches ) ) {
            return ''; // No live matches, return empty string
        }

        // Process matches for WebSocket tracking
        $this->process_matches_for_websocket( $matches );

        // Render the section
        return $this->renderer->render_live_matches_section( $matches, $args );
    }

    /**
     * Get live match IDs for JavaScript integration
     *
     * @return array Array of live match IDs
     */
    public function get_live_match_ids() {
        return array_unique( $this->live_match_ids );
    }

    /**
     * Get WebSocket match data for JavaScript
     *
     * @return array WebSocket configuration data
     */
    public function get_websocket_matches() {
        return $this->websocket_matches;
    }

    /**
     * Reset live data between shortcode instances
     */
    public function reset_live_data() {
        $this->live_match_ids = array();
        $this->websocket_matches = array();
    }

    /**
     * Check if there are any live matches to track
     *
     * @return bool True if there are live matches
     */
    public function has_live_matches() {
        return ! empty( $this->live_match_ids );
    }

    /**
     * Process matches for WebSocket tracking
     *
     * @param array $matches Array of live matches
     */
    private function process_matches_for_websocket( $matches ) {
        foreach ( $matches as $match ) {
            if ( empty( $match['id'] ) ) {
                continue;
            }

            $match_id = $match['id'];
            $this->live_match_ids[] = $match_id;

            // Prepare WebSocket data
            $websocket_data = array(
                'match_id' => $match_id,
                'game_ids' => array(), // Will be populated if available
            );

            // Add WebSocket URL if available
            if ( ! empty( $match['websocket_url'] ) ) {
                $websocket_data['events_url'] = $match['websocket_url'];
            }

            // Try to extract frames URL (fallback)
            $websocket_data['frames_url'] = $this->build_frames_url( $match_id );

            $this->websocket_matches[] = $websocket_data;
        }
    }

    /**
     * Build frames WebSocket URL for a match
     *
     * @param int $match_id Match ID
     * @return string WebSocket URL
     */
    private function build_frames_url( $match_id ) {
        return "wss://live.pandascore.co/matches/{$match_id}";
    }

    /**
     * Get live matches count for a specific game
     *
     * @param string $game Game identifier
     * @return int Number of live matches
     */
    public function get_live_matches_count( $game = '' ) {
        $matches = $this->api_handler->fetch_live_matches( 50, $game ); // Get max to count
        
        if ( is_wp_error( $matches ) ) {
            return 0;
        }

        return count( $matches );
    }

    /**
     * Get live matches for specific teams
     *
     * @param array $team_ids Array of team IDs to filter by
     * @param int   $limit Maximum number of matches
     * @return array|WP_Error Array of matches or error
     */
    public function get_live_matches_for_teams( $team_ids, $limit = 10 ) {
        if ( empty( $team_ids ) || ! is_array( $team_ids ) ) {
            return array();
        }

        $all_matches = $this->api_handler->fetch_live_matches( 50 ); // Get more to filter
        
        if ( is_wp_error( $all_matches ) ) {
            return $all_matches;
        }

        $filtered_matches = array();
        foreach ( $all_matches as $match ) {
            if ( $this->match_has_teams( $match, $team_ids ) ) {
                $filtered_matches[] = $match;
                
                if ( count( $filtered_matches ) >= $limit ) {
                    break;
                }
            }
        }

        return $filtered_matches;
    }

    /**
     * Check if match contains any of the specified teams
     *
     * @param array $match Match data
     * @param array $team_ids Team IDs to check
     * @return bool True if match contains any of the teams
     */
    private function match_has_teams( $match, $team_ids ) {
        if ( empty( $match['opponents'] ) ) {
            return false;
        }

        foreach ( $match['opponents'] as $opponent ) {
            if ( in_array( $opponent['id'], $team_ids ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get live match details with enhanced data
     *
     * @param int $match_id Match ID
     * @return array|WP_Error Enhanced match data or error
     */
    public function get_enhanced_live_match( $match_id ) {
        $match = $this->api_handler->fetch_match_details( $match_id );
        
        if ( is_wp_error( $match ) ) {
            return $match;
        }

        // Add live-specific enhancements
        $match['is_live'] = true;
        $match['websocket_url'] = $this->build_frames_url( $match_id );
        $match['last_updated'] = current_time( 'timestamp' );

        return $match;
    }

    /**
     * Debug live match data (for development)
     *
     * @param string $game Optional game filter
     * @return array Debug information
     */
    public function debug_live_data( $game = '' ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return array( 'error' => 'Debug mode not enabled' );
        }

        $debug_data = array(
            'timestamp' => current_time( 'c' ),
            'game_filter' => $game,
            'api_status' => $this->api_handler->get_api_status(),
        );

        // Fetch live matches
        $matches = $this->api_handler->fetch_live_matches( 10, $game );
        
        if ( is_wp_error( $matches ) ) {
            $debug_data['error'] = $matches->get_error_message();
            $debug_data['matches'] = array();
        } else {
            $debug_data['matches_count'] = count( $matches );
            $debug_data['matches'] = array_map( function( $match ) {
                return array(
                    'id' => $match['id'],
                    'name' => $match['name'] ?? 'Unknown',
                    'status' => $match['status'] ?? 'Unknown',
                    'opponents_count' => count( $match['opponents'] ?? array() ),
                    'has_websocket' => ! empty( $match['websocket_url'] ),
                );
            }, $matches );
        }

        // WebSocket tracking data
        $this->process_matches_for_websocket( is_array( $matches ) ? $matches : array() );
        $debug_data['websocket_matches'] = $this->websocket_matches;
        $debug_data['live_match_ids'] = $this->live_match_ids;

        return $debug_data;
    }

    /**
     * Get live matches statistics
     *
     * @return array Statistics data
     */
    public function get_live_statistics() {
        $stats = array(
            'total_live_matches' => 0,
            'games' => array(),
            'last_updated' => current_time( 'c' ),
        );

        // Get all live matches
        $matches = $this->api_handler->fetch_live_matches( 100 );
        
        if ( is_wp_error( $matches ) ) {
            $stats['error'] = $matches->get_error_message();
            return $stats;
        }

        $stats['total_live_matches'] = count( $matches );

        // Count by game
        foreach ( $matches as $match ) {
            $game = $match['game'] ?? 'unknown';
            if ( ! isset( $stats['games'][$game] ) ) {
                $stats['games'][$game] = 0;
            }
            $stats['games'][$game]++;
        }

        return $stats;
    }

    /**
     * Clear live matches cache
     */
    public function clear_live_cache() {
        $this->clear_cache( 'lives' );
    }

    /**
     * Get WebSocket connection status for admin
     *
     * @return array Connection status information
     */
    public function get_websocket_status() {
        return array(
            'enabled' => true,
            'base_url' => 'wss://live.pandascore.co',
            'tracked_matches' => count( $this->live_match_ids ),
            'websocket_matches' => count( $this->websocket_matches ),
            'last_reset' => current_time( 'c' ),
        );
    }
}
