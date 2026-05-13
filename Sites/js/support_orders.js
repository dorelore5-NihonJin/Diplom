function toggleOrderDetails(orderId) {
    const detailsElement = document.getElementById('details-' + orderId);
    const chatElement = document.getElementById('chat-' + orderId);
    const detailsBtn = document.getElementById('details-btn-' + orderId);
    const chatBtn = document.getElementById('chat-btn-' + orderId);

    if (!detailsElement || !detailsBtn) {
        return;
    }

    const isExpanded = detailsElement.classList.contains('show');

    if (chatElement && chatElement.classList.contains('show')) {
        chatElement.classList.remove('show');
        if (chatBtn) {
            chatBtn.classList.remove('expanded');
        }
    }

    document.querySelectorAll('.order-expanded-details.show, .order-support-chat.show').forEach((el) => {
        if (el.id !== 'details-' + orderId) {
            el.classList.remove('show');
            const card = el.closest('.order-card');
            if (card) {
                card.classList.remove('expanded');
            }
        }
    });

    document.querySelectorAll('.btn-order.expanded').forEach((btn) => {
        if (btn !== detailsBtn) {
            btn.classList.remove('expanded');
        }
    });

    if (isExpanded) {
        detailsElement.classList.remove('show');
        detailsBtn.classList.remove('expanded');
        detailsBtn.closest('.order-card')?.classList.remove('expanded');
    } else {
        detailsElement.classList.add('show');
        detailsBtn.classList.add('expanded');
        detailsBtn.closest('.order-card')?.classList.add('expanded');
    }
}

function toggleSupportChat(orderId) {
    const chatElement = document.getElementById('chat-' + orderId);
    const detailsElement = document.getElementById('details-' + orderId);
    const chatBtn = document.getElementById('chat-btn-' + orderId);
    const detailsBtn = document.getElementById('details-btn-' + orderId);

    if (!chatElement || !chatBtn) {
        return;
    }

    const isExpanded = chatElement.classList.contains('show');

    if (detailsElement && detailsElement.classList.contains('show')) {
        detailsElement.classList.remove('show');
        if (detailsBtn) {
            detailsBtn.classList.remove('expanded');
        }
    }

    document.querySelectorAll('.order-expanded-details.show, .order-support-chat.show').forEach((el) => {
        if (el.id !== 'chat-' + orderId) {
            el.classList.remove('show');
            const card = el.closest('.order-card');
            if (card) {
                card.classList.remove('expanded');
            }
        }
    });

    document.querySelectorAll('.btn-order.expanded').forEach((btn) => {
        if (btn !== chatBtn) {
            btn.classList.remove('expanded');
        }
    });

    if (isExpanded) {
        chatElement.classList.remove('show');
        chatBtn.classList.remove('expanded');
        chatBtn.closest('.order-card')?.classList.remove('expanded');
    } else {
        chatElement.classList.add('show');
        chatBtn.classList.add('expanded');
        chatBtn.closest('.order-card')?.classList.add('expanded');

        setTimeout(() => {
            const messagesContainer = document.getElementById('messages-' + orderId);
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }, 100);

        markMessagesAsRead(orderId);
    }
}

async function sendMessage(orderId, evt) {
    const input = document.getElementById('chat-input-' + orderId);
    const messagesContainer = document.getElementById('messages-' + orderId);
    const sendBtn = evt?.currentTarget || document.querySelector('#chat-' + orderId + ' .chat-send-btn');

    if (!input || !messagesContainer) {
        return;
    }

    const message = input.value.trim();
    if (!message) {
        return;
    }

    if (sendBtn) {
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Отправка...</span>';
    }
    input.disabled = true;

    try {
        const response = await fetch('api/send_support_reply.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId,
                message
            })
        });

        const data = await response.json();

        if (data.success) {
            input.value = '';
            input.style.height = '48px';

            const emptyState = messagesContainer.querySelector('.chat-empty');
            if (emptyState) {
                emptyState.remove();
            }

            const existingSeparators = messagesContainer.querySelectorAll('.chat-date-separator');
            let hasTodaySeparator = false;

            existingSeparators.forEach((sep) => {
                if (sep.textContent?.includes('Сегодня')) {
                    hasTodaySeparator = true;
                }
            });

            if (!hasTodaySeparator) {
                const separator = document.createElement('div');
                separator.className = 'chat-date-separator';
                separator.innerHTML = '<span>Сегодня</span>';
                messagesContainer.appendChild(separator);
            }

            const messageEl = document.createElement('div');
            messageEl.className = 'chat-message support';

            messageEl.innerHTML = `
                <div class="chat-avatar">
                    <i class="fas fa-headset"></i>
                </div>
                <div class="chat-bubble">
                    <div class="chat-bubble-header">
                        <span class="chat-sender">Техподдержка</span>
                        <span class="chat-time">${data.data.formatted_time}</span>
                    </div>
                    <div class="chat-text">${data.data.message.replace(/\n/g, '<br>')}</div>
                </div>
            `;

            messagesContainer.appendChild(messageEl);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            showNotification('Сообщение отправлено', 'success');
        } else {
            showNotification(data.message || 'Ошибка отправки сообщения', 'error');
        }
    } catch (error) {
        console.error('Send message error:', error);
        showNotification('Ошибка соединения. Проверьте подключение к интернету.', 'error');
    } finally {
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i><span>Отправить</span>';
        }
        input.disabled = false;
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #dc2626)'};
        color: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        font-weight: 600;
        font-size: 14px;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

