document.addEventListener('DOMContentLoaded', function () {
    const formatTime = date => date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
    
    const getLocalDayDisplay = date => {
        const today = new Date().setHours(0, 0, 0, 0);
        const matchDate = new Date(date).setHours(0, 0, 0, 0);
        return matchDate === today ? 'Today' : new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    const getOrdinalDate = date => {
        const day = date.getDate();
        const v = day % 100;
        const suffix = ['th', 'st', 'nd', 'rd'][(v - 20) % 10] || ['th', 'st', 'nd', 'rd'][v] || 'th';
        return `${day}${suffix} of ${date.toLocaleDateString('en-US', { month: 'long' })} ${date.getFullYear()}`;
    };

    document.querySelectorAll('.pandascore-match[data-scheduled-at]').forEach(match => {
        const utcDate = new Date(match.getAttribute('data-scheduled-at'));
        const timeElement = match.querySelector('.pandascore-time');
        const dayElement = match.querySelector('.pandascore-time-day');
        
        if (timeElement && dayElement) {
            timeElement.textContent = formatTime(utcDate);
            dayElement.textContent = getLocalDayDisplay(utcDate);
        }
    });

    const matchHeader = document.querySelector('.match-header[data-scheduled-at]');
    if (matchHeader) {
        const utcDate = new Date(matchHeader.getAttribute('data-scheduled-at'));
        const timeElement = matchHeader.querySelector('.match-time');
        if (timeElement) {
            timeElement.textContent = `${formatTime(utcDate)} - ${getOrdinalDate(utcDate)}`;
        }
    }
});