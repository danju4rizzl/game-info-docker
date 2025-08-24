# PandaScore Live Scores Implementation

## Overview

This document describes the implementation of live scores functionality in the PandaScore API WordPress plugin. The plugin now supports displaying both live matches and upcoming matches with a modern dark theme UI.

## Features Added

### 1. Live Match Support
- **Live Matches Endpoint**: Uses PandaScore's `/lives` endpoint to fetch currently running matches
- **Real-time Scores**: Displays current match scores for live games
- **Live Indicators**: Visual indicators (pulsing dot, "LIVE" header) to distinguish live matches
- **Mixed Display**: Can show both live and upcoming matches in a single widget

### 2. Enhanced UI Design
- **Modern Dark Theme**: Sleek dark interface matching esports aesthetics
- **Live Match Styling**: Special styling for live matches with red accents
- **Responsive Design**: Works on desktop and mobile devices
- **Team Logos**: Displays team logos when available
- **Score Display**: Prominent score display for live matches

### 3. New Shortcode Parameters

#### Basic Usage
```
[pandascore_tracker]
```

#### Live Matches Only
```
[pandascore_tracker type="live" limit="5"]
```

#### Mixed Display (Live + Upcoming)
```
[pandascore_tracker type="mixed" game="valorant" limit="10"]
```

#### Upcoming Matches Only
```
[pandascore_tracker type="upcoming" game="csgo" limit="5"]
```

### 4. Shortcode Parameters

| Parameter | Description | Default | Options |
|-----------|-------------|---------|---------|
| `type` | Match type to display | `mixed` | `live`, `upcoming`, `mixed` |
| `game` | Game type (for upcoming matches) | `valorant` | `valorant`, `lol`, `csgo`, `dota2`, etc. |
| `limit` | Number of matches to display | `5` | Any positive integer |
| `align` | Widget alignment | `center` | `left`, `center`, `right` |

## Technical Implementation

### 1. API Integration

#### Live Matches Endpoint
```php
private function fetch_live_matches( $limit ) {
    $url = "https://api.pandascore.co/lives";
    // Returns array of live match objects with WebSocket endpoints
}
```

#### Regular Matches Endpoint
```php
private function fetch_matches( $game, $limit ) {
    $url = "https://api.pandascore.co/{$game}/matches";
    // Returns array of upcoming/recent match objects
}
```

### 2. Data Structure

#### Live Match Data
```json
{
  "endpoints": [...],
  "match": {
    "id": 123456,
    "opponents": [
      {
        "opponent": {
          "name": "Team A",
          "image_url": "https://..."
        }
      }
    ],
    "results": [
      {"score": 1},
      {"score": 0}
    ],
    "status": "running"
  }
}
```

#### Regular Match Data
```json
{
  "id": 123456,
  "opponents": [...],
  "results": [...],
  "begin_at": "2024-01-01T12:00:00Z",
  "status": "not_started"
}
```

### 3. CSS Classes

#### Main Container
- `.pandascore-tracker` - Main widget container

#### Live Match Indicators
- `.pandascore-live-indicator` - "LIVE" header with pulsing dot
- `.pandascore-match.live` - Live match container with red accent
- `.pandascore-score.live` - Live score styling

#### Team Display
- `.pandascore-team` - Individual team row
- `.pandascore-name` - Team name
- `.pandascore-logo` - Team logo image
- `.pandascore-score` - Score display
- `.pandascore-odds` - Betting odds (upcoming matches)

## Usage Examples

### WordPress Page/Post
Add any of these shortcodes to your WordPress content:

```
[pandascore_tracker type="live"]
[pandascore_tracker type="mixed" game="lol" limit="8"]
[pandascore_tracker type="upcoming" game="csgo" limit="6" align="left"]
```

### PHP Template
```php
echo do_shortcode('[pandascore_tracker type="mixed" limit="10"]');
```

### Widget Areas
The shortcode can be used in WordPress widgets that support shortcodes.

## Configuration

### API Key Setup
1. Go to WordPress Admin → Settings → PandaScore Tracker
2. Enter your PandaScore API key
3. Save settings

### Supported Games
- Valorant (`valorant`)
- League of Legends (`lol`)
- Counter-Strike (`csgo`)
- Dota 2 (`dota2`)
- Rocket League (`rl`)
- Overwatch (`ow`)
- And more...

## Styling Customization

### CSS Variables
The plugin uses CSS custom properties that can be overridden:

```css
.pandascore-tracker {
    --live-color: #ff4444;
    --background-color: #1a1a1a;
    --text-color: #ffffff;
    --accent-color: #4CAF50;
}
```

### Custom Styling
Add custom CSS to your theme's `style.css` or use the WordPress Customizer:

```css
/* Customize live indicator */
.pandascore-live-indicator {
    background: #your-color;
}

/* Customize team names */
.pandascore-name {
    font-size: 16px;
    color: #your-color;
}
```

## Troubleshooting

### Common Issues

1. **No live matches showing**
   - Check if there are currently live matches for your selected games
   - Verify API key is valid and has live data access

2. **Styling issues**
   - Clear WordPress cache
   - Check for theme CSS conflicts
   - Ensure the plugin CSS is loading

3. **API errors**
   - Verify API key in plugin settings
   - Check PandaScore API status
   - Ensure proper game parameter format

### Debug Mode
Add this to your `wp-config.php` for debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Future Enhancements

### Planned Features
- WebSocket integration for real-time updates
- More detailed match statistics
- Tournament bracket display
- Player statistics
- Match predictions
- Custom team logo uploads

### Performance Optimizations
- API response caching
- Lazy loading for images
- Minified CSS/JS assets
- CDN integration for team logos

## Support

For technical support or feature requests:
1. Check the plugin settings page
2. Review this documentation
3. Contact the developer with specific error messages and configuration details
