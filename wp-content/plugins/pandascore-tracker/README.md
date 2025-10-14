# PandaScore Tracker WordPress Plugin

A modern WordPress plugin that provides real-time esports match data from the PandaScore API with a sleek dark theme UI.

## Features

- 🎮 **Real-time Esports Data**: Fetches live match data from PandaScore API
- 🎨 **Modern Dark Theme**: Sleek UI matching the latest design trends
- 📱 **Fully Responsive**: Works perfectly on all devices
- ⚙️ **Easy Configuration**: Simple admin interface for API key setup
- 🔧 **Customizable**: Multiple shortcode parameters for flexibility
- 🧹 **Clean Uninstall**: Properly removes all data when deleted

## Installation

### Method 1: WordPress Admin Upload
1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin

### Method 2: Manual Installation
1. Extract the plugin files
2. Upload the `pandascore-tracker` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin

## Setup & Configuration

### Step 1: Plugin Activation & Database Setup
When you activate the plugin, it automatically creates two database tables using WordPress's built-in database:
- `wp_pandascore_tournaments` - Stores tournament data
- `wp_pandascore_matches` - Stores match data with league information

These tables are created automatically upon activation, no manual database configuration needed.

### Step 2: API Key Configuration
1. Get your free API key from [PandaScore Developers](https://pandascore.co/developers)
2. Go to WordPress Admin → Settings → PandaScore Tracker
3. Enter your API key in the "API Key" field
4. Click "Save Changes"

### Step 3: Cache Duration (Optional)
1. In the same settings page, you'll see "Cache Duration (minutes)"
2. Default is 10 minutes (recommended to reduce API rate limit usage)
3. You can adjust between 1-60 minutes based on your needs
4. Click "Save Changes" if you modify this setting

### Step 4: Sync Data from PandaScore API
1. After saving your API key, click the "Sync Now" button
2. This fetches the latest:
   - Running and upcoming tournaments
   - Live matches
   - Upcoming matches
3. Data is stored in your WordPress database for fast retrieval
4. The plugin automatically syncs every 5 minutes via WP-Cron

### Step 5: Display Matches on Your Site
Add the shortcode to any page, post, or widget:
```
[pandascore_tracker]
```

This displays live and upcoming matches based on the synced data.

## Usage

### Shortcode Parameters

- **`game`**: Game type (valorant, lol, csgo, dota2, etc.) - default: 'lol'
- **`limit`**: Number of matches to display (1-20) - default: 5
- **`align`**: Text alignment (left, center, right) - default: 'center'
- **`type`**: Match type - "upcoming", "live", or "mixed" - default: 'mixed'

### Basic Shortcode
```
[pandascore_tracker]
```

### Advanced Examples
```php
// Live matches only
[pandascore_tracker game="valorant" limit="5" type="live"]

// Upcoming matches only
[pandascore_tracker game="lol" limit="10" type="upcoming"]

// Mixed display (live + upcoming)
[pandascore_tracker game="csgo" limit="8" type="mixed"]
```

## Supported Games

- **League of Legends** (`lol`)


## How It Works

### Data Flow Architecture
1. **API Integration**: Plugin connects to PandaScore API using your API key
2. **Data Fetching**: Retrieves tournaments and matches from PandaScore
3. **Database Storage**: Stores data in WordPress database tables for performance
4. **Caching Layer**: Implements caching to minimize API calls and respect rate limits
5. **Auto-Sync**: Background sync every 5 minutes keeps data fresh
6. **Frontend Display**: Shortcode renders cached data instantly without API delays

### Component Architecture
- **Settings**: Admin configuration interface
- **API**: External API interactions with PandaScore
- **Database**: Local storage for tournaments and matches
- **Sync**: Automated and manual data synchronization
- **Cache**: Response caching to reduce API usage
- **Renderer**: HTML generation for match displays
- **Main Plugin**: Orchestration and shortcode handling
## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **PandaScore API Key**: Required for functionality
- **Modern Browser**: For WebSocket support (live matches)

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- **Developer**: Deejay Dev
- **API Provider**: PandaScore
- **Design**: Modern dark theme inspired by gaming interfaces
