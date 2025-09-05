<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode.
Version: 1.1 (No Tailwind)
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PandaScore_Tracker_Plugin {
    private $option_key = 'pandascore_tracker_options';

    private $live_match_ids = array();

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_shortcode( 'pandascore_tracker', array( $this, 'shortcode_handler' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        // Only custom style (no Tailwind)
        wp_register_style(
            'pandascore-custom-style',
            plugins_url( 'css/index.css', __FILE__ ),
            array(),
            '1.0'
        );

        // Register the WebSocket script
        wp_register_script( 'pandascore-live-tracker-js', plugins_url( 'js/live-tracker.js', __FILE__ ), array(), '1.1', true );

        // Register new script for timezone conversion
        wp_register_script(
            'pandascore-timezone-js',
            plugins_url( 'js/timezone-converter.js', __FILE__ ),
            array(),
            '1.0',
            true
        );
    }

    /**
     * Output internal CSS styles for the plugin
     */
    public function get_internal_styles() {
        $css_file_path = plugin_dir_path( __FILE__ ) . 'css/index.css';

        if ( ! file_exists( $css_file_path ) ) {
            return '<style>/* PandaScore Tracker: CSS file not found */</style>';
        }

        $css_content = file_get_contents( $css_file_path );

        if ( $css_content === false ) {
            return '<style>/* PandaScore Tracker: Could not read CSS file */</style>';
        }

        return '<style>' . $css_content . '</style>';
    }

    public function admin_menu() {
        add_options_page( 'PandaScore Tracker', 'PandaScore Tracker', 'manage_options', 'pandascore-tracker', array( $this, 'settings_page' ) );
    }

    public function register_settings() {
        register_setting( $this->option_key, $this->option_key );
        add_settings_section( 'pandascore_main', 'PandaScore Settings', null, 'pandascore-tracker' );
        add_settings_field( 'api_key', 'API Key', array( $this, 'field_api_key' ), 'pandascore-tracker', 'pandascore_main' );
    }

    public function field_api_key() {
        $opts = get_option( $this->option_key );
        $val = isset( $opts['api_key'] ) ? esc_attr( $opts['api_key'] ) : '';
        echo '<input type="text" name="'.$this->option_key.'[api_key]" value="'.$val.'" class="pandascore-api-key-input">';
    }

    public function settings_page() {
        echo $this->get_internal_styles();
        ?>
        <div class="wrap">
            <h1>PandaScore Tracker</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_key );
                do_settings_sections( 'pandascore-tracker' );
                submit_button();
                ?>
            </form>
            <h3>Shortcode Usage</h3>
            <p><strong>Basic usage:</strong> <code>[pandascore_tracker game="valorant" limit="5" align="right"]</code></p>
            <p><strong>Live matches:</strong> <code>[pandascore_tracker type="live" limit="5"]</code></p>
            <p><strong>Mixed (live + upcoming):</strong> <code>[pandascore_tracker type="mixed" game="valorant" limit="10"]</code></p>

            <h4>Parameters:</h4>
            <ul>
                <li><strong>game:</strong> Game type (valorant, lol, csgo, dota2, etc.)</li>
                <li><strong>limit:</strong> Number of matches to display (default: 5)</li>
                <li><strong>align:</strong> Text alignment (left, center, right) (default: right)</li>
                <li><strong>type:</strong> Match type - "upcoming" (default), "live", or "mixed"</li>
            </ul>
        </div>
        <?php
    }

    private function get_api_key() {
        $opts = get_option( $this->option_key );
        return isset( $opts['api_key'] ) ? trim( $opts['api_key'] ) : '';
    }

    private function get_league_ids() {
        $transient_key = 'pandascore_league_ids';
        $league_ids = get_transient( $transient_key );

        if ( false === $league_ids ) {
            $api_key = $this->get_api_key();
            if ( ! $api_key ) return new WP_Error( 'no_api_key', 'PandaScore API key not set' );

            $url = add_query_arg( array(
                'filter[name]' => 'LPL,LEC'
            ), 'https://api.pandascore.co/leagues' );

            $response = wp_remote_get( $url, array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                )
            ) );

            if ( is_wp_error( $response ) ) return $response;
            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) return new WP_Error( 'api_error', 'PandaScore API returned code '.$code );

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) return new WP_Error( 'json_error', 'Invalid JSON from API' );

            $league_ids = array(4786, 5345, 5346); // LCK, LTA North, LTA South
            foreach ( $data as $league ) {
                if ( in_array( $league['name'], array( 'LPL', 'LEC' ) ) && isset( $league['id'] ) ) {
                    $league_ids[] = $league['id'];
                }
            }

            $league_ids = array_unique( $league_ids );
            set_transient( $transient_key, $league_ids, DAY_IN_SECONDS ); // Cache for 24 hours
        }

        return $league_ids;
    }

    /**
     * Private helper method to make API calls to PandaScore
     *
     * @param string $game The game type (e.g., 'lol', 'valorant', 'csgo')
     * @param int $limit Number of matches to fetch
     * @param string $endpoint The API endpoint type ('upcoming' or 'running')
     * @return array|WP_Error The API response data or WP_Error on failure
     */
    private function make_api_call( $game, $limit, $endpoint ) {
        $transient_key = 'pandascore_' . $game . '_' . $endpoint . '_' . $limit;
        $cached_data = get_transient( $transient_key );

        if ( false !== $cached_data ) {
            return $cached_data;
        }

        $api_key = $this->get_api_key();
        if ( ! $api_key ) return new WP_Error( 'no_api_key', 'PandaScore API key not set' );

        $query_args = array(
            'page[size]' => intval( $limit )
        );

        if ( strtolower( $game ) === 'lol' ) {
            $league_ids = $this->get_league_ids();
            if ( is_wp_error( $league_ids ) ) return $league_ids;
            $query_args['filter[league_id]'] = implode( ',', $league_ids );
        }

        $url = add_query_arg( $query_args, "https://api.pandascore.co/{$game}/matches/{$endpoint}" );

        $response = wp_remote_get( $url, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            )
        ) );

        if ( is_wp_error( $response ) ) return $response;
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) return new WP_Error( 'api_error', 'PandaScore API returned code '.$code );

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) return new WP_Error( 'json_error', 'Invalid JSON from API' );

        // Set expiration based on endpoint
        $expiration = ( $endpoint === 'running' ) ? 60 : 300; // 1 min for live, 5 min for upcoming
        set_transient( $transient_key, $data, $expiration );

        return $data;
    }

    private function fetch_upcoming_matches( $game, $limit ) {
        return $this->make_api_call( $game, $limit, 'upcoming' );
    }

    private function fetch_live_matches( $game, $limit ) {
        return $this->make_api_call( $game, $limit, 'running' );
    }

    /**
     * Generate team logo HTML with fallback placeholder
     *
     * @param string $logo_url The team logo URL
     * @param string $team_name The team name for alt text and fallback
     * @param string $acronym The team acronym for fallback display
     * @return string HTML for team logo or placeholder
     */
    private function get_team_logo_html( $logo_url, $team_name, $acronym ) {
        if ( !empty( $logo_url ) ) {
            return '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $team_name ) . '" class="pandascore-team-logo">';
        } else {
            // Create fallback using first letter of acronym or team name
            $fallback_letter = '';
            if ( !empty( $acronym ) && $acronym !== 'N/A' ) {
                $fallback_letter = strtoupper( substr( $acronym, 0, 1 ) );
            } elseif ( !empty( $team_name ) && $team_name !== 'N/A' ) {
                $fallback_letter = strtoupper( substr( $team_name, 0, 1 ) );
            } else {
                $fallback_letter = '?';
            }

            return '<div class="pandascore-team-logo-placeholder" title="Unknown Team">' . esc_html( $fallback_letter ) . '</div>';
        }
    }

    public function shortcode_handler( $atts ) {
        $atts = shortcode_atts( array(
            'game' => 'lol',
            'limit' => 5,
            'align' => 'center',
            'type' => 'mixed'
        ), $atts, 'pandascore_tracker' );

        // Only enqueue custom CSS
        wp_enqueue_style( 'pandascore-custom-style' );

        $this->live_match_ids = array();

        // Add internal styles and main container with alignment class
        $align_class = 'align-' . esc_attr( $atts['align'] );
        $html = $this->get_internal_styles();
        $html .= '<div class="pandascore-tracker ' . $align_class . '">';

        if ( $atts['type'] === 'live' || $atts['type'] === 'mixed' ) {
            $html .= $this->render_live_matches( $atts['game'], $atts['limit'] );
        }

        if ( $atts['type'] === 'upcoming' || $atts['type'] === 'mixed' ) {
            $html .= $this->render_upcoming_matches( $atts['game'], $atts['limit'] );
        }

        if ( ! empty( $this->live_match_ids ) ) {
            wp_enqueue_script( 'pandascore-live-tracker-js' );
            wp_localize_script( 'pandascore-live-tracker-js', 'pandaScoreLiveTracker', array(
                'apiKey'       => $this->get_api_key(),
                'matchIds'     => array_unique( $this->live_match_ids ),
                'websocketUrl' => 'wss://websocket.pandascore.co/ws'
            ) );
        }

        // Enqueue timezone script
        wp_enqueue_script( 'pandascore-timezone-js' );

        $this->live_match_ids = array();

        $html .= '</div>';
        return $html;
    }

    private function render_live_matches( $game, $limit ) {
        $live_matches = $this->fetch_live_matches( $game, $limit );

        if ( is_wp_error( $live_matches ) || empty( $live_matches ) ) {
            return '';
        }

        $html = '<div class="pandascore-section-header">';
        $html .= '<span class="pandascore-live-indicator"></span>LIVE</div>';
        $html .= '<div class="pandascore-matches-container">';

        foreach ( $live_matches as $match ) {
            // Store live match ID for tracking
            if ( isset( $match['id'] ) ) {
                $this->live_match_ids[] = $match['id'];
            }

            $html .= $this->render_match( $match, true );
        }

        $html .= '</div>';
        return $html;
    }

    private function render_match($match, $is_live = false) {
        $opponents = array();
        $acronyms = array();
        $opponent_logos = array();
        $scores = array();
        $opponent_ids = array();

        if (isset($match['opponents']) && is_array($match['opponents'])) {
            foreach ($match['opponents'] as $o) {
                $name = isset($o['opponent']['name']) ? $o['opponent']['name'] : 'N/A';
                $acronym = !empty($o['opponent']['acronym']) ? $o['opponent']['acronym'] : $name;
                $logo = isset($o['opponent']['image_url']) ? $o['opponent']['image_url'] : '';
                $opponent_ids[] = isset($o['opponent']['id']) ? $o['opponent']['id'] : null;
                $opponents[] = esc_html($name);
                $acronyms[] = esc_html($acronym);
                $opponent_logos[] = $logo;
            }
        }

        if (isset($match['results']) && is_array($match['results'])) {
            foreach ($match['results'] as $r) {
                $scores[] = isset($r['score']) ? intval($r['score']) : 0;
            }
        }

        while (count($opponents) < 2) {
            $opponents[] = 'N/A';
            $acronyms[] = 'N/A';
            $opponent_logos[] = '';
            $opponent_ids[] = null;
        }
        while (count($scores) < 2) {
            $scores[] = $is_live ? rand(0, 2) : 0;
        }

        $league_name = isset($match['league']['name']) ? esc_html($match['league']['name']) : '';
        $league_logo = isset($match['league']['image_url']) ? esc_url($match['league']['image_url']) : '';

        $match_time = '';
        $match_day = '';
        $scheduled_at = '';
        if (isset($match['scheduled_at']) && $match['scheduled_at'] && !$is_live) {
            $timestamp = strtotime($match['scheduled_at']);
            if ($timestamp) {
                $scheduled_at = $match['scheduled_at']; // Store raw UTC time
            }
        }

        // Determine if this is an upcoming match (not live and has scheduled time)
        $is_upcoming = !$is_live && !empty($scheduled_at);

        $match_id = isset($match['id']) ? esc_attr($match['id']) : '';

        $html = '<div class="pandascore-match" data-match-id="' . $match_id . '"';

        if ($is_upcoming) {
            $html .= ' data-scheduled-at="' . esc_attr($scheduled_at) . '"';
        }

        $html .= '>';

        // League logo container
        $html .= '<div class="pandascore-league-container">';

        if ($league_logo) {
            $html .= '<div class="pandascore-league-logo"><img src="' . $league_logo . '" alt="' . $league_name . '"  title="' . esc_html($league_name) . '"></div>';
        } else {
            $html .= '<div class="pandascore-league-placeholder" title="' . esc_html($league_name) . '"> ' . (!empty($league_name) ? $league_name[0] : 'L') . '</div>';
        }

        $html .= '</div>';

        if ($is_upcoming) {
            // For upcoming matches, show teams on left and match time centered on right
            $html .= '<div class="pandascore-match-content">';

            // Teams container
            $html .= '<div class="pandascore-teams-container">';

            // Team 1
            $html .= '<div class="pandascore-team">';
            $html .= '<div class="pandascore-team-info">';
            $html .= $this->get_team_logo_html($opponent_logos[0], $opponents[0], $acronyms[0]);
            $html .= '<span class="pandascore-team-name" title="' . esc_attr($opponents[0]) . '">' . esc_html($acronyms[0]) . '</span>';
            $html .= '</div>';
            $html .= '</div>';

            // Team 2
            $html .= '<div class="pandascore-team">';
            $html .= '<div class="pandascore-team-info">';
            $html .= $this->get_team_logo_html($opponent_logos[1], $opponents[1], $acronyms[1]);
            $html .= '<span class="pandascore-team-name" title="' . esc_attr($opponents[1]) . '">' . esc_html($acronyms[1]) . '</span>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>'; // End teams container

            // Match time centered on the right (placeholder for JS)
            $html .= '<div class="pandascore-time-container">';
            $html .= '<div class="pandascore-time-badge">';
            $html .= '<div class="pandascore-time">Loading...</div>';
            $html .= '<div class="pandascore-time-day">Loading...</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>'; // End main container
        } else {
            // For live/completed matches, show normal layout with scores
            $html .= '<div class="pandascore-match-content live-layout">';

            // Team 1
            $html .= '<div class="pandascore-team with-score">';
            $html .= '<div class="pandascore-team-info">';
            $html .= $this->get_team_logo_html($opponent_logos[0], $opponents[0], $acronyms[0]);
            $html .= '<span class="pandascore-team-name" title="' . esc_attr($opponents[0]) . '">' . esc_html($acronyms[0]) . '</span>';
            $html .= '</div>';
            // Score container for team 1
            $html .= '<div class="pandascore-score" data-opponent-id="' . (isset($opponent_ids[0]) ? esc_attr($opponent_ids[0]) : '') . '">' . intval($scores[0]) . '</div>';
            $html .= '</div>';

            // Team 2
            $html .= '<div class="pandascore-team with-score">';
            $html .= '<div class="pandascore-team-info">';
            $html .= $this->get_team_logo_html($opponent_logos[1], $opponents[1], $acronyms[1]);
            $html .= '<span class="pandascore-team-name" title="' . esc_attr($opponents[1]) . '">' . esc_html($acronyms[1]) . '</span>';
            $html .= '</div>';
            // Score container for team 2
            $html .= '<div class="pandascore-score" data-opponent-id="' . (isset($opponent_ids[1]) ? esc_attr($opponent_ids[1]) : '') . '">' . intval($scores[1]) . '</div>';
            $html .= '</div>';

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    private function get_match_day_display($timestamp) {
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');
        $match_date = strtotime(date('Y-m-d', $timestamp));

        if ($match_date == $today) {
            return 'Today';
        } elseif ($match_date == $tomorrow) {
            return 'Tomorrow';
        } else {
            return date('M j', $timestamp);
        }
    }

    private function render_upcoming_matches($game, $limit) {
        $upcoming_matches = $this->fetch_upcoming_matches($game, $limit);

        if (is_wp_error($upcoming_matches)) {
            return '<div class="pandascore-error">Error: ' . esc_html($upcoming_matches->get_error_message()) . '</div>';
        }

        if (empty($upcoming_matches)) {
            return '<div class="pandascore-no-matches">No upcoming matches found.</div>';
        }

        $html = '<div class="pandascore-section-header">UPCOMING</div>';
        $html .= '<div class="pandascore-matches-container">';

        foreach ($upcoming_matches as $match) {
            $html .= $this->render_match($match, false);
        }

        $html .= '</div>';
        return $html;
    }

}

new PandaScore_Tracker_Plugin();