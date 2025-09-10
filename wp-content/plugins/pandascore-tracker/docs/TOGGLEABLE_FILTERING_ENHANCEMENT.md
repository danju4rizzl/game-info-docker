# Toggleable Filtering Enhancement

## Overview

This document outlines the enhancement made to the PandaScore Tracker plugin's league filtering system to support toggleable functionality, providing users with an intuitive way to switch between filtered and default views.

## Enhancement Summary

### Previous Behavior
- Clicking a league button would filter matches to show only that league
- No way to return to default view without refreshing the page
- All matches were visible initially (including "OTHER LEAGUES")

### New Toggleable Behavior
- **Default State**: Shows matches from 5 main leagues only (LCK, LPL, LEC, LTA North, LTA South)
- **Toggle ON**: First click filters to show only selected league matches
- **Toggle OFF**: Second click returns to default state (5 main leagues)
- **Visual Feedback**: League-specific border colors when active

## Technical Implementation

### 1. CSS Enhancements

#### Added Border Styling
```css
.pandascore-league-filter {
    border: 2px solid transparent;
    transition: transform 0.2s ease, background 0.2s, border-color 0.2s;
}
```

#### League-Specific Active Colors
- **LCK**: White border (#FFFFFF)
- **LPL**: Red border (#F60909)
- **LEC**: Teal border (#00E5BE)
- **LTA North**: Brown border (#B3A27F)
- **LTA South**: Brown border (#B3A27F)
- **OTHER LEAGUES**: Brand color border

### 2. JavaScript Logic Overhaul

#### Key Functions Added
1. **`initializeDefaultState()`**: Sets up initial view with 5 main leagues
2. **`showMainLeaguesMatches()`**: Returns to default state
3. **`filterByLeague(selectedLeague)`**: Filters to specific league
4. **Toggle Detection**: Checks if button is already active

#### Toggle Logic Flow
```javascript
if (isCurrentlyActive) {
    // Toggle OFF: Return to default state
    filters.forEach((f) => f.classList.remove('active'))
    showMainLeaguesMatches()
} else {
    // Toggle ON: Filter by selected league
    filters.forEach((f) => f.classList.remove('active'))
    filter.classList.add('active')
    filterByLeague(selectedLeague)
}
```

### 3. State Management

#### Default State
- **Visible**: Matches from LCK, LPL, LEC, LTA North, LTA South
- **Hidden**: "OTHER LEAGUES" matches
- **Active Buttons**: None

#### Filtered State
- **Visible**: Only matches from selected league
- **Hidden**: All other matches
- **Active Buttons**: Selected league button with specific border color

## User Experience Improvements

### 1. Intuitive Navigation
- Users can easily toggle between filtered and unfiltered views
- Clear visual feedback shows which filter is active
- Consistent behavior across all league buttons

### 2. Default View Optimization
- Shows most relevant matches (main 5 leagues) by default
- Reduces visual clutter by hiding "OTHER LEAGUES" initially
- Provides clean starting point for users

### 3. Visual Consistency
- Each league has its own brand color for active state
- Smooth transitions between states
- Clear distinction between active and inactive buttons

## Testing Scenarios

### 1. Initial Load Test
- ✅ Only main 5 league matches visible
- ✅ No active filter buttons
- ✅ "OTHER LEAGUES" matches hidden

### 2. Toggle ON Test
- ✅ Click LCK → Only LCK matches visible, white border
- ✅ Click LPL → Only LPL matches visible, red border
- ✅ Click LEC → Only LEC matches visible, teal border
- ✅ Click OTHER LEAGUES → Only other league matches visible

### 3. Toggle OFF Test
- ✅ Click active button → Return to default state
- ✅ Button loses active styling
- ✅ Main 5 leagues visible again

### 4. Switch Between Filters Test
- ✅ Click different league buttons
- ✅ Previous button deactivates, new button activates
- ✅ Correct matches shown for each selection

## Files Modified

### 1. CSS Updates
- **File**: `wp-content/plugins/pandascore-tracker/css/index.css`
- **Changes**: Added border styling and league-specific active colors

### 2. JavaScript Rewrite
- **File**: `wp-content/plugins/pandascore-tracker/js/league-filter.js`
- **Changes**: Complete rewrite with toggle functionality and state management

### 3. Documentation Updates
- **File**: `wp-content/plugins/pandascore-tracker/docs/LEAGUE_FILTERING_FEATURE.md`
- **Changes**: Updated with toggleable functionality documentation

### 4. Test File Enhancement
- **File**: `wp-content/plugins/pandascore-tracker/test-league-filtering.html`
- **Changes**: Enhanced test scenarios for toggle functionality

## Benefits

1. **Enhanced UX**: Intuitive toggle behavior matches user expectations
2. **Visual Clarity**: League-specific colors provide clear feedback
3. **Efficient Navigation**: Easy switching between filtered and default views
4. **Reduced Cognitive Load**: Default state shows most relevant content
5. **Consistent Behavior**: All buttons follow same toggle pattern

## Backward Compatibility

- ✅ All existing functionality preserved
- ✅ Same shortcode usage: `[pandascore_tracker game="lol"]`
- ✅ No breaking changes to API or PHP code
- ✅ Enhanced behavior is additive, not replacing

## Future Enhancements

### Potential Improvements
1. **Multi-Select**: Allow multiple leagues to be selected simultaneously
2. **Keyboard Navigation**: Add keyboard shortcuts for filter buttons
3. **URL State**: Preserve filter state in URL for bookmarking
4. **Animation**: Add smooth transitions when switching between states
5. **Mobile Optimization**: Enhanced touch interactions for mobile devices

## Conclusion

The toggleable filtering enhancement significantly improves the user experience by providing intuitive navigation between filtered and default views. The implementation maintains backward compatibility while adding sophisticated state management and visual feedback systems.
