# PandaScore Tracker WordPress Plugin

A modern WordPress plugin that fetches and displays esports match data from the PandaScore API with a sleek dark theme UI.

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

## Configuration

### API Key Setup
1. Get your free API key from [PandaScore Developers](https://pandascore.co/developers)
2. Go to WordPress Admin → Settings → PandaScore Tracker
3. Enter your API key and save

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

- **Valorant** (`valorant`)
- **League of Legends** (`lol`)
- **Counter-Strike: Global Offensive** (`csgo`)
- **Dota 2** (`dota2`)
- **Overwatch** (`overwatch`)
- **Rainbow Six Siege** (`r6siege`)
- **Rocket League** (`rl`)

## Technical Features

### Component Architecture
Settings: Admin configuration

API: External API interactions

Router: URL routing and templates

Assets: CSS/JS management

Renderer: HTML generation

Main Plugin: Orchestration and shortcode handling
## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **PandaScore API Key**: Required for functionality
- **Modern Browser**: For WebSocket support (live matches)


## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- PandaScore API key (free)
- Active internet connection

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- **Developer**: Deejay Dev
- **API Provider**: PandaScore
- **Design**: Modern dark theme inspired by gaming interfaces
