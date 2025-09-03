<?php
/**
 * Settings Management Component for PandaScore Tracker Plugin
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles all admin interface and settings functionality
 * 
 * Provides methods for managing plugin settings, admin pages,
 * and configuration validation.
 */
class PandaScore_Settings extends PandaScore_Base_Component {

    /**
     * Settings page slug
     *
     * @var string
     */
    private $page_slug = 'pandascore-tracker';

    /**
     * Settings sections
     *
     * @var array
     */
    private $sections = array();

    /**
     * Settings fields
     *
     * @var array
     */
    private $fields = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'init_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // AJAX handlers
        add_action( 'wp_ajax_pandascore_clear_cache', array( $this, 'ajax_clear_cache' ) );
        add_action( 'wp_ajax_pandascore_test_api', array( $this, 'ajax_test_api' ) );
    }

    /**
     * Initialize settings (called at admin_init when translations are available)
     */
    public function init_settings() {
        $this->define_settings();
        $this->register_settings();
    }

    /**
     * Define settings structure
     */
    private function define_settings() {
        $this->sections = array(
            'api' => array(
                'title'       => __( 'API Configuration', 'pandascore-tracker' ),
                'description' => __( 'Configure your PandaScore API settings.', 'pandascore-tracker' ),
            ),
            'display' => array(
                'title'       => __( 'Display Options', 'pandascore-tracker' ),
                'description' => __( 'Customize how matches are displayed.', 'pandascore-tracker' ),
            ),
            'cache' => array(
                'title'       => __( 'Cache Settings', 'pandascore-tracker' ),
                'description' => __( 'Configure caching options for better performance.', 'pandascore-tracker' ),
            ),
        );

        $this->fields = array(
            'api_key' => array(
                'section'     => 'api',
                'title'       => __( 'API Key', 'pandascore-tracker' ),
                'type'        => 'password',
                'description' => __( 'Your PandaScore API key. Get one from <a href="https://pandascore.co/users/sign_up" target="_blank">PandaScore</a>. Use the "Test API Connection" button below to verify your key.', 'pandascore-tracker' ),
                'required'    => true,
            ),
            'default_game' => array(
                'section'     => 'display',
                'title'       => __( 'Default Game', 'pandascore-tracker' ),
                'type'        => 'select',
                'description' => __( 'Default game to display when no game is specified in shortcode.', 'pandascore-tracker' ),
                'options'     => array(
                    'valorant' => 'Valorant',
                    'lol'      => 'League of Legends',
                    'csgo'     => 'CS:GO',
                    'dota2'    => 'Dota 2',
                    'ow'       => 'Overwatch',
                ),
                'default'     => 'valorant',
            ),
            'default_limit' => array(
                'section'     => 'display',
                'title'       => __( 'Default Match Limit', 'pandascore-tracker' ),
                'type'        => 'number',
                'description' => __( 'Default number of matches to display.', 'pandascore-tracker' ),
                'default'     => 5,
                'min'         => 1,
                'max'         => 50,
            ),
            'default_type' => array(
                'section'     => 'display',
                'title'       => __( 'Default Match Type', 'pandascore-tracker' ),
                'type'        => 'select',
                'description' => __( 'Default type of matches to display.', 'pandascore-tracker' ),
                'options'     => array(
                    'mixed'    => __( 'Mixed (Live + Upcoming)', 'pandascore-tracker' ),
                    'live'     => __( 'Live Only', 'pandascore-tracker' ),
                    'upcoming' => __( 'Upcoming Only', 'pandascore-tracker' ),
                ),
                'default'     => 'mixed',
            ),
            'default_align' => array(
                'section'     => 'display',
                'title'       => __( 'Default Alignment', 'pandascore-tracker' ),
                'type'        => 'select',
                'description' => __( 'Default text alignment for match displays.', 'pandascore-tracker' ),
                'options'     => array(
                    'left'   => __( 'Left', 'pandascore-tracker' ),
                    'center' => __( 'Center', 'pandascore-tracker' ),
                    'right'  => __( 'Right', 'pandascore-tracker' ),
                ),
                'default'     => 'center',
            ),
            'cache_enabled' => array(
                'section'     => 'cache',
                'title'       => __( 'Enable Caching', 'pandascore-tracker' ),
                'type'        => 'checkbox',
                'description' => __( 'Cache API responses to improve performance and reduce API calls.', 'pandascore-tracker' ),
                'default'     => true,
            ),
            'cache_duration' => array(
                'section'     => 'cache',
                'title'       => __( 'Cache Duration', 'pandascore-tracker' ),
                'type'        => 'select',
                'description' => __( 'How long to cache API responses.', 'pandascore-tracker' ),
                'options'     => array(
                    '60'   => __( '1 minute', 'pandascore-tracker' ),
                    '300'  => __( '5 minutes', 'pandascore-tracker' ),
                    '600'  => __( '10 minutes', 'pandascore-tracker' ),
                    '1800' => __( '30 minutes', 'pandascore-tracker' ),
                    '3600' => __( '1 hour', 'pandascore-tracker' ),
                ),
                'default'     => '300',
            ),
        );
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'PandaScore Tracker', 'pandascore-tracker' ),
            __( 'PandaScore Tracker', 'pandascore-tracker' ),
            'manage_options',
            $this->page_slug,
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register settings with WordPress
     */
    public function register_settings() {
        register_setting( $this->option_key, $this->option_key, array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
        ) );

        // Register sections
        foreach ( $this->sections as $section_id => $section ) {
            add_settings_section(
                $section_id,
                $section['title'],
                array( $this, 'render_section_description' ),
                $this->page_slug
            );
        }

        // Register fields
        foreach ( $this->fields as $field_id => $field ) {
            add_settings_field(
                $field_id,
                $field['title'],
                array( $this, 'render_field' ),
                $this->page_slug,
                $field['section'],
                array( 'field_id' => $field_id )
            );
        }
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook_suffix Current admin page hook suffix
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        if ( 'settings_page_' . $this->page_slug !== $hook_suffix ) {
            return;
        }

        wp_enqueue_style(
            'pandascore-admin',
            plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ),
            array(),
            $this->get_plugin_version()
        );

        wp_enqueue_script(
            'pandascore-admin',
            plugins_url( 'assets/js/admin.js', dirname( __FILE__ ) ),
            array( 'jquery' ),
            $this->get_plugin_version(),
            true
        );

        // Localize script with API status
        $api_handler = new PandaScore_API_Handler();
        wp_localize_script( 'pandascore-admin', 'pandascoreAdmin', array(
            'apiStatus' => $api_handler->get_api_status(),
            'nonce'     => wp_create_nonce( 'pandascore_admin' ),
        ) );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'pandascore-tracker' ) );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_key );
                do_settings_sections( $this->page_slug );
                submit_button();
                ?>
            </form>

            <div class="pandascore-admin-sidebar">
                <?php $this->render_shortcode_help(); ?>
                <?php $this->render_api_status(); ?>
                <?php $this->render_cache_controls(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render section description
     *
     * @param array $args Section arguments
     */
    public function render_section_description( $args ) {
        $section_id = $args['id'];
        if ( isset( $this->sections[$section_id]['description'] ) ) {
            echo '<p>' . wp_kses_post( $this->sections[$section_id]['description'] ) . '</p>';
        }
    }

    /**
     * Render individual field
     *
     * @param array $args Field arguments
     */
    public function render_field( $args ) {
        $field_id = $args['field_id'];
        $field = $this->fields[$field_id];
        $options = get_option( $this->option_key, array() );
        $value = isset( $options[$field_id] ) ? $options[$field_id] : ( $field['default'] ?? '' );

        $field_name = $this->option_key . '[' . $field_id . ']';
        $field_id_attr = $this->option_key . '_' . $field_id;

        switch ( $field['type'] ) {
            case 'text':
            case 'password':
                printf(
                    '<input type="%s" id="%s" name="%s" value="%s" class="regular-text" %s />',
                    esc_attr( $field['type'] ),
                    esc_attr( $field_id_attr ),
                    esc_attr( $field_name ),
                    esc_attr( $value ),
                    isset( $field['required'] ) && $field['required'] ? 'required' : ''
                );
                break;

            case 'number':
                printf(
                    '<input type="number" id="%s" name="%s" value="%s" min="%s" max="%s" class="small-text" />',
                    esc_attr( $field_id_attr ),
                    esc_attr( $field_name ),
                    esc_attr( $value ),
                    esc_attr( $field['min'] ?? '' ),
                    esc_attr( $field['max'] ?? '' )
                );
                break;

            case 'select':
                printf( '<select id="%s" name="%s">', esc_attr( $field_id_attr ), esc_attr( $field_name ) );
                foreach ( $field['options'] as $option_value => $option_label ) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr( $option_value ),
                        selected( $value, $option_value, false ),
                        esc_html( $option_label )
                    );
                }
                echo '</select>';
                break;

            case 'checkbox':
                printf(
                    '<input type="checkbox" id="%s" name="%s" value="1" %s />',
                    esc_attr( $field_id_attr ),
                    esc_attr( $field_name ),
                    checked( $value, 1, false )
                );
                break;
        }

        if ( ! empty( $field['description'] ) ) {
            printf( '<p class="description">%s</p>', wp_kses_post( $field['description'] ) );
        }
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        foreach ( $this->fields as $field_id => $field ) {
            if ( ! isset( $input[$field_id] ) ) {
                continue;
            }

            $value = $input[$field_id];

            switch ( $field['type'] ) {
                case 'text':
                case 'password':
                    $sanitized[$field_id] = sanitize_text_field( $value );
                    break;

                case 'number':
                    $sanitized[$field_id] = intval( $value );
                    if ( isset( $field['min'] ) && $sanitized[$field_id] < $field['min'] ) {
                        $sanitized[$field_id] = $field['min'];
                    }
                    if ( isset( $field['max'] ) && $sanitized[$field_id] > $field['max'] ) {
                        $sanitized[$field_id] = $field['max'];
                    }
                    break;

                case 'select':
                    $sanitized[$field_id] = in_array( $value, array_keys( $field['options'] ) ) ? $value : $field['default'];
                    break;

                case 'checkbox':
                    $sanitized[$field_id] = ! empty( $value ) ? 1 : 0;
                    break;
            }

            // Custom validation (removed API key validation to prevent memory issues)
            // API key validation is now handled via AJAX "Test API Connection" button
        }

        return $sanitized;
    }



    /**
     * Render shortcode help section
     */
    private function render_shortcode_help() {
        ?>
        <div class="pandascore-admin-box">
            <h3><?php esc_html_e( 'Shortcode Usage', 'pandascore-tracker' ); ?></h3>
            <p><strong><?php esc_html_e( 'Basic usage:', 'pandascore-tracker' ); ?></strong></p>
            <code>[pandascore_tracker game="valorant" limit="5" align="center"]</code>
            
            <p><strong><?php esc_html_e( 'Live matches:', 'pandascore-tracker' ); ?></strong></p>
            <code>[pandascore_tracker type="live" limit="5"]</code>
            
            <p><strong><?php esc_html_e( 'Mixed (live + upcoming):', 'pandascore-tracker' ); ?></strong></p>
            <code>[pandascore_tracker type="mixed" game="valorant" limit="10"]</code>

            <h4><?php esc_html_e( 'Parameters:', 'pandascore-tracker' ); ?></h4>
            <p><em><?php esc_html_e( 'All parameters are optional. If not specified, the plugin will use the default values configured in the settings above.', 'pandascore-tracker' ); ?></em></p>
            <ul>
                <li><strong>game:</strong> <?php esc_html_e( 'Game type (valorant, lol, csgo, dota2, etc.) - overrides Default Game setting', 'pandascore-tracker' ); ?></li>
                <li><strong>limit:</strong> <?php esc_html_e( 'Number of matches to display (1-50) - overrides Default Match Limit setting', 'pandascore-tracker' ); ?></li>
                <li><strong>type:</strong> <?php esc_html_e( 'Match type (mixed, live, upcoming) - overrides Default Match Type setting', 'pandascore-tracker' ); ?></li>
                <li><strong>align:</strong> <?php esc_html_e( 'Text alignment (left, center, right) - overrides Default Alignment setting', 'pandascore-tracker' ); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render API status section
     */
    private function render_api_status() {
        ?>
        <div class="pandascore-admin-box">
            <h3><?php esc_html_e( 'API Status', 'pandascore-tracker' ); ?></h3>
            <div id="pandascore-api-status">
                <p><?php esc_html_e( 'Loading...', 'pandascore-tracker' ); ?></p>
            </div>
            <button type="button" id="pandascore-test-api" class="button">
                <?php esc_html_e( 'Test API Connection', 'pandascore-tracker' ); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render cache controls section
     */
    private function render_cache_controls() {
        ?>
        <div class="pandascore-admin-box">
            <h3><?php esc_html_e( 'Cache Controls', 'pandascore-tracker' ); ?></h3>
            <p><?php esc_html_e( 'Clear cached API responses to force fresh data.', 'pandascore-tracker' ); ?></p>
            <button type="button" id="pandascore-clear-cache" class="button">
                <?php esc_html_e( 'Clear Cache', 'pandascore-tracker' ); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed  $default Default value
     * @return mixed Setting value
     */
    public function get_setting( $key, $default = null ) {
        $options = get_option( $this->option_key, array() );
        return isset( $options[$key] ) ? $options[$key] : $default;
    }

    /**
     * Update setting value
     *
     * @param string $key Setting key
     * @param mixed  $value Setting value
     * @return bool True on success
     */
    public function update_setting( $key, $value ) {
        $options = get_option( $this->option_key, array() );
        $options[$key] = $value;
        return update_option( $this->option_key, $options );
    }

    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'pandascore_admin' ) ) {
            wp_send_json_error( 'Security check failed' );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        try {
            // Clear cache
            $api_handler = new PandaScore_API_Handler();
            $api_handler->clear_all_cache();

            wp_send_json_success( 'Cache cleared successfully' );
        } catch ( Exception $e ) {
            wp_send_json_error( 'Failed to clear cache: ' . $e->getMessage() );
        }
    }

    /**
     * AJAX handler for testing API connection
     */
    public function ajax_test_api() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'pandascore_admin' ) ) {
            wp_send_json_error( 'Security check failed' );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        try {
            // Test API
            $api_handler = new PandaScore_API_Handler();
            $status = $api_handler->get_api_status();

            if ( $status['api_connectivity'] ) {
                wp_send_json_success( 'API connection successful' );
            } else {
                wp_send_json_error( $status['api_error'] ?? 'API connection failed' );
            }
        } catch ( Exception $e ) {
            wp_send_json_error( 'API test failed: ' . $e->getMessage() );
        }
    }
}
