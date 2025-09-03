# PandaScore Tracker Development Guide

## Overview

This guide provides comprehensive information for developers working with the PandaScore Tracker plugin's new component-based architecture.

## Quick Start

### Plugin Structure
```
wp-content/plugins/pandascore-tracker/
├── pandascore-tracker.php          # Main plugin file (coordinator only)
├── includes/                       # Component classes
│   ├── class-pandascore-base-component.php
│   ├── class-pandascore-api-handler.php
│   ├── class-pandascore-renderer.php
│   ├── class-pandascore-settings.php
│   ├── class-pandascore-live-scores.php
│   └── class-pandascore-upcoming-matches.php
├── templates/                      # Template files
│   ├── match-card.php
│   ├── live-matches-section.php
│   ├── upcoming-matches-section.php
│   ├── error-message.php
│   └── no-matches.php
├── assets/                         # CSS and JavaScript
│   ├── css/
│   │   ├── pandascore-base.css
│   │   └── admin.css
│   └── js/
│       ├── live-tracker.js
│       └── admin.js
└── docs/                          # Documentation
    ├── ARCHITECTURE.md
    ├── DEVELOPMENT_GUIDE.md
    └── REFACTORING_SUMMARY.md
```

## Component Development

### Creating a New Component

1. **Extend Base Component**:
```php
class PandaScore_New_Component extends PandaScore_Base_Component {
    
    public function __construct() {
        // Component initialization
    }
    
    public function render_new_feature($args = array()) {
        // Implementation
    }
}
```

2. **Add to Main Plugin**:
```php
// In PandaScore_Tracker_Plugin::load_dependencies()
require_once PANDASCORE_TRACKER_PLUGIN_DIR . 'includes/class-pandascore-new-component.php';

// In PandaScore_Tracker_Plugin::init_components()
$this->new_component = new PandaScore_New_Component();

// In PandaScore_Tracker_Plugin::get_component()
case 'new_component':
    return $this->new_component;
```

### Using Existing Components

```php
// Get plugin instance
$plugin = PandaScore_Tracker_Plugin::get_instance();

// Access components
$api_handler = $plugin->get_component('api_handler');
$renderer = $plugin->get_component('renderer');
$settings = $plugin->get_component('settings');

// Use component methods
$matches = $api_handler->fetch_live_matches(10, 'valorant');
$html = $renderer->render_live_matches_section($matches);
```

## API Integration

### Making API Requests

```php
// Basic API request
$data = $this->make_api_request('valorant/matches', array(
    'page[size]' => 10,
    'sort' => 'begin_at'
));

// With caching disabled
$data = $this->make_api_request('lives', array(), false);

// Handle errors
if (is_wp_error($data)) {
    $this->log_error('API Error', array(
        'message' => $data->get_error_message(),
        'endpoint' => 'valorant/matches'
    ));
    return array();
}
```

### Rate Limiting

The API handler automatically manages rate limiting:
- 60 requests per minute maximum
- Automatic throttling when limit approached
- Error responses when limit exceeded

### Caching

```php
// Clear specific cache
$this->clear_cache('valorant/matches', array('page[size]' => 10));

// Clear all cache
$api_handler = new PandaScore_API_Handler();
$api_handler->clear_all_cache();

// Custom cache duration (in base component)
$this->cache_expiration = 600; // 10 minutes
```

## Template Development

### Creating Templates

Templates are located in `/templates/` and can be overridden by themes in `/theme/pandascore-tracker/`.

**Basic Template Structure**:
```php
<?php
/**
 * Template for custom feature
 *
 * @package PandaScore_Tracker
 * @since 2.0.0
 * 
 * Available variables:
 * @var array $data Template data
 * @var array $args Display arguments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="pandascore-custom-feature">
    <?php foreach ($data as $item) : ?>
        <div class="pandascore-item">
            <?php echo esc_html($item['name']); ?>
        </div>
    <?php endforeach; ?>
</div>
```

### Using Templates

```php
// In component class
public function render_custom_feature($data, $args = array()) {
    $template_data = array(
        'data' => $data,
        'args' => $args,
    );
    
    return $this->renderer->load_template('custom-feature', $template_data);
}
```

### Theme Overrides

Themes can override templates by creating:
```
/wp-content/themes/your-theme/pandascore-tracker/match-card.php
```

## CSS Development

### Component-Based CSS

Each component can have its own CSS file:

```css
/* pandascore-custom.css */
.pandascore-custom-feature {
    background: #1a1a1e;
    border-radius: 8px;
    padding: 12px;
}

.pandascore-item {
    margin-bottom: 8px;
    color: #ffffff;
}
```

### Enqueuing Styles

