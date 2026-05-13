let pageRoot = null;
let csrfToken = '';
let serverOffsetMinutes = 0;
let updatesIntervalId = null;
const threadStates = new Map();

const formatOffset = (minutes) => {
    const sign = minutes >= 0 ? '+' : '-';
    const abs = Math.abs(minutes);
    const hrs = String(Math.floor(abs / 60)).padStart(2, '0');
    const mins = String(abs % 60).padStart(2, '0');
    return `${sign}${hrs}:${mins}`;
};

const toIsoWithServerOffset = (timestamp) => {
    if (!timestamp) return null;
    const base = timestamp.replace(' ', 'T');
    return `${base}${formatOffset(serverOffsetMinutes)}`;
};

const formatTimestamp = (timestamp) => {
    const iso = toIsoWithServerOffset(timestamp);
    const date = iso ? new Date(iso) : new Date();
    if (Number.isNaN(date.getTime())) {
        return timestamp || new Date().toLocaleString('ru-RU');
    }
    return date.toLocaleString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const buildAvatar = (data) => {
    if (data.is_staff) {
        return '<i class="fas fa-headset"></i>';
    }
    if (data.avatar) {
        return `<img src="${escapeAttr(data.avatar)}" alt="Avatar">`;
    }
    const letter = escapeHtml((data.username || 'Пользователь').trim().charAt(0).toUpperCase());
    return letter || 'U';
};

const escapeHtml = (text = '') => {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, (m) => map[m]);
};

const escapeAttr = (value = '') => String(value).replace(/"/g, '&quot;');

const formatMessage = (text = '') => escapeHtml(text).replace(/\n/g, '<br>');

const appendMessage = (container, data) => {
    const wrapper = document.createElement('div');
    wrapper.className = `chat-message ${data.is_staff ? 'support' : 'user'}`;
    const timestamp = data.created_at || new Date().toISOString().slice(0, 19).replace('T', ' ');
    wrapper.dataset.timestamp = timestamp;
    wrapper.innerHTML = `
        <div class="chat-avatar">${buildAvatar(data)}</div>
        <div class="chat-bubble">
            <div class="chat-bubble-header">
                <span class="chat-sender">${escapeHtml(data.is_staff ? 'Техподдержка' : (data.username || 'Пользователь'))}</span>
                <span class="chat-time">${escapeHtml(formatTimestamp(data.created_at))}</span>
            </div>
            <div class="chat-text">${formatMessage(data.message)}</div>
        </div>
    `;
    const emptyState = container.querySelector('.chat-empty');
    if (emptyState) {
        emptyState.remove();
    }
    container.appendChild(wrapper);
    if (container.dataset.autoScroll !== 'false') {
        container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
    }
    return timestamp;
};

const getThreadState = (ticketId) => {
    if (threadStates.has(ticketId)) {
        return threadStates.get(ticketId);
    }
    const container = document.querySelector(`.chat-messages[data-ticket-id="${ticketId}"]`);
    const textarea = document.querySelector(`.chat-input[data-ticket-id="${ticketId}"]`);
    if (!container || !textarea) return null;
    const lastMessage = container.querySelector('.chat-message:last-child');
    const state = {
        container,
        textarea,
        lastTimestamp: lastMessage ? lastMessage.dataset.timestamp : null,
        open: false,
        loading: false,
        hasUnread: false
    };
    threadStates.set(ticketId, state);
    return state;
};

const syncExistingTimestamps = () => {
    threadStates.forEach((state) => {
        const lastMessage = state.container.querySelector('.chat-message:last-child');
        if (lastMessage) {
            state.lastTimestamp = lastMessage.dataset.timestamp;
        }
    });
};

const fetchUpdates = async (ticketId, state) => {
    if (!state || state.loading) return;
    state.loading = true;
    try {
        const since = state.lastTimestamp ? encodeURIComponent(state.lastTimestamp) : '';
        const response = await fetch(`api/ticket_updates.php?ticket_id=${ticketId}&since=${since}`);
        if (!response.ok) return;
        const data = await response.json();
        if (!data.success || !Array.isArray(data.messages) || !data.messages.length) return;
        data.messages.forEach((msg) => {
            const timestamp = appendMessage(state.container, msg);
            state.lastTimestamp = timestamp;
            if (!state.open) {
                state.hasUnread = true;
                const chatBtn = document.querySelector(`.btn-chat-toggle[data-ticket-id="${ticketId}"]`);
                if (chatBtn && !chatBtn.querySelector('.notification-badge')) {
                    const badge = document.createElement('span');
                    badge.className = 'notification-badge';
                    badge.textContent = '!';
                    chatBtn.appendChild(badge);
                }
            }
        });
    } catch (err) {
        console.error('Ticket updates error:', err);
    } finally {
        state.loading = false;
    }
};

const initTicketThreads = () => {
    threadStates.clear();

    document.querySelectorAll('.btn-chat-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const ticketId = btn.dataset.ticketId;
            const thread = document.getElementById(`ticket-thread-${ticketId}`);
            if (!thread) return;
            const state = getThreadState(ticketId);
            if (!state) return;
            const isActive = thread.classList.toggle('active');
            state.open = isActive;
            if (isActive) {
                const messagesContainer = state.container;
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                const lastMessage = messagesContainer.querySelector('.chat-message:last-child');
                if (lastMessage) {
                    state.lastTimestamp = lastMessage.dataset.timestamp;
                }
                messagesContainer.dataset.autoScroll = 'true';
                state.hasUnread = false;
                const badge = thread.previousElementSibling?.querySelector('.notification-badge');
                if (badge) {
                    badge.remove();
                }
            } else {
                state.container.dataset.autoScroll = 'false';
            }
        });
    });

    document.querySelectorAll('.btn-send-reply').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const ticketId = btn.dataset.ticketId;
            const state = getThreadState(ticketId);
            if (!state) return;
            const textarea = state.textarea;
            const messagesContainer = state.container;
            const message = textarea.value.trim();
            if (!message) return;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';

            try {
                const response = await fetch('api/ticket_reply.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ticket_id: ticketId,
                        message,
                        csrf_token: csrfToken,
                        as_staff: true
                    })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Не удалось отправить');
                }
                const timestamp = appendMessage(messagesContainer, data.data);
                state.lastTimestamp = timestamp;
                textarea.value = '';
            } catch (error) {
                alert(error.message || 'Ошибка отправки');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Отправить';
            }
        });
    });

    syncExistingTimestamps();

    if (updatesIntervalId) {
        clearInterval(updatesIntervalId);
    }
    updatesIntervalId = setInterval(() => {
        threadStates.forEach((state, ticketId) => {
            fetchUpdates(ticketId, state);
        });
    }, 3000);
};

