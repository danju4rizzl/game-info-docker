<?php

if (!defined('ABSPATH')) {
    exit;
}

class PandaScore_Cache {
    private $cache_prefix = 'pandascore_';
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
    }

    public function get($key) {
        $cache_key = $this->cache_prefix . md5($key);
        return get_transient($cache_key);
    }

    public function set($key, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = $this->settings->get_cache_duration();
        }
        $cache_key = $this->cache_prefix . md5($key);
        return set_transient($cache_key, $data, $expiration);
    }

    public function delete($key) {
        $cache_key = $this->cache_prefix . md5($key);
        return delete_transient($cache_key);
    }

    public function clear_all() {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_prefix . '%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . $this->cache_prefix . '%'
            )
        );
        return true;
    }
}