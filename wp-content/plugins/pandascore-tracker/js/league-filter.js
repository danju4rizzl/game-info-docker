document.addEventListener('DOMContentLoaded', function () {
  const filters = document.querySelectorAll('.pandascore-league-filter')
  const matches = document.querySelectorAll('.pandascore-match')

  // Define the specific leagues we're filtering for
  const specificLeagues = ['LCK', 'LPL', 'LEC', 'LTA North', 'LTA South']
  const ltaLeagues = ['LTA North', 'LTA South']

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
        match.style.display = 'flex'
      } else {
        match.style.display = 'none'
      }
    })

    // Update container visibility after filtering
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
      } else if (selectedLeague === 'LTA') {
        // Show matches from both LTA North and LTA South
        if (ltaLeagues.includes(matchLeagueName)) {
          match.style.display = 'flex'
        } else {
          match.style.display = 'none'
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

    // Update container visibility after filtering
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

  // Update container visibility after initialization
  updateContainerVisibility()
})
