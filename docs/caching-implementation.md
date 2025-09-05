# Plugin - Caching Documentation

This document provides an overview of the caching mechanism implemented in the PandaScore Tracker WordPress plugin to optimize API requests and reduce server load, especially for high-traffic environments. It includes instructions for managing and modifying caching settings.

## Overview

The plugin uses the WordPress Transients API to cache API responses from the PandaScore API. This approach minimizes the number of requests made to the external API by storing data temporarily and serving it from the cache when available. Caching is applied to league IDs and match data (both upcoming and live/running matches).

### Key Benefits

- Reduces API request frequency, staying within rate limits (e.g., 10k requests/hour for Real-time Data plans).
- Improves page load times by serving cached data.
- Scales better with multiple users by sharing the cache across the site.

### Cached Data Types

1. **League IDs**: Cached for 24 hours, as this data is relatively static.
2. **Upcoming Matches**: Cached for 5 minutes, balancing freshness with reduced calls.
3. **Live/Running Matches**: Cached for 1 minute, reflecting the dynamic nature of live data.

## Implementation Details

### Storage

- Data is stored using WordPress transients, which are saved in the `wp_options` table by default.
- If an object cache (e.g., Redis, Memcached) is enabled via a caching plugin (e.g., WP Super Cache), transients may be stored in memory for faster access.

### Transient Keys

- **League IDs**: `pandascore_league_ids`
- **Match Data**: `pandascore_{game}_{endpoint}_{limit}` (e.g., `pandascore_lol_running_5` for 5 live LoL matches)

### Expiration Times

- **League IDs**: 24 hours (`DAY_IN_SECONDS`, 86,400 seconds)
- **Upcoming Matches**: 5 minutes (300 seconds)
- **Live/Running Matches**: 1 minute (60 seconds)

## Managing Caching

### Viewing Cached Data

- Cached data can be inspected in the WordPress database under the `wp_options` table, looking for rows with `option_name` starting with `_transient_pandascore_`.
- Use a plugin like "Transients Manager" to view and delete transients via the WordPress admin interface.

### Updating or Changing Cache Expiration Times

To adjust the caching duration, modify the expiration times in the `set_transient` calls within the plugin code. Follow these steps:

1. **Locate the Code**:

   - Open the plugin file (`pandascore-tracker.php`).
   - Find the `get_league_ids` and `make_api_call` methods.

2. **Modify League IDs Cache**:
   - In `get_league_ids`, update the `set_transient` call:
     ```php
     set_transient( $transient_key, $league_ids, DAY_IN_SECONDS ); // Change DAY_IN_SECONDS (86,400) to desired seconds, e.g., 12 * HOUR_IN_SECONDS for 12 hours
     ```
