document.addEventListener('DOMContentLoaded', () => {
  const dateButtons = Array.from(
    document.querySelectorAll('.pandascore-date-filter')
  )
  if (dateButtons.length === 0) return

  const specificLeagues = ['LCK', 'LPL', 'LEC', 'LTA', 'LTA South']
  const ltaLeagues = ['LTA North', 'LTA South']

  const getYMD = (d) => {
    const yr = d.getFullYear()
    const mo = String(d.getMonth() + 1).padStart(2, '0')
    const da = String(d.getDate()).padStart(2, '0')
    return `${yr}-${mo}-${da}`
  }

  function getActiveLeague() {
    const activeBtn = document.querySelector(
      '.pandascore-league-filter.active'
    )
    return activeBtn ? activeBtn.getAttribute('data-league-name') : null
  }

  function leaguePassForMatch(match, activeLeague) {
    const img = match.querySelector('.pandascore-league-container img')
    if (!img) return false
    const name = img.alt

    if (!activeLeague) {
      // Default state: show main 5 leagues only
      return specificLeagues.includes(name)
    }

    if (activeLeague === 'OTHER LEAGUES') {
      return !specificLeagues.includes(name)
    }

    if (activeLeague === 'LTA') {
      return ltaLeagues.includes(name)
    }

    return name === activeLeague
  }

  function applyDateFilter(selectedYMD) {
    const upcomingContainer = document.querySelector(
      '.pandascore-upcoming-container'
    )
    if (!upcomingContainer) return

    const activeLeague = getActiveLeague()
    const matches = Array.from(
      upcomingContainer.querySelectorAll('.pandascore-match')
    )

    matches.forEach((m) => {
      const sched = m.getAttribute('data-scheduled-at')
      if (!sched) {
        m.style.display = 'none'
        return
      }
      const ymd = getYMD(new Date(sched))
      const matchesDate = ymd === selectedYMD
      const passesLeague = leaguePassForMatch(m, activeLeague)

      m.style.display = matchesDate && passesLeague ? 'flex' : 'none'
    })

    // Let other scripts (league filter) update caps/visibility
    document.dispatchEvent(new CustomEvent('pandascore:filters-updated'))
  }

  dateButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      dateButtons.forEach((b) => b.classList.remove('active'))
      btn.classList.add('active')
      const ymd = btn.getAttribute('data-date-iso')
      applyDateFilter(ymd)
    })
  })

  // Apply default (Today) on load
  const active =
    document.querySelector('.pandascore-date-filter.active') || dateButtons[0]
  if (active) {
    const ymd = active.getAttribute('data-date-iso')
    applyDateFilter(ymd)
  }

  // Re-apply when league filter changes
  document.addEventListener('pandascore:league-filter-changed', () => {
    const current = document.querySelector('.pandascore-date-filter.active')
    if (current) applyDateFilter(current.getAttribute('data-date-iso'))
  })
})

