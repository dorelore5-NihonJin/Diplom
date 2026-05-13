document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('replyMessage');
    const sendBtn = document.getElementById('sendReplyBtn');
    const errorBox = document.getElementById('replyError');
    const chatThread = document.getElementById('chatThread');

    if (!textarea || !sendBtn || !chatThread) {
        return;
    }

    const showError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.toggle('visible', Boolean(message));
    };

    const clearError = () => showError('');

    const formatTimestampLocal = (timestamp) => {
        if (!timestamp) return '';
        // Use global formatUserTime function from timezone.js
        if (typeof window.formatUserTime === 'function') {
            return window.formatUserTime(timestamp, 'full');
        }
        // Fallback if timezone.js not loaded
        try {
            const utcDate = new Date(timestamp.replace(' ', 'T') + 'Z');
            return utcDate.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return timestamp;
        }
    };

    const appendMessage = (data, { scroll = true } = {}) => {
        const timestamp = data.created_at || new Date().toISOString().slice(0, 19).replace('T', ' ');
        const messageId = data.id || `msg-${timestamp}-${data.message.substring(0, 20)}`;
        
        // Check if message already exists
        const existingMessage = chatThread.querySelector(`[data-message-id="${messageId}"]`);
        if (existingMessage) {
            return; // Skip duplicate
        }

        const emptyState = chatThread.querySelector('.chat-empty');
        if (emptyState) {
            emptyState.remove();
        }
        
        const wrapper = document.createElement('div');
        wrapper.className = `chat-message ${data.is_staff ? 'support' : 'user'}`;
        wrapper.dataset.timestamp = timestamp;
        wrapper.dataset.messageId = messageId;
        const formatted = data.formatted_time || formatTimestampLocal(timestamp);
        
        const avatarHtml = data.is_staff 
            ? '<i class="fas fa-headset"></i>'
            : (data.avatar 
                ? `<img src="${escapeAttr(data.avatar)}" alt="Avatar">`
                : escapeHtml((data.username || 'U').charAt(0).toUpperCase()));
        
        const username = data.is_staff ? 'Техподдержка' : (data.username || 'Пользователь');
        
        wrapper.innerHTML = `
            <div class="chat-avatar">${avatarHtml}</div>
            <div class="chat-bubble">
                <div class="chat-bubble-header">
                    <span class="chat-sender">${escapeHtml(username)}</span>
                    <span class="chat-time" data-utc-time="${escapeAttr(timestamp)}">${escapeHtml(formatted)}</span>
                </div>
                <div class="chat-text">${formatMessage(data.message)}</div>
            </div>
        `;
        chatThread.appendChild(wrapper);
        if (scroll) {
            chatThread.scrollTo({ top: chatThread.scrollHeight, behavior: 'smooth' });
        }
    };

    const escapeHtml = (text = '') => {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, (m) => map[m]);
    };

    const escapeAttr = (value = '') => {
        return String(value).replace(/"/g, '&quot;');
    };

    const formatMessage = (text = '') => {
        return escapeHtml(text).replace(/\n/g, '<br>');
    };

    const sendBtnDefault = sendBtn.innerHTML;
    const setLoading = (state) => {
        sendBtn.disabled = state;
        if (state) {
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Отправка...</span>';
        } else {
            sendBtn.innerHTML = sendBtnDefault;
        }
    };

    const SEND_URL = 'api/ticket_reply.php';

    const sendMessage = async () => {
        const message = textarea.value.trim();
        if (!message) {
            showError('Введите сообщение');
            return;
        }
        clearError();
        setLoading(true);
        textarea.disabled = true;
        
        // Check if replying as staff
        const asStaffCheckbox = document.getElementById('replyAsStaff');
        const asStaff = asStaffCheckbox ? asStaffCheckbox.checked : false;
        
        try {
            const response = await fetch(SEND_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    message,
                    csrf_token: csrfToken,
                    as_staff: asStaff
                })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Не удалось отправить сообщение');
            }
            const separators = chatThread.querySelectorAll('.chat-date-separator');
            let hasTodaySeparator = false;
            separators.forEach((sep) => {
                if (sep.textContent?.includes('Сегодня')) {
                    hasTodaySeparator = true;
                }
            });
            if (!hasTodaySeparator) {
                const separator = document.createElement('div');
                separator.className = 'chat-date-separator';
                separator.innerHTML = '<span>Сегодня</span>';
                chatThread.appendChild(separator);
            }

            appendMessage(data.data);
            lastMessageTimestamp = data.data.created_at;
            textarea.value = '';
            textarea.style.height = '';
        } catch (error) {
            showError(error.message || 'Ошибка отправки. Попробуйте позже');
        } finally {
            setLoading(false);
            textarea.disabled = false;
        }
    };

    sendBtn.addEventListener('click', sendMessage);
    textarea.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });
    textarea.addEventListener('input', () => {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    });

    const hydrateExistingMessages = () => {
        chatThread.querySelectorAll('.chat-time[data-utc-time]').forEach((timeEl) => {
            const timestamp = timeEl.getAttribute('data-utc-time');
            if (timestamp) {
                timeEl.textContent = formatTimestampLocal(timestamp);
            }
        });
        chatThread.scrollTop = chatThread.scrollHeight;
    };

    hydrateExistingMessages();

    let lastMessageTimestamp = chatThread.lastElementChild
        ? chatThread.lastElementChild.dataset.timestamp || null
        : null;

    const fetchNewReplies = async () => {
        try {
            const sinceParam = lastMessageTimestamp ? encodeURIComponent(lastMessageTimestamp) : '';
            const response = await fetch(`api/ticket_updates.php?ticket_id=${ticketId}&since=${sinceParam}`);
            if (!response.ok) return;
            const data = await response.json();
            if (!data.success || !Array.isArray(data.messages) || !data.messages.length) return;
            data.messages.forEach((msg) => appendMessage(msg, { scroll: true }));
            lastMessageTimestamp = data.latest_timestamp || lastMessageTimestamp;
        } catch (err) {
            console.error('Ticket polling error:', err);
        }
    };

    setInterval(fetchNewReplies, 3000);
});
