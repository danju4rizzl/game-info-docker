document.addEventListener('DOMContentLoaded', function () {
  /**
   * Match Details Navigation Handler
   * Handles click events on match cards to navigate to match details page
   */

  // Add click handlers to all match cards
  function initializeMatchCardClicks() {
    const matchCards = document.querySelectorAll('.pandascore-match')
    
    matchCards.forEach(card => {
      const matchId = card.getAttribute('data-match-id')
      if (matchId) {
        card.style.cursor = 'pointer'
        card.addEventListener('click', function(e) {
          e.preventDefault()
          navigateToMatchDetails(matchId)
        })
      }
    })
  }

  // Navigate to match details page
  function navigateToMatchDetails(matchId) {
    const currentUrl = new URL(window.location)
    currentUrl.searchParams.set('match_id', matchId)
    currentUrl.searchParams.set('view', 'match-details')
    
    window.location.href = currentUrl.toString()
  }

  // Initialize when DOM is ready
  initializeMatchCardClicks()
})