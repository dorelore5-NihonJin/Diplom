// Reviews page interactivity
let pendingReviewDeleteId = null;

function handleReviewDeleteConfirm() {
    if (!pendingReviewDeleteId) return;
    setReviewDeleteLoading(true);
    fetch('api/delete_review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ review_id: pendingReviewDeleteId })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeReviewDeleteModal();
                location.reload();
            } else {
                setReviewDeleteLoading(false);
                showNotification(data.error || 'Ошибка удаления', 'error');
            }
        })
        .catch(() => {
            setReviewDeleteLoading(false);
            showNotification('Ошибка удаления', 'error');
        });
}

function openReviewDeleteModal() {
    const modal = document.getElementById('deleteReviewModal');
    if (modal) {
        modal.classList.add('is-visible');
    }
}

function closeReviewDeleteModal() {
    const modal = document.getElementById('deleteReviewModal');
    if (modal) {
        modal.classList.remove('is-visible');
    }
    pendingReviewDeleteId = null;
    setReviewDeleteLoading(false);
}

function setReviewDeleteLoading(state) {
    const btn = document.getElementById('confirmReviewDelete');
    if (!btn) return;
    if (state) {
        btn.classList.add('loading');
        btn.setAttribute('disabled', 'disabled');
    } else {
        btn.classList.remove('loading');
        btn.removeAttribute('disabled');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Rating slider live update
    const ratingInput = document.getElementById('ratingInput');
    const ratingValue = document.getElementById('ratingValue');
    
    if (ratingInput && ratingValue) {
        ratingInput.addEventListener('input', function() {
            ratingValue.textContent = this.value;
            
            // Add visual feedback
            const percentage = ((this.value - 1) / 4) * 100;
            this.style.background = `linear-gradient(to right, rgba(79, 155, 255, 0.3) 0%, rgba(143, 91, 255, 0.3) ${percentage}%, rgba(255, 255, 255, 0.1) ${percentage}%, rgba(255, 255, 255, 0.1) 100%)`;
        });

        // Initialize slider background
        ratingInput.dispatchEvent(new Event('input'));
    }
    
    // Component category filter
    const categorySelect = document.getElementById('componentCategory');
    const componentSelect = document.getElementById('componentSelect');
    
    if (categorySelect && componentSelect) {
        const allOptions = Array.from(componentSelect.querySelectorAll('option[data-category]'));
        
        categorySelect.addEventListener('change', function() {
            const selectedCategory = this.value;
            
            // Reset component select
            componentSelect.value = '';
            
            // Hide all options first
            allOptions.forEach(option => {
                option.style.display = 'none';
            });
            
            // Show only matching category options
            if (selectedCategory) {
                const matchingOptions = allOptions.filter(opt => opt.dataset.category === selectedCategory);
                matchingOptions.forEach(option => {
                    option.style.display = 'block';
                });
                
                // Show placeholder if no matches
                if (matchingOptions.length === 0) {
                    componentSelect.innerHTML = '<option value="">Нет компонентов в этой категории</option>';
                }
            } else {
                // Show all if no category selected
                allOptions.forEach(option => {
                    option.style.display = 'block';
                });
            }
        });
        
        // Initialize on page load
        categorySelect.dispatchEvent(new Event('change'));
    }
    
    // Category chips navigation
    const categoryChips = document.querySelectorAll('.chip[data-category-link]');
    
    categoryChips.forEach(chip => {
        chip.addEventListener('click', function() {
            const categoryId = this.dataset.categoryLink;
            window.location.href = `reviews.php?category=${categoryId}`;
        });
    });
    
    // Smooth scroll for CTA buttons
    const ctaButtons = document.querySelectorAll('a[href^="#"]');
    
    ctaButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId && targetId !== '#') {
                e.preventDefault();
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Form validation enhancement
    const reviewForm = document.querySelector('.review-form');
    
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            const title = reviewForm.querySelector('[name="title"]');
            const summary = reviewForm.querySelector('[name="summary"]');
            
            let isValid = true;
            let errorMessage = '';
            
            if (title && title.value.trim().length < 6) {
                isValid = false;
                errorMessage = 'Заголовок должен содержать минимум 6 символов.';
                title.focus();
            }
            
            if (summary && summary.value.trim().length < 40) {
                isValid = false;
                errorMessage = 'Основной текст должен содержать минимум 40 символов.';
                summary.focus();
            }
            
            if (!isValid) {
                e.preventDefault();
                showNotification(errorMessage, 'error');
            }
        });
        
        // Character counter for summary
        const summaryField = reviewForm.querySelector('[name="summary"]');
        if (summaryField) {
            const counterDiv = document.createElement('div');
            counterDiv.className = 'char-counter';
            counterDiv.style.cssText = 'font-size: 12px; color: var(--reviews-muted); margin-top: 4px; text-align: right;';
            summaryField.parentElement.appendChild(counterDiv);
            
            function updateCounter() {
                const length = summaryField.value.length;
                const minLength = 40;
                const remaining = Math.max(0, minLength - length);
                
                if (length < minLength) {
                    counterDiv.textContent = `Еще ${remaining} символов до минимума`;
                    counterDiv.style.color = 'var(--reviews-muted)';
                } else {
                    counterDiv.textContent = `${length} символов`;
                    counterDiv.style.color = 'var(--reviews-accent)';
                }
            }
            
            summaryField.addEventListener('input', updateCounter);
            updateCounter();
        }
    }
    
    // Review cards hover effect enhancement
    const reviewCards = document.querySelectorAll('.review-card');
    
    reviewCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.zIndex = '10';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.zIndex = '1';
        });
    });
    
    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 16px 20px;
            background: ${type === 'error' ? 'rgba(248, 113, 113, 0.95)' : 'rgba(79, 155, 255, 0.95)'};
            color: white;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
            max-width: 350px;
            font-size: 14px;
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
    
    // Delete review buttons
    const deleteButtons = document.querySelectorAll('.review-delete-btn');
    const deleteModal = document.getElementById('deleteReviewModal');
    const confirmBtn = document.getElementById('confirmReviewDelete');
    const messageEl = document.getElementById('deleteReviewMessage');

    if (deleteButtons.length && deleteModal && confirmBtn) {
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                pendingReviewDeleteId = btn.dataset.reviewId;
                const title = btn.dataset.reviewTitle;
                if (messageEl && title) {
                    messageEl.innerHTML = `Вы уверены, что хотите удалить обзор <strong>${title}</strong>?`;
                }
                openReviewDeleteModal();
            });
        });

        confirmBtn.addEventListener('click', handleReviewDeleteConfirm);
    }

    // Lazy load review cards on scroll
    const observerOptions = {
        root: null,
        rootMargin: '50px',
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    reviewCards.forEach((card, index) => {
        if (index > 3) { // Only lazy load cards after the first 4
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            observer.observe(card);
        }
    });
});
