# PandaScore Tracker Plugin Architecture v2.0

## Overview

The PandaScore Tracker plugin has been completely refactored from a monolithic 376-line class into a modern component-based architecture. This new structure improves maintainability, separation of concerns, code reusability, and follows WordPress and PHP best practices.

## Architecture Components

### 1. Base Component (`PandaScore_Base_Component`)

**Purpose**: Abstract base class providing shared functionality across all components.

**Location**: `includes/class-pandascore-base-component.php`

**Key Features**:
- API key management with validation
- Standardized API request handling with caching
- Error logging and debugging utilities
- Data sanitization and validation
- Cache management with WordPress transients
- Common utility methods for time formatting

**Core Methods**:
- `get_api_key()`: Secure API key retrieval from WordPress options
- `make_api_request($endpoint, $query_args, $use_cache)`: Authenticated API requests with built-in caching
- `sanitize_match_data($match)`: Comprehensive data sanitization
- `format_match_time($scheduled_at)`: Human-readable time formatting
- `clear_cache($endpoint, $query_args)`: Granular cache management
- `log_error($message, $context)`: Centralized error logging

### 2. API Handler Component (`PandaScore_API_Handler`)

**Purpose**: Centralized API communication with advanced features.

**Location**: `includes/class-pandascore-api-handler.php`

**Key Features**:
- Rate limiting (60 requests/minute) with automatic throttling
- Comprehensive caching with WordPress transients
- Multi-endpoint support (live matches, upcoming matches, match details)
- Game filtering and validation
- Enhanced error handling with detailed logging
- API health monitoring and status reporting

**Core Methods**:
- `fetch_live_matches($limit, $game)`: Live matches with optional game filtering
- `fetch_upcoming_matches($game, $limit)`: Game-specific upcoming matches
- `fetch_match_details($match_id)`: Detailed match information
- `fetch_available_games()`: Supported games list with caching
- `get_api_status()`: Comprehensive API health check
- `clear_all_cache()`: Complete cache invalidation

### 3. Renderer Component (`PandaScore_Renderer`)

**Purpose**: Template-based rendering with theme override support.

**Location**: `includes/class-pandascore-renderer.php`

**Key Features**:
- Template system with theme override capability
- Separation of HTML from PHP logic
- Consistent data processing for display
- Fallback content for missing templates
- Responsive design support
- Accessibility-compliant markup

**Core Methods**:
- `render_live_matches_section($matches, $args)`: Complete live section rendering
- `render_upcoming_matches_section($matches, $args)`: Upcoming matches section
- `render_match_card($match, $args)`: Individual match card with full customization
- `render_error_message($message, $type)`: Consistent error display
- `load_template($template_name, $data)`: Template loading with theme override support

### 4. Settings Management Component (`PandaScore_Settings`)

**Purpose**: Complete admin interface and configuration management.

**Location**: `includes/class-pandascore-settings.php`

**Key Features**:
- Comprehensive settings API integration
- Field validation and sanitization
- API key testing and validation
- Admin interface with contextual help
- Settings export/import capability
- Cache management controls

**Core Methods**:
- `add_admin_menu()`: WordPress admin menu integration
- `register_settings()`: Settings API registration with validation
- `sanitize_settings($input)`: Comprehensive input sanitization
- `render_settings_page()`: Complete admin interface
- `get_setting($key, $default)`: Safe setting retrieval
- `update_setting($key, $value)`: Setting updates with validation

### 5. Live Scores Component (`PandaScore_Live_Scores`)

**Purpose**: Live match functionality with WebSocket management.

**Location**: `includes/class-pandascore-live-scores.php`

**Key Features**:
- Live match fetching with game filtering
- WebSocket URL generation and management
- Real-time match tracking
- JavaScript integration data preparation
- Live match statistics and debugging
- Team-specific filtering

