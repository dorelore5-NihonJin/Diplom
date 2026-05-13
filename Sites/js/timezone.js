/**
 * Timezone Detection and Management
 * Automatically detects user's timezone and sends it to the server
 */

(function() {
    'use strict';
    
    /**
     * Get user's timezone offset in minutes
     * Negative for west of UTC, positive for east
     */
    function getUserTimezoneOffset() {
        const offset = new Date().getTimezoneOffset();
        // JavaScript returns negative for east of UTC, we need to reverse it
        return -offset;
    }
    
    /**
     * Get user's timezone name (e.g., "Europe/Moscow", "America/New_York")
     */
    function getUserTimezoneName() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (e) {
            return 'Unknown';
        }
    }
    
    /**
     * Send timezone info to server
     */
    async function sendTimezoneToServer() {
        const offset = getUserTimezoneOffset();
        const timezone = getUserTimezoneName();
        
        // Store in localStorage for quick access
        localStorage.setItem('user_timezone_offset', offset);
        localStorage.setItem('user_timezone_name', timezone);
        
        try {
            const response = await fetch('api/set_timezone.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    offset: offset,
                    timezone: timezone
                })
            });
            
            if (!response.ok) {
                console.warn('Failed to set timezone on server');
            }
        } catch (error) {
            console.warn('Timezone sync error:', error);
        }
    }
    
    /**
     * Format UTC datetime to user's local time
     * @param {string} utcDatetime - datetime string in format "YYYY-MM-DD HH:MM:SS"
     * @param {string} format - output format ('full', 'date', 'time', 'relative')
     * @return {string}
     */
    window.formatUserTime = function(utcDatetime, format = 'full') {
        if (!utcDatetime) return '';
        
        try {
            // Parse UTC datetime
            const utcDate = new Date(utcDatetime.replace(' ', 'T') + 'Z');
            
            if (isNaN(utcDate.getTime())) {
                return utcDatetime;
            }
            
            switch (format) {
                case 'full':
                    return utcDate.toLocaleString('ru-RU', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                case 'date':
                    return utcDate.toLocaleDateString('ru-RU', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                    
                case 'time':
                    return utcDate.toLocaleTimeString('ru-RU', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                case 'relative':
                    return getRelativeTime(utcDate);
                    
                default:
                    return utcDate.toLocaleString('ru-RU');
            }
        } catch (e) {
            console.error('Time formatting error:', e);
            return utcDatetime;
        }
    };
    
    /**
     * Get relative time string (e.g., "5 минут назад")
     */
    function getRelativeTime(date) {
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // difference in seconds
        
        if (diff < 60) {
            return 'только что';
        } else if (diff < 3600) {
            const minutes = Math.floor(diff / 60);
            return `${minutes} ${pluralize(minutes, 'минуту', 'минуты', 'минут')} назад`;
        } else if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return `${hours} ${pluralize(hours, 'час', 'часа', 'часов')} назад`;
        } else if (diff < 604800) {
            const days = Math.floor(diff / 86400);
            return `${days} ${pluralize(days, 'день', 'дня', 'дней')} назад`;
        } else {
            return formatUserTime(date.toISOString(), 'full');
        }
    }
    
    /**
     * Russian pluralization
     */
    function pluralize(number, one, two, five) {
        number = Math.abs(number) % 100;
        const n1 = number % 10;
        
        if (number > 10 && number < 20) return five;
        if (n1 > 1 && n1 < 5) return two;
        if (n1 === 1) return one;
        return five;
    }
    
    /**
     * Convert all datetime elements on page to user's timezone
     */
    function convertPageDatetimes() {
        // Convert elements with data-utc-time attribute
        document.querySelectorAll('[data-utc-time]').forEach(element => {
            const utcTime = element.getAttribute('data-utc-time');
            const format = element.getAttribute('data-time-format') || 'full';
            element.textContent = formatUserTime(utcTime, format);
        });
    }
    
    /**
     * Initialize timezone detection
     */
    function init() {
        // Send timezone to server on page load
        sendTimezoneToServer();
        
        // Convert existing datetimes on page
        convertPageDatetimes();
        
        // Re-check timezone every 5 minutes (in case user travels or changes timezone)
        setInterval(sendTimezoneToServer, 5 * 60 * 1000);
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Expose global function for manual conversion
    window.TimezoneHelper = {
        getOffset: getUserTimezoneOffset,
        getName: getUserTimezoneName,
        format: formatUserTime,
        convert: convertPageDatetimes
    };
})();
