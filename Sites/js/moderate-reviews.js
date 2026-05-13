let pendingArchiveReviewId = null;

// Moderate reviews functionality
async function moderateReview(reviewId, newStatus) {

    const card = document.querySelector(`[data-review-id="${reviewId}"]`);
    if (card) {
        card.style.opacity = '0.6';
        card.style.pointerEvents = 'none';
    }

    try {
        const response = await fetch('api/moderate_review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                review_id: reviewId,
                status: newStatus
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Статус обзора успешно обновлён', 'success');
            
            // Reload list after short delay
            setTimeout(() => {
                applyFilters({ preservePage: true });
            }, 600);
        } else {
            showNotification(data.error || 'Ошибка при обновлении статуса', 'error');
            if (card) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
        }
    } catch (error) {
        console.error('Moderate review error:', error);
        showNotification('Ошибка соединения с сервером', 'error');
        if (card) {
            card.style.opacity = '1';
            card.style.pointerEvents = 'auto';
        }
    }
}

function openArchiveModal(reviewId) {
    pendingArchiveReviewId = reviewId;
    const modal = document.getElementById('archiveModal');
    if (!modal) return;
    modal.classList.add('is-visible');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeArchiveModal() {
    const modal = document.getElementById('archiveModal');
    if (!modal) return;
    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    pendingArchiveReviewId = null;
}

function confirmArchiveReview() {
    if (!pendingArchiveReviewId) return;
    const reviewId = pendingArchiveReviewId;
    closeArchiveModal();
    moderateReview(reviewId, 'archived');
}

function applyFilters(options = {}) {
    const form = document.querySelector('.filters-form');
    if (!form) return;

    const formData = new FormData(form);
    const params = new URLSearchParams(formData);

    if (!options.preservePage) {
        params.set('page', '1');
    }

    const url = new URL(window.location.href);
    url.search = params.toString();

    const ajaxParams = new URLSearchParams(params);
    ajaxParams.set('ajax', '1');

    fetch(`moderate_reviews.php?${ajaxParams.toString()}`)
        .then(res => res.json())
        .then(data => {
            const reviewsContainer = document.getElementById('reviewsContainer');
            const paginationContainer = document.getElementById('paginationContainer');
            if (reviewsContainer) reviewsContainer.innerHTML = data.reviews_html || '';
            if (paginationContainer) paginationContainer.innerHTML = data.pagination_html || '';

            const resetBtn = document.getElementById('filtersReset');
            if (resetBtn) {
                resetBtn.classList.toggle('is-hidden', !params.get('status') && !params.get('category'));
            }

            window.history.replaceState({}, '', url.toString());
        })
        .catch(() => {
            showNotification('Не удалось обновить список обзоров', 'error');
        });
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.filters-form');
    if (form) {
        form.addEventListener('change', () => applyFilters());
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            applyFilters();
        });
    }

    document.addEventListener('click', (event) => {
        const reset = event.target.closest('#filtersReset');
        if (reset) {
            event.preventDefault();
            const statusSelect = document.querySelector('select[name="status"]');
            const categorySelect = document.querySelector('select[name="category"]');
            if (statusSelect) statusSelect.value = '';
            if (categorySelect) categorySelect.value = '';
            applyFilters();
        }

        const paginationLink = event.target.closest('.pagination-btn');
        if (paginationLink) {
            event.preventDefault();
            const href = paginationLink.getAttribute('href');
            if (!href) return;
            const url = new URL(href, window.location.origin);
            const form = document.querySelector('.filters-form');
            if (form) {
                const statusSelect = form.querySelector('select[name="status"]');
                const categorySelect = form.querySelector('select[name="category"]');
                if (statusSelect) statusSelect.value = url.searchParams.get('status') || '';
                if (categorySelect) categorySelect.value = url.searchParams.get('category') || '';
            }
            applyFilters({ preservePage: true });
        }
    });
});

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const bgColor = type === 'error' ? 'rgba(248, 113, 113, 0.95)' : 
                    type === 'success' ? 'rgba(16, 185, 129, 0.95)' : 
                    'rgba(79, 155, 255, 0.95)';
    
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 18px 24px;
        background: ${bgColor};
        color: white;
        border-radius: 14px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
        max-width: 400px;
        font-size: 15px;
        font-weight: 500;
        backdrop-filter: blur(10px);
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
