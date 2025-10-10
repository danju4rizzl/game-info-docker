# API Requests Documentation - Match Details Page

## Overview

This document outlines all API requests made when a user visits the match details page (`match-details.php`).

## Current Implementation

### Total API Requests: Up to 12 requests per page load

### Request Breakdown:

#### 1. Match Details Request

- **Endpoint:** `GET /matches/{match_id}`
- **Purpose:** Fetch basic match information
- **Data Retrieved:**
  - League name and details
  - Match scheduled time
  - Team information (names, logos)
  - Match scores
  - Basic match metadata
- **Count:** 1 request
- **Required:** Yes

#### 2. Player Stats Request

- **Endpoint:** `GET /lol/matches/{match_id}/players/stats`
- **Purpose:** Get organized team player data and statistics
- **Data Retrieved:**
  - Players organized by teams
  - Player names and IDs
  - Player statistics (kills, deaths, assists)
  - Team association data
- **Count:** 1 request
- **Required:** Yes (for team organization)

#### 3. Individual Player Image Requests

- **Endpoint:** `GET /players/{player_id}` (for each player)
- **Purpose:** Fetch individual player profile images
- **Data Retrieved:**
  - Player image URLs
  - Player profile details
- **Count:** Up to 10 requests (5 players per team × 2 teams)
- **Required:** No (can be optimized)

## Performance Impact

### Current Issues:

- **High API usage:** 12 requests per page load
- **Slow page loading:** Multiple sequential API calls
- **Rate limiting risk:** Excessive API calls may hit rate limits
- **Poor user experience:** Delayed content loading

### Optimization Opportunities:

#### Option 1: Remove Individual Player Image Requests

- **Reduce to:** 2 requests total
- **Implementation:** Use placeholder images with player initials
- **Benefits:**
  - 83% reduction in API calls (12 → 2)
  - Faster page load times
  - Reduced API rate limit usage
  - Still displays player names correctly

#### Option 2: Batch Player Data (Future Enhancement)

- **Endpoint:** `GET /players?filter[id]={id1,id2,id3...}`
- **Reduce to:** 3 requests total
- **Benefits:**
  - Single request for all player images
  - Real player images maintained
  - Significant performance improvement

## Recommended Implementation

### Immediate Optimization:

```php
// Replace individual player image requests with placeholders
$player_images = [];
foreach (array_merge($teamA_players, $teamB_players) as $player) {
    $player_images[$player['id']] = 'https://via.placeholder.com/40/666/fff?text=' . substr($player['name'], 0, 1);
}


### Benefits:
API Requests: 12 → 2 (83% reduction)

Page Load Time: Significantly improved

User Experience: Faster content display

API Rate Limits: Reduced usage


User visits match-details.php
    ↓
1. GET /matches/{id} → Match basic info
    ↓
2. GET /lol/matches/{id}/players/stats → Team players & stats
    ↓
3. Generate placeholder images → Player avatars
    ↓
Display complete match details page
```
