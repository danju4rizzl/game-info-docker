<?php
/**
 * WordPress Function Stubs for IDE Support
 * This file provides function signatures for WordPress functions to help with IDE IntelliSense.
 * It should NOT be included in the actual plugin execution.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// WordPress Constants
if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', false );
}

// WordPress Core Functions
if ( ! function_exists( 'get_option' ) ) {
    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    function get_option( $option, $default = false ) { return $default; }
    
    /**
     * @param array|string $args
     * @param string $url
     * @return string
     */
    function add_query_arg( $args, $url = '' ) { return $url; }
    
    /**
     * @param string $url
     * @param array $args
     * @return array|WP_Error
     */
    function wp_remote_get( $url, $args = array() ) { return array(); }
    
    /**
     * @param mixed $thing
     * @return bool
     */
    function is_wp_error( $thing ) { return false; }
    
    /**
     * @param array $response
     * @return int
     */
    function wp_remote_retrieve_response_code( $response ) { return 200; }
    
    /**
     * @param array $response
     * @return string
     */
    function wp_remote_retrieve_body( $response ) { return ''; }
    
    /**
     * @param string $text
     * @return string
     */
    function esc_html( $text ) { return htmlspecialchars( $text ); }
    
    /**
     * @param string $text
     * @return string
     */
    function esc_attr( $text ) { return htmlspecialchars( $text ); }
    
    /**
     * @param string $url
     * @return string
     */
    function esc_url( $url ) { return $url; }
    
    /**
     * @param string $url
     * @return string
     */
    function esc_url_raw( $url ) { return $url; }
    
    /**
     * @param string $hook
     * @param callable $callback
     * @param int $priority
     * @param int $args
     */
    function add_action( $hook, $callback, $priority = 10, $args = 1 ) { }
    
    /**
     * @param string $tag
     * @param callable $callback
     */
    function add_shortcode( $tag, $callback ) { }
    
    /**
     * @param string $file
     * @return string
     */
    function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
    
    /**
     * @param string $path
     * @param string $plugin
     * @return string
     */
    function plugins_url( $path = '', $plugin = '' ) { return $path; }
    
    /**
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string|bool $ver
     * @param string $media
     */
    function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) { }
    
    /**
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string|bool $ver
     * @param bool $in_footer
     */
    function wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) { }
    
    /**
     * @param string $handle
     * @param string $list
     * @return bool
     */
    function wp_style_is( $handle, $list = 'enqueued' ) { return false; }
    
    /**
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string|bool $ver
     * @param string $media
     */
    function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) { }
    
    /**
     * @param string $handle
     * @param string $data
     */
    function wp_add_inline_style( $handle, $data ) { }
    
    /**
     * @param string $handle
     * @param string $src
     * @param array $deps
     * @param string|bool $ver
     * @param bool $in_footer
     */
    function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) { }
    
    /**
     * @param string $handle
     * @param string $object_name
     * @param array $l10n
     */
    function wp_localize_script( $handle, $object_name, $l10n ) { }
    
    /**
     * @param string $path
     * @param string $scheme
     * @return string
     */
    function rest_url( $path = '', $scheme = 'rest' ) { return $path; }
    
    /**
     * @param string|int $action
     * @return string
     */
    function wp_create_nonce( $action = -1 ) { return 'nonce'; }
    
    /**
     * @param array $pairs
     * @param array $atts
     * @param string $shortcode
     * @return array
     */
    function shortcode_atts( $pairs, $atts, $shortcode = '' ) { return array_merge( $pairs, (array) $atts ); }
    
    /**
     * @param string $namespace
     * @param string $route
     * @param array $args
     * @param bool $override
     */
    function register_rest_route( $namespace, $route, $args = array(), $override = false ) { }
    
    /**
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param callable $function
     */
    function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function = '' ) { }
    
    /**
     * @param string $option_group
     * @param string $option_name
     * @param array $args
     */
    function register_setting( $option_group, $option_name, $args = array() ) { }
    
    /**
     * @param string $id
     * @param string $title
     * @param callable $callback
     * @param string $page
     */
    function add_settings_section( $id, $title, $callback, $page ) { }
    
    /**
     * @param string $id
     * @param string $title
     * @param callable $callback
     * @param string $page
     * @param string $section
     * @param array $args
     */
    function add_settings_field( $id, $title, $callback, $page, $section = 'default', $args = array() ) { }
    
    /**
     * @param string $option_group
     */
    function settings_fields( $option_group ) { }
    
    /**
     * @param string $page
     */
    function do_settings_sections( $page ) { }
    
    /**
     * @param string $text
     * @param string $type
     * @param string $name
     * @param bool $wrap
     * @param array $other_attributes
     */
    function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) { }
    
    /**
     * @return bool
     */
    function __return_true() { return true; }
}

// WordPress Classes
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        /**
         * @param string $code
         * @param string $message
         * @param mixed $data
         */
        public function __construct( $code = '', $message = '', $data = '' ) {}
        
        /**
         * @return string
         */
        public function get_error_message() { return ''; }
    }
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
    class WP_REST_Request {
        /**
         * @param string $key
         * @return mixed
         */
        public function get_param( $key ) { return null; }
    }
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        /**
         * @param mixed $data
         * @param int $status
         * @param array $headers
         */
        public function __construct( $data = null, $status = 200, $headers = array() ) {}
    }
}
