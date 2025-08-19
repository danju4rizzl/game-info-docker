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
1. Get your free API key from [PandaScore](https://pandascore.co/)
2. Go to WordPress Admin → Settings → PandaScore Tracker
3. Enter your API key and save

## Usage

### Basic Shortcode
```
[pandascore_tracker]
```

### Advanced Usage
```
[pandascore_tracker game="valorant" limit="6" title="UPCOMING MATCHES"]
```

### Available Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `game` | `valorant` | Game type (valorant, lol, cs, dota2, etc.) |
| `limit` | `5` | Number of matches to display (1-20) |
| `title` | `UP-COMING` | Header title text |
| `align` | `center` | Alignment (left, center, right) |

### Supported Games

- **Valorant** (`valorant`)
- **League of Legends** (`lol`)
- **Counter-Strike** (`cs`)
- **Dota 2** (`dota2`)
- **Overwatch** (`overwatch`)
- And more supported by PandaScore API

## Design Features

The plugin features a modern dark theme with:
- **Dark Background**: Professional #1a1a1a background
- **Team Logos**: Automatic logo fetching from API
- **Match Times**: Clean time display format
- **Hover Effects**: Smooth transitions and interactions
- **Typography**: Modern system fonts for optimal readability
- **Responsive Design**: Perfect on mobile, tablet, and desktop

## CSS Classes

For custom styling, the plugin uses these CSS classes:

```css
.pandascore-tracker        /* Main container */
.pandascore-header         /* Title header */
.pandascore-matches        /* Matches container */
.pandascore-match          /* Individual match */
.pandascore-team           /* Team row */
.pandascore-time           /* Match time */
.pandascore-logo           /* Team logo */
.pandascore-name           /* Team name */
.pandascore-dash           /* Score placeholder */
.pandascore-error          /* Error state */
.pandascore-empty          /* Empty state */
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- PandaScore API key (free)
- Active internet connection

## Troubleshooting

### Plugin Not Displaying
- Check if the API key is correctly set
- Verify the game parameter is supported
- Check browser console for JavaScript errors

### Styling Issues
- Clear WordPress cache
- Check for theme CSS conflicts
- Ensure the plugin CSS is loading

### API Errors
- Verify API key is valid
- Check PandaScore API status
- Ensure proper game parameter format

## Support

For support, feature requests, or bug reports:
- Check the plugin settings page
- Review the troubleshooting section
- Contact the developer

## Changelog

### Version 1.1
- Modern dark theme UI
- Improved responsive design
- Better error handling
- Proper uninstall cleanup
- Enhanced CSS structure

### Version 1.0
- Initial release
- Basic API integration
- Simple shortcode functionality

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- **Developer**: Deejay Dev
- **API Provider**: PandaScore
- **Design**: Modern dark theme inspired by gaming interfaces
