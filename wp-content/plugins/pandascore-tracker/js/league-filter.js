document.addEventListener('DOMContentLoaded', function () {
  const filters = document.querySelectorAll('.pandascore-league-filter')
  const matches = document.querySelectorAll('.pandascore-match')

  // Define the specific leagues we're filtering for (default visible set)
  const specificLeagues = ['LCK', 'LPL', 'LEC', 'LTA North', 'LTA South']
  const ltaLeagues = ['LTA North', 'LTA South']
  const MAX_DISPLAY = 8

  // Helpers: sorting
  const leaguePriority = ['LCK', 'LPL', 'LEC', 'LTA North', 'LTA South']
  function getLeagueName(match) {
    const img = match.querySelector('.pandascore-league-container img')
    return img ? img.alt : ''
  }
  function sortByScheduledAtAsc(a, b) {
    const ta = a.getAttribute('data-scheduled-at')
    const tb = b.getAttribute('data-scheduled-at')
    if (!ta && !tb) return 0
    if (!ta) return 1
    if (!tb) return -1
    return new Date(ta) - new Date(tb)
  }
  function sortLiveMatches() {
    const liveContainer = document.querySelector(
      '.pandascore-live-container .pandascore-matches-container'
    )
    if (!liveContainer) return
    const items = Array.from(
      liveContainer.querySelectorAll('.pandascore-match')
    )
    items.sort((a, b) => {
      const la = getLeagueName(a)
      const lb = getLeagueName(b)
      const pa = leaguePriority.includes(la) ? leaguePriority.indexOf(la) : 999
      const pb = leaguePriority.includes(lb) ? leaguePriority.indexOf(lb) : 999
      if (pa !== pb) return pa - pb
      // Secondary: scheduled time if available
      return sortByScheduledAtAsc(a, b)
    })
    items.forEach((el) => liveContainer.appendChild(el))
  }
  function sortUpcomingMatches() {
    const upContainer = document.querySelector(
      '.pandascore-upcoming-container .pandascore-matches-container'
    )
    if (!upContainer) return
    const items = Array.from(upContainer.querySelectorAll('.pandascore-match'))
    items.sort(sortByScheduledAtAsc)
    items.forEach((el) => upContainer.appendChild(el))
  }

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

    // Initial sort
    sortLiveMatches()
    sortUpcomingMatches()
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

    // Enforce max visible per section, then update container visibility
    sortLiveMatches()
    sortUpcomingMatches()
    enforceDisplayLimit()
    updateContainerVisibility()
    document.dispatchEvent(new CustomEvent('pandascore:league-filter-changed'))
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
        // Show both LTA North and LTA South
        match.style.display = ltaLeagues.includes(matchLeagueName)
          ? 'flex'
          : 'none'
      } else {
        // Show matches from the selected specific league only
        match.style.display =
          matchLeagueName === selectedLeague ? 'flex' : 'none'
      }
    })

    // Enforce max visible per section, then update container visibility
    sortLiveMatches()
    sortUpcomingMatches()
    enforceDisplayLimit()
    updateContainerVisibility()
    document.dispatchEvent(new CustomEvent('pandascore:league-filter-changed'))
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

  // Allow external reset to default main leagues (used by date filter toggle-off)
  document.addEventListener('pandascore:reset-league-default', () => {
    filters.forEach((f) => f.classList.remove('active'))
    showMainLeaguesMatches()
  })

  // React to external filter changes (e.g., date filter) — attach once globally
  document.addEventListener('pandascore:filters-updated', () => {
    sortLiveMatches()
    sortUpcomingMatches()
    enforceDisplayLimit()
    updateContainerVisibility()
  })

  // Reapply current league selection without changing active state (used when date filter is cleared)
  document.addEventListener('pandascore:reapply-league', () => {
    const active = document.querySelector('.pandascore-league-filter.active')
    if (active) {
      const selectedLeague = active.getAttribute('data-league-name')
      filterByLeague(selectedLeague)
    } else {
      showMainLeaguesMatches()
    }
  })

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