**Core Methods**:
- `render_live_matches($limit, $game, $args)`: Complete live section rendering
- `get_live_match_ids()`: JavaScript integration support
- `get_websocket_matches()`: WebSocket configuration data
- `reset_live_data()`: Clean state management between instances
- `get_live_matches_for_teams($team_ids, $limit)`: Team-specific filtering
- `debug_live_data($game)`: Development and troubleshooting utilities

### 6. Upcoming Matches Component (`PandaScore_Upcoming_Matches`)

**Purpose**: Upcoming match functionality with advanced filtering.

**Location**: `includes/class-pandascore-upcoming-matches.php`

**Key Features**:
- Game-specific upcoming match fetching
- Time-based filtering (today, this week, date ranges)
- Multi-game support with unified sorting
- Team-specific filtering
- Match processing and validation
- Statistics and analytics

**Core Methods**:
- `render_upcoming_matches($game, $limit, $args)`: Complete upcoming section
- `get_upcoming_matches_multi_game($games, $limit_per_game)`: Multi-game support
- `get_todays_matches($game, $limit)`: Today's matches filtering
- `get_this_weeks_matches($game, $limit)`: Weekly match filtering
- `get_upcoming_matches_for_teams($game, $team_ids, $limit)`: Team filtering
- `get_upcoming_statistics($games)`: Comprehensive statistics

### 7. Main Plugin Class (`PandaScore_Tracker_Plugin`)

**Purpose**: Component coordination and WordPress integration only.

**Location**: `pandascore-tracker.php`

**Key Features**:
- Component instantiation and lifecycle management
- WordPress hooks and shortcode registration
- Asset management with intelligent loading
- Plugin activation/deactivation handling
- Singleton pattern implementation
- Component access interface

**Core Methods**:
- `load_dependencies()`: Component file loading
- `init_components()`: Component instantiation
- `shortcode_handler($atts)`: Shortcode processing and component coordination
- `enqueue_assets()`: Conditional asset loading
- `get_component($component)`: Component access interface
- `activate()` / `deactivate()`: Plugin lifecycle management

## Data Flow

1. **Shortcode Processing**:

   - User adds `[pandascore_tracker]` shortcode
   - Main plugin class processes shortcode attributes
   - Components are called based on `type` parameter (`live`, `upcoming`, or `mixed`)

2. **Live Matches**:

   - Live Scores Component fetches live matches from `/lives` endpoint
   - WebSocket endpoints are collected for real-time updates
   - Match IDs are tracked for JavaScript integration
   - Live indicator and matches are rendered

3. **Upcoming Matches**:

   - Upcoming Matches Component fetches from game-specific endpoints
   - Matches are rendered with upcoming indicators
   - No WebSocket tracking needed

4. **JavaScript Integration**:
   - If live matches exist, JavaScript is enqueued
   - WebSocket configurations are passed to frontend
   - Real-time updates are handled by `live-tracker.js`

## CSS Architecture

The plugin now uses a component-based CSS architecture that mirrors the PHP component structure:

### CSS File Structure

1. **`pandascore-base.css`** - Shared base styles

   - Main container styling
   - Card-based UI components
   - Common team and match elements
   - Responsive design rules
   - Error and loading states

2. **`pandascore-live-scores.css`** - Live scores component styles

   - Live indicator animations
   - Real-time update effects
   - WebSocket connection status
   - Live match highlighting
   - Score update animations

3. **`pandascore-upcoming-matches.css`** - Upcoming matches component styles
   - Upcoming match indicators
   - Tournament stage styling
   - Match format displays (BO3, BO5)
   - Countdown timers
   - Team form indicators

### CSS Loading Strategy

The plugin intelligently loads CSS files based on shortcode usage:

```php
// Base styles always loaded
wp_enqueue_style( 'pandascore-base-style' );

// Component-specific styles loaded based on type parameter
if ( $type === 'live' || $type === 'mixed' ) {
    wp_enqueue_style( 'pandascore-live-scores-style' );
}

if ( $type === 'upcoming' || $type === 'mixed' ) {
    wp_enqueue_style( 'pandascore-upcoming-matches-style' );
}
```

