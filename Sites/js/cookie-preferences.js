// Cookie Preferences Management

// Load saved preferences on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCookiePreferences();
});

function loadCookiePreferences() {
    const consent = localStorage.getItem('cookieConsent');
    
    if (consent) {
        try {
            const consentData = JSON.parse(consent);
            const preferences = consentData.preferences || {};
            
            // Set toggle states based on saved preferences
            const functionalCheckbox = document.getElementById('functionalCookies');
            const analyticsCheckbox = document.getElementById('analyticsCookies');
            const marketingCheckbox = document.getElementById('marketingCookies');
            
            if (functionalCheckbox) {
                functionalCheckbox.checked = preferences.functional !== false; // default true
            }
            if (analyticsCheckbox) {
                analyticsCheckbox.checked = preferences.analytics === true;
            }
            if (marketingCheckbox) {
                marketingCheckbox.checked = preferences.marketing === true;
            }
        } catch (e) {
            console.error('Error loading cookie preferences:', e);
        }
    }
}

function updateCookiePreferences() {
    // This function is called when toggles are changed
    // We don't auto-save, user must click "Save Settings"
    console.log('Cookie preferences updated (not saved yet)');
}

function getCurrentPreferences() {
    const functionalCheckbox = document.getElementById('functionalCookies');
    const analyticsCheckbox = document.getElementById('analyticsCookies');
    const marketingCheckbox = document.getElementById('marketingCookies');
    
    return {
        functional: functionalCheckbox ? functionalCheckbox.checked : true,
        analytics: analyticsCheckbox ? analyticsCheckbox.checked : false,
        marketing: marketingCheckbox ? marketingCheckbox.checked : false
    };
}

function saveCookiePreferences() {
    const preferences = getCurrentPreferences();
    
    const consentData = {
        accepted: preferences.functional || preferences.analytics || preferences.marketing,
        preferences: preferences,
        timestamp: new Date().toISOString(),
        version: '1.0'
    };
    
    localStorage.setItem('cookieConsent', JSON.stringify(consentData));
    
    // Apply preferences
    applyCookiePreferences(preferences);
    
    // Show success notification
    showNotification('Настройки cookie сохранены', 'success');
}

function acceptAllCookies() {
    const functionalCheckbox = document.getElementById('functionalCookies');
    const analyticsCheckbox = document.getElementById('analyticsCookies');
    const marketingCheckbox = document.getElementById('marketingCookies');
    
    if (functionalCheckbox) functionalCheckbox.checked = true;
    if (analyticsCheckbox) analyticsCheckbox.checked = true;
    if (marketingCheckbox) marketingCheckbox.checked = true;
    
    saveCookiePreferences();
}

function declineAllCookies() {
    const functionalCheckbox = document.getElementById('functionalCookies');
    const analyticsCheckbox = document.getElementById('analyticsCookies');
    const marketingCheckbox = document.getElementById('marketingCookies');
    
    // Keep functional cookies enabled by default
    if (functionalCheckbox) functionalCheckbox.checked = true;
    if (analyticsCheckbox) analyticsCheckbox.checked = false;
    if (marketingCheckbox) marketingCheckbox.checked = false;
    
    saveCookiePreferences();
}

function applyCookiePreferences(preferences) {
    // Apply analytics cookies
    if (preferences.analytics) {
        enableAnalytics();
    } else {
        disableAnalytics();
    }
    
    // Apply marketing cookies
    if (preferences.marketing) {
        enableMarketing();
    } else {
        disableMarketing();
    }
    
    console.log('Cookie preferences applied:', preferences);
}

function enableAnalytics() {
    // Example: Enable Google Analytics
    // if (typeof gtag !== 'undefined') {
    //     gtag('consent', 'update', {
    //         'analytics_storage': 'granted'
    //     });
    // }
    
    console.log('Analytics cookies enabled');
}

function disableAnalytics() {
    // Example: Disable Google Analytics
    // if (typeof gtag !== 'undefined') {
    //     gtag('consent', 'update', {
    //         'analytics_storage': 'denied'
    //     });
    // }
    
    // Remove analytics cookies
    deleteCookie('_ga');
    deleteCookie('_gid');
    deleteCookie('_gat');
    
    console.log('Analytics cookies disabled');
}

function enableMarketing() {
    // Example: Enable Facebook Pixel
    // if (typeof fbq !== 'undefined') {
    //     fbq('consent', 'grant');
    // }
    
    console.log('Marketing cookies enabled');
}

function disableMarketing() {
    // Example: Disable Facebook Pixel
    // if (typeof fbq !== 'undefined') {
    //     fbq('consent', 'revoke');
    // }
    
    // Remove marketing cookies
    deleteCookie('_fbp');
    deleteCookie('_fbc');
    
    console.log('Marketing cookies disabled');
}

function deleteCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname + ';';
}

function showNotification(message, type = 'info') {
    // Check if showToast function exists (from main.js)
    if (typeof showToast === 'function') {
        showToast(message, type);
    } else {
        // Fallback to simple alert
        alert(message);
    }
}

// Export functions for use in cookie banner
window.cookiePreferences = {
    load: loadCookiePreferences,
    save: saveCookiePreferences,
    acceptAll: acceptAllCookies,
    declineAll: declineAllCookies,
    apply: applyCookiePreferences
};