async function markMessagesAsRead(orderId) {
    try {
        console.log('Marking messages as read for order:', orderId);
        const response = await fetch('api/mark_messages_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ order_id: orderId })
        });

        const data = await response.json();
        console.log('Mark messages response:', data);

        if (data.success) {
            console.log('Messages marked as read, removing badge');
            const badge = document.querySelector('#chat-btn-' + orderId + ' .notification-badge');
            if (badge) {
                console.log('Badge found, applying fadeOut animation');
                badge.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    badge.remove();
                    console.log('Badge removed');
                }, 300);
            } else {
                console.log('No badge found for order', orderId);
            }
        } else {
            console.error('Failed to mark messages as read:', data.message);
        }
    } catch (error) {
        console.error('Mark messages read error:', error);
    }
}

function cancelOrder(orderId) {
    if (confirm('Вы уверены, что хотите отменить этот заказ?')) {
        alert('Функция отмены заказа будет реализована позже');
    }
}

async function deleteOrder(orderId) {
    openOrderDeleteModal(orderId);
}

function openOrderDeleteModal(orderId, orderNumber = '') {
    pendingOrderDeleteId = orderId;
    const modal = document.getElementById('orderDeleteModal');
    const message = document.getElementById('orderDeleteMessage');
    if (message) {
        message.innerHTML = orderNumber
            ? `Вы уверены, что хотите удалить заказ <strong>${orderNumber}</strong>? Это действие нельзя отменить.`
            : 'Эта операция удалит заказ без возможности восстановления.';
    }
    if (modal) {
        modal.classList.add('is-visible');
    }
}

function closeOrderDeleteModal() {
    const modal = document.getElementById('orderDeleteModal');
    if (modal) {
        modal.classList.remove('is-visible');
    }
    pendingOrderDeleteId = null;
    setOrderDeleteLoading(false);
}

function setOrderDeleteLoading(isLoading) {
    const btn = document.getElementById('orderDeleteConfirm');
    if (!btn) return;
    if (isLoading) {
        btn.classList.add('loading');
        btn.setAttribute('disabled', 'disabled');
    } else {
        btn.classList.remove('loading');
        btn.removeAttribute('disabled');
    }
}

async function confirmOrderDelete() {
    if (!pendingOrderDeleteId) {
        closeOrderDeleteModal();
        return;
    }

    setOrderDeleteLoading(true);

    try {
        const response = await fetch('api/delete_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ order_id: pendingOrderDeleteId })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Заказ удалён', 'success');
            closeOrderDeleteModal();
            setTimeout(() => window.location.reload(), 800);
        } else {
            setOrderDeleteLoading(false);
            showNotification(data.message || 'Не удалось удалить заказ', 'error');
        }
    } catch (error) {
        console.error('Delete order error:', error);
        setOrderDeleteLoading(false);
        showNotification('Ошибка сервера при удалении заказа', 'error');
    }
}

// Filter functions
let ordersRequestToken = 0;

function updateOrdersResetButton(hasFilters) {
    const resetBtn = document.getElementById('ordersReset');
    if (!resetBtn) return;
    resetBtn.classList.toggle('is-hidden', !hasFilters);
}

function updateOrdersUrl(params) {
    const url = new URL(window.location.href);
    const cleanParams = new URLSearchParams(params.toString());
    cleanParams.delete('ajax');
    url.search = cleanParams.toString();
    window.history.replaceState({}, '', url.toString());
}

