# PandaScore Tracker Plugin Architecture

## Overview

The PandaScore Tracker plugin has been refactored from a monolithic structure into a component-based architecture to improve maintainability, separation of concerns, and code reusability.

## Architecture Components

### 1. Base Component (`PandaScore_Base_Component`)

**Purpose**: Abstract base class containing shared functionality across all components.

**Key Features**:

- API key management
- Common API request handling
- Match rendering utilities
- Match statistics display logic

**Methods**:

- `get_api_key()`: Retrieves API key from WordPress options
- `make_api_request($endpoint, $query_args)`: Makes authenticated requests to PandaScore API
- `get_match_stat_display($match, $is_live)`: Formats match statistics for display
- `render_match($match, $is_live)`: Renders individual match cards with teams, scores, and logos

### 2. Live Scores Component (`PandaScore_Live_Scores_Component`)

**Purpose**: Handles all live match functionality including WebSocket management and real-time updates.

**Key Features**:

- Live match fetching from PandaScore API
- WebSocket endpoint management for real-time updates
- Live match rendering with real-time indicators
- Match ID tracking for JavaScript integration

**Methods**:

- `fetch_live_matches($game, $limit)`: Fetches live matches from API
- `render_live_matches($game, $limit)`: Renders live matches section with indicators
- `get_live_match_ids()`: Returns array of live match IDs for WebSocket tracking
- `get_ws_matches()`: Returns WebSocket configuration for JavaScript
- `reset_live_data()`: Clears tracking data between shortcode instances
- `debug_live_data($game)`: Debug utility for live match data

### 3. Upcoming Matches Component (`PandaScore_Upcoming_Matches_Component`)

**Purpose**: Handles upcoming match functionality with simplified API calls.

**Key Features**:

- Upcoming match fetching from game-specific endpoints
- Clean separation from live match logic
- Error handling for upcoming matches

**Methods**:

- `fetch_upcoming_matches($game, $limit)`: Fetches upcoming matches for specific game
- `render_upcoming_matches($game, $limit)`: Renders upcoming matches section

### 4. Main Plugin Class (`PandaScore_Tracker_Plugin`)

**Purpose**: Coordinates components and handles WordPress integration.

**Key Features**:

- Component instantiation and management
- WordPress hooks and shortcode registration
- Asset management (CSS/JS)
- Admin interface and settings

**Methods**:

- `__construct()`: Initializes components and WordPress hooks
- `shortcode_handler($atts)`: Processes shortcode and coordinates components
- `enqueue_assets()`: Manages CSS and JavaScript loading
- `register_rest_routes()`: Sets up REST API endpoints
- Admin methods: `admin_menu()`, `register_settings()`, `settings_page()`

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
