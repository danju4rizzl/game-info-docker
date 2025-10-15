<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode.
Version: 1.5
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if (!defined('ABSPATH')) {
    exit;
}

// Include required classes
$sync_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-sync.php';
$settings_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-settings.php';
$database_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-database.php';
$api_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-api.php';
$router_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-router.php';
$assets_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-assets.php';
$renderer_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-renderer.php';
$shortcode_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-shortcode.php';
$match_details_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-match-details.php';

// Verify file existence before including
$required_files = [
    'settings' => $settings_file,
    'database' => $database_file,
    'sync' => $sync_file,
    'api' => $api_file,
    'router' => $router_file,
    'assets' => $assets_file,
    'renderer' => $renderer_file,
    'shortcode' => $shortcode_file,
    'match_details' => $match_details_file
];

foreach ($required_files as $name => $file) {
    if (!file_exists($file)) {
        error_log("[PandaScore Tracker] Error: Required file '$file' not found");
        add_action('admin_notices', function() use ($name) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php printf(esc_html__('PandaScore Tracker Error: %s file not found.', 'pandascore-tracker'), esc_html($name)); ?></p>
            </div>
            <?php
        });
        return;
    }
    require_once $file;
    error_log("[PandaScore Tracker] Loaded file: $file");
}

class PandaScore_Tracker_Plugin {
    private $settings;
    private $database;
    private $sync;
    private $api;
    private $router;
    private $assets;
    private $renderer;
    private $shortcode;

    public function __construct() {
        // Initialize settings and database
        $this->settings = new PandaScore_Settings();
        $this->database = new PandaScore_Database();

        // Verify sync class and method
        if (!class_exists('PandaScore_Sync')) {
            error_log('[PandaScore Tracker] Error: PandaScore_Sync class not found');
            add_action('admin_notices', [$this, 'show_sync_class_error']);
            return;
        }

        $this->sync = new PandaScore_Sync($this->settings, $this->database);

        if (!method_exists($this->sync, 'init_hooks')) {
            error_log('[PandaScore Tracker] Error: init_hooks method not found in PandaScore_Sync');
            add_action('admin_notices', [$this, 'show_sync_method_error']);
            return;
        }

        // Initialize API and verify required method
        if (!class_exists('PandaScore_API')) {
            error_log('[PandaScore Tracker] Error: PandaScore_API class not found');
            add_action('admin_notices', [$this, 'show_api_class_error']);
            return;
        }

        $this->api = new PandaScore_API($this->settings, $this->database);

        if (!method_exists($this->api, 'get_all_tournament_matches')) {
            error_log('[PandaScore Tracker] Error: get_all_tournament_matches method not found in PandaScore_API');
            add_action('admin_notices', [$this, 'show_api_method_error']);
            return;
        }

        // Initialize remaining dependencies
        $this->router = new PandaScore_Router(plugin_file: __FILE__);
        $this->assets = new PandaScore_Assets(plugin_file: __FILE__);
        $this->renderer = new PandaScore_Renderer(plugin_file: __FILE__, router: $this->router);
        $this->shortcode = new PandaScore_Shortcode($this->api, assets: $this->assets, renderer: $this->renderer, settings: $this->settings);

        // Initialize sync hooks
        try {
            $this->sync->init_hooks();
            error_log('[PandaScore Tracker] Successfully initialized sync hooks');
        } catch (Exception $e) {
            error_log('[PandaScore Tracker] Error initializing sync hooks: ' . $e->getMessage());
            add_action('admin_notices', [$this, 'show_sync_init_error']);
            return;
        }

        add_filter('cron_schedules', [$this, 'add_cron_interval']);
    }

    /**
     * Add custom cron interval for 5 minutes.
     *
     * @param array $schedules Existing cron schedules.
     * @return array Updated schedules.
     */
    public function add_cron_interval($schedules) {
        $schedules['pandascore_5min'] = [
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'pandascore-tracker')
        ];
        return $schedules;
    }

    /**
     * Show admin notice if sync class is missing.
     */
    public function show_sync_class_error() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('PandaScore Tracker Error: PandaScore_Sync class not found. Please check plugin files.', 'pandascore-tracker'); ?></p>
        </div>
        <?php
    }

    /**
     * Show admin notice if init_hooks method is missing.
     */
    public function show_sync_method_error() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('PandaScore Tracker Error: init_hooks method not found in PandaScore_Sync class. Please check plugin files.', 'pandascore-tracker'); ?></p>
        </div>
        <?php
    }

    /**
     * Show admin notice if API class is missing.
     */
    public function show_api_class_error() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('PandaScore Tracker Error: PandaScore_API class not found. Please check plugin files.', 'pandascore-tracker'); ?></p>
        </div>
        <?php
    }

    /**
     * Show admin notice if get_all_tournament_matches method is missing.
     */
    public function show_api_method_error() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('PandaScore Tracker Error: get_all_tournament_matches method not found in PandaScore_API class. Please reinstall the plugin.', 'pandascore-tracker'); ?></p>
        </div>
        <?php
    }

    /**
     * Show admin notice if sync initialization fails.
     */
    public function show_sync_init_error() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('PandaScore Tracker Error: Failed to initialize sync hooks. Please check error logs.', 'pandascore-tracker'); ?></p>
        </div>
        <?php
    }

    /**
     * Plugin activation hook.
     */
    public static function activate() {
        $router_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-router.php';
        $database_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-database.php';
        $settings_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-settings.php';
        $sync_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-sync.php';

        foreach ([$router_file, $database_file, $settings_file, $sync_file] as $file) {
            if (!file_exists($file)) {
                error_log("[PandaScore Tracker] Activation Error: Required file '$file' not found");
                return;
            }
            require_once $file;
        }

        PandaScore_Router::activate();
        
        $database = new PandaScore_Database();
        $database->create_tables();
        
        $settings = new PandaScore_Settings();
        $sync = new PandaScore_Sync($settings, $database);
        $sync->manual_sync();
    }

    /**
     * Plugin deactivation hook.
     */
    public static function deactivate() {
        $router_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-router.php';
        $sync_file = plugin_dir_path(__FILE__) . 'includes/class-pandascore-sync.php';

        foreach ([$router_file, $sync_file] as $file) {
            if (!file_exists($file)) {
                error_log("[PandaScore Tracker] Deactivation Error: Required file '$file' not found");
                return;
            }
            require_once $file;
        }

        PandaScore_Router::deactivate();
        PandaScore_Sync::clear_scheduled_sync();
    }
}

register_activation_hook(__FILE__, ['PandaScore_Tracker_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['PandaScore_Tracker_Plugin', 'deactivate']);

// Only instantiate if all required files exist
$all_files_exist = true;
foreach ($required_files as $name => $file) {
    if (!file_exists($file)) {
        $all_files_exist = false;
        break;
    }
}

if ($all_files_exist) {
    new PandaScore_Tracker_Plugin();
} else {
    error_log('[PandaScore Tracker] Error: Not all required files found, plugin not initialized');
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e('PandaScore Tracker Error: Some required plugin files are missing. Please reinstall the plugin.', 'pandascore-tracker'); ?></p>
        </div>
        <?php
    });
}