async function applyOrderFilters(options = {}) {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const ordersContainer = document.getElementById('ordersContainer');
    const ordersPagination = document.getElementById('ordersPagination');

    if (!searchInput || !statusFilter || !ordersContainer || !ordersPagination) {
        return;
    }

    const params = new URLSearchParams();
    const searchValue = searchInput.value.trim();
    const statusValue = statusFilter.value;
    const pageValue = options.page ? String(options.page) : '';

    if (searchValue) {
        params.set('search', searchValue);
    }
    if (statusValue) {
        params.set('status', statusValue);
    }
    if (pageValue) {
        params.set('page', pageValue);
    }

    params.set('ajax', '1');
    const requestToken = ++ordersRequestToken;

    try {
        const response = await fetch(`support_orders.php?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();

        if (requestToken !== ordersRequestToken) {
            return;
        }

        ordersContainer.innerHTML = data.orders_html || '';
        ordersPagination.innerHTML = data.pagination_html || '';

        updateOrdersUrl(params);
        updateOrdersResetButton(Boolean(searchValue || statusValue));
    } catch (error) {
        console.error('Orders filter error:', error);
        showNotification('Не удалось обновить список заказов', 'error');
    }
}

function applyFilters() {
    applyOrderFilters();
}

function resetFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    applyOrderFilters();
}

// Toggle status menu
function toggleStatusMenu(orderId) {
    const menu = document.getElementById('status-menu-' + orderId);
    const button = document.getElementById('status-btn-' + orderId);
    
    // Close all other menus
    document.querySelectorAll('.status-menu.show').forEach(m => {
        if (m.id !== 'status-menu-' + orderId) {
            m.classList.remove('show');
        }
    });
    
    document.querySelectorAll('.btn-status.active').forEach(btn => {
        if (btn.id !== 'status-btn-' + orderId) {
            btn.classList.remove('active');
        }
    });
    
    menu.classList.toggle('show');
    button.classList.toggle('active');
}

// Close status menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.status-dropdown')) {
        document.querySelectorAll('.status-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
        document.querySelectorAll('.btn-status.active').forEach(btn => {
            btn.classList.remove('active');
        });
    }
});

// Change order status
async function changeOrderStatus(orderId, newStatus) {
    pendingStatusChange = { orderId, status: newStatus };
    const modal = document.getElementById('orderStatusModal');
    if (modal) {
        modal.classList.add('is-visible');
    }
}

function closeStatusModal() {
    const modal = document.getElementById('orderStatusModal');
    if (modal) {
        modal.classList.remove('is-visible');
    }
    pendingStatusChange = { orderId: null, status: null };
    setStatusButtonLoading(false);
}

function setStatusButtonLoading(isLoading) {
    const btn = document.getElementById('statusModalConfirm');
    if (!btn) return;
    if (isLoading) {
        btn.classList.add('loading');
        btn.setAttribute('disabled', 'disabled');
    } else {
        btn.classList.remove('loading');
        btn.removeAttribute('disabled');
    }
}

async function confirmStatusChange() {
    const { orderId, status } = pendingStatusChange;
    if (!orderId || !status) {
        closeStatusModal();
        return;
    }

    setStatusButtonLoading(true);

    try {
        const response = await fetch('api/update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId,
                status
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Статус заказа успешно изменен', 'success');
            closeStatusModal();
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            setStatusButtonLoading(false);
            showNotification(data.message || 'Ошибка изменения статуса', 'error');
        }
    } catch (error) {
        console.error('Change status error:', error);
        setStatusButtonLoading(false);
        showNotification('Ошибка соединения. Попробуйте позже.', 'error');
    }
}

document.addEventListener('input', (e) => {
    if (e.target.classList.contains('chat-input')) {
        e.target.style.height = 'auto';
        e.target.style.height = Math.min(e.target.scrollHeight, 120) + 'px';
    }
});

document.addEventListener('keydown', (e) => {
    if (e.target.classList.contains('chat-input') && e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const orderId = e.target.id.replace('chat-input-', '');
        sendMessage(orderId);
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const statusFilter = document.getElementById('statusFilter');
    const searchInput = document.getElementById('searchInput');
    const resetBtn = document.getElementById('ordersReset');
    const pagination = document.getElementById('ordersPagination');

    if (statusFilter) {
        statusFilter.addEventListener('change', () => applyOrderFilters());
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyOrderFilters();
            }
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            resetFilters();
        });
    }

    if (pagination) {
        pagination.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) {
                return;
            }
            const url = new URL(link.getAttribute('href'), window.location.href);
            const page = url.searchParams.get('page');
            if (!page) {
                return;
            }
            event.preventDefault();
            applyOrderFilters({ page });
        });
    }
});

window.toggleOrderDetails = toggleOrderDetails;
window.toggleSupportChat = toggleSupportChat;
window.sendMessage = sendMessage;
window.cancelOrder = cancelOrder;
window.openOrderDeleteModal = openOrderDeleteModal;
window.closeOrderDeleteModal = closeOrderDeleteModal;
window.confirmOrderDelete = confirmOrderDelete;
window.closeStatusModal = closeStatusModal;
window.confirmStatusChange = confirmStatusChange;
