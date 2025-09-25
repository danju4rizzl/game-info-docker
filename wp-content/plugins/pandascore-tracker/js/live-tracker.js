document.addEventListener('DOMContentLoaded', function () {
  /**
   * Improved PandaScore Live Tracker
   * Handles WebSocket connections more reliably with proper error handling
   */

  // Configuration
  const CONFIG = {
    MAX_RETRIES: 3,
    RETRY_DELAY: 5000,
    POLL_INTERVAL: 15000,
    NON_RETRY_CODES: [1000, 4001, 4003, 4029] // Codes that shouldn't trigger reconnection
  }

  // Validate environment
  if (typeof pandaScoreLiveTracker === 'undefined') {
    console.log('[PandaScore] No live tracking data available')
    return
  }

  const { wsMatches, restBase, preloadedMatches } = pandaScoreLiveTracker

  if (!Array.isArray(wsMatches) || wsMatches.length === 0) {
    console.log('[PandaScore] No live matches to track')
    return
  }

  console.log(`[PandaScore] Starting tracker for ${wsMatches.length} matches`)

  // State management
  const connections = new Map()
  const retryAttempts = new Map()

  /**
   * Build WebSocket URL properly
   */
  function buildWebSocketUrl(matchId) {
    return `wss://live.pandascore.co/matches/${matchId}`
  }

  /**
   * Update match scores in the DOM
   */
  function updateMatchScores(matchId, matchData) {
    const matchElement = document.querySelector(`[data-match-id="${matchId}"]`)
    if (!matchElement) {
      console.warn(`[PandaScore] Match element not found: ${matchId}`)
      return
    }

    // Update scores if available
    if (matchData.results && Array.isArray(matchData.results)) {
      matchData.results.forEach((result, index) => {
        const opponentId = result.team_id || result.opponent_id
        if (!opponentId) return

        const scoreElement = matchElement.querySelector(
          `[data-opponent-id="${opponentId}"]`
        )
        if (scoreElement) {
          const newScore = parseInt(result.score, 10) || 0
          const currentScore = parseInt(scoreElement.textContent, 10) || 0

          if (newScore !== currentScore) {
            scoreElement.textContent = newScore

            // Add visual feedback for score changes
            scoreElement.classList.add('score-updating')
            setTimeout(() => {
              scoreElement.classList.remove('score-updating')
            }, 1000)

            console.log(
              `[PandaScore] Score updated for match ${matchId}: ${newScore}`
            )
          }
        }
      })
    }

    // Update match status if finished
    if (matchData.status === 'finished') {
      console.log(`[PandaScore] Match ${matchId} finished, stopping tracking`)
      stopTracking(matchId)
    }
  }

  /**
   * Fetch match data via REST API
   */
  async function fetchMatchData(matchId) {
    try {
      const base = typeof restBase === 'string' ? restBase.replace(/\/$/, '') : ''
      const response = await fetch(`${base}/match/${matchId}`)

      if (!response.ok) {
        console.warn(
          `[PandaScore] HTTP ${response.status} for match ${matchId}`
        )
        return null
      }

      const data = await response.json()
      updateMatchScores(matchId, data)
      return data
    } catch (error) {
      console.warn(
        `[PandaScore] Fetch failed for match ${matchId}:`,
        error.message
      )
      return null
    }
  }

  /**
   * Create WebSocket connection with proper error handling
   */
  function createWebSocketConnection(matchId) {
    const wsUrl = buildWebSocketUrl(matchId)
    let ws
    let pollTimer = null

    console.log(`[PandaScore] Connecting to match ${matchId}`)

    try {
      ws = new WebSocket(wsUrl)
    } catch (error) {
      console.error(
        `[PandaScore] Failed to create WebSocket for ${matchId}:`,
        error
      )
      scheduleRetry(matchId)
      return
    }

    // Store connection
    connections.set(matchId, { socket: ws, pollTimer: null })

    ws.onopen = function (event) {
      console.log(`[PandaScore] Connected to match ${matchId}`)

      // Reset retry counter on successful connection
      retryAttempts.delete(matchId)

      // Do not fetch immediately; rely on preloaded data and WS messages
      // Start periodic polling as backup
      pollTimer = setInterval(() => {
        fetchMatchData(matchId)
      }, CONFIG.POLL_INTERVAL)

      // Update connection object
      connections.set(matchId, { socket: ws, pollTimer: pollTimer })
    }

    ws.onmessage = function (event) {
      try {
        const data = JSON.parse(event.data)

        // Handle different message types
        if (data.type === 'hello') {
          console.log(`[PandaScore] Received hello from match ${matchId}`)
          return
        }

        // For any other message, fetch latest match data
        fetchMatchData(matchId)
      } catch (error) {
        console.warn(`[PandaScore] Invalid JSON from match ${matchId}`)
        // Still fetch data even if JSON is invalid
        fetchMatchData(matchId)
      }
    }

    ws.onerror = function (error) {
      console.warn(`[PandaScore] WebSocket error for match ${matchId}:`, error)
    }

    ws.onclose = function (event) {
      console.log(
        `[PandaScore] Connection closed for match ${matchId}, code: ${event.code}`
      )

      // Clean up polling timer
      const connection = connections.get(matchId)
      if (connection && connection.pollTimer) {
        clearInterval(connection.pollTimer)
      }
      connections.delete(matchId)

      // Handle reconnection based on close code
      if (CONFIG.NON_RETRY_CODES.includes(event.code)) {
        console.log(
          `[PandaScore] Not retrying match ${matchId} due to close code ${event.code}`
        )

        // For 4003 (forbidden), fall back to polling only
        if (event.code === 4003) {
          startPollingOnlyMode(matchId)
        }
        return
      }

      // Schedule retry for other cases
      scheduleRetry(matchId)
    }
  }

  /**
   * Schedule reconnection attempt
   */
  function scheduleRetry(matchId) {
    const attempts = retryAttempts.get(matchId) || 0

    if (attempts >= CONFIG.MAX_RETRIES) {
      console.warn(
        `[PandaScore] Max retries reached for match ${matchId}, switching to polling`
      )
      startPollingOnlyMode(matchId)
      return
    }

    retryAttempts.set(matchId, attempts + 1)

    console.log(
      `[PandaScore] Retrying match ${matchId} in ${
        CONFIG.RETRY_DELAY
      }ms (attempt ${attempts + 1})`
    )

    setTimeout(() => {
      createWebSocketConnection(matchId)
    }, CONFIG.RETRY_DELAY)
  }

  /**
   * Fall back to polling-only mode
   */
  function startPollingOnlyMode(matchId) {
    console.log(`[PandaScore] Starting polling-only mode for match ${matchId}`)

    // Initial fetch
    fetchMatchData(matchId)

    // Set up polling
    const pollTimer = setInterval(() => {
      fetchMatchData(matchId)
    }, CONFIG.POLL_INTERVAL)

    connections.set(matchId, { type: 'polling', pollTimer: pollTimer })
  }

  /**
   * Stop tracking a match
   */
  function stopTracking(matchId) {
    const connection = connections.get(matchId)
    if (!connection) return

    if (connection.socket && connection.socket.readyState === WebSocket.OPEN) {
      connection.socket.close(1000, 'Match finished')
    }

    if (connection.pollTimer) {
      clearInterval(connection.pollTimer)
    }

    connections.delete(matchId)
    retryAttempts.delete(matchId)

    console.log(`[PandaScore] Stopped tracking match ${matchId}`)
  }

  /**
   * Initialize tracking for all live matches
   */
  function initializeTracking() {
    const preloadMap = new Map()
    if (Array.isArray(preloadedMatches)) {
      preloadedMatches.forEach((m) => {
        if (m && m.id) preloadMap.set(String(m.id), m)
      })
    }

    wsMatches.forEach((match) => {
      const matchId = match.match_id

      if (!matchId) {
        console.warn('[PandaScore] Match missing ID:', match)
        return
      }

      // Apply preloaded data to DOM (no network)
      const preload = preloadMap.get(String(matchId))
      if (preload) {
        updateMatchScores(matchId, preload)
      }

      // Start with WebSocket connection attempt
      createWebSocketConnection(matchId)
    })
  }

  /**
   * Clean up on page unload
   */
  window.addEventListener('beforeunload', () => {
    connections.forEach((connection, matchId) => {
      if (
        connection.socket &&
        connection.socket.readyState === WebSocket.OPEN
      ) {
        connection.socket.close(1000, 'Page unload')
      }
      if (connection.pollTimer) {
        clearInterval(connection.pollTimer)
      }
    })
  })

  // Start tracking
  initializeTracking()
})