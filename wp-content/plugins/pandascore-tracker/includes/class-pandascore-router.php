<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Router {
    private $plugin_file;

    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_filter('template_include', [$this, 'match_template_include']);
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^match/([0-9]+)/?$', 'index.php?match=$matches[1]', 'top');
    }

    public function register_query_vars($vars) {
        $vars[] = 'match';
        return $vars;
    }

    public function match_template_include($template) {
        $match_id = get_query_var('match');
        if (!empty($match_id)) {
            $plugin_template = plugin_dir_path($this->plugin_file) . 'templates/match-details.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function get_match_url($match) {
        $id = isset($match['id']) ? intval($match['id']) : 0;
        if (!$id) return '#';
        return add_query_arg('match', $id, home_url('/'));
    }

    public static function activate() {
        add_rewrite_rule('^match/([0-9]+)/?$', 'index.php?match=$matches[1]', 'top');
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}