<?php
/*
Plugin Name: PandaScore Tracker
Description: Fetches and displays PandaScore game scores via shortcode.
Version: 1.3 (Improved WebSocket Implementation + Match Details Routing)
Author: Deejay Dev
Text Domain: pandascore-tracker
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-router.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-renderer.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-pandascore-shortcode.php';

class PandaScore_Tracker_Plugin {
    private $settings;
    private $api;
    private $router;
    private $assets;
    private $renderer;
    private $shortcode;

    public function __construct() {
        $this->settings = new PandaScore_Settings();
        $this->api = new PandaScore_API($this->settings);
        $this->router = new PandaScore_Router(__FILE__);
        $this->assets = new PandaScore_Assets(__FILE__);
        $this->renderer = new PandaScore_Renderer(__FILE__, $this->router);
        $this->shortcode = new PandaScore_Shortcode($this->api, $this->assets, $this->renderer, $this->settings);
    }



    public static function activate() {
        PandaScore_Router::activate();
    }

    public static function deactivate() {
        PandaScore_Router::deactivate();
    }




}

register_activation_hook(__FILE__, ['PandaScore_Tracker_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['PandaScore_Tracker_Plugin', 'deactivate']);
new PandaScore_Tracker_Plugin();
