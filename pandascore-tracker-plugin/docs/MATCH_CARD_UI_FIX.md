# Match Card UI Fix Documentation

## Overview

This document describes the fixes applied to the PandaScore Tracker plugin to correctly implement the match card UI structure as shown in the provided screenshot.

## Issues Fixed

### 1. Incorrect Match Card Structure

**Problem**: The previous implementation rendered each team as a separate row, which didn't match the intended UI design.

**Solution**: Restructured the match rendering to use the proper card-based layout:

```html
<div class="ps-card">
  <div class="ps-card-head">
    <!-- League logo and name -->
  </div>
  <div class="ps-match">
    <div class="ps-left">
      <!-- League logo or time -->
    </div>
    <div class="ps-teams">
      <div class="ps-team-row">
        <!-- Team 1 logo and name -->
      </div>
      <div class="ps-team-row">
        <!-- Team 2 logo and name -->
      </div>
    </div>
    <div class="ps-right">
      <!-- Scores or odds container -->
    </div>
  </div>
</div>
```

### 2. Redundant CSS Code

**Problem**: Multiple CSS files contained duplicate styles, causing maintenance issues and bloated code.

**Solution**: 
- Removed redundant inline CSS from PHP file
- Consolidated duplicate styles from deprecated CSS files
- Updated CSS loading to use component-based approach
- Marked old CSS files as deprecated with backward compatibility

### 3. Incorrect CSS Loading

**Problem**: The plugin was using inline styles instead of proper CSS files.

**Solution**: Updated the asset loading system:

```php
// New component-based CSS loading
wp_enqueue_style( 'pandascore-base-style' );

if ( $atts['type'] === 'live' || $atts['type'] === 'mixed' ) {
    wp_enqueue_style( 'pandascore-live-scores-style' );
}

if ( $atts['type'] === 'upcoming' || $atts['type'] === 'mixed' ) {
    wp_enqueue_style( 'pandascore-upcoming-matches-style' );
}
```

## UI Structure Explanation

Based on the provided screenshot, the correct structure includes:

1. **League Logo**: Positioned on the left side of the card header
2. **Match Card**: Container for the entire match
3. **Team 1 & Team 2**: Both teams displayed vertically within the same match card
4. **Team Logos**: Small logos next to each team name
5. **Team Names**: Displayed next to their respective logos
6. **Scores/Odds Container**: Right-aligned container showing scores for live matches or odds for upcoming matches

## Files Modified

### PHP Files
- `pandascore-tracker.php`: Updated `render_match()` method and asset loading

### CSS Files
- `css/pandascore-base.css`: Fixed layout styles for proper card structure
- `css/pandascore-tracker.css`: Marked as deprecated, removed duplicate styles
- `css/pandascore-live-tracker.css`: Marked as deprecated, removed duplicate styles

## Backward Compatibility

All changes maintain backward compatibility:
- Existing shortcodes continue to work
- Old CSS files are still registered but marked as deprecated
- Same CSS class names are used where possible
- JavaScript functionality remains unchanged

## Testing

To verify the fixes:

1. Use the shortcode: `[pandascore_tracker type="mixed" game="valorant" limit="5"]`
2. Check that matches display in the correct card format
3. Verify that league logos appear in the card header
4. Confirm that both teams appear within the same match card
5. Ensure scores/odds appear in the right container

## Future Improvements

- Consider removing deprecated CSS files in a future major version
- Add more customization options for card appearance
- Implement responsive design improvements for mobile devices
