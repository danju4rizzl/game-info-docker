// cspell:ignore pandascore retryable
;;(function () {
  'use strict'

  /**
   * PandaScore Live Tracker - Enhanced WebSocket Implementation
   * Handles real-time score updates with direct WebSocket message processing
   */

  // Configuration
  const CONFIG = {
    MAX_RETRIES: 5,
    BASE_RETRY_DELAY: 2000,
    MAX_RETRY_DELAY: 30000,
    POLL_INTERVAL: 20000,
    NON_RETRY_CODES: new Set([1000, 4001, 4003, 4029])
  }

  // Validate environment
  if (typeof pandaScoreLiveTracker === 'undefined') {
    console.error('[PandaScore] Localized data not found.')
    return
  }

  const { apiKey, matchIds, websocketUrl } = pandaScoreLiveTracker

  if (!apiKey?.trim()) {
    console.error('[PandaScore] API key is missing or invalid.')
    return
  }

  if (!Array.isArray(matchIds) || matchIds.length === 0) {
    console.info('[PandaScore] No live matches to track.')
    return
  }

  // State management
  const connections = new Map()
  const lastResults = new Map()

  /**
   * Utility Functions
   */
  function buildWebSocketUrl(matchId, useFrames = false) {
    const baseUrl = `${websocketUrl.replace(/\/ws$/, '')}/${matchId}${
      useFrames ? '/frames' : '/events'
    }`
    const separator = baseUrl.includes('?') ? '&' : '?'
    const timestamp = Date.now()
    return `${baseUrl}${separator}token=${encodeURIComponent(
      apiKey
    )}&t=${timestamp}`
  }

  function updateDomWithMatchData(matchId, data) {
    const key = String(matchId)
    const previousData = lastResults.get(key)

    // Skip update if data hasn't changed
    if (previousData && JSON.stringify(previousData) === JSON.stringify(data)) {
      return
    }

    lastResults.set(key, data)

    const matchElement = document.querySelector(
      `.pandascore-match[data-match-id='${matchId}']`
    )

    if (!matchElement) {
      console.warn(`[PandaScore] Match element not found for ID: ${matchId}`)
      return
    }

    // Update scores from frames data
    if (data.type === 'frames' && data.payload) {
      updateScores(matchElement, data.payload.red, data.payload.blue)
    }
  }

  function updateScores(matchElement, redTeam, blueTeam) {
    const scoreElements = matchElement.querySelectorAll('.pandascore-score')
    if (scoreElements.length < 2) return

    // Assume first score is red team, second is blue team
    const redScore = redTeam?.score || 0
    const blueScore = blueTeam?.score || 0

    ;[redScore, blueScore].forEach((score, index) => {
      const scoreElement = scoreElements[index]
      if (scoreElement) {
        const newScore = String(score)
        if (scoreElement.textContent !== newScore) {
          scoreElement.textContent = newScore
          scoreElement.classList.add('score-updating')
          scoreElement.classList.remove('bg-gray-700')
          scoreElement.classList.add('bg-green-500')
          setTimeout(() => {
            scoreElement.classList.remove('bg-green-500')
            scoreElement.classList.add('bg-gray-700')
          }, 1000)
        }
      }
    })
  }

  /**
   * Connection Management with Smart Fallback
   */
  function createConnection(matchId, attempt = 0, useFrames = true) {
    if (attempt >= CONFIG.MAX_RETRIES) {
      console.warn(`[PandaScore] Max retries reached for match ${matchId}`)
      if (!connections.has(matchId)) {
        startPollingFallback(matchId)
      }
      return
    }

    const url = buildWebSocketUrl(matchId, useFrames)
    let socket
    let pollTimer = null

    console.log(
      `[PandaScore] Attempting connection to match ${matchId} (${
        useFrames ? 'frames' : 'events'
      } endpoint)`
    )

    try {
      socket = new WebSocket(url)
    } catch (error) {
      console.error(
        `[PandaScore] Failed to create WebSocket for match ${matchId}:`,
        error
      )
      scheduleReconnect(matchId, attempt + 1, useFrames)
      return
    }

    connections.set(matchId, socket)

    socket.onopen = function () {
      console.log(`[PandaScore] Connected to match ${matchId}`)
      // Send recovery request for events if supported
      if (!useFrames) {
        socket.send(
          JSON.stringify({
            type: 'recover',
            payload: { game_id: null } // Adjust game_id as needed from initial data
          })
        )
      }
      // Initial sync
      fetchMatchResults(matchId)
    }

    socket.onmessage = function (event) {
      try {
        const message = JSON.parse(event.data)
        if (message.type === 'hello') return

        updateDomWithMatchData(matchId, message)
      } catch (error) {
        console.warn(
          `[PandaScore] Invalid message for match ${matchId}:`,
          error
        )
      }
    }

    socket.onerror = function (error) {
      console.error(`[PandaScore] WebSocket error for match ${matchId}:`, error)
    }

    socket.onclose = function (event) {
      if (pollTimer) {
        clearInterval(pollTimer)
        pollTimer = null
      }

      connections.delete(matchId)

      if (event?.code === 4003 && !useFrames) {
        console.warn(
          `[PandaScore] Events endpoint forbidden for match ${matchId} (code: 4003)`
        )
        scheduleReconnect(matchId, 0, true) // Try frames endpoint
        return
      }

      if (CONFIG.NON_RETRY_CODES.has(event?.code)) {
        console.log(
          `[PandaScore] Connection closed for match ${matchId} (code: ${event.code})`
        )
        return
      }

      console.warn(
        `[PandaScore] Connection lost for match ${matchId}, reconnecting...`
      )
      scheduleReconnect(matchId, attempt + 1, useFrames)
    }
  }

  function scheduleReconnect(matchId, attempt, useFrames = true) {
    const delay = Math.min(
      CONFIG.MAX_RETRY_DELAY,
      CONFIG.BASE_RETRY_DELAY * Math.pow(2, attempt - 1)
    )

    setTimeout(() => createConnection(matchId, attempt, useFrames), delay)
  }

  /**
   * Polling Fallback for when WebSocket is unavailable
   */
  async function fetchMatchResults(matchId) {
    try {
      const url = `https://api.pandascore.co/matches/${matchId}?token=${encodeURIComponent(
        apiKey
      )}`
      const response = await fetch(url, {
        headers: { Accept: 'application/json' }
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const data = await response.json()
      if (data && data.results) {
        updateDomWithMatchData(matchId, { type: 'frames', payload: data })
      }
    } catch (error) {
      console.warn(
        `[PandaScore] Failed to fetch results for match ${matchId}:`,
        error.message
      )
    }
  }

  function startPollingFallback(matchId) {
    console.info(`[PandaScore] Starting polling fallback for match ${matchId}`)
    fetchMatchResults(matchId)
    const pollInterval = setInterval(() => fetchMatchResults(matchId), 3000)
    connections.set(matchId, { type: 'polling', timer: pollInterval })
  }

  function stopPollingFallback(matchId) {
    const connection = connections.get(matchId)
    if (connection && connection.type === 'polling') {
      clearInterval(connection.timer)
      connections.delete(matchId)
    }
  }

  // Initialize connections for all live matches
  matchIds.forEach((matchId) => createConnection(matchId))
})()