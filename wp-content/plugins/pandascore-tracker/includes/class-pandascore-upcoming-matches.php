<?php
/**
 * Upcoming Matches Component for PandaScore Tracker Plugin
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles upcoming match functionality
 * 
 * Provides methods for fetching upcoming matches with game-specific filtering
 * and display logic.
 */
class PandaScore_Upcoming_Matches extends PandaScore_Base_Component {

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
     * Constructor
     */
    public function __construct() {
        $this->api_handler = new PandaScore_API_Handler();
        $this->renderer = new PandaScore_Renderer();
    }

    /**
     * Fetch and render upcoming matches section
     *
     * @param string $game Game identifier (e.g., 'valorant', 'lol', 'csgo')
     * @param int    $limit Maximum number of matches to display
     * @param array  $args Display arguments
     * @return string HTML output
     */
    public function render_upcoming_matches( $game, $limit = 10, $args = array() ) {
        // Validate game parameter
        if ( empty( $game ) ) {
            return $this->renderer->render_error_message( 
                __( 'Game parameter is required for upcoming matches.', 'pandascore-tracker' ) 
            );
        }

        // Fetch upcoming matches
        $matches = $this->api_handler->fetch_upcoming_matches( $game, $limit );

        if ( is_wp_error( $matches ) ) {
            return $this->renderer->render_error_message( $matches->get_error_message() );
        }

        if ( empty( $matches ) ) {
            return $this->renderer->render_no_matches_message( 
                sprintf( __( 'No upcoming %s matches found.', 'pandascore-tracker' ), ucfirst( $game ) )
            );
        }

        // Filter and sort matches
        $processed_matches = $this->process_upcoming_matches( $matches );

        // Render the section
        return $this->renderer->render_upcoming_matches_section( $processed_matches, $args );
    }

    /**
     * Get upcoming matches for multiple games
     *
     * @param array $games Array of game identifiers
     * @param int   $limit_per_game Limit per game
     * @return array|WP_Error Combined matches or error
     */
    public function get_upcoming_matches_multi_game( $games, $limit_per_game = 5 ) {
        if ( empty( $games ) || ! is_array( $games ) ) {
            return new WP_Error( 'invalid_games', __( 'Games parameter must be a non-empty array.', 'pandascore-tracker' ) );
        }

        $all_matches = array();
        $errors = array();

        foreach ( $games as $game ) {
            $matches = $this->api_handler->fetch_upcoming_matches( $game, $limit_per_game );
            
            if ( is_wp_error( $matches ) ) {
                $errors[] = sprintf( '%s: %s', $game, $matches->get_error_message() );
                continue;
            }

            // Add game identifier to each match
            foreach ( $matches as &$match ) {
                $match['game_slug'] = $game;
            }

            $all_matches = array_merge( $all_matches, $matches );
        }

        if ( empty( $all_matches ) && ! empty( $errors ) ) {
            return new WP_Error( 'multi_game_error', implode( '; ', $errors ) );
        }

        // Sort by scheduled time
        usort( $all_matches, function( $a, $b ) {
            $time_a = strtotime( $a['scheduled_at'] ?? '0' );
            $time_b = strtotime( $b['scheduled_at'] ?? '0' );
            return $time_a - $time_b;
        } );

        return $all_matches;
    }

