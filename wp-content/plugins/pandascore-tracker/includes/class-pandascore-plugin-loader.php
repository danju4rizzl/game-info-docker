<?php
/**
 * Plugin Loader Class for PandaScore Tracker Plugin
 *
 * Handles class autoloading, dependency injection, and component
 * initialization for the plugin.
 *
 * @package PandaScore_Tracker
 * @since 1.2.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PandaScore Plugin Loader Class
 *
 * Bootstraps the plugin and manages component dependencies
 */
class PandaScore_Plugin_Loader {

    /**
     * Plugin version
     *
     * @var string
     */
    private $version = '1.2.0';

    /**
     * Plugin directory path
     *
     * @var string
     */
    private $plugin_path;

    /**
     * Plugin directory URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Component instances
     *
     * @var array
     */
    private $components = array();

    /**
     * Whether the plugin has been initialized
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Constructor
     *
     * @param string $plugin_file Main plugin file path
     */
    public function __construct( $plugin_file ) {
        $this->plugin_path = plugin_dir_path( $plugin_file );
        $this->plugin_url = plugin_dir_url( $plugin_file );
        
        $this->register_autoloader();
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        if ( $this->initialized ) {
            return;
        }

        // Load required files
        $this->load_required_files();

        // Initialize components in dependency order
        $this->init_components();

        // Hook into WordPress
        $this->setup_hooks();

        $this->initialized = true;
    }

    /**
     * Register autoloader for plugin classes
     */
    private function register_autoloader() {
        spl_autoload_register( array( $this, 'autoload_class' ) );
    }

    /**
     * Autoload plugin classes
     *
     * @param string $class_name Class name to load
     */
    public function autoload_class( $class_name ) {
        // Only handle our plugin classes
        if ( 0 !== strpos( $class_name, 'PandaScore_' ) ) {
            return;
        }

        // Convert class name to file name
        $file_name = $this->class_name_to_file_name( $class_name );
        $file_path = $this->plugin_path . 'includes/' . $file_name;

        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }

    /**
     * Convert class name to file name
     *
     * @param string $class_name Class name
     * @return string File name
     */
    private function class_name_to_file_name( $class_name ) {
        // Convert PandaScore_Class_Name to class-pandascore-class-name.php
        $file_name = str_replace( '_', '-', strtolower( $class_name ) );
        return 'class-' . $file_name . '.php';
    }

    /**
     * Load required files
     */
    private function load_required_files() {
        $required_files = array(
            'class-pandascore-base-component.php',
            'class-pandascore-cache-manager.php',
            'class-pandascore-api-handler.php',
            'class-pandascore-match-renderer.php',
            'class-pandascore-asset-manager.php',
            'class-pandascore-admin.php',
            'class-pandascore-frontend.php'
        );

        foreach ( $required_files as $file ) {
            $file_path = $this->plugin_path . 'includes/' . $file;
            if ( file_exists( $file_path ) ) {
                require_once $file_path;
            } else {
                wp_die( sprintf( 'PandaScore Tracker: Required file %s not found.', $file ) );
            }
        }
    }

    /**
     * Initialize components in proper dependency order
     */
    private function init_components() {
        // Initialize base components first (no dependencies)
        $this->components['cache_manager'] = new PandaScore_Cache_Manager();
        $this->components['asset_manager'] = new PandaScore_Asset_Manager();
        $this->components['match_renderer'] = new PandaScore_Match_Renderer();

        // Initialize components with dependencies
        $this->components['api_handler'] = new PandaScore_API_Handler( 
            $this->components['cache_manager'] 
        );

        $this->components['admin'] = new PandaScore_Admin(
            $this->components['cache_manager'],
            $this->components['asset_manager']
        );

        $this->components['frontend'] = new PandaScore_Frontend(
            $this->components['api_handler'],
            $this->components['match_renderer'],
            $this->components['asset_manager']
        );

        // Initialize component functionality
        $this->components['admin']->init();
        $this->components['frontend']->init();
        $this->components['frontend']->register_ajax_handlers();
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Plugin activation/deactivation hooks
        register_activation_hook( $this->plugin_path . 'pandascore-tracker.php', array( $this, 'activate' ) );
        register_deactivation_hook( $this->plugin_path . 'pandascore-tracker.php', array( $this, 'deactivate' ) );

        // Cache cleanup hook
        add_action( 'pandascore_cache_cleanup', array( $this, 'cleanup_cache' ) );
    }

