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
      container.classList.add('is-dragging')
      if (container.setPointerCapture) {
        try {
          container.setPointerCapture(e.pointerId)
        } catch (_) {}
      }
      e.preventDefault()
    }

    const onPointerMove = (e) => {
      if (!isDown) return
      const dx = e.clientX - startX
      if (Math.abs(dx) > 3) dragged = true
      container.scrollLeft = startScrollLeft - dx
    }

    const endDrag = (e) => {
      if (!isDown) return
      isDown = false
      container.classList.remove('is-dragging')
      if (container.releasePointerCapture) {
        try {
          container.releasePointerCapture(e.pointerId)
        } catch (_) {}
      }
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

