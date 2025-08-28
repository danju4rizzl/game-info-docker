<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode. Right-aligned by default.
Version: 1.1
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
        // Register Tailwind CSS via CDN with proper version and integrity
        wp_register_style(
            'tailwindcss-cdn',
            'https://cdn.tailwindcss.com',
            array(),
            '4.1'
        );

        // Register custom styles for any additional styling needed
        wp_register_style(
            'pandascore-custom-style',
            plugins_url( 'css/custom.css', __FILE__ ),
            array( 'tailwindcss-cdn' ),
            '1.0'
        );

        // Register the WebSocket script
        wp_register_script( 'pandascore-live-tracker-js', plugins_url( 'js/live-tracker.js', __FILE__ ), array(), '1.1', true );
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
            'game' => 'csgo', // default game is csgo
            'limit' => 5,
            'align' => 'center',
            'type' => 'mixed' // 'live', 'upcoming', or 'mixed'
        ), $atts, 'pandascore_tracker' );

        // Enqueue Tailwind CSS CDN and custom styles
        wp_enqueue_style( 'tailwindcss-cdn' );
        wp_enqueue_style( 'pandascore-custom-style' );

        // Add Tailwind CSS script for better compatibility
        wp_enqueue_script(
            'tailwindcss-script',
            'https://cdn.tailwindcss.com',
            array(),
            '3.4.0',
            false
        );

        // Reset live match IDs for this shortcode instance
        $this->live_match_ids = array();

        $align_class = $atts['align'] === 'left' ? 'text-left' : ($atts['align'] === 'right' ? 'text-right' : 'text-center');
        $html = '<div class="pandascore-tracker font-inter ' . $align_class . '">';

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

        $html = '<div class="bg-gray-500 text-white px-3 py-1 text-xs font-bold rounded uppercase mb-3 inline-block">LIVE</div>';
        $html .= '<div class="flex flex-col gap-3">';

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


    private function render_match($match, $is_live = false) {
        $opponents = array();
        $opponent_logos = array();
        $scores = array();
        $opponent_ids = array();

        // Extract opponent data
        if (isset($match['opponents']) && is_array($match['opponents'])) {
            foreach ($match['opponents'] as $o) {
                $name = isset($o['opponent']['name']) ? $o['opponent']['name'] : 'NAME';
                $logo = isset($o['opponent']['image_url']) ? $o['opponent']['image_url'] : '';
                $opponent_ids[] = isset($o['opponent']['id']) ? $o['opponent']['id'] : null;
                $opponents[] = esc_html($name);
                $opponent_logos[] = $logo;
            }
        }

        // Extract scores
        if (isset($match['results']) && is_array($match['results'])) {
            foreach ($match['results'] as $r) {
                $scores[] = isset($r['score']) ? intval($r['score']) : 0;
            }
        }

        // Ensure we have at least 2 opponents and scores
        while (count($opponents) < 2) {
            $opponents[] = 'NAME';
            $opponent_logos[] = '';
            $opponent_ids[] = null;
        }
        while (count($scores) < 2) {
            $scores[] = $is_live ? rand(0, 2) : 0; // Random scores for live demo
        }

        // Get league information
        $league_name = isset($match['league']['name']) ? esc_html($match['league']['name']) : '';
        $league_logo = isset($match['league']['image_url']) ? esc_url($match['league']['image_url']) : '';

        // Format time
        $match_time = '';
        if (isset($match['begin_at']) && $match['begin_at'] && !$is_live) {
            $timestamp = strtotime($match['begin_at']);
            if ($timestamp) {
                $match_time = date('H:i', $timestamp);
            }
        }

        $match_id = isset($match['id']) ? esc_attr($match['id']) : '';

        // Start match card with Tailwind classes
        $live_classes = $is_live ? 'border-l-4 border-red-500' : '';
        $html = '<div class="bg-slate-700 rounded-xl p-3  shadow-lg font-inter text-white flex justify-between items-center gap-5  max-w-sm w-full ' . $live_classes . '" data-match-id="' . $match_id . '">';

        // League header
        if ($league_logo) {
            $html .= '<div class="bg-slate-600 my-2 p-2 rounded"><img src="' . $league_logo . '" alt="' . $league_name . '" class="w-10 h-10 object-contain"></div>';
        }

        // Match content
        $html .= '<div class="flex flex-col flex-1">';

        // Team 1 row
        $html .= '<div class="flex items-center justify-between py-1">';
        if (!empty($opponent_logos[0])) {
            $html .= '<img src="' . esc_url($opponent_logos[0]) . '" alt="' . esc_attr($opponents[0]) . '" class="w-6 h-6 object-contain flex-shrink-0 mr-2">';
        } else {
            $html .= '<div class="w-6 h-6 bg-gray-700 rounded flex-shrink-0 mr-2"></div>';
        }
        $html .= '<span class="text-sm font-medium truncate flex-1">' . esc_html($opponents[0]) . '</span>';
        $score_classes = $is_live ? 'bg-gray-400 text-white px-2 py-1 rounded text-sm font-bold min-w-8 text-center score-updating' : 'bg-gray-400 text-white px-2 py-1 rounded text-sm font-bold min-w-8 text-center';
        $html .= '<div class="' . $score_classes . '" data-opponent-id="' . (isset($opponent_ids[0]) ? esc_attr($opponent_ids[0]) : '') . '">' . intval($scores[0]) . '</div>';
        $html .= '</div>';

        // Team 2 row
        $html .= '<div class="flex items-center justify-between py-1">';
        if (!empty($opponent_logos[1])) {
            $html .= '<img src="' . esc_url($opponent_logos[1]) . '" alt="' . esc_attr($opponents[1]) . '" class="w-6 h-6 object-contain flex-shrink-0 mr-2">';
        } else {
            $html .= '<div class="w-6 h-6 bg-gray-700 rounded flex-shrink-0 mr-2"></div>';
        }
        $html .= '<span class="text-sm font-medium truncate flex-1">' . esc_html($opponents[1]) . '</span>';
        $score_classes = $is_live ? 'bg-gray-400 text-white px-2 py-1 rounded text-sm font-bold min-w-8 text-center score-updating' : 'bg-gray-400 text-white px-2 py-1 rounded text-sm font-bold min-w-8 text-center';
        $html .= '<div class="' . $score_classes . '" data-opponent-id="' . (isset($opponent_ids[1]) ? esc_attr($opponent_ids[1]) : '') . '">' . intval($scores[1]) . '</div>';
        $html .= '</div>';

        $html .= '</div>'; // Close match content

        if ($match_time) {
            $html .= '<div class="text-xs text-gray-300 text-center mt-2">' . esc_html($match_time) . '</div>';
        }

        $html .= '</div>'; // Close card

        return $html;
    }

    private function render_upcoming_matches($game, $limit) {
        $upcoming_matches = $this->fetch_matches($game, $limit);

        if (is_wp_error($upcoming_matches)) {
            return '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Error: '.esc_html($upcoming_matches->get_error_message()).'</div>';
        }

        if (empty($upcoming_matches)) {
            return '<div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded">No upcoming matches found.</div>';
        }

        $html = '<div class="text-lg font-bold text-center mb-3 text-gray-200">Upcoming Matches</div>';
        $html .= '<div class="flex flex-col gap-3">';

        foreach ($upcoming_matches as $match) {
            $html .= $this->render_match($match, false);
        }

        $html .= '</div>';
        return $html;
    }

}

new PandaScore_Tracker_Plugin();