    /**
     * Plugin activation handler
     */
    public function activate() {
        // Schedule cache cleanup
        if ( isset( $this->components['cache_manager'] ) ) {
            $this->components['cache_manager']->schedule_cache_cleanup();
        }

        // Flush rewrite rules if needed
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation handler
     */
    public function deactivate() {
        // Unschedule cache cleanup
        if ( isset( $this->components['cache_manager'] ) ) {
            $this->components['cache_manager']->unschedule_cache_cleanup();
        }

        // Clear scheduled hooks
        wp_clear_scheduled_hook( 'pandascore_cache_cleanup' );
    }

    /**
     * Cache cleanup handler
     */
    public function cleanup_cache() {
        if ( isset( $this->components['cache_manager'] ) ) {
            $this->components['cache_manager']->clear_all_caches();
        }
    }

    /**
     * Get component instance
     *
     * @param string $component_name Component name
     * @return object|null Component instance or null if not found
     */
    public function get_component( $component_name ) {
        return isset( $this->components[ $component_name ] ) ? $this->components[ $component_name ] : null;
    }

    /**
     * Check if component exists
     *
     * @param string $component_name Component name
     * @return bool True if component exists, false otherwise
     */
    public function has_component( $component_name ) {
        return isset( $this->components[ $component_name ] );
    }

    /**
     * Get all components
     *
     * @return array All component instances
     */
    public function get_all_components() {
        return $this->components;
    }

    /**
     * Get plugin version
     *
     * @return string Plugin version
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Get plugin path
     *
     * @return string Plugin directory path
     */
    public function get_plugin_path() {
        return $this->plugin_path;
    }

    /**
     * Get plugin URL
     *
     * @return string Plugin directory URL
     */
    public function get_plugin_url() {
        return $this->plugin_url;
    }

    /**
     * Check if plugin is initialized
     *
     * @return bool True if initialized, false otherwise
     */
    public function is_initialized() {
        return $this->initialized;
    }

    /**
     * Get plugin information
     *
     * @return array Plugin information
     */
    public function get_plugin_info() {
        return array(
            'version' => $this->version,
            'path' => $this->plugin_path,
            'url' => $this->plugin_url,
            'initialized' => $this->initialized,
            'components' => array_keys( $this->components )
        );
    }

    /**
     * Handle plugin errors
     *
     * @param string $message Error message
     * @param string $context Error context
     */
    public function handle_error( $message, $context = '' ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $log_message = 'PandaScore Tracker Plugin Loader: ' . $message;
            if ( ! empty( $context ) ) {
                $log_message .= ' | Context: ' . $context;
            }
            error_log( $log_message );
        }
    }

    /**
     * Validate plugin requirements
     *
     * @return bool True if requirements are met, false otherwise
     */
    public function validate_requirements() {
        // Check PHP version
        if ( version_compare( PHP_VERSION, '7.0', '<' ) ) {
            $this->handle_error( 'PHP version 7.0 or higher is required' );
            return false;
        }

        // Check WordPress version
        if ( version_compare( get_bloginfo( 'version' ), '5.0', '<' ) ) {
            $this->handle_error( 'WordPress version 5.0 or higher is required' );
            return false;
        }

        // Check required PHP extensions
        $required_extensions = array( 'json', 'curl' );
        foreach ( $required_extensions as $extension ) {
            if ( ! extension_loaded( $extension ) ) {
                $this->handle_error( sprintf( 'Required PHP extension %s is not loaded', $extension ) );
                return false;
            }
        }

        return true;
    }

    /**
     * Get system information for debugging
     *
     * @return array System information
     */
    public function get_system_info() {
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo( 'version' ),
            'plugin_version' => $this->version,
            'object_cache' => wp_using_ext_object_cache(),
            'multisite' => is_multisite(),
            'debug_mode' => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'memory_limit' => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' )
        );
    }
}
