document.addEventListener('DOMContentLoaded', function () {
  const filters = document.querySelectorAll('.pandascore-league-filter')
  const matches = document.querySelectorAll('.pandascore-match')

  // Define the specific leagues we're filtering for
  const specificLeagues = ['LCK', 'LPL', 'LEC', 'LTA', 'LTA South']
  const ltaLeagues = ['LTA North', 'LTA South']
  const MAX_DISPLAY = 8

  // Initialize default state: show only main 5 leagues, hide OTHER LEAGUES
  function initializeDefaultState() {
    matches.forEach((match) => {
      const matchLeague = match.querySelector(
        '.pandascore-league-container img'
      )

      if (!matchLeague) {
        // If no league image, check for placeholder
        const placeholder = match.querySelector(
          '.pandascore-league-placeholder'
        )
        if (placeholder) {
          match.style.display = 'none' // Hide matches without proper league info
        }
        return
      }

      const matchLeagueName = matchLeague.alt

      // Show matches from the 5 main leagues, hide OTHER LEAGUES
      if (specificLeagues.includes(matchLeagueName)) {
        match.style.display = 'flex'
      } else {
        match.style.display = 'none'
      }
    })

    // Ensure no filters are active initially
    filters.forEach((f) => f.classList.remove('active'))
  }

  // Show matches from the 5 main leagues (default state)
  function showMainLeaguesMatches() {
    matches.forEach((match) => {
      const matchLeague = match.querySelector(
        '.pandascore-league-container img'
      )

      if (!matchLeague) {
        match.style.display = 'none'
        return
      }

      const matchLeagueName = matchLeague.alt

      // Show only matches from the 5 main leagues
      if (specificLeagues.includes(matchLeagueName)) {
        console.log(specificLeagues)
        console.log(matchLeagueName)
        match.style.display = 'flex'
      } else {
        match.style.display = 'none'
      }
    })

    // Enforce max visible per section, then update container visibility
    enforceDisplayLimit()
    updateContainerVisibility()
  }

  // Filter matches for a specific league
  function filterByLeague(selectedLeague) {
    matches.forEach((match) => {
      const matchLeague = match.querySelector(
        '.pandascore-league-container img'
      )

      if (!matchLeague) {
        match.style.display = 'none'
        return
      }

      const matchLeagueName = matchLeague.alt

      if (selectedLeague === 'OTHER LEAGUES') {
        // Show matches that are NOT from the specific 5 leagues
        if (specificLeagues.includes(matchLeagueName)) {
          match.style.display = 'none'
        } else {
          match.style.display = 'flex'
        }
      } else {
        // Show matches from the selected specific league only
        if (matchLeagueName === selectedLeague) {
          match.style.display = 'flex'
        } else {
          match.style.display = 'none'
        }
      }
    })

    // Enforce max visible per section, then update container visibility
    enforceDisplayLimit()
    updateContainerVisibility()
  }

  // Update container visibility based on visible matches
  function updateContainerVisibility() {
    const liveContainer = document.querySelector('.pandascore-live-container')
    const upcomingContainer = document.querySelector(
      '.pandascore-upcoming-container'
    )

    if (liveContainer) {
      const liveMatches = liveContainer.querySelectorAll('.pandascore-match')
      const visibleLiveMatches = Array.from(liveMatches).filter(
        (match) => match.style.display !== 'none'
      )
      liveContainer.style.display =
        visibleLiveMatches.length > 0 ? 'block' : 'none'
    }

    if (upcomingContainer) {
      const upcomingMatches =
        upcomingContainer.querySelectorAll('.pandascore-match')
      const visibleUpcomingMatches = Array.from(upcomingMatches).filter(
        (match) => match.style.display !== 'none'
      )
      upcomingContainer.style.display =
        visibleUpcomingMatches.length > 0 ? 'block' : 'none'
    }
  }

  // Enforce max number of visible matches per section
  function enforceDisplayLimit() {
    const applyCap = (selector) => {
      const container = document.querySelector(selector)
      if (!container) return
      const matches = Array.from(
        container.querySelectorAll('.pandascore-match')
      )
      const visible = matches.filter((m) => m.style.display !== 'none')
      visible.forEach((m, idx) => {
        m.style.display = idx < MAX_DISPLAY ? 'flex' : 'none'
      })
    }
    applyCap('.pandascore-live-container')
    applyCap('.pandascore-upcoming-container')
  }

  // Add click event listeners to filters
  filters.forEach((filter) => {
    filter.addEventListener('click', () => {
      const selectedLeague = filter.getAttribute('data-league-name')
      const isCurrentlyActive = filter.classList.contains('active')

      if (isCurrentlyActive) {
        // Toggle OFF: Return to default state (show main 5 leagues)
        filters.forEach((f) => f.classList.remove('active'))
        showMainLeaguesMatches()
      } else {
        // Toggle ON: Filter by selected league
        filters.forEach((f) => f.classList.remove('active'))
        filter.classList.add('active')
        filterByLeague(selectedLeague)
      }
    })
  })

  // Initialize the default state on page load
  initializeDefaultState()

  // Enforce max visible per section and update visibility after initialization
  enforceDisplayLimit()
  updateContainerVisibility()
})
