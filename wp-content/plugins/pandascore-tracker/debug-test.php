<?php
/**
 * Debug test file for PandaScore Tracker Plugin
 * 
 * This file can be used to test plugin loading without WordPress admin interface
 * Remove this file in production
 */

// Only run if WP_DEBUG is enabled
if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
    wp_die( 'Debug mode not enabled' );
}

// Test plugin loading
echo '<h2>PandaScore Tracker Plugin Debug Test</h2>';

// Check if plugin is loaded
if ( class_exists( 'PandaScore_Tracker_Plugin' ) ) {
    echo '<p style="color: green;">✓ Main plugin class loaded successfully</p>';
    
    // Get plugin instance
    $plugin = PandaScore_Tracker_Plugin::get_instance();
    
    if ( $plugin ) {
        echo '<p style="color: green;">✓ Plugin instance created successfully</p>';
        
        // Test component loading
        $components = array( 'api_handler', 'renderer', 'settings', 'live_scores', 'upcoming_matches' );
        
        foreach ( $components as $component_name ) {
            $component = $plugin->get_component( $component_name );
            if ( $component ) {
                echo '<p style="color: green;">✓ Component "' . $component_name . '" loaded successfully</p>';
            } else {
                echo '<p style="color: red;">✗ Component "' . $component_name . '" failed to load</p>';
            }
        }
        
        // Test shortcode registration
        if ( shortcode_exists( 'pandascore_tracker' ) ) {
            echo '<p style="color: green;">✓ Shortcode registered successfully</p>';
        } else {
            echo '<p style="color: red;">✗ Shortcode not registered</p>';
        }
        
        // Test text domain loading
        if ( is_textdomain_loaded( 'pandascore-tracker' ) ) {
            echo '<p style="color: green;">✓ Text domain loaded successfully</p>';
        } else {
            echo '<p style="color: orange;">⚠ Text domain not loaded (may be normal if no translations exist)</p>';
        }
        
    } else {
        echo '<p style="color: red;">✗ Failed to get plugin instance</p>';
    }
    
} else {
    echo '<p style="color: red;">✗ Main plugin class not found</p>';
}

echo '<h3>WordPress Hook Status</h3>';
echo '<p>Current hook: ' . current_filter() . '</p>';
echo '<p>Did action "init": ' . ( did_action( 'init' ) ? 'Yes' : 'No' ) . '</p>';
echo '<p>Did action "plugins_loaded": ' . ( did_action( 'plugins_loaded' ) ? 'Yes' : 'No' ) . '</p>';
echo '<p>Did action "admin_init": ' . ( did_action( 'admin_init' ) ? 'Yes' : 'No' ) . '</p>';

echo '<h3>Translation Function Test</h3>';
if ( function_exists( '__' ) ) {
    echo '<p style="color: green;">✓ Translation function available</p>';
    echo '<p>Test translation: ' . __( 'Test message', 'pandascore-tracker' ) . '</p>';
} else {
    echo '<p style="color: red;">✗ Translation function not available</p>';
}
