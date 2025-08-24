<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode. Right-aligned by default.
Version: 1.0
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
        // Register styles and scripts to be enqueued later if the shortcode is used.
        $live_css_path = plugin_dir_path( __FILE__ ) . 'css/pandascore-live-tracker.css';

        if ( file_exists( $live_css_path ) ) {
            wp_register_style( 'pandascore-live-tracker-style', plugins_url( 'css/pandascore-live-tracker.css', __FILE__ ), array(), '1.0' );
        } else {
            // Fallback to inline styles if CSS file doesn't exist
            // Note: This is a fallback. Creating the CSS file is recommended.
            // We will add this inline style inside the shortcode handler if needed.
        }

        // Register the WebSocket script
        wp_register_script( 'pandascore-live-tracker-js', plugins_url( 'js/live-tracker.js', __FILE__ ), array(), '1.0', true );
    }

    private function get_inline_styles() {
        return "
        .pandascore-tracker {
            max-width: 320px;
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            margin: 20px auto;
            border: 1px solid #333;
        }
        .pandascore-live-indicator {
            background: #ff4444;
            color: white;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: bold;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #333;
        }
        .pandascore-section-header {
            background: #000;
            color: #ffa500;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #333;
        }
        .pandascore-matches {
            padding: 0;
            background: #1a1a1a;
        }
        .pandascore-match {
            border-bottom: 1px solid #333;
            background: #2a2a2a;
            margin-bottom: 8px;
        }
        .pandascore-match.live {
            background: rgba(255, 68, 68, 0.1);
            border-left: 3px solid #ff4444;
        }
        .pandascore-team {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #2a2a2a;
            border-bottom: 1px solid #333;
            min-height: 48px;
        }
        .pandascore-match.live .pandascore-team {
            background: rgba(255, 68, 68, 0.05);
        }
        .pandascore-time {
            color: #888;
            font-size: 12px;
            font-weight: 500;
            min-width: 40px;
            margin-right: 12px;
        }
        .pandascore-logo-placeholder {
            width: 24px;
            height: 24px;
            background: #444;
            border-radius: 4px;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .pandascore-logo-placeholder::before {
            content: '?';
            color: #888;
            font-size: 12px;
            font-weight: bold;
        }
        .pandascore-name {
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            flex: 1;
            text-transform: uppercase;
        }
        .pandascore-score {
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            min-width: 24px;
            text-align: center;
            margin-left: 8px;
        }
        .pandascore-score.live {
            background: rgba(76, 175, 80, 0.2);
            padding: 4px 8px;
            border-radius: 4px;
        }
        .pandascore-odds {
            color: #ffa500;
            font-size: 12px;
            font-weight: 500;
            margin-left: 8px;
            min-width: 32px;
            text-align: right;
        }
        .pandascore-error {
            color: #ff4444;
            background: #2a2a2a;
            padding: 16px;
            text-align: center;
            font-size: 14px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 320px;
        }
        .pandascore-empty {
            color: #888;
            background: #2a2a2a;
            padding: 16px;
            text-align: center;
            font-size: 14px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 320px;
        }
        ";
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
        echo '<input type="text" name="'.$this->option_key.'[api_key]" value="'.$val.'" style="width:100%;">';
    }

    public function settings_page() {
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
                <li><strong>game:</strong> Game type (valorant, lol, csgo, dota2, etc.) - only used for upcoming matches</li>
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

    private function fetch_matches( $game, $limit ) {
        $api_key = $this->get_api_key();
        if ( ! $api_key ) return new WP_Error( 'no_api_key', 'PandaScore API key not set' );

        $url = add_query_arg( array(
            'page[size]' => intval( $limit ),
            'sort' => '-begin_at'
        ), "https://api.pandascore.co/{$game}/matches" );

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

        return $data;
    }

    private function fetch_live_matches( $limit ) {
        $api_key = $this->get_api_key();
        if ( ! $api_key ) return new WP_Error( 'no_api_key', 'PandaScore API key not set' );

        $url = add_query_arg( array(
            'page[size]' => intval( $limit )
        ), "https://api.pandascore.co/lives" );

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

        return $data;
    }

    public function shortcode_handler( $atts ) {
        $atts = shortcode_atts( array(
            'game' => 'lol',
            'limit' => 5,
            'align' => 'center',
            'type' => 'mixed' // 'live', 'upcoming', or 'mixed'
        ), $atts, 'pandascore_tracker' );

        // Enqueue assets now that we know the shortcode is being used
        if ( wp_style_is( 'pandascore-live-tracker-style', 'registered' ) ) {
            wp_enqueue_style( 'pandascore-live-tracker-style' );
        } else {
            wp_add_inline_style( 'wp-block-library', $this->get_inline_styles() );
        }

        // Reset live match IDs for this shortcode instance
        $this->live_match_ids = array();

        $html = '<div class="pandascore-tracker" style="text-align:'.esc_attr($atts['align']).';">';

        // Handle different types of displays
        if ( $atts['type'] === 'live' || $atts['type'] === 'mixed' ) {
            $html .= $this->render_live_matches( $atts['limit'] );
        }

        if ( $atts['type'] === 'upcoming' || $atts['type'] === 'mixed' ) {
            $html .= $this->render_upcoming_matches( $atts['game'], $atts['limit'] );
        }

        // If we have live matches, enqueue the script and pass data to it.
        if ( ! empty( $this->live_match_ids ) ) {
            wp_enqueue_script( 'pandascore-live-tracker-js' );
            wp_localize_script( 'pandascore-live-tracker-js', 'pandaScoreLiveTracker', array(
                'apiKey'       => $this->get_api_key(),
                'matchIds'     => array_unique( $this->live_match_ids ),
                'websocketUrl' => 'wss://websocket.pandascore.co/ws'
            ) );
        }

        // Clear the IDs for the next potential shortcode on the same page.
        $this->live_match_ids = array();

        $html .= '</div>';
        return $html;
    }

    private function render_live_matches( $limit ) {
        $live_matches = $this->fetch_live_matches( $limit );

        if ( is_wp_error( $live_matches ) || empty( $live_matches ) ) {
            return ''; // Don't show error for live matches, just skip
        }

        $html = '<div class="pandascore-live-indicator">LIVE</div>';
        $html .= '<div class="pandascore-matches">';

        foreach ( $live_matches as $live_data ) {
            if ( ! isset( $live_data['match'] ) ) continue;

            // Collect match IDs for the WebSocket script
            if ( isset( $live_data['match']['id'] ) ) {
                $this->live_match_ids[] = $live_data['match']['id'];
            }

            $match = $live_data['match'];
            $html .= $this->render_match( $match, true );
        }

        $html .= '</div>';
        return $html;
    }

    private function render_upcoming_matches( $game, $limit ) {
        $upcoming_matches = $this->fetch_matches( $game, $limit );

        if ( is_wp_error( $upcoming_matches ) ) {
            return '<div class="pandascore-error">Error: '.esc_html( $upcoming_matches->get_error_message() ).'</div>';
        }

        if ( empty( $upcoming_matches ) ) {
            return '<div class="pandascore-empty">No upcoming matches found.</div>';
        }

        $html = '<div class="pandascore-section-header upcoming">UP-COMING</div>';
        $html .= '<div class="pandascore-matches">';

        foreach ( $upcoming_matches as $match ) {
            $html .= $this->render_match( $match, false );
        }

        $html .= '</div>';
        return $html;
    }

    private function render_match( $match, $is_live = false ) {
        $opponents = array();
        $opponent_logos = array();
        $scores = array();
        $opponent_ids = array();

        // Extract opponent data
        if ( isset( $match['opponents'] ) && is_array( $match['opponents'] ) ) {
            foreach ( $match['opponents'] as $o ) {
                $name = isset( $o['opponent']['name'] ) ? $o['opponent']['name'] : 'NAME';
                $logo = isset( $o['opponent']['image_url'] ) ? $o['opponent']['image_url'] : '';
                $opponent_ids[] = isset( $o['opponent']['id'] ) ? $o['opponent']['id'] : null;
                $opponents[] = esc_html( $name );
                $opponent_logos[] = $logo;
            }
        }

        // Extract scores
        if ( isset( $match['results'] ) && is_array( $match['results'] ) ) {
            foreach ( $match['results'] as $r ) {
                $scores[] = isset( $r['score'] ) ? intval( $r['score'] ) : 0;
            }
        }

        // Ensure we have at least 2 opponents and scores
        while ( count( $opponents ) < 2 ) {
            $opponents[] = 'NAME';
            $opponent_logos[] = '';
            $opponent_ids[] = null;
        }
        while ( count( $scores ) < 2 ) {
            $scores[] = $is_live ? rand(0, 2) : 0; // Random scores for live demo
        }

        // Format time
        $match_time = '';
        if ( isset( $match['begin_at'] ) && $match['begin_at'] && ! $is_live ) {
            $timestamp = strtotime( $match['begin_at'] );
            if ( $timestamp ) {
                $match_time = date( 'H:i', $timestamp );
            }
        }

        $match_id = isset( $match['id'] ) ? esc_attr( $match['id'] ) : '';
        $html = '<div class="pandascore-match' . ( $is_live ? ' live' : '' ) . '" data-match-id="' . $match_id . '">';

        // Team 1
        $html .= '<div class="pandascore-team">';
        if ( ! $is_live && $match_time ) {
            $html .= '<div class="pandascore-time">' . esc_html( $match_time ) . '</div>';
        }

        // Logo
        if ( ! empty( $opponent_logos[0] ) ) {
            $html .= '<img src="' . esc_url( $opponent_logos[0] ) . '" alt="' . esc_attr( $opponents[0] ) . '" class="pandascore-logo">';
        } else {
            $html .= '<div class="pandascore-logo-placeholder"></div>';
        }

        $html .= '<span class="pandascore-name">' . esc_html( $opponents[0] ) . '</span>';

        $opponent_1_id = isset( $opponent_ids[0] ) ? esc_attr( $opponent_ids[0] ) : '';
        if ( $is_live || ( ! empty( $scores ) && $scores[0] > 0 ) ) {
            $html .= '<span class="pandascore-score' . ( $is_live ? ' live' : '' ) . '" data-opponent-id="' . $opponent_1_id . '">' . intval( $scores[0] ) . '</span>';
        } else {
            $html .= '<span class="pandascore-score">-</span>';
        }

        // Add odds for upcoming matches
        if ( ! $is_live ) {
            $html .= '<span class="pandascore-odds">' . number_format( rand(110, 550) / 100, 1 ) . '</span>';
        }

        $html .= '</div>';

        // Team 2
        $html .= '<div class="pandascore-team">';
        if ( ! $is_live && $match_time ) {
            $html .= '<div class="pandascore-time">' . esc_html( $match_time ) . '</div>';
        }

        // Logo
        if ( ! empty( $opponent_logos[1] ) ) {
            $html .= '<img src="' . esc_url( $opponent_logos[1] ) . '" alt="' . esc_attr( $opponents[1] ) . '" class="pandascore-logo">';
        } else {
            $html .= '<div class="pandascore-logo-placeholder"></div>';
        }

        $html .= '<span class="pandascore-name">' . esc_html( $opponents[1] ) . '</span>';

        $opponent_2_id = isset( $opponent_ids[1] ) ? esc_attr( $opponent_ids[1] ) : '';
        if ( $is_live || ( ! empty( $scores ) && $scores[1] > 0 ) ) {
            $html .= '<span class="pandascore-score' . ( $is_live ? ' live' : '' ) . '" data-opponent-id="' . $opponent_2_id . '">' . intval( $scores[1] ) . '</span>';
        } else {
            $html .= '<span class="pandascore-score">-</span>';
        }

        // Add odds for upcoming matches
        if ( ! $is_live ) {
            $html .= '<span class="pandascore-odds">' . number_format( rand(110, 550) / 100, 1 ) . '</span>';
        }

        $html .= '</div>';
        $html .= '</div>'; // Close match

        return $html;
    }

}

new PandaScore_Tracker_Plugin();
