function viewBuild(buildId) {
    window.location.href = `build-details.php?id=${buildId}`;
}

// Comparison selection logic
const compareStorageKey = 'compareBuilds';
let selectedBuilds = [];
let pendingDeleteId = null;

document.addEventListener('DOMContentLoaded', () => {
    initCompareSelection();
});

function initCompareSelection() {
    selectedBuilds = loadCompareSelection();
    const checkboxes = document.querySelectorAll('.compare-checkbox');
    checkboxes.forEach(checkbox => {
        const id = parseInt(checkbox.dataset.buildId, 10);
        checkbox.checked = selectedBuilds.some(item => item.id === id);
        checkbox.addEventListener('change', (event) => handleCompareToggle(event, checkbox));
    });
    updateCompareBar();
}

function handleCompareToggle(event, checkbox) {
    const buildId = parseInt(checkbox.dataset.buildId, 10);
    const buildName = checkbox.dataset.buildName;
    const buildPrice = parseFloat(checkbox.dataset.buildPrice) || 0;

    if (checkbox.checked) {
        if (selectedBuilds.length >= compareLimit) {
            event.preventDefault();
            checkbox.checked = false;
            alert(`Можно сравнить максимум ${compareLimit} сборки.`);
            return;
        }
        selectedBuilds.push({ id: buildId, name: buildName, price: buildPrice });
    } else {
        selectedBuilds = selectedBuilds.filter(item => item.id !== buildId);
    }

    saveCompareSelection();
    updateCompareBar();
}

function loadCompareSelection() {
    try {
        const stored = localStorage.getItem(compareStorageKey);
        if (!stored) return [];
        const parsed = JSON.parse(stored);
        if (Array.isArray(parsed)) {
            return parsed.slice(0, compareLimit);
        }
        return [];
    } catch (error) {
        console.warn('Failed to parse compare selection', error);
        return [];
    }
}

function saveCompareSelection() {
    localStorage.setItem(compareStorageKey, JSON.stringify(selectedBuilds));
}

function updateCompareBar() {
    const bar = document.getElementById('compareBar');
    if (!bar) return;
    const selectedContainer = document.getElementById('compareSelected');
    const btn = document.getElementById('compareActionBtn');
    const btnLabel = document.getElementById('compareBtnLabel');

    if (selectedBuilds.length === 0) {
        bar.classList.remove('show');
    } else {
        bar.classList.add('show');
    }

    selectedContainer.innerHTML = selectedBuilds.map(item => `
        <div class="selected-build-card">
            <strong>${item.name}</strong>
            <div class="selected-build-meta">
                <span>ID: ${item.id}</span>
                <span>${formatPriceValue(item.price)}</span>
            </div>
            <button type="button" onclick="removeCompareBuild(${item.id})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');

    if (selectedBuilds.length >= 2) {
        btn.removeAttribute('disabled');
        btnLabel.textContent = `Сравнить ${selectedBuilds.length} сборки`;
    } else {
        btn.setAttribute('disabled', 'disabled');
        btnLabel.textContent = 'Выберите минимум 2 сборки';
    }
}

function removeCompareBuild(buildId) {
    selectedBuilds = selectedBuilds.filter(item => item.id !== buildId);
    const checkbox = document.querySelector(`.compare-checkbox[data-build-id="${buildId}"]`);
    if (checkbox) {
        checkbox.checked = false;
    }
    saveCompareSelection();
    updateCompareBar();
}

function clearCompareSelection() {
    selectedBuilds = [];
    document.querySelectorAll('.compare-checkbox').forEach(cb => cb.checked = false);
    saveCompareSelection();
    updateCompareBar();
}

function openComparePage(forcePrompt = false) {
    selectedBuilds = selectedBuilds.slice(0, compareLimit);
    saveCompareSelection();

    if (selectedBuilds.length < 2) {
        const message = 'Выберите как минимум две сборки для сравнения.';
        showCompareWarning(message);
        return;
    }

    const ids = selectedBuilds.map(item => item.id).join(',');
    window.location.href = `compare-builds.php?ids=${ids}`;
}

function showCompareWarning(text) {
    const modal = document.getElementById('compareWarningModal');
    const messageEl = document.getElementById('compareWarningMessage');
    if (messageEl && text) {
        messageEl.textContent = text;
    }
    if (modal) {
        modal.classList.add('is-visible');
        const content = modal.querySelector('.compare-modal-content');
        if (content) {
            content.setAttribute('tabindex', '-1');
        }
    } else if (text) {
        alert(text);
    }
}

function closeCompareWarning() {
    const modal = document.getElementById('compareWarningModal');
    if (modal) {
        modal.classList.remove('is-visible');
    }
}

function formatPriceValue(value) {
    if (!value) return '—';
    try {
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(value);
    } catch (error) {
        return value + ' ₽';
    }
}

function openDeleteModal(buildId, buildName) {
    pendingDeleteId = buildId;
    const modal = document.getElementById('deleteBuildModal');
    const message = document.getElementById('deleteModalMessage');
    if (message && buildName) {
        message.innerHTML = `Вы уверены, что хотите удалить сборку <strong>${buildName}</strong>? Это действие нельзя отменить.`;
    }
    if (modal) {
        modal.classList.add('is-visible');
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteBuildModal');
    if (modal) {
        modal.classList.remove('is-visible');
    }
    pendingDeleteId = null;
    setDeleteButtonLoading(false);
}

function setDeleteButtonLoading(isLoading) {
    const deleteBtn = document.getElementById('deleteModalConfirm');
    if (!deleteBtn) return;
    if (isLoading) {
        deleteBtn.classList.add('loading');
        deleteBtn.setAttribute('disabled', 'disabled');
    } else {
        deleteBtn.classList.remove('loading');
        deleteBtn.removeAttribute('disabled');
    }
}

function confirmDeleteBuild() {
    if (!pendingDeleteId) {
        closeDeleteModal();
        return;
    }

    setDeleteButtonLoading(true);

    fetch('api/delete_build.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ build_id: pendingDeleteId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            location.reload();
        } else {
            setDeleteButtonLoading(false);
            showToast ? showToast(data.error || 'Ошибка удаления', 'error') : alert(data.error || 'Ошибка удаления');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setDeleteButtonLoading(false);
        showToast ? showToast('Ошибка удаления', 'error') : alert('Ошибка удаления');
    });
}

function toggleLike(buildId) {
    // Check if user is logged in (will be set by PHP)
    if (typeof isUserLoggedIn !== 'undefined' && !isUserLoggedIn) {
        window.location.href = 'login.php';
        return;
    }

    fetch('api/toggle_like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ build_id: buildId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
