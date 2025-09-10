document.addEventListener('DOMContentLoaded', function () {
  const filters = document.querySelectorAll('.pandascore-league-filter')
  const matches = document.querySelectorAll('.pandascore-match')

  filters.forEach((filter) => {
    filter.addEventListener('click', () => {
      const leagueId = filter.getAttribute('data-league-id')

      // Toggle active state
      filters.forEach((f) => f.classList.remove('active'))
      filter.classList.add('active')

      matches.forEach((match) => {
        const matchLeague = match.querySelector(
          '.pandascore-league-container img'
        )
        if (matchLeague && matchLeague.alt === filter.title) {
          match.style.display = 'flex'
        } else {
          match.style.display = 'none'
        }
      })
    })
  })
})