function applyTicketFilters(options = {}) {
    const status = document.getElementById('statusFilter')?.value || '';
    const priority = document.getElementById('priorityFilter')?.value || '';
    const search = document.getElementById('searchInput')?.value.trim() || '';
    const page = options.page || '1';

    const params = new URLSearchParams();
    if (status) params.set('status', status);
    if (priority) params.set('priority', priority);
    if (search) params.set('search', search);
    if (page) params.set('page', page);

    const url = new URL(window.location.href);
    url.search = params.toString();

    params.set('ajax', '1');

    fetch(`support_tickets.php?${params.toString()}`)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('ticketsContainer');
            const pagination = document.getElementById('ticketsPagination');
            if (container) container.innerHTML = data.tickets_html || '';
            if (pagination) pagination.innerHTML = data.pagination_html || '';

            const resetBtn = document.getElementById('ticketsReset');
            if (resetBtn) {
                resetBtn.classList.toggle('is-hidden', !status && !priority && !search);
            }

            window.history.replaceState({}, '', url.toString());
            initTicketThreads();
        })
        .catch(() => {
            alert('Не удалось обновить список тикетов');
        });
}

document.addEventListener('DOMContentLoaded', () => {
    pageRoot = document.querySelector('.tickets-page');
    csrfToken = pageRoot?.dataset.csrf || '';
    serverOffsetMinutes = pageRoot?.dataset.offset ? parseInt(pageRoot.dataset.offset, 10) : 0;
    initTicketThreads();

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyTicketFilters();
            }
        });
    }

    document.addEventListener('change', (event) => {
        if (event.target.closest('#statusFilter') || event.target.closest('#priorityFilter')) {
            applyTicketFilters();
        }
    });

    const resetBtn = document.getElementById('ticketsReset');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            const statusSelect = document.getElementById('statusFilter');
            const prioritySelect = document.getElementById('priorityFilter');
            const search = document.getElementById('searchInput');
            if (statusSelect) statusSelect.value = '';
            if (prioritySelect) prioritySelect.value = '';
            if (search) search.value = '';
            applyTicketFilters();
        });
    }

    document.addEventListener('click', (event) => {
        const pageLink = event.target.closest('.pagination a');
        if (!pageLink) return;
        event.preventDefault();
        const url = new URL(pageLink.getAttribute('href'), window.location.origin);
        const page = url.searchParams.get('page') || '1';
        applyTicketFilters({ page });
    });
});