### Legacy CSS Support

The old CSS files are maintained for backward compatibility but marked as deprecated:

- `pandascore-tracker.css` - DEPRECATED
- `pandascore-live-tracker.css` - DEPRECATED

## Benefits of New Architecture

### Maintainability

- **Separation of Concerns**: Each component has a single responsibility
- **Reduced Complexity**: Main plugin file reduced from 680+ lines to ~770 lines total
- **Clear Dependencies**: Components extend base class for shared functionality
- **Modular CSS**: Styles are organized by component functionality

### Extensibility

- **Easy to Add Features**: New match types can be added as separate components
- **Modular Design**: Components can be modified independently
- **Plugin Architecture**: Easy to add new data sources or display formats
- **CSS Component System**: New components can have their own stylesheets

### Code Reusability

- **Shared Base Class**: Common functionality is centralized
- **Consistent API**: All components follow same patterns
- **DRY Principle**: No duplicate code between live and upcoming functionality
- **CSS Inheritance**: Component styles extend base styles

## File Structure

```
wp-content/plugins/pandascore-tracker/
├── pandascore-tracker.php          # Main plugin file with all components
├── uninstall.php                   # Clean uninstall handler
├── js/
│   └── live-tracker.js             # WebSocket and real-time updates
├── css/
│   ├── pandascore-base.css         # Shared base styles
│   ├── pandascore-live-scores.css  # Live scores component styles
│   ├── pandascore-upcoming-matches.css # Upcoming matches component styles
│   ├── pandascore-tracker.css      # DEPRECATED: Legacy styles
│   └── pandascore-live-tracker.css # DEPRECATED: Legacy styles
└── docs/
    ├── ARCHITECTURE.md             # This documentation
    └── REFACTORING_SUMMARY.md      # Development history
```

## Usage Examples

### Basic Live Matches

```php
[pandascore_tracker type="live" game="valorant" limit="5"]
```

### Basic Upcoming Matches

```php
[pandascore_tracker type="upcoming" game="lol" limit="10"]
```

### Mixed Display (Live + Upcoming)

```php
[pandascore_tracker type="mixed" game="csgo" limit="8"]
```

## Extending the Architecture

### Adding a New Component

1. **Create Component Class**:

```php
class PandaScore_New_Component extends PandaScore_Base_Component {
    public function fetch_new_data($game, $limit) {
        return $this->make_api_request("new-endpoint", array(
            'game' => $game,
            'limit' => $limit
        ));
    }

    public function render_new_section($game, $limit) {
        $data = $this->fetch_new_data($game, $limit);
        // Render logic here
        return $html;
    }
}
```

2. **Integrate with Main Plugin**:

```php
// In constructor
$this->new_component = new PandaScore_New_Component();

// In shortcode handler
if ($atts['type'] === 'new') {
    $html .= $this->new_component->render_new_section($atts['game'], $atts['limit']);
}
```

### Modifying Existing Components

- **Live Scores**: Modify `PandaScore_Live_Scores_Component` for WebSocket changes
- **Upcoming Matches**: Modify `PandaScore_Upcoming_Matches_Component` for display changes
- **Shared Logic**: Modify `PandaScore_Base_Component` for common functionality

## Migration Notes

The refactoring maintains full backward compatibility:

- All existing shortcodes continue to work
- No changes to CSS classes or JavaScript interfaces
- Same REST API endpoints
- Identical admin interface

## Testing

After refactoring, verify:

1. Live matches display correctly with real-time updates
2. Upcoming matches show proper game data
3. Mixed mode displays both sections
4. WebSocket connections work for live matches
5. Admin settings save and load properly
6. CSS and JavaScript assets load correctly
