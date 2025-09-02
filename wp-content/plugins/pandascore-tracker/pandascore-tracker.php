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
            plugins_url( 'css/custom.css', __FILE__ ),
            array(),
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
            'game' => 'csgo',
            'limit' => 5,
            'align' => 'center',
            'type' => 'mixed'
        ), $atts, 'pandascore_tracker' );

        // Only enqueue custom CSS
        wp_enqueue_style( 'pandascore-custom-style' );

        $this->live_match_ids = array();

        $align_style = "text-align:{$atts['align']};";
        $html = '<div class="pandascore-tracker" style="'.$align_style.'">';

        if ( $atts['type'] === 'live' || $atts['type'] === 'mixed' ) {
            $html .= $this->render_live_matches( $atts['limit'] );
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

        $this->live_match_ids = array();

        $html .= '</div>';
        return $html;
    }

    private function render_live_matches( $limit ) {
        $live_matches = $this->fetch_live_matches( $limit );

        if ( is_wp_error( $live_matches ) || empty( $live_matches ) ) {
            return '';
        }

        $html = '<div style="font-weight:600;display:flex;align-items:center;margin-bottom:10px;text-align:left;font-family:inter;color:#FFC700">
        <span style="background:#FFC700;border-radius:100%;width:8px;height:8px;display:inline-block;margin:0 7px;"> </span>LIVE</div>';
        $html .= '<div style="display:flex;flex-direction:column;gap:10px;">';

        foreach ( $live_matches as $live_data ) {
            if ( ! isset( $live_data['match'] ) ) continue;

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

        if (isset($match['opponents']) && is_array($match['opponents'])) {
            foreach ($match['opponents'] as $o) {
                $name = isset($o['opponent']['name']) ? $o['opponent']['name'] : 'NAME';
                $logo = isset($o['opponent']['image_url']) ? $o['opponent']['image_url'] : '';
                $opponent_ids[] = isset($o['opponent']['id']) ? $o['opponent']['id'] : null;
                $opponents[] = esc_html($name);
                $opponent_logos[] = $logo;
            }
        }

        if (isset($match['results']) && is_array($match['results'])) {
            foreach ($match['results'] as $r) {
                $scores[] = isset($r['score']) ? intval($r['score']) : 0;
            }
        }

        while (count($opponents) < 2) {
            $opponents[] = 'NAME';
            $opponent_logos[] = '';
            $opponent_ids[] = null;
        }
        while (count($scores) < 2) {
            $scores[] = $is_live ? rand(0, 2) : 0;
        }

        $league_name = isset($match['league']['name']) ? esc_html($match['league']['name']) : '';
        $league_logo = isset($match['league']['image_url']) ? esc_url($match['league']['image_url']) : '';

        $match_time = '';
        if (isset($match['scheduled_at']) && $match['scheduled_at'] && !$is_live) {
            $timestamp = strtotime($match['scheduled_at']);
            if ($timestamp) {
                $match_time = date('H:i', $timestamp);
            }
        }

        $match_id = isset($match['id']) ? esc_attr($match['id']) : '';

        $html = '<div class="pandascore-match" style="background:#1a1a1e;color:#fff;padding:10px;border-radius:8px;display:flex;align-items:center;justify-content:space-between;gap:10px;min-width:250px; data-match-id="'.$match_id.'">';

        // League logo and match time container
        $html .= '<div style="display:flex;flex-direction:column;align-items:center;gap:5px;">';

        if ($league_logo) {
            $html .= '<div style="background:#FFC700;padding:5px;border-radius:6px;"><img src="' . $league_logo . '" alt="' . $league_name . '" style="width:40px;height:40px;object-fit:contain;"></div>';
        }else {
            $html .= '<div style="background:#FFC700;padding:5px;border-radius:6px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;">'. $league_name[0] .'</div>';
        }

        if ($match_time) {
            $html .= '<div style="font-size:12px;color:#FFC700;text-align:center;">'.esc_html($match_time).'</div>';
        }

        $html .= '</div>';

        $html .= '<div style="flex:1;display:flex;flex-direction:column;gap:5px;">';

        // Team 1
        $html .= '<div style="display:flex;align-items:center;justify-content:space-between;">';
        if (!empty($opponent_logos[0])) {
            $html .= '<img src="'.esc_url($opponent_logos[0]).'" alt="'.esc_attr($opponents[0]).'" style="width:24px;height:24px;object-fit:contain;margin-right:5px;">';
        }
        $html .= '<span style="flex:1;font-size:14px;">'.esc_html($opponents[0]).'</span>';
        $html .= '<div style="background:#707073;color:#fff;padding:3px 8px;border-radius:4px;min-width:20px;text-align:center;" data-opponent-id="'.(isset($opponent_ids[0]) ? esc_attr($opponent_ids[0]) : '').'">'.intval($scores[0]).'</div>';
        $html .= '</div>';

        // Team 2
        $html .= '<div style="display:flex;align-items:center;justify-content:space-between;">';
        if (!empty($opponent_logos[1])) {
            $html .= '<img src="'.esc_url($opponent_logos[1]).'" alt="'.esc_attr($opponents[1]).'" style="width:24px;height:24px;object-fit:contain;margin-right:5px;">';
        }
        $html .= '<span style="flex:1;font-size:14px;">'.esc_html($opponents[1]).'</span>';
        $html .= '<div style="background:#707073;color:#fff;padding:3px 8px;border-radius:4px;min-width:20px;text-align:center;" data-opponent-id="'.(isset($opponent_ids[1]) ? esc_attr($opponent_ids[1]) : '').'">'.intval($scores[1]).'</div>';
        $html .= '</div>';

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    private function render_upcoming_matches($game, $limit) {
        $upcoming_matches = $this->fetch_matches($game, $limit);

        if (is_wp_error($upcoming_matches)) {
            return '<div style="background:#f8d7da;border:1px solid #f5c2c7;color:#842029;padding:10px;border-radius:6px;">Error: '.esc_html($upcoming_matches->get_error_message()).'</div>';
        }

        if (empty($upcoming_matches)) {
            return '<div style="background:#eee;border:1px solid #ccc;color:#333;padding:10px;border-radius:6px;">No upcoming matches found.</div>';
        }

        $html = '<div style="font-weight:600;margin-bottom:10px;text-align:left;font-family:inter;color:#FFC700">UPCOMING</div>';
        $html .= '<div style="display:flex;flex-direction:column;gap:10px;min-width: 250px;">';

        foreach ($upcoming_matches as $match) {
            $html .= $this->render_match($match, false);
        }

        $html .= '</div>';
        return $html;
    }

}

new PandaScore_Tracker_Plugin();