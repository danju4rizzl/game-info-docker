document.addEventListener('DOMContentLoaded', function () {
    // Select all upcoming match containers
    const matchElements = document.querySelectorAll('.pandascore-match[data-scheduled-at]');

    matchElements.forEach(match => {
        const scheduledAt = match.getAttribute('data-scheduled-at');
        if (scheduledAt) {
            const utcDate = new Date(scheduledAt);
            const localTime = utcDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
            const localDay = getLocalDayDisplay(utcDate);

            const timeElement = match.querySelector('.pandascore-time');
            const dayElement = match.querySelector('.pandascore-time-day');

            if (timeElement && dayElement) {
                timeElement.textContent = localTime;
                dayElement.textContent = localDay;
            }
        }
    });

    function getLocalDayDisplay(date) {
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        const matchDate = new Date(date);
        matchDate.setHours(0, 0, 0, 0);

        if (matchDate.getTime() === today.getTime()) {
            return 'Today';
        } else {
            return matchDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    }
});