    /**
     * Get upcoming matches for specific teams
     *
     * @param string $game Game identifier
     * @param array  $team_ids Array of team IDs to filter by
     * @param int    $limit Maximum number of matches
     * @return array|WP_Error Array of matches or error
     */
    public function get_upcoming_matches_for_teams( $game, $team_ids, $limit = 10 ) {
        if ( empty( $team_ids ) || ! is_array( $team_ids ) ) {
            return array();
        }

        $all_matches = $this->api_handler->fetch_upcoming_matches( $game, 50 ); // Get more to filter
        
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
     * Get upcoming matches within a time range
     *
     * @param string $game Game identifier
     * @param int    $start_time Start timestamp
     * @param int    $end_time End timestamp
     * @param int    $limit Maximum number of matches
     * @return array|WP_Error Array of matches or error
     */
    public function get_upcoming_matches_in_timeframe( $game, $start_time, $end_time, $limit = 20 ) {
        $all_matches = $this->api_handler->fetch_upcoming_matches( $game, $limit );
        
        if ( is_wp_error( $all_matches ) ) {
            return $all_matches;
        }

        $filtered_matches = array();
        foreach ( $all_matches as $match ) {
            $match_time = strtotime( $match['scheduled_at'] ?? '0' );
            
            if ( $match_time >= $start_time && $match_time <= $end_time ) {
                $filtered_matches[] = $match;
            }
        }

        return $filtered_matches;
    }

    /**
     * Get today's upcoming matches
     *
     * @param string $game Game identifier
     * @param int    $limit Maximum number of matches
     * @return array|WP_Error Array of matches or error
     */
    public function get_todays_matches( $game, $limit = 10 ) {
        $start_of_day = strtotime( 'today' );
        $end_of_day = strtotime( 'tomorrow' ) - 1;

        return $this->get_upcoming_matches_in_timeframe( $game, $start_of_day, $end_of_day, $limit );
    }

    /**
     * Get this week's upcoming matches
     *
     * @param string $game Game identifier
     * @param int    $limit Maximum number of matches
     * @return array|WP_Error Array of matches or error
     */
    public function get_this_weeks_matches( $game, $limit = 20 ) {
        $start_of_week = strtotime( 'monday this week' );
        $end_of_week = strtotime( 'sunday this week' ) + 86399; // End of Sunday

        return $this->get_upcoming_matches_in_timeframe( $game, $start_of_week, $end_of_week, $limit );
    }

    /**
     * Process upcoming matches for display
     *
     * @param array $matches Raw matches data
     * @return array Processed matches
     */
    private function process_upcoming_matches( $matches ) {
        $processed = array();

        foreach ( $matches as $match ) {
            // Skip matches without scheduled time
            if ( empty( $match['scheduled_at'] ) ) {
                continue;
            }

            // Skip matches that are too far in the future (more than 30 days)
            $match_time = strtotime( $match['scheduled_at'] );
            $thirty_days_from_now = strtotime( '+30 days' );
            
            if ( $match_time > $thirty_days_from_now ) {
                continue;
            }

            // Skip matches that are in the past
            if ( $match_time < time() ) {
                continue;
            }

            $processed[] = $match;
        }

        // Sort by scheduled time (earliest first)
        usort( $processed, function( $a, $b ) {
            $time_a = strtotime( $a['scheduled_at'] );
            $time_b = strtotime( $b['scheduled_at'] );
            return $time_a - $time_b;
        } );

        return $processed;
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
     * Get upcoming matches count for a specific game
     *
     * @param string $game Game identifier
     * @return int Number of upcoming matches
     */
    public function get_upcoming_matches_count( $game ) {
        $matches = $this->api_handler->fetch_upcoming_matches( $game, 50 ); // Get max to count
        
        if ( is_wp_error( $matches ) ) {
            return 0;
        }

        return count( $this->process_upcoming_matches( $matches ) );
    }

    /**
     * Get upcoming matches statistics
     *
     * @param array $games Array of games to check
     * @return array Statistics data
     */
    public function get_upcoming_statistics( $games = array( 'valorant', 'lol', 'csgo', 'dota2' ) ) {
        $stats = array(
            'total_upcoming_matches' => 0,
            'games' => array(),
            'timeframes' => array(
                'today' => 0,
                'this_week' => 0,
                'next_week' => 0,
            ),
            'last_updated' => current_time( 'c' ),
        );

        foreach ( $games as $game ) {
            $matches = $this->api_handler->fetch_upcoming_matches( $game, 50 );
            
            if ( is_wp_error( $matches ) ) {
                $stats['games'][$game] = array( 'error' => $matches->get_error_message() );
                continue;
            }

            $processed_matches = $this->process_upcoming_matches( $matches );
            $count = count( $processed_matches );
            
            $stats['games'][$game] = array(
                'count' => $count,
                'error' => null,
            );
            
            $stats['total_upcoming_matches'] += $count;

            // Count by timeframe
            foreach ( $processed_matches as $match ) {
                $match_time = strtotime( $match['scheduled_at'] );
                $today = strtotime( 'today' );
                $tomorrow = strtotime( 'tomorrow' );
                $end_of_week = strtotime( 'sunday this week' ) + 86399;
                $end_of_next_week = strtotime( 'sunday next week' ) + 86399;

                if ( $match_time >= $today && $match_time < $tomorrow ) {
                    $stats['timeframes']['today']++;
                } elseif ( $match_time >= $today && $match_time <= $end_of_week ) {
                    $stats['timeframes']['this_week']++;
                } elseif ( $match_time > $end_of_week && $match_time <= $end_of_next_week ) {
                    $stats['timeframes']['next_week']++;
                }
            }
        }

        return $stats;
    }

    /**
     * Clear upcoming matches cache for a specific game
     *
     * @param string $game Game identifier
     */
    public function clear_upcoming_cache( $game ) {
        $this->clear_cache( "{$game}/matches" );
    }

    /**
     * Clear all upcoming matches cache
     */
    public function clear_all_upcoming_cache() {
        $games = array( 'valorant', 'lol', 'csgo', 'dota2', 'ow', 'r6siege' );
        
        foreach ( $games as $game ) {
            $this->clear_upcoming_cache( $game );
        }
    }

    /**
     * Debug upcoming matches data (for development)
     *
     * @param string $game Game identifier
     * @return array Debug information
     */
    public function debug_upcoming_data( $game ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return array( 'error' => 'Debug mode not enabled' );
        }

        $debug_data = array(
            'timestamp' => current_time( 'c' ),
            'game' => $game,
            'api_status' => $this->api_handler->get_api_status(),
        );

        // Fetch upcoming matches
        $matches = $this->api_handler->fetch_upcoming_matches( $game, 20 );
        
        if ( is_wp_error( $matches ) ) {
            $debug_data['error'] = $matches->get_error_message();
            $debug_data['matches'] = array();
        } else {
            $processed_matches = $this->process_upcoming_matches( $matches );
            
            $debug_data['raw_matches_count'] = count( $matches );
            $debug_data['processed_matches_count'] = count( $processed_matches );
            $debug_data['matches'] = array_map( function( $match ) {
                return array(
                    'id' => $match['id'],
                    'name' => $match['name'] ?? 'Unknown',
                    'scheduled_at' => $match['scheduled_at'] ?? null,
                    'status' => $match['status'] ?? 'Unknown',
                    'opponents_count' => count( $match['opponents'] ?? array() ),
                );
            }, $processed_matches );
        }

        return $debug_data;
    }
}
