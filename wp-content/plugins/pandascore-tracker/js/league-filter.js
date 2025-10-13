document.addEventListener('DOMContentLoaded', function () {
  const filters = document.querySelectorAll('.pandascore-league-filter')
  const matches = document.querySelectorAll('.pandascore-match')

  const LCK = 'LCK'
  const LPL = 'LPL'
  const LEC = 'LEC'
  const LTA = 'LTA'
  const LTANorth = 'LTA North'
  const LTASouth = 'LTA South'
  const WORLDS = 'Worlds'

  // Define the specific leagues we're filtering for (default visible set)
  const specificLeagues = [LCK, LPL, LEC, LTA, WORLDS, LTANorth, LTASouth]

  const MAX_DISPLAY = 8

  // Helpers: sorting
  const leaguePriority = [LCK, LPL, LEC, LTA, WORLDS, LTANorth, LTASouth]

  function getLeagueName(match) {
    const img = match.querySelector('.pandascore-league-container img')
    const placeholder = match.querySelector(
      '.pandascore-league-container .pandascore-league-placeholder'
    )
    return img ? img.alt : placeholder ? placeholder.title : ''
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

  // Initialize default state: show ALL matches
  function initializeDefaultState() {
    matches.forEach((match) => {
      // Show ALL matches by default
      match.style.display = 'flex'
    })

    // Ensure no filters are active initially
    filters.forEach((f) => f.classList.remove('active'))

    // Initial sort
    sortLiveMatches()
    sortUpcomingMatches()
  }

  // Show ALL matches (default state)
  function showMainLeaguesMatches() {
    matches.forEach((match) => {
      // Show ALL matches
      match.style.display = 'flex'
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
    // console.log('🔍 Filtering by:', selectedLeague)
    matches.forEach((match) => {
      const matchLeagueName = getLeagueName(match)
      // console.log('Match league name:', matchLeagueName)

      if (!matchLeagueName) {
        match.style.display = 'none'
        return
      }

      if (selectedLeague === 'OTHER LEAGUES') {
        // Show matches that are NOT from the specific  leagues
        if (specificLeagues.includes(matchLeagueName)) {
          match.style.display = 'none'
        } else {
          match.style.display = 'flex'
        }
      } else if (selectedLeague === LTA) {
        // Show matches for LTA, LTA North, and LTA South
        match.style.display =
          matchLeagueName === LTA ||
          matchLeagueName === LTANorth ||
          matchLeagueName === LTASouth
            ? 'flex'
            : 'none'
      } else if (selectedLeague === WORLDS) {
        // Show matches for Worlds
        match.style.display = matchLeagueName === WORLDS ? 'flex' : 'none'
      } else if (selectedLeague === LCK) {
        // Show matches for LCK
        match.style.display = matchLeagueName === LCK ? 'flex' : 'none'
      } else if (selectedLeague === LPL) {
        // Show matches for LPL
        match.style.display = matchLeagueName === LPL ? 'flex' : 'none'
      } else if (selectedLeague === LCE) {
        // Show matches for LEC
        match.style.display = matchLeagueName === LCE ? 'flex' : 'none'
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

  // Log all unique league names for debugging
  const uniqueLeagues = new Set()
  matches.forEach((match) => {
    const leagueName = getLeagueName(match)
    if (leagueName) {
      uniqueLeagues.add(leagueName)
    }
  })
  console.log('🏆 All available league names:', Array.from(uniqueLeagues))

  // Enforce max visible per section and update visibility after initialization
  enforceDisplayLimit()
  updateContainerVisibility()

  // Enable drag-to-scroll on the league filters container
  const leagueStrips = Array.from(
    document.querySelectorAll('.pandascore-league-filters')
  )

  leagueStrips.forEach((container) => {
    let isDown = false
    let startX = 0
    let startScrollLeft = 0
    let dragged = false

    const onPointerDown = (e) => {
      if (e.pointerType === 'mouse' && e.button !== 0) return
      isDown = true
      dragged = false
      startX = e.clientX
      startScrollLeft = container.scrollLeft
    }

    const onPointerMove = (e) => {
      if (!isDown) return
      const dx = e.clientX - startX
      if (Math.abs(dx) > 3 && !dragged) {
        dragged = true
        container.classList.add('is-dragging')
      }
      if (dragged) {
        container.scrollLeft = startScrollLeft - dx
        e.preventDefault()
      }
    }

    const endDrag = () => {
      if (!isDown) return
      isDown = false
      container.classList.remove('is-dragging')
    }

    container.addEventListener('pointerdown', onPointerDown)
    container.addEventListener('pointermove', onPointerMove)
    container.addEventListener('pointerup', endDrag)
    container.addEventListener('pointercancel', endDrag)
    container.addEventListener('pointerleave', endDrag)

    container.addEventListener(
      'click',
      (e) => {
        if (dragged) {
          e.preventDefault()
          e.stopPropagation()
          dragged = false
        }
      },
      true
    )
  })
})
