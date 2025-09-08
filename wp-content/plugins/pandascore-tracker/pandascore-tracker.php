<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode.
Version: 1.2.0 (Refactored Architecture)
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'PANDASCORE_TRACKER_VERSION', '1.2.0' );
define( 'PANDASCORE_TRACKER_PLUGIN_FILE', __FILE__ );
define( 'PANDASCORE_TRACKER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PANDASCORE_TRACKER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main Plugin Class - Lightweight coordinator for the refactored architecture
 *
 * This class serves as the entry point and coordinator for the plugin's
 * component-based architecture following WordPress best practices and SOLID principles.
 */
class PandaScore_Tracker_Plugin {

    /**
     * Plugin loader instance
     *
     * @var PandaScore_Plugin_Loader
     */
    private $loader;

    /**
     * Constructor - Initialize the plugin using the new architecture
     */
    public function __construct() {
        $this->load_plugin_loader();
        $this->init_plugin();
    }

    /**
     * Load the plugin loader class
     */
    private function load_plugin_loader() {
        require_once PANDASCORE_TRACKER_PLUGIN_DIR . 'includes/class-pandascore-plugin-loader.php';
        $this->loader = new PandaScore_Plugin_Loader( PANDASCORE_TRACKER_PLUGIN_FILE );
    }

    /**
     * Initialize the plugin
     */
    private function init_plugin() {
        // Validate requirements before initialization
        if ( ! $this->loader->validate_requirements() ) {
            add_action( 'admin_notices', array( $this, 'display_requirements_notice' ) );
            return;
        }

        // Initialize the plugin
        $this->loader->init();
    }

    /**
     * Display requirements notice if plugin requirements are not met
     */
    public function display_requirements_notice() {
        ?>
        <div class="notice notice-error">
            <p><strong>PandaScore Tracker:</strong> Plugin requirements not met. Please check PHP and WordPress versions.</p>
        </div>
        <?php
    }

    /**
     * Get plugin loader instance
     *
     * @return PandaScore_Plugin_Loader Plugin loader instance
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Get component instance
     *
     * @param string $component_name Component name
     * @return object|null Component instance or null
     */
    public function get_component( $component_name ) {
        return $this->loader ? $this->loader->get_component( $component_name ) : null;
    }
}

// Initialize the plugin
new PandaScore_Tracker_Plugin();