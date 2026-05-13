// Create review page interactivity
document.addEventListener('DOMContentLoaded', function() {
    // Rating slider with visual feedback
    const ratingInput = document.getElementById('ratingInput');
    const ratingValue = document.getElementById('ratingValue');
    const ratingStars = document.getElementById('ratingStars');
    
    function updateRating(value) {
        ratingValue.textContent = value;
        
        // Update stars
        const stars = ratingStars.querySelectorAll('i');
        stars.forEach((star, index) => {
            if (index < value) {
                star.classList.remove('inactive');
            } else {
                star.classList.add('inactive');
            }
        });
        
        // Update slider background
        const percentage = ((value - 1) / 4) * 100;
        ratingInput.style.background = `linear-gradient(to right, rgba(79, 155, 255, 0.4) 0%, rgba(143, 91, 255, 0.4) ${percentage}%, rgba(255, 255, 255, 0.1) ${percentage}%, rgba(255, 255, 255, 0.1) 100%)`;
    }
    
    if (ratingInput) {
        ratingInput.addEventListener('input', function() {
            updateRating(this.value);
        });
        
        // Initialize
        updateRating(ratingInput.value);
    }
    
    // Component category filter
    const categorySelect = document.getElementById('componentCategory');
    const componentSelect = document.getElementById('componentSelect');
    
    if (categorySelect && componentSelect) {
        const allOptions = Array.from(componentSelect.querySelectorAll('option[data-category]'));
        const placeholder = componentSelect.querySelector('option[value=""]');
        
        function filterComponents() {
            const selectedCategory = categorySelect.value;
            
            // Reset component select
            componentSelect.value = '';
            
            let visibleCount = 0;
            
            if (selectedCategory) {
                placeholder.textContent = 'Выберите модель';
                
                // Show/hide options based on category
                allOptions.forEach(option => {
                    if (option.dataset.category === selectedCategory) {
                        option.style.display = '';
                        visibleCount++;
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                if (visibleCount === 0) {
                    placeholder.textContent = 'Нет компонентов в этой категории';
                }
            } else {
                placeholder.textContent = 'Сначала выберите категорию';
                // Hide all options when no category selected
                allOptions.forEach(option => {
                    option.style.display = 'none';
                });
            }
        }
        
        categorySelect.addEventListener('change', filterComponents);
        
        // Initialize
        filterComponents();
    }
    
    // Form validation with real-time feedback
    const reviewForm = document.getElementById('reviewForm');
    
    if (reviewForm) {
        const titleInput = reviewForm.querySelector('[name="title"]');
        const summaryInput = reviewForm.querySelector('[name="summary"]');
        
        // Character counter for summary
        if (summaryInput) {
            const counterDiv = document.createElement('div');
            counterDiv.className = 'char-counter';
            counterDiv.style.cssText = `
                font-size: 13px;
                color: var(--create-review-muted);
                margin-top: 8px;
                text-align: right;
                font-weight: 500;
            `;
            summaryInput.parentElement.appendChild(counterDiv);
            
            function updateSummaryCounter() {
                const length = summaryInput.value.length;
                const minLength = 40;
                
                if (length < minLength) {
                    const remaining = minLength - length;
                    counterDiv.textContent = `Еще ${remaining} символов до минимума`;
                    counterDiv.style.color = 'var(--create-review-muted)';
                } else {
                    counterDiv.textContent = `${length} символов ✓`;
                    counterDiv.style.color = 'var(--create-review-success)';
                }
            }
            
            summaryInput.addEventListener('input', updateSummaryCounter);
            updateSummaryCounter();
        }
        
        // Title character counter
        if (titleInput) {
            const titleCounter = document.createElement('div');
            titleCounter.className = 'char-counter';
            titleCounter.style.cssText = `
                font-size: 13px;
                color: var(--create-review-muted);
                margin-top: 8px;
                text-align: right;
                font-weight: 500;
            `;
            titleInput.parentElement.appendChild(titleCounter);
            
            function updateTitleCounter() {
                const length = titleInput.value.length;
                const maxLength = 160;
                const minLength = 6;
                
                if (length < minLength) {
                    titleCounter.textContent = `Минимум ${minLength} символов`;
                    titleCounter.style.color = 'var(--create-review-muted)';
                } else {
                    titleCounter.textContent = `${length}/${maxLength}`;
                    titleCounter.style.color = 'var(--create-review-success)';
                }
            }
            
            titleInput.addEventListener('input', updateTitleCounter);
            updateTitleCounter();
        }
        
        // Form submission validation
        reviewForm.addEventListener('submit', function(e) {
            let isValid = true;
            let errorMessages = [];
            
            // Validate category
            if (!categorySelect.value) {
                isValid = false;
                errorMessages.push('Выберите категорию компонента');
                categorySelect.focus();
            }
            
            // Validate component
            if (!componentSelect.value) {
                isValid = false;
                errorMessages.push('Выберите конкретный компонент');
                if (isValid) componentSelect.focus();
            }
            
            // Validate title
            if (titleInput && titleInput.value.trim().length < 6) {
                isValid = false;
                errorMessages.push('Заголовок должен содержать минимум 6 символов');
                if (errorMessages.length === 1) titleInput.focus();
            }
            
            // Validate summary
            if (summaryInput && summaryInput.value.trim().length < 40) {
                isValid = false;
                errorMessages.push('Детальный обзор должен содержать минимум 40 символов');
                if (errorMessages.length === 1) summaryInput.focus();
            }
            
            if (!isValid) {
                e.preventDefault();
                showNotification(errorMessages.join('<br>'), 'error');
            } else {
                // Show loading state
                const submitBtn = reviewForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
                }
            }
        });
    }
    
    // Smooth scroll for back link
    const backLink = document.querySelector('.back-link');
    if (backLink) {
        backLink.addEventListener('click', function(e) {
            // Add smooth transition effect
            document.body.style.opacity = '0.8';
            setTimeout(() => {
                window.location.href = this.href;
            }, 150);
        });
    }
    
    // Auto-save draft to localStorage
    const formFields = reviewForm ? reviewForm.querySelectorAll('input, select, textarea') : [];
    const DRAFT_KEY = 'review_draft';
    
    function saveDraft() {
        const draft = {};
        formFields.forEach(field => {
            if (field.name && field.name !== 'csrf_token' && field.name !== 'review_action') {
                if (field.type === 'checkbox') {
                    draft[field.name] = field.checked;
                } else {
                    draft[field.name] = field.value;
                }
            }
        });
        localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    }
    
    function loadDraft() {
        try {
            const draft = JSON.parse(localStorage.getItem(DRAFT_KEY));
            if (draft) {
                formFields.forEach(field => {
                    if (field.name && draft.hasOwnProperty(field.name)) {
                        if (field.type === 'checkbox') {
                            field.checked = draft[field.name];
                        } else {
                            field.value = draft[field.name];
                        }
                    }
                });
                
                // Trigger events to update UI
                if (categorySelect) categorySelect.dispatchEvent(new Event('change'));
                if (ratingInput) updateRating(ratingInput.value);
                if (summaryInput) summaryInput.dispatchEvent(new Event('input'));
                if (titleInput) titleInput.dispatchEvent(new Event('input'));
                
                showNotification('Черновик восстановлен', 'info');
            }
        } catch (e) {
            console.error('Failed to load draft:', e);
        }
    }
    
    // Auto-save every 30 seconds
    if (reviewForm) {
        let autoSaveInterval = setInterval(saveDraft, 30000);
        
        // Save on form change
        formFields.forEach(field => {
            field.addEventListener('change', saveDraft);
        });
        
        // Clear draft on successful submission
        reviewForm.addEventListener('submit', function() {
            localStorage.removeItem(DRAFT_KEY);
            clearInterval(autoSaveInterval);
        });
        
        const draftExists = localStorage.getItem(DRAFT_KEY);
        const draftModal = document.getElementById('draftModal');
        const draftModalConfirm = document.getElementById('draftModalConfirm');
        const draftModalDismiss = document.getElementById('draftModalDismiss');
        const draftModalClose = document.getElementById('draftModalClose');

        function openDraftModal() {
            if (!draftModal) return;
            draftModal.classList.add('is-visible');
        }

        function closeDraftModal() {
            if (!draftModal) return;
            draftModal.classList.remove('is-visible');
        }

        if (draftExists && !reviewForm.querySelector('[name="title"]').value && draftModal) {
            setTimeout(openDraftModal, 400);

            draftModalConfirm?.addEventListener('click', () => {
                closeDraftModal();
                loadDraft();
            });

            draftModalDismiss?.addEventListener('click', () => {
                closeDraftModal();
                // Don't delete draft, just close modal
            });
            
            draftModalClose?.addEventListener('click', () => {
                closeDraftModal();
                // Don't delete draft, just close modal
            });

            draftModal.addEventListener('click', (e) => {
                if (e.target === draftModal) {
                    closeDraftModal();
                    // Don't delete draft on backdrop click
                }
            });
            
            // Add explicit delete draft button functionality
            const deleteDraftBtn = document.createElement('button');
            deleteDraftBtn.className = 'btn btn-ghost btn-delete-draft';
            deleteDraftBtn.type = 'button';
            deleteDraftBtn.innerHTML = '<i class="fas fa-trash"></i> Удалить черновик';
            deleteDraftBtn.style.cssText = 'margin-top: 12px; font-size: 13px; padding: 8px 16px; opacity: 0.7;';
            deleteDraftBtn.addEventListener('click', () => {
                if (confirm('Вы уверены, что хотите удалить черновик?')) {
                    localStorage.removeItem(DRAFT_KEY);
                    closeDraftModal();
                    showNotification('Черновик удалён', 'info');
                }
            });
            
            const modalActions = draftModal.querySelector('.draft-modal__actions');
            if (modalActions) {
                modalActions.appendChild(deleteDraftBtn);
            }
        }
    }
    
    // Bullet behavior for pros/cons
    const bulletAreas = document.querySelectorAll('.bullet-textarea');
    bulletAreas.forEach(area => {
        area.addEventListener('focus', () => {
            if (!area.value.trim()) {
                area.value = '• ';
                area.setSelectionRange(area.value.length, area.value.length);
            }
        });
        
        area.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                const start = area.selectionStart;
                const end = area.selectionEnd;
                area.setRangeText('\n• ', start, end, 'end');
            } else if (event.key === 'Backspace') {
                const { selectionStart, selectionEnd, value } = area;
                if (selectionStart === selectionEnd) {
                    const beforeCursor = value.slice(0, selectionStart);
                    if (beforeCursor.endsWith('• ')) {
                        event.preventDefault();
                        const newStart = Math.max(0, selectionStart - 2);
                        area.setRangeText('', newStart, selectionStart, 'end');
                    }
                }
            }
        });
    });
    
    // Notification system
    function showNotification(message, type) {
        const notification = document.createElement('div');
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
            font-size: 14px;
            line-height: 1.6;
            backdrop-filter: blur(10px);
        `;
        notification.innerHTML = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
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
});
