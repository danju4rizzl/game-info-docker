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

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_shortcode( 'pandascore_tracker', array( $this, 'shortcode_handler' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_register_style( 'pandascore-tracker-style', plugins_url( 'css/pandascore-tracker.css', __FILE__ ) );
        wp_enqueue_style( 'pandascore-tracker-style' );
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
            <p>Shortcode usage: <code>[pandascore_tracker game="valorant" limit="5" align="right"]</code></p>
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

    public function shortcode_handler( $atts ) {
        $atts = shortcode_atts( array(
            'game' => 'valorant',
            'limit' => 5,
            'align' => 'right'
        ), $atts, 'pandascore_tracker' );

        $matches = $this->fetch_matches( $atts['game'], $atts['limit'] );
        if ( is_wp_error( $matches ) ) {
            return '<div class="pandascore-tracker pandascore-error" style="text-align:'.esc_attr($atts['align']).';">Error: '.esc_html( $matches->get_error_message() ).'</div>';
        }

        if ( empty( $matches ) ) {
            return '<div class="pandascore-tracker" style="text-align:'.esc_attr($atts['align']).';">No matches found.</div>';
        }

        $html = '<div class="pandascore-tracker" style="text-align:'.esc_attr($atts['align']).';">';
        $html .= '<ul class="pandascore-list">';
        foreach ( $matches as $m ) {
            $opponents = array();
            if ( isset( $m['opponents'] ) && is_array( $m['opponents'] ) ) {
                foreach ( $m['opponents'] as $o ) {
                    $name = isset( $o['opponent']['name'] ) ? $o['opponent']['name'] : 'Unknown';
                    $opponents[] = esc_html( $name );
                }
            }
            $score = '';
            if ( isset( $m['results'] ) && is_array( $m['results'] ) ) {
                $parts = array();
                foreach ( $m['results'] as $r ) {
                    $parts[] = isset( $r['score'] ) ? intval( $r['score'] ) : '';
                }
                $score = implode( ' - ', $parts );
            }
            $begin = isset( $m['begin_at'] ) ? esc_html( $m['begin_at'] ) : '';
            $html .= '<li class="pandascore-item"><strong>'.implode( ' vs ', $opponents ).'</strong>';
            if ( $score !== '' ) $html .= ' — <span class="pandascore-score">'.$score.'</span>';
            if ( $begin ) $html .= ' <div class="pandascore-time">'.esc_html( $begin ).'</div>';
            $html .= '</li>';
        }
        $html .= '</ul></div>';

        return $html;
    }

}

new PandaScore_Tracker_Plugin();
