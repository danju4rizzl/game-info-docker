# Real-time Live Updates (WebSockets + Polling Fallback)

This document explains how the PandaScore tracker plugin now delivers real-time score updates without requiring page reloads.

## Overview
- Uses PandaScore WebSockets when available for low-latency updates
- Automatically falls back to fast REST polling when WebSockets are not available or blocked
- No page refresh required; scores update in place

## How it works
1. Server renders live matches via REST and collects their IDs
2. Server queries PandaScore `/lives` to find open WebSocket endpoints for those matches
3. Front-end receives `wsMatches` with `{ match_id, frames_url?, events_url?, game_ids? }`
4. JS (`js/live-tracker.js`) connects to WebSocket if possible; otherwise, it fetches results periodically

## Files involved
- PHP: `pandascore-tracker.php`
  - Enqueues and localizes live tracker data
  - New helper `get_ws_matches_payload()` builds the `wsMatches` payload
- JS: `js/live-tracker.js`
  - Manages WebSocket connections and reconnection
  - Polling fallback for updates when WS is unavailable

## Configuration
- API key is taken from Settings → PandaScore Tracker
- Ensure the key has access to live data; otherwise WS will fallback to polling

## Security note
PandaScore advises not exposing API tokens in client-side apps. This plugin currently injects the token for client-side WebSocket and REST. For production hardening, consider a server-side proxy that:
- Hides the token from the browser
- Proxies both WebSocket (if needed) and REST calls

## Troubleshooting
- If WebSockets fail (403/4003/too many connections), the plugin automatically falls back to polling every few seconds
- If your host blocks WebSockets entirely, polling will still provide updates (slightly less instant)


