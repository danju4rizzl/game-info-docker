# League Filtering Feature Documentation

## Overview

The PandaScore Tracker plugin includes an advanced toggleable league filtering feature that allows users to filter League of Legends matches by specific leagues. The implementation uses local images instead of API-provided logos for better performance and consistency, with intelligent toggle functionality for enhanced user experience.

## Features

### Supported Leagues

- **LCK** (League of Legends Champions Korea)
- **LPL** (League of Legends Pro League)
- **LEC** (League of Legends European Championship)
- **LTA North** (League of Legends Tournament Arena North)
- **LTA South** (League of Legends Tournament Arena South)
- **OTHER LEAGUES** (All other LoL leagues not listed above)

### Local Images

All league logos are stored locally in the plugin's `images/` directory:

- `LCK-logo.png`
- `LPL-logo.png`
- `LEC-logo.png`
- `LTA-NORTH-logo.png`
- `LTA-SOUTH-logo.png`
- `OTHERS-LEAGUES-logo.png`

## Implementation Details

### PHP Changes

#### 1. Modified `render_league_filters()` Method

- **Location**: `pandascore-tracker.php` lines 82-107
- **Purpose**: Generates league filter buttons using local images
- **Key Changes**:
  - Removed API dependency for league logos
  - Uses local images from `images/` directory
  - Added "OTHER LEAGUES" button
  - Uses `data-league-name` attribute instead of `data-league-id`

#### 2. Updated `make_api_call()` Method

- **Location**: `pandascore-tracker.php` lines 109-133
- **Purpose**: Fetches all LoL matches without league filtering
- **Key Changes**:
  - Removed league-specific API filtering
  - Fetches all LoL matches for client-side filtering
  - Simplified parameter structure

### JavaScript Changes

#### 1. Enhanced `league-filter.js`

- **Location**: `js/league-filter.js`
- **Purpose**: Handles client-side filtering logic
- **Key Features**:
  - Filters matches based on selected league
  - Implements "OTHER LEAGUES" functionality
  - Shows/hides matches dynamically
  - Maintains active state for selected filter

#### 2. Filtering Logic

```javascript
// For specific leagues: show only matches from that league
if (matchLeagueName === selectedLeague) {
  match.style.display = 'flex'
} else {
  match.style.display = 'none'
}

// For "OTHER LEAGUES": show matches NOT from the 5 main leagues
if (selectedLeague === 'OTHER LEAGUES') {
  if (specificLeagues.includes(matchLeagueName)) {
    match.style.display = 'none'
  } else {
    match.style.display = 'flex'
  }
}
```

## Usage

### Shortcode Usage

The league filtering feature is automatically included when using the shortcode:

```php
[pandascore_tracker game="lol" type="mixed" limit="10"]
```

### User Interaction - Toggleable Filtering

#### Default State

- **Initial View**: Shows matches from all 5 main leagues (LCK, LPL, LEC, LTA North, LTA South)
- **Hidden by Default**: "OTHER LEAGUES" matches are not shown initially
- **No Active Buttons**: No filter buttons have active styling on page load

#### Toggle ON (First Click)

1. User clicks any specific league button (LCK, LPL, LEC, LTA North, LTA South)
2. **Filtered View**: Shows ONLY matches from the selected league
3. **Visual Feedback**: Button displays active styling with league-specific border color
4. **Hidden Content**: All other matches (including other main leagues and "OTHER LEAGUES") are hidden

#### Toggle OFF (Second Click)

1. User clicks the same active league button again
2. **Return to Default**: Shows matches from ALL 5 main leagues again
3. **Reset Styling**: Button loses active state and returns to normal appearance
4. **Consistent Behavior**: "OTHER LEAGUES" matches remain hidden (default state)

#### "OTHER LEAGUES" Button

- **Toggle ON**: Shows only non-main league matches, hides all main league matches
- **Toggle OFF**: Returns to default state (shows main 5 leagues, hides others)
- **Consistent Behavior**: Follows same toggle pattern as specific league buttons

## CSS Styling

### Filter Button Styles

#### Base Button Styling

```css
.pandascore-league-filter {
  background: var(--brand-bg);
  border-radius: 6px;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  padding: 6px;
  border: 2px solid transparent;
  transition: transform 0.2s ease, background 0.2s, border-color 0.2s;
}

.pandascore-league-filter.active {
  background: var(--brand-color);
  transform: scale(1.1);
}
```

#### League-Specific Border Colors

```css
/* LCK - White border when active */
.pandascore-league-filter[data-league-name='LCK'].active {
  border-color: #ffffff;
}

/* LPL - Red border when active */
.pandascore-league-filter[data-league-name='LPL'].active {
  border-color: #f60909;
}

/* LEC - Teal border when active */
.pandascore-league-filter[data-league-name='LEC'].active {
  border-color: #00e5be;
}

/* LTA North - Brown border when active */
.pandascore-league-filter[data-league-name='LTA North'].active {
  border-color: #b3a27f;
}

/* LTA South - Brown border when active */
.pandascore-league-filter[data-league-name='LTA South'].active {
  border-color: #b3a27f;
}

/* OTHER LEAGUES - Brand color border when active */
.pandascore-league-filter[data-league-name='OTHER LEAGUES'].active {
  border-color: var(--brand-color);
}
```

## Benefits

1. **Performance**: Local images load faster than API requests
2. **Reliability**: No dependency on external image URLs
3. **Consistency**: Uniform image quality and sizing
4. **User Experience**: Instant filtering without API calls
5. **Flexibility**: Easy to add/modify league images

## File Structure

```
wp-content/plugins/pandascore-tracker/
├── images/
│   ├── LCK-logo.png
│   ├── LPL-logo.png
│   ├── LEC-logo.png
│   ├── LTA-NORTH-logo.png
│   ├── LTA-SOUTH-logo.png
│   └── OTHERS-LEAGUES-logo.png
├── js/
│   └── league-filter.js
├── css/
│   └── index.css
└── pandascore-tracker.php
```

## Testing

To test the league filtering feature:

1. **Visual Test**: Verify all 6 filter buttons display with correct images
2. **Functionality Test**: Click each button and verify correct matches are shown/hidden
3. **"OTHER LEAGUES" Test**: Verify it shows matches from leagues not in the main 5
4. **Active State Test**: Verify selected button is highlighted correctly
5. **Responsive Test**: Verify buttons work on different screen sizes

## Troubleshooting

### Common Issues

1. **Images not loading**: Check file paths and permissions in `images/` directory
2. **Filtering not working**: Verify JavaScript is loaded and no console errors
3. **No matches showing**: Check API key configuration and network connectivity
4. **Styling issues**: Verify CSS is loaded and brand variables are defined

### Debug Steps

1. Check browser console for JavaScript errors
2. Verify image file paths are correct
3. Test with different games (feature only works with "lol")
4. Verify API key is configured in plugin settings
