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
        const response = await fetch('api/send_support_message.php', {
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
            messageEl.className = 'chat-message user';

            const avatar = data.data.avatar
                ? `<img src="${data.data.avatar}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">`
                : (data.data.username || 'В').charAt(0).toUpperCase();

            messageEl.innerHTML = `
                <div class="chat-avatar">${avatar}</div>
                <div class="chat-bubble">
                    <div class="chat-bubble-header">
                        <span class="chat-sender">${data.data.username}</span>
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

async function cancelOrder(orderId) {
    if (!confirm('Отменить заказ? Действие нельзя отменить.')) {
        return;
    }

    try {
        const response = await fetch('api/cancel_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        });
        const data = await response.json();
        if (data.success) {
            showNotification('Заказ отменён', 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showNotification(data.message || 'Не удалось отменить заказ', 'error');
        }
    } catch (error) {
        console.error('Cancel order error:', error);
        showNotification('Ошибка сервера при отмене заказа', 'error');
    }
}

async function payOrder(orderId) {
    const proceed = confirm('Оплатить заказ сейчас?');
    if (!proceed) {
        showNotification('Оплата отменена', 'info');
        return;
    }

    try {
        const response = await fetch('api/mock_pay_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId })
        });
        const data = await response.json();
        if (data.success) {
            showNotification('Заказ успешно оплачен', 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showNotification(data.message || 'Не удалось выполнить оплату', 'error');
        }
    } catch (error) {
        console.error('Mock pay error:', error);
        showNotification('Ошибка сервера при оплате заказа', 'error');
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

window.toggleOrderDetails = toggleOrderDetails;
window.toggleSupportChat = toggleSupportChat;
window.sendMessage = sendMessage;
window.cancelOrder = cancelOrder;
window.payOrder = payOrder;
