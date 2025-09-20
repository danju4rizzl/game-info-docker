document.addEventListener('DOMContentLoaded', () => {
  const dateButtons = Array.from(
    document.querySelectorAll('.pandascore-date-filter')
  )
  if (dateButtons.length === 0) return

  const specificLeagues = ['LCK', 'LPL', 'LEC', 'LTA North', 'LTA South']
  const ltaLeagues = ['LTA North', 'LTA South']

  const getYMD = (d) => {
    const yr = d.getFullYear()
    const mo = String(d.getMonth() + 1).padStart(2, '0')
    const da = String(d.getDate()).padStart(2, '0')
    return `${yr}-${mo}-${da}`
  }

  function getActiveLeague() {
    const activeBtn = document.querySelector('.pandascore-league-filter.active')
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

  function sortUpcomingMatches() {
    const upContainer = document.querySelector(
      '.pandascore-upcoming-container .pandascore-matches-container'
    )
    if (!upContainer) return
    const items = Array.from(upContainer.querySelectorAll('.pandascore-match'))
    items.sort((a, b) => {
      const ta = a.getAttribute('data-scheduled-at')
      const tb = b.getAttribute('data-scheduled-at')
      if (!ta && !tb) return 0
      if (!ta) return 1
      if (!tb) return -1
      return new Date(ta) - new Date(tb)
    })
    items.forEach((el) => upContainer.appendChild(el))
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

    // Sort visible upcoming matches by time, then notify others
    sortUpcomingMatches()
    document.dispatchEvent(new CustomEvent('pandascore:filters-updated'))
  }

  dateButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const isActive = btn.classList.contains('active')

      if (isActive) {
        // Toggle OFF: clear active date only and reapply current league selection
        dateButtons.forEach((b) => b.classList.remove('active'))
        document.dispatchEvent(new CustomEvent('pandascore:reapply-league'))
        document.dispatchEvent(new CustomEvent('pandascore:filters-updated'))
        return
      }

      // Toggle ON: activate this date and filter
      dateButtons.forEach((b) => b.classList.remove('active'))
      btn.classList.add('active')
      const ymd = btn.getAttribute('data-date-iso')
      applyDateFilter(ymd)
    })
  })

  // Apply default (Today) on load
  const active = document.querySelector('.pandascore-date-filter.active')
  if (active) {
    const ymd = active.getAttribute('data-date-iso')
    applyDateFilter(ymd)
  }

  // Re-apply when league filter changes
  document.addEventListener('pandascore:league-filter-changed', () => {
    const current = document.querySelector('.pandascore-date-filter.active')
    if (current) applyDateFilter(current.getAttribute('data-date-iso'))
  })

  // Enable drag-to-scroll on the date filters container (desktop + touch)
  const dateStrips = Array.from(
    document.querySelectorAll('.pandascore-date-filters')
  )

  dateStrips.forEach((container) => {
    let isDown = false
    let startX = 0
    let startScrollLeft = 0
    let dragged = false

    const onPointerDown = (e) => {
      // For mouse: only left button; allow touch/pen
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

    // Prevent accidental click on a date after dragging
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

