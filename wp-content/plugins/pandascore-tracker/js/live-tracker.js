;(function () {
  // Check if our localized data from WordPress exists
  if (typeof pandaScoreLiveTracker === 'undefined') {
    console.error('PandaScore Live Tracker: Localized data not found.')
    return
  }

  const { apiKey, matchIds, websocketUrl } = pandaScoreLiveTracker

  if (!apiKey) {
    console.error('PandaScore Live Tracker: API key is missing.')
    return
  }

  if (!matchIds || matchIds.length === 0) {
    // No live matches to track on this page load, so no need to connect.
    return
  }

  const socket = new WebSocket(`${websocketUrl}?token=${apiKey}`)

  socket.onopen = function (e) {
    console.log('[PandaScore] WebSocket connection established.')

    // Subscribe to score updates for each live match currently displayed on the page
    matchIds.forEach((matchId) => {
      const subscription = {
        action: 'subscribe',
        resource: `/matches/${matchId}/score`
      }
      console.log(`[PandaScore] Subscribing to: ${subscription.resource}`)
      socket.send(JSON.stringify(subscription))
    })
  }

  socket.onmessage = function (event) {
    try {
      const eventData = JSON.parse(event.data)

      // We are only interested in 'update' events which contain new data
      if (eventData.event !== 'update' || !eventData.data) {
        return
      }

      const { match_id, results } = eventData.data

      if (!match_id || !results) {
        return
      }

      // Find the corresponding match container on the page using the data-match-id attribute
      const matchElement = document.querySelector(
        `.pandascore-match[data-match-id='${match_id}']`
      )
      if (!matchElement) {
        return // Match not found on this page
      }

      // Update scores for each opponent in the message
      results.forEach((result) => {
        const opponentId = result.team_id
        const score = result.score

        // Find the specific score element using the data-opponent-id attribute
        const scoreElement = matchElement.querySelector(
          `.pandascore-score[data-opponent-id='${opponentId}']`
        )
        if (scoreElement) {
          scoreElement.textContent = score
        }
      })
    } catch (error) {
      console.error('[PandaScore] Error processing message:', error)
    }
  }

  socket.onclose = function (event) {
    if (event.wasClean) {
      console.log(
        `[PandaScore] Connection closed cleanly, code=${event.code} reason=${event.reason}`
      )
    } else {
      // e.g. server process killed or network down
      console.error('[PandaScore] Connection died unexpectedly.')
    }
  }

  socket.onerror = function (error) {
    console.error(`[PandaScore] WebSocket Error: ${error.message}`)
  }
})()
