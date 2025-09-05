# PandaScore Live Scores Implementation

## Overview

This document describes the implementation of live scores functionality in the PandaScore API WordPress plugin. The plugin now supports displaying both live matches and upcoming matches with a modern dark theme UI.

## Features Added

### 1. Live Match Support
- **Live Matches Endpoint**: Uses PandaScore's `/{game}/matches/running` endpoint to fetch currently running matches for specific games
- **Real-time Scores via WebSockets**: Connects to `wss://live.pandascore.co/matches/{match_id}/events?token=YOUR_TOKEN` for each live match
- **Recovery Support**: Sends `{type: 'recover', payload: { game_id }}` after connection per the docs
- **Intelligent Reconnection**: Exponential backoff with retry limits and non-retryable code detection
- **REST Fallback**: Periodic lightweight REST refresh to keep the scoreboard consistent
- **Live Indicators**: Visual indicators (pulsing dot, "LIVE" header) to distinguish live matches
- **Mixed Display**: Can show both live and upcoming matches in a single widget

### 2. Modern JavaScript Architecture (v1.1)
- **Proper Error Handling**: Try-catch blocks with specific error messages and graceful degradation
- **Connection State Management**: Centralized tracking of WebSocket connections and polling timers
- **Memory Leak Prevention**: Proper cleanup of timers and event listeners on disconnect
- **Exponential Backoff**: Smart reconnection strategy that prevents server overload
- **Non-Retryable Codes**: Stops reconnection attempts for permanent failures (4001, 4003, 4029)
- **Visual Feedback**: Score changes trigger brief color animations for better UX
## Code Architecture & Best Practices

### JavaScript Refactoring (v1.1)

The live-tracker.js has been completely refactored using modern JavaScript best practices:

#### 1. **Configuration Management**
```javascript
const CONFIG = {
  MAX_RETRIES: 5,
  BASE_RETRY_DELAY: 2000,
  MAX_RETRY_DELAY: 30000,
  POLL_INTERVAL: 20000,
  NON_RETRY_CODES: new Set([1000, 4001, 4003, 4029])
}
```

#### 2. **Proper Error Handling**
- Async/await pattern for REST API calls
- Try-catch blocks with specific error messages
- Graceful degradation when WebSocket fails
- AbortController for request cancellation (future-ready)

#### 3. **Connection Management**
- Centralized state with `connections` and `lastResults` Maps
- Proper cleanup of timers and connections
- Exponential backoff: `delay = min(30s, 2s * 2^(attempt-1))`
- Smart retry logic that stops on permanent failures

#### 4. **Memory Management**
- Automatic cleanup of polling timers on disconnect
- Connection tracking to prevent memory leaks
- Proper event listener cleanup

#### 5. **Performance Optimizations**
- Result comparison to avoid unnecessary DOM updates
- Timestamp-based WebSocket URLs to prevent caching issues
- Efficient DOM querying with fallback selectors

### WebSocket Error Handling

The refactored code handles these scenarios properly:

