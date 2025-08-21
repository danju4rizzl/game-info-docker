<?php
/*
Plugin Name: Panda Score API Tracker
Description: Fetches and displays PandaScore game scores via shortcode. Right-aligned by default.
Version: 1.4
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register activation and deactivation hooks
// Note: Uninstall is handled by uninstall.php file
register_activation_hook( __FILE__, 'pandascore_tracker_activate' );
register_deactivation_hook( __FILE__, 'pandascore_tracker_deactivate' );

/**
 * Plugin activation hook
 */
function pandascore_tracker_activate() {
    // Set default options on activation
    $default_options = array(
        'api_key' => '',
        'version' => '1.1'
    );

    if ( ! get_option( 'pandascore_tracker_options' ) ) {
        add_option( 'pandascore_tracker_options', $default_options );
    }
}

/**
 * Plugin deactivation hook
 */
function pandascore_tracker_deactivate() {
    // Clear any cached data when plugin is deactivated
    delete_transient( 'pandascore_tracker_matches' );
    delete_transient( 'pandascore_tracker_api_cache' );

    // Clear WordPress cache
    wp_cache_flush();
}



class PandaScore_Tracker_Plugin {
    private $option_key = 'pandascore_tracker_options';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_shortcode( 'pandascore_tracker', array( $this, 'shortcode_handler' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        // Get the CSS file path
        $css_file = plugin_dir_path( __FILE__ ) . 'css/pandascore-tracker.css';
        $css_url = plugins_url( 'css/pandascore-tracker.css', __FILE__ );

        // Always add inline styles as primary method to ensure compatibility
        add_action( 'wp_head', array( $this, 'add_inline_styles' ), 5 );

        // Also try to enqueue external CSS file if it exists (as backup)
        if ( file_exists( $css_file ) ) {
            wp_enqueue_style(
                'pandascore-tracker-style',
                $css_url,
                array(),
                '1.3',
                'all'
            );
        }
    }

    public function add_inline_styles() {
        ?>
        <style type="text/css">
        /* PandaScore Tracker Inline Styles */
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
        .pandascore-header {
            background: #000;
            color: #fff;
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
        }
        .pandascore-match:last-child {
            border-bottom: none;
        }
        .pandascore-team {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            background: #2a2a2a;
            border-bottom: 1px solid #333;
            transition: background-color 0.2s ease;
            min-height: 48px;
        }
        .pandascore-team:hover {
            background: #333;
        }
        .pandascore-team:last-child {
            border-bottom: none;
        }
        .pandascore-time {
            color: #888;
            font-size: 12px;
            font-weight: 500;
            min-width: 40px;
            margin-right: 12px;
            text-align: left;
        }
        .pandascore-logo {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            margin-right: 12px;
            object-fit: cover;
            flex-shrink: 0;
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
            position: relative;
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pandascore-dash {
            color: #666;
            font-size: 14px;
            margin-left: 8px;
            min-width: 12px;
            text-align: center;
            font-weight: 500;
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
        @media (max-width: 480px) {
            .pandascore-tracker {
                max-width: 100%;
                margin: 10px auto;
                border-radius: 6px;
            }
            .pandascore-team {
                padding: 10px 12px;
                min-height: 44px;
            }
            .pandascore-name {
                font-size: 13px;
            }
            .pandascore-time {
                font-size: 11px;
                min-width: 35px;
                margin-right: 8px;
            }
            .pandascore-logo,
            .pandascore-logo-placeholder {
                width: 20px;
                height: 20px;
                margin-right: 8px;
            }
            .pandascore-header {
                padding: 10px 12px;
                font-size: 13px;
            }
        }
        </style>
        <?php
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
            'align' => 'center',
            'title' => 'UP-COMING'
        ), $atts, 'pandascore_tracker' );

        $matches = $this->fetch_matches( $atts['game'], $atts['limit'] );
        if ( is_wp_error( $matches ) ) {
            return '<div class="pandascore-error">Error: '.esc_html( $matches->get_error_message() ).'</div>';
        }

        if ( empty( $matches ) ) {
            return '<div class="pandascore-empty">No matches found for '.esc_html($atts['game']).'.</div>';
        }

        $html = '<div class="pandascore-tracker">';
        $html .= '<div class="pandascore-header">'.esc_html($atts['title']).'</div>';
        $html .= '<div class="pandascore-matches">';

        foreach ( $matches as $m ) {
            $opponents = array();
            $opponent_logos = array();

            if ( isset( $m['opponents'] ) && is_array( $m['opponents'] ) ) {
                foreach ( $m['opponents'] as $o ) {
                    $name = isset( $o['opponent']['name'] ) ? $o['opponent']['name'] : 'Unknown';
                    $logo = isset( $o['opponent']['image_url'] ) ? $o['opponent']['image_url'] : '';
                    $opponents[] = esc_html( $name );
                    $opponent_logos[] = $logo;
                }
            }

            // Format the match time
            $match_time = '';
            if ( isset( $m['begin_at'] ) && $m['begin_at'] ) {
                $timestamp = strtotime( $m['begin_at'] );
                if ( $timestamp ) {
                    $match_time = date( 'H:i', $timestamp );
                }
            }

            $html .= '<div class="pandascore-match">';

            // Team 1
            if ( isset( $opponents[0] ) ) {
                $html .= '<div class="pandascore-team">';
                $html .= '<div class="pandascore-time">' . esc_html( $match_time ) . '</div>';
                if ( !empty( $opponent_logos[0] ) ) {
                    $html .= '<img src="' . esc_url( $opponent_logos[0] ) . '" alt="' . esc_attr( $opponents[0] ) . '" class="pandascore-logo">';
                } else {
                    $html .= '<div class="pandascore-logo-placeholder"></div>';
                }
                $html .= '<span class="pandascore-name">' . esc_html( $opponents[0] ) . '</span>';
                $html .= '<span class="pandascore-dash">-</span>';
                $html .= '<span class="pandascore-dash">-</span>';
                $html .= '</div>';
            }

            // Team 2
            if ( isset( $opponents[1] ) ) {
                $html .= '<div class="pandascore-team">';
                $html .= '<div class="pandascore-time">' . esc_html( $match_time ) . '</div>';
                if ( !empty( $opponent_logos[1] ) ) {
                    $html .= '<img src="' . esc_url( $opponent_logos[1] ) . '" alt="' . esc_attr( $opponents[1] ) . '" class="pandascore-logo">';
                } else {
                    $html .= '<div class="pandascore-logo-placeholder"></div>';
                }
                $html .= '<span class="pandascore-name">' . esc_html( $opponents[1] ) . '</span>';
                $html .= '<span class="pandascore-dash">-</span>';
                $html .= '<span class="pandascore-dash">-</span>';
                $html .= '</div>';
            }

            $html .= '</div>'; // Close match
        }

        $html .= '</div>'; // Close matches
        $html .= '</div>'; // Close tracker

        return $html;
    }

}

new PandaScore_Tracker_Plugin();
