<!-- Cookie Consent Banner -->
<div class="cookie-banner" id="cookieBanner" aria-live="polite" role="dialog" aria-labelledby="cookieBannerTitle">
    <div class="cookie-banner-content">
        <div class="cookie-banner-icon">
            <i class="fas fa-cookie-bite"></i>
        </div>
        <div class="cookie-banner-text">
            <h4 id="cookieBannerTitle">Мы используем файлы cookie</h4>
            <p>
                Мы используем обязательные cookie для работы сайта и дополнительные для улучшения вашего опыта. 
                Вы можете принять все или отклонить необязательные cookie.
                <a href="cookie-policy.php" class="cookie-link">Подробнее о cookie</a>
            </p>
        </div>
        <div class="cookie-banner-actions">
            <button type="button" class="btn-cookie btn-cookie-decline" onclick="handleCookieConsent(false)">
                <i class="fas fa-times-circle"></i>
                Отклонить
            </button>
            <button type="button" class="btn-cookie btn-cookie-accept" onclick="handleCookieConsent(true)">
                <i class="fas fa-check-circle"></i>
                Принять все
            </button>
        </div>
    </div>
</div>

<style>
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--card-bg);
    border-top: 2px solid var(--border);
    box-shadow: 0 -8px 32px rgba(15, 23, 42, 0.2);
    z-index: 9999;
    transform: translateY(100%);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease;
    backdrop-filter: blur(10px);
}

.cookie-banner.show {
    transform: translateY(0);
    opacity: 1;
}

.cookie-banner-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.cookie-banner-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(251, 191, 36, 0.15));
    color: #f59e0b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.cookie-banner-text {
    flex: 1;
    min-width: 280px;
}

.cookie-banner-text h4 {
    margin: 0 0 6px;
    font-size: 16px;
    font-weight: 700;
    color: var(--text);
}

.cookie-banner-text p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: var(--text-secondary);
}

.cookie-link {
    color: var(--primary);
    text-decoration: underline;
    font-weight: 600;
    transition: color 0.2s ease;
}

.cookie-link:hover {
    color: var(--secondary);
}

.cookie-banner-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-cookie {
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    white-space: nowrap;
}

.btn-cookie-decline {
    background: var(--bg-secondary);
    color: var(--text);
    border: 2px solid var(--border);
}

.btn-cookie-decline:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239, 68, 68, 0.08);
}

.btn-cookie-accept {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
}

.btn-cookie-accept:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(59, 130, 246, 0.4);
}

@media (max-width: 768px) {
    .cookie-banner-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }

    .cookie-banner-actions {
        width: 100%;
    }

    .btn-cookie {
        flex: 1;
        justify-content: center;
    }
}
</style>

<script>
function handleCookieConsent(accepted) {
    const banner = document.getElementById('cookieBanner');
    if (!banner) return;

    // Save consent to localStorage
    const consentData = {
        accepted: accepted,
        timestamp: new Date().toISOString(),
        version: '1.0'
    };
    localStorage.setItem('cookieConsent', JSON.stringify(consentData));

    // Hide banner with animation
    banner.classList.remove('show');
    setTimeout(() => {
        banner.style.display = 'none';
    }, 400);

    // If accepted, you can enable optional analytics/tracking here
    if (accepted) {
        console.log('Cookie consent accepted');
        // Example: Enable Google Analytics, Facebook Pixel, etc.
        // enableAnalytics();
    } else {
        console.log('Cookie consent declined');
    }
}

function checkCookieConsent() {
    const banner = document.getElementById('cookieBanner');
    if (!banner) return;

    const consent = localStorage.getItem('cookieConsent');
    
    if (!consent) {
        // First time visitor - show banner after short delay
        setTimeout(() => {
            banner.classList.add('show');
        }, 1000);
    } else {
        // Already responded - hide banner
        banner.style.display = 'none';
        
        // Optional: Check if consent is still valid (e.g., older than 6 months)
        try {
            const consentData = JSON.parse(consent);
            const consentDate = new Date(consentData.timestamp);
            const sixMonthsAgo = new Date();
            sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
            
            if (consentDate < sixMonthsAgo) {
                // Consent expired - ask again
                localStorage.removeItem('cookieConsent');
                setTimeout(() => {
                    banner.style.display = '';
                    banner.classList.add('show');
                }, 1000);
            }
        } catch (e) {
            console.error('Error parsing cookie consent:', e);
        }
    }
}

// Check consent on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkCookieConsent);
} else {
    checkCookieConsent();
}
</script>