1. **Connection Failures**: Exponential backoff with max retry limit
2. **Authentication Errors (4001)**: Stop retrying, log error
3. **Permission Errors (4003)**: Stop retrying (plan doesn't include endpoint)
4. **Rate Limiting (4029)**: Stop retrying, respect server limits
5. **Normal Closure (1000)**: Clean shutdown, no retry needed
6. **Network Issues**: Retry with increasing delays

### Visual Feedback

Score updates now include visual feedback:
- Brief green background flash when scores change
- Smooth CSS transitions for better UX
- "live" class added to active score elements
## Error Code 4003 Diagnosis & Solution

### Problem Analysis
The error `[PandaScore] Connection closed for match 1209103 (code: 4003)` indicates:

- **4003 = Forbidden**: Your PandaScore plan doesn't include access to the Events WebSocket endpoint
- **Root Cause**: The plugin was trying to connect to `wss://live.pandascore.co/matches/{id}/events` which requires a Pro plan
- **Previous Issue**: The old code would retry infinitely, causing performance problems

### Smart Fallback Strategy (v1.1)

The refactored code now implements a 3-tier fallback system:

#### Tier 1: Events Endpoint (Preferred)
```javascript
wss://live.pandascore.co/matches/{match_id}/events?token=YOUR_TOKEN
```
- **Best**: Real-time events with recovery support
- **Requires**: Live Pro plan or higher
- **Fallback**: If 4003 error, try Tier 2

#### Tier 2: Frames Endpoint (Fallback)
```javascript
wss://live.pandascore.co/matches/{match_id}?token=YOUR_TOKEN
```
- **Good**: Real-time score frames
- **Requires**: Live Basic plan or higher
- **Fallback**: If 4003 error, try Tier 3

#### Tier 3: REST Polling (Final Fallback)
```javascript
https://api.pandascore.co/matches/{match_id}?token=YOUR_TOKEN
```
- **Reliable**: Works with any plan that includes match data
- **Method**: Polls every 10 seconds (2x faster than normal)
- **Graceful**: No WebSocket errors, just REST calls

### Implementation Details

The system automatically detects and handles the 4003 error:

```javascript
if (event?.code === 4003) {
  console.warn(`Events endpoint forbidden (code: 4003)`)

  if (!useFrames && match.frames_url) {
    console.info(`Trying frames endpoint`)
    scheduleReconnect(match, 0, true) // Try frames
  } else {
    console.info(`WebSocket unavailable, using polling`)
    startPollingFallback(match) // Use REST polling
  }
}
```

### Expected Console Output

With the new system, you should see:
```
[PandaScore] Attempting connection to match 1209103 (events endpoint)
[PandaScore] Connected to match 1209103
[PandaScore] Events endpoint forbidden for match 1209103 (code: 4003)
[PandaScore] Trying frames endpoint for match 1209103
[PandaScore] Attempting connection to match 1209103 (frames endpoint)
[PandaScore] Connected to match 1209103
```

Or if frames also fail:
```
[PandaScore] WebSocket unavailable, using polling for match 1209103
[PandaScore] Starting polling fallback for match 1209103
```

### Benefits

1. **No More Infinite Loops**: Smart error handling stops retries on permanent failures
2. **Automatic Fallback**: Gracefully degrades to available endpoints
3. **Better Performance**: Polling fallback is more efficient than failed WebSocket retries
4. **Plan Compatibility**: Works with any PandaScore plan level
5. **User Experience**: Scores still update in real-time regardless of plan limitations

## Real-Time Updates Fix (v1.1.1)

### Issues Diagnosed & Fixed

#### **Issue 1: Team Names Not Displaying**
- **Problem**: Live matches data structure differs from regular matches
- **Root Cause**: `/lives` endpoint returns different opponent nesting than `/matches`
- **Solution**: Enhanced opponent data extraction to handle both formats:

```php
// Handle both nested and direct opponent structures
if (isset($o['opponent'])) {
    $name = $o['opponent']['name'];
    $logo = $o['opponent']['image_url'];
    $opponent_ids[] = $o['opponent']['id'];
} else {
    $name = $o['name'];
    $logo = $o['image_url'];
    $opponent_ids[] = $o['id'];
}
```

#### **Issue 2: Real-Time Updates Limited to Scores Only**
- **Problem**: JavaScript only updated scores, not team names, win/lose states, or odds
- **Solution**: Comprehensive DOM update system:
  - `updateScores()`: Updates scores with visual feedback
  - `updateTeamInfo()`: Updates team names and logos dynamically
  - `updateWinLoseStates()`: Updates win/lose CSS classes in real-time
  - `updateOdds()`: Simulates live odds changes with variation

#### **Issue 3: Slow Update Frequency**
- **Problem**: 20-second polling was too slow for live sports
- **Solution**: Aggressive update intervals:
  - **WebSocket + Polling**: 5-second REST safety net
  - **Polling Fallback**: 3-second updates when WebSocket unavailable
  - **Visual Feedback**: Immediate color changes on score updates

### Enhanced Real-Time Features

#### **1. Complete Match Data Updates**
```javascript
function updateDomWithMatchData(matchId, matchData) {
  // Update scores with win/lose states
  if (matchData.results) updateScores(matchElement, matchData.results)

  // Update team names and logos
  if (matchData.opponents) updateTeamInfo(matchElement, matchData.opponents)

  // Update win/lose visual states
  updateWinLoseStates(matchElement, matchData.results)

  // Update odds with live variation
  updateOdds(matchElement)
}
```

#### **2. Visual Feedback System**
- **Score Changes**: Green flash for 1 second
- **Odds Changes**: Orange highlight for 0.8 seconds
- **Win/Lose States**: Automatic CSS class updates (green/red backgrounds)

#### **3. Debugging & Monitoring**
- **PHP Debug Logs**: Match data structure logging when WP_DEBUG enabled
- **Console Logging**: Clear connection status and update messages
- **Data Validation**: Handles missing or malformed API responses gracefully

### Expected User Experience

Users now see:
1. **Team names appear correctly** on initial load and stay updated
2. **Scores update every 3-5 seconds** without page refresh
3. **Win/lose colors change instantly** when scores change
4. **Odds fluctuate realistically** during live matches
5. **Visual feedback** when any data changes
6. **No browser performance issues** from infinite loops

### Performance Improvements

- **3x Faster Updates**: 3-5 second intervals vs 20 seconds
- **Smart Caching**: Prevents unnecessary DOM updates when data unchanged
- **Efficient Polling**: Only when WebSocket unavailable
- **Memory Management**: Proper cleanup prevents memory leaks

## Real Odds Replacement (v1.1.2)

### Issue: Fake Odds Generation Removed

**Problem Identified**: The plugin was generating fake betting odds using `Math.random()`, which:
- Provided misleading information to users
- Did not reflect real betting markets
- Could confuse users expecting authentic data

**Root Cause**: PandaScore API focuses on esports statistics and live game data, but does not provide betting odds information.

### Solution: Authentic Match Statistics

Replaced fake odds with real match data from PandaScore API:

#### **What's Now Displayed Instead of Fake Odds:**

1. **Live Matches**:
   - Current game number: `G1`, `G2`, `G3`
   - Total games played: `3G` (when between games)
   - Live indicator: `LIVE` (when no game data available)

2. **Upcoming Matches**:
   - Match format: `BO3`, `BO5` (Best of 3, Best of 5)
   - Game count: `3G`, `5G` (when format available)

3. **Finished Matches**:
   - Final game count: `3G`, `4G`
   - End indicator: `END`

#### **Implementation Details:**

**PHP Side** (`get_match_stat_display()`):
```php
if ($is_live) {
    // Show current game number or "LIVE"
    if (isset($match['games']) && count($match['games']) > 0) {
        $running_game = find_running_game($match['games']);
        return $running_game ? 'G' . $running_game : count($match['games']) . 'G';
    }
    return 'LIVE';
} else {
    // Show match format (BO3, BO5, etc.)
    return isset($match['number_of_games']) ? 'BO' . $match['number_of_games'] : 'BO3';
}
```

**JavaScript Side** (`updateMatchStats()`):
```javascript
// Display real match statistics based on API data
if (matchData.status === 'running') {
    statValue = currentGame ? `G${gameNumber}` : `${totalGames}G`;
} else if (matchData.status === 'finished') {
    statValue = `${matchData.games.length}G`;
} else {
    statValue = `BO${matchData.number_of_games}`;
}
```

### Benefits of Real Data

1. **Authentic Information**: Users see real match statistics instead of fake odds
2. **Useful Context**: Game numbers and match formats provide valuable context
3. **Live Updates**: Statistics update in real-time as matches progress
4. **No Misleading Data**: Eliminates confusion from fake betting information
5. **Better UX**: More relevant information for esports viewers

### Visual Changes

- **Same UI Elements**: `.ps-odds` class retained for styling consistency
- **Smaller Font**: Reduced to 11px for compact display of match stats
- **Letter Spacing**: Added for better readability of short codes
- **Color Highlights**: Orange flash when statistics change (game progression)

### User Experience

Users now see authentic, useful match information:
- **"G2"** = Currently playing Game 2
- **"BO5"** = Best of 5 match format
- **"3G"** = 3 games have been played
- **"LIVE"** = Match is live but no detailed game data
- **"END"** = Match has finished

## Game Filtering Fix for Live Matches (v1.1.3)

### Issue: Live Matches Ignored Game Parameter

**Problem**: The shortcode `[pandascore_tracker game="dota2" limit="5" type="live"]` was not filtering live matches by game, showing all live matches regardless of the game parameter.

**Root Cause**: The `render_live_matches()` and `fetch_live_matches()` functions were not receiving or using the `$game` parameter.

### Solution: Complete Game Filtering Implementation

#### **1. Function Signature Updates**
```php
// Before
private function render_live_matches($limit)
private function fetch_live_matches($limit)

// After
private function render_live_matches($game, $limit)
private function fetch_live_matches($game, $limit)
```

#### **2. API Filtering**
Added game filtering to the `/lives` endpoint:
```php
$query_args = array('page[size]' => intval($limit));

// Add game filter if specified and not 'all'
if (!empty($game) && $game !== 'all') {
    $query_args['filter[videogame]'] = $game;
}

$url = add_query_arg($query_args, "https://api.pandascore.co/lives");
```

#### **3. Client-Side Filtering (Backup)**
Added additional filtering in PHP to ensure accuracy:
```php
// Additional client-side filtering by game if specified
if (!empty($game) && $game !== 'all') {
    $match_game = $live_data['match']['videogame']['slug'];
    if ($match_game !== $game) {
        continue; // Skip this match
    }
}
```

#### **4. Debug Logging**
Added debugging to help troubleshoot game filtering:
```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("PandaScore Live Matches URL: " . $url);
}
```

### Expected Behavior Now

| Shortcode | Result |
|-----------|--------|
| `[pandascore_tracker type="live" game="dota2"]` | Shows only Dota 2 live matches |
| `[pandascore_tracker type="live" game="lol"]` | Shows only League of Legends live matches |
| `[pandascore_tracker type="live" game="csgo"]` | Shows only CS:GO live matches |
| `[pandascore_tracker type="live" game="valorant"]` | Shows only Valorant live matches |
| `[pandascore_tracker type="live"]` | Shows all live matches (default) |

### Benefits

1. **✅ Consistent Behavior**: Live matches now filter by game like upcoming matches
2. **✅ Accurate Results**: Users see only matches for their requested game
3. **✅ Better UX**: No confusion from seeing wrong game matches
4. **✅ API Efficiency**: Reduced data transfer by filtering at API level
5. **✅ Fallback Protection**: Client-side filtering ensures accuracy

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
private function fetch_live_matches( $game, $limit ) {
    $url = "https://api.pandascore.co/{$game}/matches/running";
    // Returns array of currently running match objects for specific game
}
```

#### Upcoming Matches Endpoint
```php
private function fetch_upcoming_matches( $game, $limit ) {
    $url = "https://api.pandascore.co/{$game}/matches/upcoming";
    // Returns array of upcoming match objects for specific game
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
