# CSS Migration Guide

## Overview

The PandaScore Tracker plugin has migrated from inline styles to external CSS files for better maintainability and performance. This guide explains the changes and how to work with the new CSS architecture.

## Latest Migration (v1.1)

### Inline Styles to External CSS
**Date**: Current update
**Change**: Moved all inline styles from `get_internal_styles()` method to `css/index.css` file

### Before (Inline Styles)
```php
// Styles were embedded directly in PHP
private function get_internal_styles() {
    return '<style>/* All CSS here */</style>';
}
```

### After (External CSS)
```
css/
└── index.css                   # All plugin styles in external file
```

## Previous CSS Architecture

### Before (Legacy)
```
css/
├── pandascore-tracker.css      # All styles mixed together
└── pandascore-live-tracker.css # Duplicate of above
```

### After (Component-Based)
```
css/
├── pandascore-base.css         # Shared base styles
├── pandascore-live-scores.css  # Live scores component styles
├── pandascore-upcoming-matches.css # Upcoming matches component styles
├── pandascore-tracker.css      # DEPRECATED: Legacy styles
└── pandascore-live-tracker.css # DEPRECATED: Legacy styles
```

## Current CSS Implementation

### css/index.css
**Contains all plugin styles in a single external file:**
- `.pandascore-tracker` - Main container with alignment options
- `.pandascore-section-header` - Section headers for live/upcoming matches
- `.pandascore-live-indicator` - Live match indicator with animation
- `.pandascore-matches-container` - Container for match listings
- `.pandascore-match` - Individual match cards
- `.pandascore-league-container` - League logo containers
- `.pandascore-team` - Team display components
- `.pandascore-team-logo` - Team logos and placeholders
- `.pandascore-score` - Score displays for live matches
- `.pandascore-time-container` - Time/date displays for upcoming matches
- `.pandascore-error` - Error state styling
- `.pandascore-no-matches` - Empty state styling
- `.pandascore-api-key-input` - Admin settings input styling

### Benefits of External CSS
- **Better Performance**: CSS is cached by browsers and CDNs
- **Cleaner PHP Code**: Removes large inline style blocks from PHP
- **Easier Maintenance**: CSS can be edited without touching PHP
- **Better Development**: Syntax highlighting and CSS tools work properly
- **Smaller HTML**: No inline styles in rendered output

## CSS File Breakdown (Legacy Component-Based Architecture)

### 1. pandascore-base.css
**Contains shared styles used by all components:**
- `.pandascore-tracker` - Main container
- `.ps-card` - Card-based UI components
- `.ps-match` - Match layout grid
- `.ps-team-row` - Team display rows
- `.ps-team-logo` - Team logos
- `.ps-team-name` - Team names
- `.ps-score` - Score displays
- `.ps-odds` - Odds/statistics displays
- Error states (`.pandascore-error`, `.pandascore-empty`)
- Responsive design rules
- Legacy compatibility classes

### 2. pandascore-live-scores.css
**Contains styles specific to live match functionality:**
- `.pandascore-live-indicator` - Live match header
- `.ps-score.win` / `.ps-score.lose` - Win/lose score styling
- `.pandascore-live-dot` - Live indicators with pulse animation
- `.ps-card.live` - Live match card styling
- WebSocket connection status indicators
- Real-time update animations
- Live match hover effects

### 3. pandascore-upcoming-matches.css
**Contains styles specific to upcoming match functionality:**
- `.pandascore-section-header.upcoming` - Upcoming section header
- `.pandascore-time` - Match time displays
- `.ps-odds.match-format` - Match format indicators (BO3, BO5)
- `.pandascore-countdown` - Countdown timers
- Tournament stage indicators
- Team form displays
- Prize pool indicators

## Migration Steps

### Current Implementation (v1.1)

1. **CSS Enqueuing**
   ```php
   // Current implementation
   wp_enqueue_style('pandascore-tracker-style', plugins_url('css/index.css', __FILE__), [], '1.1');
   ```

2. **No More Inline Styles**
   ```php
   // Old way (removed)
   private function get_internal_styles() {
       return '<style>/* CSS here */</style>';
   }

   // New way - styles are automatically loaded from index.css
   // No inline styles needed
   ```

3. **Custom CSS Overrides**
   ```css
   /* All existing selectors continue to work */
   .pandascore-tracker {
       /* Your custom styles */
   }

   .pandascore-live-indicator {
       /* Your custom live indicator styles */
   }

   .pandascore-match {
       /* Your custom match card styles */
   }
   ```

### For Theme Developers (Legacy Component-Based)