```php
// In component constructor or init method
add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

public function enqueue_styles() {
    wp_register_style(
        'pandascore-custom-style',
        PANDASCORE_TRACKER_PLUGIN_URL . 'assets/css/pandascore-custom.css',
        array('pandascore-base-style'),
        PANDASCORE_TRACKER_VERSION
    );
}

// In shortcode handler
wp_enqueue_style('pandascore-custom-style');
```

## JavaScript Development

### Live Updates

For real-time features, extend the existing WebSocket system:

```javascript
// Custom live update handler
function handleCustomUpdate(matchId, data) {
    const element = document.querySelector(`[data-match-id="${matchId}"]`);
    if (element) {
        // Update element with new data
        updateCustomElement(element, data);
    }
}

// Register with existing system
if (window.pandaScoreLiveTracker) {
    window.pandaScoreLiveTracker.addCustomHandler(handleCustomUpdate);
}
```

### Admin JavaScript

```javascript
// admin-custom.js
(function($) {
    'use strict';
    
    $(document).ready(function() {
        initCustomFeature();
    });
    
    function initCustomFeature() {
        // Custom admin functionality
    }
    
})(jQuery);
```

## Settings Development

### Adding Settings

```php
// In settings component
private function define_custom_settings() {
    $this->fields['custom_option'] = array(
        'section'     => 'display',
        'title'       => __('Custom Option', 'pandascore-tracker'),
        'type'        => 'select',
        'description' => __('Description of custom option.', 'pandascore-tracker'),
        'options'     => array(
            'option1' => 'Option 1',
            'option2' => 'Option 2',
        ),
        'default'     => 'option1',
        'validate'    => 'custom_validation',
    );
}

// Custom validation
private function validate_custom_option($value, $field_id) {
    if (!in_array($value, array('option1', 'option2'))) {
        add_settings_error(
            $this->option_key,
            $field_id,
            __('Invalid custom option selected.', 'pandascore-tracker')
        );
        return 'option1'; // Default fallback
    }
    return $value;
}
```

### Using Settings

```php
// Get setting value
$custom_value = $this->settings->get_setting('custom_option', 'option1');

// Update setting
$this->settings->update_setting('custom_option', 'option2');
```

## Testing

### Component Testing

```php
// Test component functionality
public function test_custom_component() {
    $component = new PandaScore_Custom_Component();
    
    // Test method
    $result = $component->custom_method('test_input');
    
    // Assertions
    $this->assertNotEmpty($result);
    $this->assertIsArray($result);
}
```

### API Testing

```php
// Test API integration
public function test_api_request() {
    $api_handler = new PandaScore_API_Handler();
    
    // Mock API response
    $result = $api_handler->fetch_live_matches(5, 'valorant');
    
    // Verify result
    $this->assertFalse(is_wp_error($result));
    $this->assertIsArray($result);
}
```

## Debugging

### Error Logging

```php
// Enable debug logging
if (defined('WP_DEBUG') && WP_DEBUG) {
    $this->log_error('Debug Message', array(
        'component' => 'custom',
        'data' => $debug_data,
    ));
}
```

### Debug Methods

```php
// Component debug info
$debug_info = $this->live_scores->debug_live_data('valorant');
$debug_info = $this->upcoming_matches->debug_upcoming_data('valorant');

// API status
$api_status = $this->api_handler->get_api_status();
```

## Performance Optimization

### Caching Strategy

1. **API Responses**: Cached for 5 minutes by default
2. **Template Output**: Consider object caching for complex templates
3. **Settings**: Cached by WordPress options system
4. **Asset Loading**: Only load required CSS/JS

### Best Practices

1. **Lazy Loading**: Only instantiate components when needed
2. **Conditional Assets**: Load CSS/JS only when shortcode is used
3. **Database Queries**: Use WordPress caching functions
4. **API Calls**: Implement proper rate limiting and caching

## Security

### Data Sanitization

```php
// Always sanitize input
$game = sanitize_text_field($input['game']);
$limit = max(1, min(50, intval($input['limit'])));

// Sanitize match data
$match = $this->sanitize_match_data($raw_match_data);
```

### Output Escaping

```php
// In templates
echo esc_html($team_name);
echo esc_url($team_logo);
echo esc_attr($match_id);
```

### Nonce Verification

```php
// In AJAX handlers
if (!wp_verify_nonce($_POST['nonce'], 'pandascore_action')) {
    wp_die('Security check failed');
}
```

## Deployment

### Version Management

1. Update version in main plugin file
2. Update version constant
3. Clear cache on activation
4. Test all components

### Database Changes

```php
// In activation hook
public function activate() {
    // Update options
    $this->update_default_options();
    
    // Clear cache
    $this->api_handler->clear_all_cache();
    
    // Flush rewrite rules if needed
    flush_rewrite_rules();
}
```

This guide provides the foundation for extending and maintaining the PandaScore Tracker plugin. For specific implementation details, refer to the existing component code and the Architecture documentation.