1. **Update CSS Dependencies**
   ```php
   // Old way
   wp_enqueue_style( 'pandascore-live-tracker-style' );

   // New way - load only what you need
   wp_enqueue_style( 'pandascore-base-style' );
   wp_enqueue_style( 'pandascore-live-scores-style' ); // If using live matches
   wp_enqueue_style( 'pandascore-upcoming-matches-style' ); // If using upcoming matches
   ```

2. **Update Custom CSS Overrides**
   ```css
   /* Old selectors still work but are deprecated */
   .pandascore-live-indicator { /* ... */ }

   /* New component-specific selectors are preferred */
   .pandascore-live-indicator { /* Live scores component */ }
   .pandascore-section-header.upcoming { /* Upcoming matches component */ }
   ```

### For Plugin Customization

1. **Identify Component Usage**
   - Live matches only: Load `pandascore-base.css` + `pandascore-live-scores.css`
   - Upcoming matches only: Load `pandascore-base.css` + `pandascore-upcoming-matches.css`
   - Mixed display: Load all three CSS files

2. **Override Component Styles**
   ```css
   /* Override base styles */
   .pandascore-tracker {
       max-width: 400px; /* Custom width */
   }
   
   /* Override live scores styles */
   .pandascore-live-indicator {
       background: #custom-color;
   }
   
   /* Override upcoming matches styles */
   .pandascore-section-header.upcoming {
       color: #custom-orange;
   }
   ```

## Backward Compatibility

### Legacy CSS Files
The old CSS files are maintained for backward compatibility:
- `pandascore-tracker.css` - Contains all styles (deprecated)
- `pandascore-live-tracker.css` - Duplicate of above (deprecated)

### Automatic Fallback
The plugin automatically falls back to legacy CSS if new component files are missing:

```php
// Plugin automatically handles fallback
if ( ! wp_style_is( 'pandascore-base-style', 'registered' ) ) {
    wp_enqueue_style( 'pandascore-legacy-style' );
}
```

### CSS Class Compatibility
All existing CSS classes continue to work:
- `.pandascore-tracker`
- `.pandascore-match`
- `.pandascore-team`
- `.pandascore-name`
- `.pandascore-score`
- `.pandascore-live-indicator`

## Performance Benefits

### Reduced CSS Load
- **Before**: Always loaded ~428 lines of CSS
- **After**: Load only needed components
  - Live only: ~250 lines (base + live)
  - Upcoming only: ~220 lines (base + upcoming)
  - Mixed: ~350 lines (all components)

### Better Caching
- Component-specific CSS files cache independently
- Changes to one component don't invalidate other caches
- Smaller file sizes for faster loading

## Customization Examples

### Custom Live Match Styling
```css
/* Custom live indicator */
.pandascore-live-indicator {
    background: linear-gradient(45deg, #ff4444, #ff6666);
    border-radius: 8px;
}

/* Custom live score colors */
.ps-score.win {
    background: #1a5d1a;
    color: #4caf50;
    box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
}
```

### Custom Upcoming Match Styling
```css
/* Custom upcoming header */
.pandascore-section-header.upcoming {
    background: linear-gradient(135deg, #ff8c00, #ffa500);
    color: #000;
}

/* Custom countdown styling */
.pandascore-countdown {
    background: rgba(255, 140, 0, 0.2);
    border: 1px solid #ff8c00;
    border-radius: 6px;
}
```

### Custom Base Styling
```css
/* Custom card styling */
.ps-card {
    border-radius: 12px;
    background: linear-gradient(135deg, #1e1e1e, #2a2a2a);
    border: 2px solid #333;
}

/* Custom team name styling */
.ps-team-name {
    font-family: 'Custom Font', sans-serif;
    letter-spacing: 1px;
}
```

## Troubleshooting

### Styles Not Loading
1. Check if new CSS files exist in `/css/` directory
2. Verify WordPress is enqueueing the correct files
3. Clear any caching plugins
4. Check browser developer tools for 404 errors

### Missing Styles
1. Ensure you're loading the correct component CSS files
2. Check if custom overrides are conflicting
3. Verify shortcode `type` parameter matches loaded CSS

### Legacy Compatibility Issues
1. Old CSS classes should still work
2. If issues persist, temporarily use legacy CSS files
3. Report compatibility issues for future updates

## Future Considerations

### Planned Enhancements
- CSS custom properties (CSS variables) for easier theming
- Additional component-specific animations
- Better responsive design utilities
- Dark/light theme toggle support

### Migration Timeline
- **Current**: Both legacy and component CSS supported
- **Future**: Legacy CSS will be marked for removal
- **Long-term**: Component-based CSS only

For questions or issues with CSS migration, refer to the plugin documentation or create an issue in the project repository.
