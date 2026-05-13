// Build state
let currentBuild;
try {
    currentBuild = JSON.parse(localStorage.getItem('currentBuild') || '{}');
    if (typeof currentBuild !== 'object' || currentBuild === null) {
        currentBuild = {};
    }
} catch (error) {
    console.warn('Failed to parse saved build, resetting.', error);
    currentBuild = {};
    localStorage.removeItem('currentBuild');
}
let currentCategory = null;
let selectedResolution = '1920x1080';
let selectedQuality = 'ultra';
let allModalComponents = [];
let filteredModalComponents = [];
const loggedInUserId = typeof builderUserId !== 'undefined' ? builderUserId : null;
let lastGeneratedGhostName = null;
let lastGuestSignature = null;
let toastContainerEl = null;

function flattenBuildComponents(buildState) {
    const result = [];
    Object.values(buildState || {}).forEach(item => {
        if (!item) return;
        if (Array.isArray(item)) {
            item.forEach(inner => {
                if (inner) result.push(inner);
            });
        } else {
            result.push(item);
        }
    });
    return result;
}

document.addEventListener('DOMContentLoaded', () => {
    const saveForm = document.getElementById('saveBuildForm');
    if (saveForm) {
        saveForm.addEventListener('submit', handleSaveBuildSubmit);
    }

    const goToBuildsBtn = document.getElementById('goToMyBuildsBtn');
    if (goToBuildsBtn) {
        goToBuildsBtn.addEventListener('click', () => {
            closeSaveResultModal();
            if (loggedInUserId) {
                window.location.href = 'profile.php#my-builds';
            } else {
                window.location.href = 'login.php?redirect=profile.php%23my-builds';
            }
        });
    }
});

const qualityLabels = {
    low: 'Низкое',
    medium: 'Среднее',
    high: 'Высокое',
    ultra: 'Ультра'
};

const resolutionLabels = {
    '1920x1080': '1080p',
    '2560x1440': '1440p',
    '3840x2160': '4K'
};

function generateGhostSignature() {
    const digits = Math.floor(10000000 + Math.random() * 90000000);
    return String(digits);
}

function openSaveModal() {
    const modal = document.getElementById('saveModal');
    if (!modal) return;

    const components = flattenBuildComponents(currentBuild);
    if (!components.length) {
        showToast('Добавьте хотя бы один компонент', 'warning');
        return;
    }

    if (!loggedInUserId) {
        showToast('Войдите в аккаунт, чтобы сохранять сборки', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php?redirect=builder.php';
        }, 1200);
        return;
    }

    const nameInput = document.getElementById('saveBuildName');
    const notice = document.getElementById('guestSaveNotice');

    if (nameInput) {
        nameInput.disabled = false;
        nameInput.value = '';
        nameInput.focus();
    }
    if (notice) {
        notice.style.display = 'none';
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSaveModal() {
    const modal = document.getElementById('saveModal');
    if (modal) {
        modal.classList.remove('active');
    }
    document.body.style.overflow = '';
}

function openSaveResultModal({ name, buildId, message }) {
    const modal = document.getElementById('saveResultModal');
    if (!modal) return;

    const nameEl = document.getElementById('saveResultName');
    const idEl = document.getElementById('saveResultId');
    const messageEl = document.getElementById('saveResultMessage');

    if (nameEl) nameEl.textContent = name || '—';
    if (idEl) idEl.textContent = buildId ? `#${buildId}` : '—';
    if (messageEl && message) messageEl.textContent = message;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSaveResultModal() {
    const modal = document.getElementById('saveResultModal');
    if (modal) modal.classList.remove('active');
    document.body.style.overflow = '';
}

async function handleSaveBuildSubmit(event) {
    event.preventDefault();

    const components = flattenBuildComponents(currentBuild);
    if (!components.length) {
        showToast('Добавьте хотя бы один компонент', 'warning');
        closeSaveModal();
        return;
    }

    const nameInput = document.getElementById('saveBuildName');
    const categorySelect = document.getElementById('saveBuildCategory');

    if (!loggedInUserId) {
        showToast('Войдите в аккаунт, чтобы сохранять сборки', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php?redirect=builder.php';
        }, 1200);
        return;
    }

    const buildName = (nameInput?.value || '').trim();
    if (!buildName) {
        showToast('Введите название сборки', 'warning');
        return;
    }

    const purpose = categorySelect?.value || 'other';
    closeSaveModal();
    await saveBuild(buildName, purpose);
}

// Category-specific filter definitions (from catalog.js)
const categoryFilters = {
    '1': { // CPU
        slug: 'cpu',
        filters: [
            { type: 'range', name: 'cpu_cores', label: 'Количество ядер', min: 'cpu_cores_min', max: 'cpu_cores_max', placeholder: ['2', '32'], icon: 'fa-microchip' },
            { type: 'range', name: 'cpu_threads', label: 'Потоков', min: 'cpu_threads_min', max: 'cpu_threads_max', placeholder: ['4', '64'], icon: 'fa-stream' },
            { type: 'range', name: 'cpu_base_clock', label: 'Частота (ГГц)', min: 'cpu_base_clock_min', max: 'cpu_base_clock_max', placeholder: ['2.0', '5.0'], icon: 'fa-gauge-high' },
            { type: 'checkbox', name: 'cpu_socket', label: 'Сокет', options: ['AM4', 'AM5', 'LGA1700', 'LGA1851', 'LGA1200'], icon: 'fa-plug' }
        ]
    },
    '2': { // GPU
        slug: 'gpu',
        filters: [
            { type: 'range', name: 'gpu_memory', label: 'Память (ГБ)', min: 'gpu_memory_min', max: 'gpu_memory_max', placeholder: ['4', '24'], icon: 'fa-memory' },
            { type: 'range', name: 'gpu_tdp', label: 'TDP (Вт)', min: 'gpu_tdp_min', max: 'gpu_tdp_max', placeholder: ['100', '600'], icon: 'fa-bolt' },
            { type: 'checkbox', name: 'gpu_memory_type', label: 'Тип памяти', options: ['GDDR6', 'GDDR6X', 'GDDR7'], icon: 'fa-microchip' }
        ]
    },
    '3': { // Motherboard
        slug: 'motherboard',
        filters: [
            { type: 'checkbox', name: 'mobo_form_factor', label: 'Форм-фактор', options: ['ATX', 'Micro-ATX', 'Mini-ITX'], icon: 'fa-table-cells' },
            { type: 'checkbox', name: 'mobo_chipset', label: 'Чипсет', options: ['Z790', 'B760', 'X670', 'B650', 'AM5'], icon: 'fa-microchip' },
            { type: 'checkbox', name: 'socket_type', label: 'Сокет', options: ['LGA1700', 'LGA1851', 'AM5', 'AM4'], icon: 'fa-plug' }
        ]
    },
    '4': { // RAM
        slug: 'ram',
        filters: [
            { type: 'range', name: 'ram_capacity', label: 'Объем (ГБ)', min: 'ram_capacity_min', max: 'ram_capacity_max', placeholder: ['8', '128'], icon: 'fa-memory' },
            { type: 'range', name: 'ram_speed', label: 'Частота (МГц)', min: 'ram_speed_min', max: 'ram_speed_max', placeholder: ['2400', '7200'], icon: 'fa-gauge-high' },
            { type: 'checkbox', name: 'ram_type', label: 'Тип', options: ['DDR4', 'DDR5'], icon: 'fa-sd-card' }
        ]
    },
    '5': { // Storage
        slug: 'storage',
        filters: [
            { type: 'range', name: 'storage_capacity', label: 'Объем (ГБ)', min: 'storage_capacity_min', max: 'storage_capacity_max', placeholder: ['256', '4096'], icon: 'fa-hard-drive' },
            { type: 'checkbox', name: 'storage_type', label: 'Тип', options: ['SSD', 'HDD', 'NVMe'], icon: 'fa-database' }
        ]
    },
    '6': { // PSU
        slug: 'psu',
        filters: [
            { type: 'range', name: 'psu_wattage', label: 'Мощность (Вт)', min: 'psu_wattage_min', max: 'psu_wattage_max', placeholder: ['450', '1600'], icon: 'fa-bolt' },
            { type: 'checkbox', name: 'psu_efficiency', label: 'Сертификат', options: ['80+ Bronze', '80+ Gold', '80+ Platinum'], icon: 'fa-certificate' }
        ]
    },
    '7': { // Case
        slug: 'case',
        filters: [
            { type: 'range', name: 'case_gpu_length', label: 'Макс. GPU (мм)', min: 'case_gpu_length_min', max: 'case_gpu_length_max', placeholder: ['250', '450'], icon: 'fa-ruler-horizontal' },
            { type: 'range', name: 'case_cooler_height', label: 'Макс. кулер (мм)', min: 'case_cooler_height_min', max: 'case_cooler_height_max', placeholder: ['150', '190'], icon: 'fa-ruler-vertical' },
            { type: 'checkbox', name: 'case_form_factor', label: 'Форм-фактор', options: ['Full Tower', 'Mid Tower', 'Mini Tower'], icon: 'fa-box' }
        ]
    },
    '8': { // Cooling
        slug: 'cooling',
        filters: [
            { type: 'checkbox', name: 'cooler_type', label: 'Тип', options: ['Башенный', 'AIO 240мм', 'AIO 360мм'], icon: 'fa-fan' },
            { type: 'range', name: 'cooler_tdp', label: 'TDP (Вт)', min: 'cooler_tdp_min', max: 'cooler_tdp_max', placeholder: ['65', '250'], icon: 'fa-temperature-high' },
            { type: 'checkbox', name: 'cooler_socket', label: 'Сокет', options: ['AM4', 'AM5', 'LGA1700', 'LGA1851', 'LGA1200'], icon: 'fa-plug' }
        ]
    }
};

const defaultCategorySlugs = {
    '1': 'cpu',
    '2': 'gpu',
    '3': 'motherboard',
    '4': 'ram',
    '5': 'storage',
    '6': 'psu',
    '7': 'case',
    '8': 'cooling'
};

const categorySlugMap = (typeof categories !== 'undefined' ? categories : []).reduce((map, category) => {
    if (!category || typeof category.id === 'undefined') return map;
    const id = String(category.id);
    map[id] = category.slug || (categoryFilters[id] && categoryFilters[id].slug ? categoryFilters[id].slug : (defaultCategorySlugs[id] || null));
    return map;
}, { ...defaultCategorySlugs });

function parseComponentSpecs(rawSpecs) {
    if (!rawSpecs) return {};
    if (typeof rawSpecs === 'object') return rawSpecs;
    try {
        return JSON.parse(rawSpecs);
    } catch (e) {
        return {};
    }
}

function buildStorageMeta(component) {
    const specs = parseComponentSpecs(component.specs);
    const meta = [];
    const capacity = component.storage_capacity || component.capacity || specs.capacity;
    if (capacity) meta.push(capacity);
    const type = component.storage_type || specs.type;
    if (type) meta.push(type);
    const iface = component.storage_interface || specs.interface;
    if (iface) meta.push(iface);
    return meta.join(' • ');
}

function buildRamMeta(component) {
    const specs = parseComponentSpecs(component.specs);
    const meta = [];
    const capacity = component.ram_capacity || specs.capacity;
    if (capacity) {
        meta.push(isFinite(Number(capacity)) ? `${capacity}GB` : capacity);
    }
    const type = component.ram_type || specs.type;
    if (type) meta.push(type);
    const speed = component.ram_speed || specs.speed;
    if (speed) {
        meta.push(isFinite(Number(speed)) ? `${speed}MHz` : speed);
    }
    const clRaw = specs.cas_latency || specs.latency || specs.cl;
    if (clRaw) {
        const clValue = String(clRaw).toUpperCase().replace(/\s+/g, '');
        meta.push(clValue.startsWith('CL') ? clValue : `CL${clValue}`);
    }
    const modules = specs.modules || specs.module_count || specs.sticks;
    if (modules) {
        meta.push(String(modules).replace(/\s+/g, ''));
    }
    return meta.join(' • ');
}

function buildCoolingMeta(component) {
    const specs = parseComponentSpecs(component.specs);
    const meta = [];
    const type = component.cooler_type || specs.type;
    if (type) meta.push(type);
    const tdp = component.cooler_tdp || specs.tdp || specs.max_tdp;
    if (tdp) {
        const value = String(tdp).match(/([\d.]+)/);
        meta.push(value ? `${value[1]}W` : tdp);
    }
    const sockets = normalizeSocketList(component.cooler_socket || specs.socket || specs.sockets || specs.supported_sockets);
    if (sockets.length) {
        meta.push(sockets.join(', '));
    }
    return meta.join(' • ');
}

function getMoboRamSlots(mobo) {
    if (!mobo) return null;
    const direct = parseInt(mobo.mobo_ram_slots);
    if (Number.isFinite(direct) && direct > 0) return direct;
    const specs = parseSpecs(mobo.specs);
    if (specs && specs.ram_slots) return parseInt(specs.ram_slots);
    if (specs && specs.memory_slots) return parseInt(specs.memory_slots);
    return null;
}

function getMoboM2Slots(mobo) {
    if (!mobo) return null;
    const direct = parseInt(mobo.mobo_m2_slots);
    if (Number.isFinite(direct)) return direct;
    const specs = parseSpecs(mobo.specs);
    if (specs && specs.m2_slots) return parseInt(specs.m2_slots);
    if (specs && specs.m_2_slots) return parseInt(specs.m_2_slots);
    if (specs && specs['m2-slots']) return parseInt(specs['m2-slots']);
    const text = (mobo.name + ' ' + JSON.stringify(specs)).toUpperCase();
    const match = text.match(/M\.2[^\d]*(\d+)/);
    if (match) return parseInt(match[1]);
    return null;
}

function getMoboMaxRamSpeed(mobo) {
    if (!mobo) return null;
    const direct = parseInt(mobo.mobo_max_ram_speed);
    if (Number.isFinite(direct) && direct > 0) return direct;
    return extractMaxRAMSpeed(mobo.name, mobo.specs);
}

function extractMaxRAMCapacity(specs) {
    const specsObj = parseSpecs(specs);
    const candidates = [specsObj && specsObj.max_ram, specsObj && specsObj.max_memory, specsObj && specsObj.max_capacity];
    for (const value of candidates) {
        if (!value) continue;
        const match = String(value).match(/(\d+)\s*GB/i);
        if (match) return parseInt(match[1]);
    }
    const text = JSON.stringify(specsObj).toUpperCase();
    const regex = /(\d+)\s*GB\s*(MAX|MAXIMUM)/i;
    const match = text.match(regex);
    if (match) return parseInt(match[1]);
    return null;
}

function extractRamModuleCount(name, specs) {
    const specsObj = parseSpecs(specs);
    const candidates = [specsObj && specsObj.modules, specsObj && specsObj.module_count, specsObj && specsObj.sticks];
    for (const value of candidates) {
        if (!value) continue;
        const match = String(value).match(/(\d+)\s*x/i);
        if (match) return parseInt(match[1]);
        if (!isNaN(parseInt(value))) return parseInt(value);
    }
    const text = (name + ' ' + JSON.stringify(specsObj)).toUpperCase();
    const pattern = /(\d+)\s*[X×]/i;
    const match = text.match(pattern);
    return match ? parseInt(match[1]) : null;
}

function getRamModuleCount(kit) {
    if (!kit) return null;
    const modules = extractRamModuleCount(kit.name, kit.specs);
    if (modules) return modules;
    const specs = parseComponentSpecs(kit.specs);
    const capacityRaw = kit.ram_capacity || specs.capacity || parseRamCapacity(kit.name);
    const match = capacityRaw ? String(capacityRaw).match(/(\d+)/) : null;
    const capacity = match ? parseInt(match[1], 10) : (capacityRaw ? parseInt(capacityRaw, 10) : null);
    if (!capacity) return null;
    return capacity >= 16 ? 2 : 1;
}

function getTotalRamModules(ramEntry) {
    const kits = Array.isArray(ramEntry) ? ramEntry : (ramEntry ? [ramEntry] : []);
    let total = 0;
    let hasKnown = false;
    kits.forEach((kit) => {
        const count = getRamModuleCount(kit);
        if (count) {
            total += count;
            hasKnown = true;
        }
    });
    return hasKnown ? total : null;
}

// Initialize build
function initBuild() {
    renderBuild();
    updateSummary();
    updateBuildCounter();
    checkCompatibility();
    calculateFPS();
}

// Render current build
function renderBuild() {
    categories.forEach(category => {
        const categoryId = String(category.id);
        const componentData = currentBuild[categoryId];
        const slot = document.getElementById(`slot-${categoryId}`);
        if (!slot) {
            return;
        }

        slot.classList.remove('slot-storage-multi');

        if (componentData) {
            // Для накопителей (category 5) отображаем массив компонентов
            if (categoryId == '5' && Array.isArray(componentData)) {
                slot.classList.add('slot-storage-multi');
                const nextIndexLabel = componentData.length + 1;
                const cardsHtml = componentData.map((component, index) => {
                    const meta = buildStorageMeta(component) || component.manufacturer || '';
                    return `
                        <div class="storage-component-card">
                            <div class="storage-component-count">Накопитель ${index + 1}</div>
                            <div class="storage-component-body">
                                <div class="storage-component-info">
                                    <div class="storage-component-name">${component.name}</div>
                                    <div class="storage-component-meta">${meta}</div>
                                </div>
                                <div class="storage-component-actions">
                                    <div class="storage-component-price">${formatPrice(component.price)}</div>
                                    <button class="btn-remove-component btn-remove-storage" onclick="removeComponent(${categoryId}, ${index})" title="Удалить накопитель">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                slot.innerHTML = `
                    <div class="storage-components-stack">${cardsHtml}</div>
                    <button class="btn-add-more-storage" onclick="openComponentSelector(${categoryId}, '${category?.name || ''}')">
                        <div class="add-storage-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="add-storage-text">
                            <span class="add-storage-title">Добавить еще накопитель</span>
                            <span class="add-storage-subtitle">Накопитель №${nextIndexLabel}</span>
                        </div>
                        <div class="add-storage-plus"><i class="fas fa-plus"></i></div>
                    </button>
                `;
            } else if (categoryId == '4' && Array.isArray(componentData)) {
                slot.classList.add('slot-storage-multi');
                const nextIndexLabel = componentData.length + 1;
                const mobo = currentBuild[3];
                const moboSlots = mobo ? getMoboRamSlots(mobo) : null;
                const usedSlots = getTotalRamModules(componentData);
                const canAddMoreRam = !moboSlots || usedSlots === null || usedSlots < moboSlots;
                const cardsHtml = componentData.map((component, index) => {
                    const meta = buildRamMeta(component) || component.manufacturer || '';
                    return `
                        <div class="storage-component-card">
                            <div class="storage-component-count">Комплект ${index + 1}</div>
                            <div class="storage-component-body">
                                <div class="storage-component-info">
                                    <div class="storage-component-name">${component.name}</div>
                                    <div class="storage-component-meta">${meta}</div>
                                </div>
                                <div class="storage-component-actions">
                                    <div class="storage-component-price">${formatPrice(component.price)}</div>
                                    <button class="btn-remove-component btn-remove-storage" onclick="removeComponent(${categoryId}, ${index})" title="Удалить комплект">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                slot.innerHTML = `
                    <div class="storage-components-stack">${cardsHtml}</div>
                    ${canAddMoreRam ? `
                        <button class="btn-add-more-storage" onclick="openComponentSelector(${categoryId}, '${category?.name || ''}')">
                            <div class="add-storage-icon">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div class="add-storage-text">
                                <span class="add-storage-title">Добавить ещё память</span>
                                <span class="add-storage-subtitle">Комплект №${nextIndexLabel}</span>
                            </div>
                            <div class="add-storage-plus"><i class="fas fa-plus"></i></div>
                        </button>
                    ` : ''}
                `;
            } else if (!Array.isArray(componentData)) {
                if (categoryId == 5) {
                    slot.classList.add('slot-storage-multi');
                }
                if (categoryId == 4) {
                    slot.classList.add('slot-storage-multi');
                }
                // Для остальных категорий - одиночный компонент
                const component = componentData;
                let extraInfo = component.manufacturer || '';
        if (categoryId == 6 && component.psu_wattage) {
            const efficiency = component.psu_efficiency ? ` • ${component.psu_efficiency}` : '';
            extraInfo = `${component.psu_wattage}Вт${efficiency} • ${component.manufacturer || ''}`;
        } else if (categoryId == 4) {
            const ramMeta = buildRamMeta(component);
            if (ramMeta) {
                extraInfo = ramMeta;
            }
        } else if (categoryId == 8) {
            const coolingMeta = buildCoolingMeta(component);
            if (coolingMeta) {
                extraInfo = coolingMeta;
            }
        }
                
                const baseCard = `
                    <div class="selected-component">
                        <div class="selected-component-info">
                            <div class="selected-component-name">${component.name}</div>
                            <div class="selected-component-specs">${extraInfo}</div>
                        </div>
                        <div class="selected-component-actions">
                            <div class="selected-component-price">${formatPrice(component.price)}</div>
                            <div class="component-action-buttons">
                                <button class="btn-component-action" onclick="viewComponentDetails(${categoryId})" title="Подробнее">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                                <button class="btn-component-action" onclick="changeComponent(${categoryId}, '${category?.name || ''}')" title="Заменить">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                                <button class="btn-remove-component" onclick="removeComponent(${categoryId})" title="Удалить">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                if (categoryId == 4) {
                    const mobo = currentBuild[3];
                    const moboSlots = mobo ? getMoboRamSlots(mobo) : null;
                    const usedSlots = getTotalRamModules(component);
                    const canAddMoreRam = !moboSlots || usedSlots === null || usedSlots < moboSlots;
                    slot.innerHTML = `
                        ${baseCard}
                        ${canAddMoreRam ? `
                            <button class="btn-add-more-storage" onclick="openComponentSelector(${categoryId}, '${category?.name || ''}')">
                                <div class="add-storage-icon">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <div class="add-storage-text">
                                    <span class="add-storage-title">Добавить ещё память</span>
                                    <span class="add-storage-subtitle">Комплект №2</span>
                                </div>
                                <div class="add-storage-plus"><i class="fas fa-plus"></i></div>
                            </button>
                        ` : ''}
                    `;
                } else {
                    slot.innerHTML = baseCard;
                }
            }
        } else {
            slot.innerHTML = `
                <button class="btn-add-component" onclick="openComponentSelector(${categoryId}, '${category?.name || ''}')">
                    <i class="fas fa-plus"></i>
                    Выбрать ${category?.name || 'компонент'}
                </button>
            `;
        }
    });
}

// Open component selector
async function openComponentSelector(categoryId, categoryName) {
    currentCategory = categoryId;
    const modal = document.getElementById('componentModal');
    const modalTitle = document.getElementById('modalTitle');
    const componentsList = document.getElementById('componentsList');
    
    modalTitle.textContent = `Выбор: ${categoryName}`;
    componentsList.innerHTML = '<div class="loading">Загрузка...</div>';
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Reset filters
    document.getElementById('componentSearch').value = '';
    document.getElementById('modalManufacturer').value = '';
    document.getElementById('modalMinPrice').value = '';
    document.getElementById('modalMaxPrice').value = '';
    document.getElementById('modalSort').value = 'price_asc';
    
    // Render category-specific filters
    renderCategorySpecificFilters(categoryId);
    
    // Fetch components
    try {
        const response = await fetch(`api/get_components.php?category=${categoryId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const text = await response.text();
        console.log('API Response:', text.substring(0, 500));
        const components = JSON.parse(text);
        
        if (components.error) {
            throw new Error(components.error);
        }
        
        if (components.length === 0) {
            componentsList.innerHTML = '<p style="text-align: center; color: var(--text-tertiary);">Нет доступных компонентов</p>';
            allModalComponents = [];
            filteredModalComponents = [];
            return;
        }
        
        allModalComponents = components;
        filteredModalComponents = [...components];
        
        // Populate manufacturers dropdown
        const manufacturers = [...new Set(components.map(c => c.manufacturer).filter(Boolean))].sort();
        const manufacturerSelect = document.getElementById('modalManufacturer');
        manufacturerSelect.innerHTML = '<option value="">Все производители</option>' + 
            manufacturers.map(m => `<option value="${m}">${m}</option>`).join('');

        filterModalComponents();
    } catch (error) {
        console.error('Error loading components:', error);
        componentsList.innerHTML = `<p style="text-align: center; color: var(--error);">Ошибка загрузки компонентов<br><small>${error.message}</small></p>`;
        allModalComponents = [];
        filteredModalComponents = [];
    }
}

// Render category-specific filters
function renderCategorySpecificFilters(categoryId) {
    const container = document.getElementById('categorySpecificFilters');
    if (!container) return;
    
    const config = categoryFilters[categoryId];
    if (!config || !config.filters) {
        container.innerHTML = '';
        container.style.display = 'none';
        return;
    }
    
    let html = '';
    config.filters.forEach(filter => {
        if (filter.type === 'range') {
            html += `
                <div class="modal-filter-group">
                    <label><i class="fas ${filter.icon}"></i> ${filter.label}</label>
                    <div class="price-inputs">
                        <input type="number" id="${filter.min}" placeholder="${filter.placeholder[0]}" onchange="filterModalComponents()">
                        <span>—</span>
                        <input type="number" id="${filter.max}" placeholder="${filter.placeholder[1]}" onchange="filterModalComponents()">
                    </div>
                </div>
            `;
        } else if (filter.type === 'checkbox') {
            html += `
                <div class="modal-filter-group">
                    <label><i class="fas ${filter.icon}"></i> ${filter.label}</label>
                    <div class="checkbox-group" id="${filter.name}_group">
                        ${filter.options.map(opt => `
                            <label class="checkbox-label">
                                <input type="checkbox" name="${filter.name}" value="${opt}" onchange="filterModalComponents()">
                                <span>${opt}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
        }
    });
    
    container.innerHTML = html;
    container.style.display = 'block';

    applyAutoFilters(categoryId);
}

// Render modal components
function renderModalComponents() {
    const componentsList = document.getElementById('componentsList');
    const resultsCount = document.getElementById('modalResultsCount');
    
    if (filteredModalComponents.length === 0) {
        componentsList.innerHTML = '<p style="text-align: center; color: var(--text-tertiary); padding: 40px;">Компоненты не найдены</p>';
        resultsCount.innerHTML = 'Найдено: <strong>0</strong> компонентов';
        return;
    }
    
    componentsList.innerHTML = filteredModalComponents.map(comp => {
        // Формируем дополнительную информацию в зависимости от категории
        let specsInfo = `${comp.manufacturer || 'Неизвестно'}`;
        
        // Для БП показываем мощность и сертификат
        if (currentCategory == 6 && comp.psu_wattage) {
            specsInfo += ` • ${comp.psu_wattage}Вт`;
            if (comp.psu_efficiency) {
                specsInfo += ` • ${comp.psu_efficiency}`;
            }
        } else if (currentCategory == 4) {
            const ramMeta = buildRamMeta(comp);
            if (ramMeta) {
                specsInfo = ramMeta;
            }
        } else if (currentCategory == 8) {
            const coolingMeta = buildCoolingMeta(comp);
            if (coolingMeta) {
                specsInfo = coolingMeta;
            }
        } else if (comp.power_consumption && comp.power_consumption > 0) {
            specsInfo += ` • ${comp.power_consumption}W`;
        }
        
        return `
            <div class="modal-component-item" onclick='selectComponent(${JSON.stringify(comp).replace(/'/g, "&apos;")})'>
                <div class="modal-component-info">
                    <h4>${comp.name}</h4>
                    <div class="modal-component-specs">${specsInfo}</div>
                </div>
                <div class="modal-component-price">${formatPrice(comp.price)}</div>
            </div>
        `;
    }).join('');
    
    resultsCount.innerHTML = `Найдено: <strong>${filteredModalComponents.length}</strong> из ${allModalComponents.length} компонентов`;
}

// Filter modal components
function filterModalComponents() {
    const search = document.getElementById('componentSearch').value.toLowerCase();
    const manufacturer = document.getElementById('modalManufacturer').value;
    const minPrice = parseFloat(document.getElementById('modalMinPrice').value) || 0;
    const maxPrice = parseFloat(document.getElementById('modalMaxPrice').value) || Infinity;
    
    // Get category-specific filters
    const config = categoryFilters[currentCategory];
    const categoryFiltersData = {};
    
    if (config && config.filters) {
        config.filters.forEach(filter => {
            if (filter.type === 'range') {
                const minEl = document.getElementById(filter.min);
                const maxEl = document.getElementById(filter.max);
                if (minEl && maxEl && (minEl.value || maxEl.value)) {
                    categoryFiltersData[filter.name] = {
                        min: parseFloat(minEl.value) || 0,
                        max: parseFloat(maxEl.value) || Infinity
                    };
                }
            } else if (filter.type === 'checkbox') {
                const checked = Array.from(document.querySelectorAll(`input[name="${filter.name}"]:checked`))
                    .map(cb => cb.value);
                if (checked.length > 0) {
                    categoryFiltersData[filter.name] = checked;
                }
            }
        });
    }
    
    filteredModalComponents = allModalComponents.filter(comp => {
        const matchesSearch = !search || comp.name.toLowerCase().includes(search);
        const matchesManufacturer = !manufacturer || comp.manufacturer === manufacturer;
        const matchesPrice = comp.price >= minPrice && comp.price <= maxPrice;
        
        // Parse specs if it's a string
        let specs = comp.specs;
        if (typeof specs === 'string') {
            try {
                specs = JSON.parse(specs);
            } catch (e) {
                specs = {};
            }
        }
        
        // Check category-specific filters
        let matchesCategoryFilters = true;
        for (const [key, value] of Object.entries(categoryFiltersData)) {
            if (value.min !== undefined && value.max !== undefined) {
                // Range filter - используем прямые поля из БД
                let compValue = 0;
                
                // Прямое сопоставление с полями БД
                if (key === 'cpu_cores' && comp.cpu_cores) {
                    compValue = parseFloat(comp.cpu_cores);
                } else if (key === 'cpu_threads' && comp.cpu_threads) {
                    compValue = parseFloat(comp.cpu_threads);
                } else if (key === 'cpu_base_clock' && comp.cpu_base_clock) {
                    compValue = parseFloat(comp.cpu_base_clock);
                } else if (key === 'gpu_memory' && comp.gpu_memory) {
                    compValue = parseFloat(comp.gpu_memory);
                } else if (key === 'gpu_tdp' && comp.power_consumption) {
                    // Для GPU TDP берем из power_consumption
                    compValue = parseFloat(comp.power_consumption);
                } else if (key === 'ram_capacity' && comp.ram_capacity) {
                    compValue = parseFloat(comp.ram_capacity);
                } else if (key === 'ram_speed' && comp.ram_speed) {
                    compValue = parseFloat(comp.ram_speed);
                } else if (key === 'storage_capacity' && comp.storage_capacity) {
                    compValue = parseFloat(comp.storage_capacity);
                } else if (key === 'psu_wattage' && comp.psu_wattage) {
                    compValue = parseFloat(comp.psu_wattage);
                } else if (key === 'case_gpu_length' && comp.case_max_gpu_length) {
                    compValue = parseFloat(comp.case_max_gpu_length);
                } else if (key === 'case_cooler_height' && comp.case_max_cooler_height) {
                    compValue = parseFloat(comp.case_max_cooler_height);
                } else if (key === 'cooler_tdp' && comp.cooler_tdp) {
                    compValue = parseFloat(comp.cooler_tdp);
                } else if (specs) {
                    // Fallback на specs если прямого поля нет
                    const specKeyMap = {
                        'cpu_cores': 'cores',
                        'cpu_threads': 'threads',
                        'cpu_base_clock': 'base_clock',
                        'gpu_memory': 'memory',
                        'gpu_tdp': 'tdp',
                        'ram_capacity': 'capacity',
                        'ram_speed': 'speed',
                        'storage_capacity': 'capacity',
                        'psu_wattage': 'wattage',
                        'case_gpu_length': 'max_gpu_length',
                        'cooler_tdp': 'tdp'
                    };
                    const specKey = specKeyMap[key] || key;
                    if (specs[specKey]) {
                        const specValue = String(specs[specKey]);
                        const match = specValue.match(/([\\d.]+)/);
                        if (match) {
                            compValue = parseFloat(match[1]);
                        }
                    }
                }
                
                if (compValue < value.min || (value.max !== Infinity && compValue > value.max)) {
                    matchesCategoryFilters = false;
                    break;
                }
            } else if (Array.isArray(value)) {
                // Checkbox filter - используем прямые поля из БД
                let compValue = null;
                
                if (key === 'mobo_form_factor' && comp.mobo_form_factor) {
                    compValue = comp.mobo_form_factor;
                } else if (key === 'mobo_chipset' && comp.mobo_chipset) {
                    compValue = comp.mobo_chipset;
                } else if (key === 'socket_type' && comp.socket_type) {
                    compValue = comp.socket_type;
                } else if (key === 'mobo_socket' && comp.socket_type) {
                    compValue = comp.socket_type;
                } else if (key === 'cpu_socket' && comp.socket_type) {
                    compValue = comp.socket_type;
                } else if (key === 'ram_type' && comp.ram_type) {
                    compValue = comp.ram_type;
                } else if (key === 'storage_type' && comp.storage_type) {
                    compValue = comp.storage_type;
                } else if (key === 'psu_efficiency' && comp.psu_efficiency) {
                    compValue = comp.psu_efficiency;
                } else if (key === 'case_form_factor' && comp.case_form_factor) {
                    compValue = comp.case_form_factor;
                } else if (key === 'cooler_type' && comp.cooler_type) {
                    compValue = comp.cooler_type;
                } else if (key === 'cooler_socket') {
                    const sockets = normalizeSocketList(comp.cooler_socket || (specs && (specs.socket || specs.sockets || specs.supported_sockets)));
                    if (sockets.length) {
                        const matchesAny = value.some(v => sockets.includes(normalizeSocket(v)));
                        if (!matchesAny) {
                            matchesCategoryFilters = false;
                        }
                        continue;
                    }
                } else if (key === 'gpu_memory_type' && comp.gpu_memory_type) {
                    compValue = comp.gpu_memory_type;
                } else if (specs) {
                    // Fallback на specs
                    const specKeyMap = {
                        'mobo_form_factor': 'form_factor',
                        'mobo_socket': 'socket',
                        'ram_type': 'type',
                        'storage_type': 'type',
                        'psu_efficiency': 'efficiency',
                        'case_form_factor': 'form_factor',
                        'cooler_type': 'type',
                        'cooler_socket': 'socket'
                    };
                    const specKey = specKeyMap[key] || key;
                    if (specs[specKey]) {
                        compValue = specs[specKey];
                    }
                }
                
                // Check if component value matches any of selected values
                if (compValue) {
                    const compValueUpper = String(compValue).toUpperCase();
                    const matchesAny = value.some(v => compValueUpper.includes(v.toUpperCase()));
                    if (!matchesAny) {
                        matchesCategoryFilters = false;
                        break;
                    }
                }
            }
        }
        
        return matchesSearch && matchesManufacturer && matchesPrice && matchesCategoryFilters;
    });
    
    sortModalComponents();
}

// Sort modal components
function sortModalComponents() {
    const sortBy = document.getElementById('modalSort').value;
    
    switch(sortBy) {
        case 'price_asc':
            filteredModalComponents.sort((a, b) => a.price - b.price);
            break;
        case 'price_desc':
            filteredModalComponents.sort((a, b) => b.price - a.price);
            break;
        case 'name_asc':
            filteredModalComponents.sort((a, b) => a.name.localeCompare(b.name, 'ru'));
            break;
        case 'name_desc':
            filteredModalComponents.sort((a, b) => b.name.localeCompare(a.name, 'ru'));
            break;
    }
    
    renderModalComponents();
}

// Close component selector
function closeComponentSelector() {
    const modal = document.getElementById('componentModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    currentCategory = null;
}

// Select component
function selectComponent(component) {
    if (!currentCategory) return;
    
    // Для БП выводим отладочную информацию
    if (currentCategory == 6) {
        console.log('Selecting PSU:', {
            name: component.name,
            psu_wattage: component.psu_wattage,
            power_consumption: component.power_consumption,
            psu_efficiency: component.psu_efficiency,
            full_component: component
        });
    }
    
    const componentData = {
        id: component.id,
        name: component.name,
        manufacturer: component.manufacturer,
        category_id: Number(currentCategory),
        price: parseFloat(component.price),
        power: parseInt(component.power_consumption) || 0,
        performance: parseInt(component.performance_score) || 0,
        specs: component.specs,
        // Дополнительные поля для проверки совместимости
        socket_type: component.socket_type,
        psu_wattage: parseInt(component.psu_wattage) || 0,
        psu_efficiency: component.psu_efficiency,
        gpu_memory: component.gpu_memory,
        mobo_form_factor: component.mobo_form_factor,
        mobo_ram_type: component.mobo_ram_type,
        mobo_max_ram_speed: parseInt(component.mobo_max_ram_speed) || null,
        mobo_ram_slots: parseInt(component.mobo_ram_slots) || null,
        mobo_m2_slots: parseInt(component.mobo_m2_slots) || null,
        ram_type: component.ram_type,
        ram_speed: component.ram_speed,
        ram_capacity: component.ram_capacity,
        storage_interface: component.storage_interface,
        case_max_gpu_length: component.case_max_gpu_length,
        case_max_cooler_height: component.case_max_cooler_height,
        cooler_height: component.cooler_height,
        cooler_socket: component.cooler_socket,
        pcie_version: component.pcie_version
    };
    
    // Для накопителей (category 5) поддерживаем множественный выбор
    if (currentCategory == 5) {
        if (!Array.isArray(currentBuild[currentCategory])) {
            currentBuild[currentCategory] = [];
        }
        currentBuild[currentCategory].push(componentData);
    } else if (currentCategory == 4) {
        if (!Array.isArray(currentBuild[currentCategory])) {
            currentBuild[currentCategory] = currentBuild[currentCategory]
                ? [currentBuild[currentCategory]]
                : [];
        }
        if (currentBuild[currentCategory].length >= 2) {
            showToast('Можно добавить максимум 2 комплекта памяти', 'warning');
            return;
        }
        currentBuild[currentCategory].push(componentData);
    } else {
        currentBuild[currentCategory] = componentData;
    }
    
    localStorage.setItem('currentBuild', JSON.stringify(currentBuild));
    window.dispatchEvent(new CustomEvent('build:updated', { detail: currentBuild }));
    
    renderBuild();
    updateSummary();
    checkCompatibility();
    calculateFPS();
    closeComponentSelector();
    
    showToast(`${component.name} добавлен в сборку`, 'success');
}

// Remove component
function removeComponent(categoryId, storageIndex = null) {
    // Для накопителей и RAM удаляем конкретный элемент из массива
    if ((categoryId == 5 || categoryId == 4) && storageIndex !== null) {
        if (Array.isArray(currentBuild[categoryId])) {
            currentBuild[categoryId].splice(storageIndex, 1);
            if (currentBuild[categoryId].length === 0) {
                delete currentBuild[categoryId];
            }
        }
    } else {
        delete currentBuild[categoryId];
    }
    
    localStorage.setItem('currentBuild', JSON.stringify(currentBuild));
    window.dispatchEvent(new CustomEvent('build:updated', { detail: currentBuild }));

    renderBuild();
    updateSummary();
    checkCompatibility();
    calculateFPS();
    showToast('Компонент удален', 'info');
}

// Update summary
function updateSummary() {
    let totalComponents = 0;
    let totalPrice = 0;
    let totalPower = 0;
    
    Object.values(currentBuild).forEach(item => {
        if (Array.isArray(item)) {
            // Для массивов (накопители)
            totalComponents += item.length;
            item.forEach(component => {
                totalPrice += normalizeNumber(component?.price);
                totalPower += normalizeNumber(component?.power);
            });
        } else {
            // Для одиночных компонентов
            totalComponents += 1;
            totalPrice += normalizeNumber(item?.price);
            totalPower += normalizeNumber(item?.power);
        }
    });
    
    document.getElementById('totalComponents').textContent = totalComponents;
    document.getElementById('totalPrice').textContent = formatPrice(totalPrice);
    document.getElementById('totalPower').textContent = totalPower + ' Вт';
    
    // Show checkout button if build has components
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.style.display = totalComponents > 0 ? 'flex' : 'none';
    }
    
    updateBuildCounter();
}

// Proceed to checkout
function proceedToCheckout() {
    const components = Object.values(currentBuild);
    if (components.length === 0) {
        alert('Добавьте компоненты в сборку перед оформлением заказа');
        return;
    }
    
    // Save current build to localStorage for checkout page
    localStorage.setItem('checkoutBuild', JSON.stringify(currentBuild));
    
    // Redirect to checkout page
    window.location.href = 'checkout.php';
}

// Check compatibility
function checkCompatibility() {
    const status = document.getElementById('compatibilityStatus');
    const components = Object.values(currentBuild);
    
    if (components.length === 0) {
        status.className = 'compatibility-status';
        status.innerHTML = '<i class="fas fa-circle-info"></i><span>Добавьте компоненты для проверки</span>';
        return;
    }
    
    // Get components by category
    const cpu = currentBuild[1];        // Category 1 = CPU
    const gpu = currentBuild[2];        // Category 2 = GPU
    const motherboard = currentBuild[3]; // Category 3 = Motherboard
    const ramEntry = currentBuild[4];        // Category 4 = RAM
    const storage = currentBuild[5];    // Category 5 = Storage
    const psu = currentBuild[6];        // Category 6 = PSU
    const pcCase = currentBuild[7];     // Category 7 = Case
    const cooling = currentBuild[8];    // Category 8 = Cooling
    
    const issues = [];
    const warnings = [];
    const tips = [];
    const moboRamSlots = motherboard ? getMoboRamSlots(motherboard) : null;
    const moboM2Slots = motherboard ? getMoboM2Slots(motherboard) : null;
    const moboMaxRamSpeed = motherboard ? getMoboMaxRamSpeed(motherboard) : null;
    const moboMaxRamCapacity = motherboard ? extractMaxRAMCapacity(motherboard.specs) : null;

    const ramKits = Array.isArray(ramEntry) ? ramEntry : (ramEntry ? [ramEntry] : []);
    const ramTypes = new Set();
    const ramSpeeds = [];
    const ramCls = new Set();
    let totalRamCapacity = 0;
    let totalRamModules = 0;
    ramKits.forEach((kit) => {
        const specs = parseComponentSpecs(kit.specs);
        const type = normalizeRAMType(kit.ram_type) || extractRAMType(kit.name, kit.specs);
        if (type) ramTypes.add(type);

        const speedRaw = kit.ram_speed || specs.speed || parseRamSpeed(kit.name);
        const speedMatch = speedRaw ? String(speedRaw).match(/(\d+)/) : null;
        const speed = speedMatch ? parseInt(speedMatch[1], 10) : (speedRaw ? parseInt(speedRaw, 10) : null);
        if (speed) ramSpeeds.push(speed);

        const clRaw = specs.cas_latency || specs.latency || specs.cl;
        if (clRaw) {
            const clValue = String(clRaw).toUpperCase().replace(/\s+/g, '');
            ramCls.add(clValue.startsWith('CL') ? clValue : `CL${clValue}`);
        }

        const capacityRaw = kit.ram_capacity || specs.capacity || parseRamCapacity(kit.name);
        const capMatch = capacityRaw ? String(capacityRaw).match(/(\d+)/) : null;
        const capacity = capMatch ? parseInt(capMatch[1], 10) : (capacityRaw ? parseInt(capacityRaw, 10) : null);
        if (capacity) totalRamCapacity += capacity;

        const modules = extractRamModuleCount(kit.name, kit.specs);
        if (modules) {
            totalRamModules += modules;
        } else if (capacity) {
            totalRamModules += capacity >= 16 ? 2 : 1;
        }
    });
    
    // 1. CPU + Motherboard Socket Compatibility
    if (cpu && motherboard) {
        const cpuSocket = normalizeSocket(cpu.socket_type) || extractSocket(cpu.name, cpu.specs);
        const moboSocket = normalizeSocket(motherboard.socket_type) || extractSocket(motherboard.name, motherboard.specs);
        
        if (cpuSocket && moboSocket && cpuSocket !== moboSocket) {
            issues.push(`❌ Несовместимость сокета: CPU ${cpuSocket} ≠ Материнская плата ${moboSocket}`);
        }
    }
    
    // 2. RAM + Motherboard DDR Compatibility
    if (ramKits.length && motherboard) {
        const ramType = ramTypes.size ? Array.from(ramTypes)[0] : null;
        const moboRAMType = normalizeRAMType(motherboard.mobo_ram_type) || extractRAMType(motherboard.name, motherboard.specs);

        if (ramTypes.size > 1) {
            issues.push('❌ В сборке разные типы памяти (DDR4/DDR5) — такие комплекты не совместимы');
        } else if (ramType && moboRAMType && ramType !== moboRAMType) {
            issues.push(`❌ Несовместимость памяти: RAM ${ramType} ≠ Материнская плата поддерживает ${moboRAMType}`);
        }

        if (totalRamModules && moboRamSlots && totalRamModules > moboRamSlots) {
            issues.push(`❌ Установлено ${totalRamModules} модулей, а материнская плата имеет только ${moboRamSlots} слота`);
        }
        if (totalRamModules && !moboRamSlots) {
            warnings.push('⚠️ Не удалось определить количество слотов RAM — проверьте спецификацию материнской платы');
        }

        if (totalRamCapacity && moboMaxRamCapacity && totalRamCapacity > moboMaxRamCapacity) {
            issues.push(`❌ Объём RAM (${totalRamCapacity} ГБ) превышает поддерживаемые ${moboMaxRamCapacity} ГБ на материнской плате`);
        }

        if (ramSpeeds.length > 1) {
            const minSpeed = Math.min(...ramSpeeds);
            const maxSpeed = Math.max(...ramSpeeds);
            if (minSpeed !== maxSpeed) {
                warnings.push(`⚠️ Разные частоты памяти (${minSpeed}–${maxSpeed} МГц) — модули будут работать на минимальной`);
            }
        }

        if (ramCls.size > 1) {
            warnings.push('⚠️ Разные тайминги CL — память будет работать по более медленным значениям');
        }
    }
    
    // 3. Cooling + CPU Socket Compatibility
    if (cooling && cpu) {
        const cpuSocket = normalizeSocket(cpu.socket_type) || extractSocket(cpu.name, cpu.specs);
        const coolingCompatibility = normalizeSocketList(cooling.cooler_socket) || extractCoolingSocket(cooling.name, cooling.specs);
        
        if (cpuSocket && coolingCompatibility && !coolingCompatibility.includes(cpuSocket)) {
            issues.push(`❌ Кулер не совместим с сокетом ${cpuSocket}`);
        }
    }
    
    // 4. GPU + Case Size Compatibility
    if (gpu && pcCase) {
        const gpuLength = extractGPULength(gpu.name, gpu.specs);
        const caseMaxGPU = extractCaseMaxGPU(pcCase.name, pcCase.specs);
        
        if (gpuLength && caseMaxGPU && gpuLength > caseMaxGPU) {
            issues.push(`❌ Видеокарта (${gpuLength}мм) не поместится в корпус (макс. ${caseMaxGPU}мм)`);
        }
    }
    
    // 5. Cooling Height + Case Compatibility
    if (cooling && pcCase) {
        const coolerHeight = extractCoolerHeight(cooling.name, cooling.specs);
        const caseMaxCooler = extractCaseMaxCooler(pcCase.name, pcCase.specs);
        
        if (coolerHeight && caseMaxCooler && coolerHeight > caseMaxCooler) {
            issues.push(`❌ Кулер (${coolerHeight}мм) не поместится в корпус (макс. ${caseMaxCooler}мм)`);
        }
    }
    
    // 6. PSU Power Check
    const flatComponents = flattenBuildComponents(currentBuild);
    const totalPower = flatComponents.reduce((sum, c) => sum + (parseInt(c?.power) || 0), 0);
    if (psu) {
        // Используем прямое поле psu_wattage
        let psuPower = parseInt(psu.psu_wattage);
        
        // Если не нашли в прямом поле, пробуем извлечь
        if (!psuPower || psuPower === 0 || isNaN(psuPower)) {
            psuPower = extractPSUWattage(psu);
        }
        
        const recommendedPower = Math.ceil(totalPower * 1.2); // +20% запас
        
        if (!psuPower || psuPower === 0) {
            warnings.push(`⚠️ Не удалось определить мощность БП`);
        } else if (psuPower < totalPower) {
            issues.push(`❌ Недостаточная мощность БП: ${psuPower}Вт < ${totalPower}Вт (потребление)`);
        } else if (psuPower < recommendedPower) {
            warnings.push(`⚠️ Рекомендуется БП мощнее: ${psuPower}Вт < ${recommendedPower}Вт (рекомендуемая)`);
        } else {
            const efficiency = psu.psu_efficiency;
            const powerReserve = ((psuPower - totalPower) / totalPower * 100).toFixed(0);
            if (psuPower > totalPower * 2) {
                warnings.push(`⚠️ БП с большим запасом (${psuPower}Вт при потреблении ${totalPower}Вт) — можно рассмотреть более бюджетную модель`);
            }
            if (efficiency) {
                if (efficiency.includes('Platinum') || efficiency.includes('Titanium')) {
                    tips.push(`ℹ️ БП ${psuPower}Вт ${efficiency} (запас ${powerReserve}%) — высокая эффективность и надежность`);
                } else if (efficiency.includes('Gold')) {
                    tips.push(`ℹ️ БП ${psuPower}Вт ${efficiency} (запас ${powerReserve}%) — хороший баланс цены и эффективности`);
                } else if (efficiency.includes('Bronze')) {
                    tips.push(`ℹ️ БП ${psuPower}Вт ${efficiency} (запас ${powerReserve}%) — для лучшей эффективности можно рассмотреть Gold или выше`);
                }
            }
        }
    }
    
    // 7. PCIe Version Warning (GPU + Motherboard)
    if (gpu && motherboard) {
        const gpuPCIe = parseFloat(gpu.pcie_version) || extractPCIeVersion(gpu.name, gpu.specs);
        const moboPCIe = parseFloat(motherboard.pcie_version) || extractPCIeVersion(motherboard.name, motherboard.specs);
        
        if (gpuPCIe && moboPCIe) {
            if (gpuPCIe > moboPCIe) {
                const perfLoss = (gpuPCIe - moboPCIe) * 2.5; // примерно 2-3% на версию
                warnings.push(`⚠️ Видеокарта PCIe ${gpuPCIe}.0, материнская плата PCIe ${moboPCIe}.0 — возможна потеря ~${Math.round(perfLoss)}% производительности`);
            }
        }
    }
    
    // 8. Motherboard Form Factor + Case Compatibility
    if (motherboard && pcCase) {
        const moboFormFactor = normalizeFormFactorValue(motherboard.mobo_form_factor) 
            || normalizeFormFactorValue(extractFormFactor(motherboard.name, motherboard.specs));
        const caseFormFactorsRaw = pcCase.case_form_factor || extractCaseFormFactors(pcCase.name, pcCase.specs);
        const caseFormFactors = expandCaseFormFactors(normalizeFormFactorList(caseFormFactorsRaw));
        
        if (moboFormFactor && caseFormFactors.length && !caseFormFactors.includes(moboFormFactor)) {
            issues.push(`❌ Материнская плата ${moboFormFactor} не поместится в корпус`);
        }
    }
    
    // 9. RAM Speed Warning
    if (ramKits.length && motherboard) {
        const ramSpeed = ramSpeeds.length ? Math.min(...ramSpeeds) : null;
        if (ramSpeed && moboMaxRamSpeed && ramSpeed > moboMaxRamSpeed) {
            warnings.push(`⚠️ RAM ${ramSpeed} МГц будет понижена до ${moboMaxRamSpeed} МГц — ограничение материнской платы`);
        } else if (ramSpeed && !moboMaxRamSpeed) {
            warnings.push(`ℹ️ Не удалось определить максимальную частоту памяти для материнской платы — проверьте спецификацию вручную`);
        }
    }
    
    // 10. Storage Interface Warning
    if (storage && motherboard) {
        const storageInterface = (extractStorageInterface(storage.name, storage.specs) || storage.storage_interface || '').toUpperCase();
        const moboSupportsNVMe = (typeof moboM2Slots === 'number')
            ? moboM2Slots > 0
            : checkNVMeSupport(motherboard.name, motherboard.specs);
        
        if (storageInterface === 'NVME' && !moboSupportsNVMe) {
            issues.push('❌ Материнская плата не имеет слотов M.2 для NVMe накопителей');
        } else if (storageInterface === 'NVME' && typeof moboM2Slots === 'number') {
            if (moboM2Slots === 1) {
                warnings.push('⚠️ На материнской плате всего 1 слот M.2 — дополнительных NVMe накопителей установить не получится');
            }
        }
    }
    
    // 11. Build Balance Check (CPU vs GPU)
    if (cpu && gpu && cpu.performance && gpu.performance) {
        const cpuPerf = parseInt(cpu.performance);
        const gpuPerf = parseInt(gpu.performance);
        const perfRatio = cpuPerf / gpuPerf;
        
        if (perfRatio < 0.6) {
            warnings.push(`⚠️ CPU может ограничивать производительность GPU — рассмотрите более мощный процессор`);
        } else if (perfRatio > 1.5) {
            tips.push(`ℹ️ CPU заметно мощнее GPU — при желании можно усилить видеокарту для баланса`);
        }
    }
    
    // 12. RAM Capacity Recommendation
    if (ramKits.length) {
        const ramCapacity = totalRamCapacity;
        if (ramCapacity && ramCapacity < 16) {
            warnings.push(`⚠️ Для современных игр рекомендуется минимум 16 ГБ оперативной памяти`);
        } else if (ramCapacity >= 64) {
            tips.push(`ℹ️ Большой объём памяти — подойдет для тяжёлых задач и многозадачности`);
        }
    }
    
    // Display results
    if (issues.length > 0) {
        status.className = 'compatibility-status incompatible';
        status.innerHTML = `
            <div class="compatibility-header">
                <i class="fas fa-circle-xmark"></i>
                <span>Обнаружены проблемы совместимости</span>
            </div>
            <ul class="compatibility-list compatibility-list--issues">
                ${issues.map(issue => `
                    <li>
                        <span class="compatibility-emoji">🛑</span>
                        <span>${stripLeadingEmoji(issue)}</span>
                    </li>
                `).join('')}
            </ul>
        `;
    } else if (warnings.length > 0 || tips.length > 0) {
        const hasWarnings = warnings.length > 0;
        status.className = `compatibility-status ${hasWarnings ? 'warning' : 'compatible'}`;
        status.innerHTML = `
            <div class="compatibility-header">
                <i class="fas ${hasWarnings ? 'fa-triangle-exclamation' : 'fa-circle-check'}"></i>
                <span>${hasWarnings ? 'Сборка совместима, но есть предупреждения' : 'Сборка совместима — есть полезные замечания'}</span>
            </div>
            ${hasWarnings ? `
                <div class="compatibility-section-title">Предупреждения</div>
                <ul class="compatibility-list compatibility-list--warnings">
                    ${warnings.map(warning => `
                        <li>
                            <span class="compatibility-emoji">⚠️</span>
                            <span>${stripLeadingEmoji(warning)}</span>
                        </li>
                    `).join('')}
                </ul>
            ` : ''}
            ${tips.length > 0 ? `
                <div class="compatibility-section-title">Полезные замечания</div>
                <ul class="compatibility-list compatibility-list--tips">
                    ${tips.map(tip => `
                        <li>
                            <span class="compatibility-emoji">💡</span>
                            <span>${stripLeadingEmoji(tip)}</span>
                        </li>
                    `).join('')}
                </ul>
            ` : ''}
        `;
    } else {
        status.className = 'compatibility-status compatible';
        status.innerHTML = `
            <div class="compatibility-header">
                <i class="fas fa-circle-check"></i>
                <span>✨ Все компоненты полностью совместимы!</span>
            </div>
        `;
    }
}

// Helper functions for extracting specs
function extractSocket(name, specs) {
    // Common sockets
    const sockets = ['AM4', 'AM5', 'LGA1700', 'LGA1200', 'LGA1151'];
    
    // Parse specs if it's a string
    let specsObj = specs;
    if (typeof specs === 'string') {
        try {
            specsObj = JSON.parse(specs);
        } catch (e) {
            specsObj = {};
        }
    }
    
    const text = (name + ' ' + JSON.stringify(specsObj)).toUpperCase();
    for (const socket of sockets) {
        if (text.includes(socket)) return socket;
    }
    
    // Try to extract from specs
    if (specsObj && specsObj.socket) return specsObj.socket;
    if (specsObj && specsObj.cpu_socket) return specsObj.cpu_socket;
    if (specsObj && specsObj.mobo_socket) return specsObj.mobo_socket;
    
    return null;
}

function parseSpecs(specs) {
    if (!specs) return {};
    if (typeof specs === 'object') return specs;
    try {
        return JSON.parse(specs);
    } catch (e) {
        return {};
    }
}

function extractRAMType(name, specs) {
    const specsObj = parseSpecs(specs);
    
    const text = (name + ' ' + JSON.stringify(specsObj)).toUpperCase();
    
    if (text.includes('DDR5')) return 'DDR5';
    if (text.includes('DDR4')) return 'DDR4';
    if (text.includes('DDR3')) return 'DDR3';
    
    if (specsObj && specsObj.type) return specsObj.type;
    if (specsObj && specsObj.ram_type) return specsObj.ram_type;
    if (specsObj && specsObj.memory_type) return specsObj.memory_type;
    
    return null;
}

function extractCoolingSocket(name, specs) {
    const specsObj = parseSpecs(specs);
    
    const text = (name + ' ' + JSON.stringify(specsObj)).toUpperCase();
    const sockets = [];
    
    if (text.includes('AM4')) sockets.push('AM4');
    if (text.includes('AM5')) sockets.push('AM5');
    if (text.includes('LGA1700')) sockets.push('LGA1700');
    if (text.includes('LGA1200')) sockets.push('LGA1200');
    if (text.includes('LGA1151')) sockets.push('LGA1151');
    
    if (specsObj && specsObj.socket) {
        const socketStr = String(specsObj.socket);
        return socketStr.split(',').map(s => s.trim());
    }
    
    return sockets.length > 0 ? sockets : null;
}

function extractGPULength(name, specs) {
    const match = name.match(/(\d{3})mm/i);
    if (match) return parseInt(match[1]);
    
    if (specs && specs.gpu_length) return parseInt(specs.gpu_length);
    if (specs && specs.length) return parseInt(specs.length);
    
    return null;
}

function extractCaseMaxGPU(name, specs) {
    const match = name.match(/GPU[:\s]*(\d{3})/i);
    if (match) return parseInt(match[1]);
    
    if (specs && specs.case_gpu_length) return parseInt(specs.case_gpu_length);
    if (specs && specs.max_gpu_length) return parseInt(specs.max_gpu_length);
    
    return null;
}

function extractCoolerHeight(name, specs) {
    const match = name.match(/(\d{2,3})mm/i);
    if (match) return parseInt(match[1]);
    
    if (specs && specs.cooler_height) return parseInt(specs.cooler_height);
    if (specs && specs.height) return parseInt(specs.height);
    
    return null;
}

function extractCaseMaxCooler(name, specs) {
    const match = name.match(/Cooler[:\s]*(\d{2,3})/i);
    if (match) return parseInt(match[1]);
    
    if (specs && specs.case_cooler_height) return parseInt(specs.case_cooler_height);
    if (specs && specs.max_cooler_height) return parseInt(specs.max_cooler_height);
    
    return null;
}

function normalizeSocket(value) {
    if (!value) return null;
    const text = Array.isArray(value) ? value.join(' ').toUpperCase() : String(value).toUpperCase();
    const knownSockets = ['LGA1851', 'LGA1700', 'LGA1200', 'LGA1151', 'AM5', 'AM4', 'TR4', 'STRX4'];
    for (const socket of knownSockets) {
        if (text.includes(socket)) {
            return socket;
        }
    }
    // fallback: return first token (letters+digits)
    const match = text.match(/([A-Z]+\d+)/i);
    return match ? match[1].toUpperCase() : text.trim();
}

function normalizeSocketList(value) {
    if (!value) return [];
    if (Array.isArray(value)) {
        return value.map(v => normalizeSocket(v)).filter(Boolean);
    }
    return String(value)
        .split(/[,\s]+/)
        .map(v => normalizeSocket(v))
        .filter(Boolean);
}

function normalizeRAMType(value) {
    if (!value) return null;
    const upper = String(value).toUpperCase();
    if (upper.includes('DDR5')) return 'DDR5';
    if (upper.includes('DDR4')) return 'DDR4';
    if (upper.includes('DDR3')) return 'DDR3';
    return upper.trim();
}

function normalizeFormFactorValue(value) {
    if (!value) return null;
    const upper = String(value).toUpperCase();
    if (upper.includes('E-ATX')) return 'E-ATX';
    if (upper.includes('MICRO') || upper.includes('M-ATX') || upper.includes('MATX')) return 'MICRO-ATX';
    if (upper.includes('MINI')) return 'MINI-ITX';
    if (upper.includes('ATX')) return 'ATX';
    return upper.trim();
}

function normalizeFormFactorList(value) {
    if (!value) return [];
    if (Array.isArray(value)) {
        return value.map(normalizeFormFactorValue).filter(Boolean);
    }
    return String(value)
        .split(/[,/]+/)
        .map(normalizeFormFactorValue)
        .filter(Boolean);
}

function expandCaseFormFactors(factors) {
    const supported = new Set();
    factors.forEach(factor => {
        switch (factor) {
            case 'E-ATX':
                supported.add('E-ATX');
                supported.add('ATX');
                supported.add('MICRO-ATX');
                supported.add('MINI-ITX');
                break;
            case 'ATX':
                supported.add('ATX');
                supported.add('MICRO-ATX');
                supported.add('MINI-ITX');
                break;
            case 'MICRO-ATX':
                supported.add('MICRO-ATX');
                supported.add('MINI-ITX');
                break;
            default:
                supported.add(factor);
        }
    });
    return Array.from(supported);
}

function extractPSUWattage(component) {
    if (!component) return null;
    
    // Сначала проверяем прямое поле psu_wattage
    if (component.psu_wattage && parseInt(component.psu_wattage) > 0) {
        return parseInt(component.psu_wattage);
    }
    
    // Затем пробуем извлечь из названия (например "1000W" или "1000 Вт")
    if (!component.name) return null;
    const nameMatch = component.name.match(/(\d{3,4})\s*[WВ]/i);
    if (nameMatch) {
        return parseInt(nameMatch[1]);
    }
    
    // Пробуем из specs
    let specsObj = component.specs;
    if (typeof specsObj === 'string') {
        try { 
            specsObj = JSON.parse(specsObj); 
        } catch (e) { 
            specsObj = {}; 
        }
    }
    
    if (specsObj && specsObj.wattage) {
        return parseInt(specsObj.wattage);
    }
    if (specsObj && specsObj.psu_wattage) {
        return parseInt(specsObj.psu_wattage);
    }
    if (specsObj && specsObj.power) {
        return parseInt(specsObj.power);
    }
    
    return null;
}

function extractPCIeVersion(name, specs) {
    const text = (name + ' ' + JSON.stringify(specs)).toUpperCase();
    
    if (text.includes('PCIE 5.0') || text.includes('PCIe 5.0')) return 5;
    if (text.includes('PCIE 4.0') || text.includes('PCIe 4.0')) return 4;
    if (text.includes('PCIE 3.0') || text.includes('PCIe 3.0')) return 3;
    
    if (specs && specs.pcie_version) return parseInt(specs.pcie_version);
    if (specs && specs.pci_express) return parseInt(specs.pci_express);
    
    return null;
}

function extractFormFactor(name, specs) {
    let specsObj = specs;
    if (typeof specs === 'string') {
        try { specsObj = JSON.parse(specs); } catch (e) { specsObj = {}; }
    }
    
    const text = (name + ' ' + JSON.stringify(specsObj)).toUpperCase();
    
    if (text.includes('E-ATX')) return 'E-ATX';
    if (text.includes('MICRO-ATX') || text.includes('MICRO ATX')) return 'Micro-ATX';
    if (text.includes('MINI-ITX') || text.includes('MINI ITX')) return 'Mini-ITX';
    if (text.includes('ATX')) return 'ATX';
    
    if (specsObj && specsObj.form_factor) return specsObj.form_factor;
    if (specsObj && specsObj.mobo_form_factor) return specsObj.mobo_form_factor;
    
    return null;
}

function extractCaseFormFactors(name, specs) {
    const text = (name + ' ' + JSON.stringify(specs)).toUpperCase();
    const factors = [];
    
    if (text.includes('E-ATX')) factors.push('E-ATX');
    if (text.includes('ATX')) factors.push('ATX', 'Micro-ATX', 'Mini-ITX');
    if (text.includes('MICRO-ATX')) factors.push('Micro-ATX', 'Mini-ITX');
    if (text.includes('MINI-ITX')) factors.push('Mini-ITX');
    
    if (specs && specs.case_form_factor) return specs.case_form_factor.split(',').map(s => s.trim());
    if (specs && specs.supported_form_factors) return specs.supported_form_factors.split(',').map(s => s.trim());
    
    return factors.length > 0 ? factors : null;
}

function extractRAMSpeed(name, specs) {
    const match = name.match(/(\d{4,5})\s*MHz/i);
    if (match) return parseInt(match[1]);
    
    if (specs && specs.ram_speed) return parseInt(specs.ram_speed);
    if (specs && specs.frequency) return parseInt(specs.frequency);
    
    return null;
}

function extractMaxRAMSpeed(name, specs) {
    const match = name.match(/DDR[45][:\s]*(\d{4,5})/i);
    if (match) return parseInt(match[1]);
    
    if (specs && specs.max_ram_speed) return parseInt(specs.max_ram_speed);
    if (specs && specs.max_memory_speed) return parseInt(specs.max_memory_speed);
    
    return null;
}

function extractStorageInterface(name, specs) {
    const text = (name + ' ' + JSON.stringify(specs)).toUpperCase();
    
    if (text.includes('NVME') || text.includes('M.2')) return 'NVMe';
    if (text.includes('SATA')) return 'SATA';
    
    if (specs?.storage_interface) return specs.storage_interface;
    if (specs?.interface) return specs.interface;
    
    return null;
}

function checkNVMeSupport(name, specs) {
    const text = (name + ' ' + JSON.stringify(specs)).toUpperCase();
    
    if (text.includes('M.2') || text.includes('NVME')) return true;
    
    if (specs?.m2_slots) return parseInt(specs.m2_slots) > 0;
    if (specs?.nvme_support) return specs.nvme_support === true || specs.nvme_support === 'yes';
    
    return false;
}

// Calculate FPS
async function calculateFPS() {
    const gameId = document.getElementById('gameSelect').value;
    const gpu = currentBuild[2]; // Category 2 = GPU
    const cpu = currentBuild[1]; // Category 1 = CPU
    
    const fpsResult = document.getElementById('fpsResult');
    const fpsPlaceholder = document.getElementById('fpsPlaceholder');
    
    if (!gameId || !gpu) {
        fpsResult.style.display = 'none';
        fpsPlaceholder.style.display = 'block';
        return;
    }
    
    try {
        const response = await fetch(`api/calculate_fps.php?component_id=${gpu.id}&game_id=${gameId}&resolution=${selectedResolution}`);
        const data = await response.json();
        
        if (data.avg_fps) {
            const qualityPenalty = getQualityPenalty(selectedQuality, gameId);
            const cpuFactor = getCpuBottleneckFactor(cpu, gpu, gameId);
            const ramFactor = getRamPenalty();
            const coolingStability = getCoolingStability();
            const storageFactor = getStorageBonus();
            const psuFactor = getPsuEfficiency();
            const gameTuning = getGameTuning(gameId);
            
            const combinedMultiplier = qualityPenalty * cpuFactor * ramFactor * storageFactor * psuFactor * gameTuning.multiplier;
            const stabilityImpact = coolingStability;

            let avgFps = Math.round(data.avg_fps * combinedMultiplier * stabilityImpact);
            const spread = getFpsSpread(gameId);
            let minFps = Math.round(data.min_fps * combinedMultiplier * (stabilityImpact * spread.min));
            let maxFps = Math.round(data.max_fps * combinedMultiplier * (stabilityImpact * spread.max));

            if (gameTuning.softCap) {
                avgFps = applySoftCap(avgFps, gameTuning.softCap.value, gameTuning.softCap.softness);
                minFps = applySoftCap(minFps, gameTuning.softCap.value, gameTuning.softCap.softness + 0.05);
                maxFps = applySoftCap(maxFps, gameTuning.softCap.value * 1.15, gameTuning.softCap.softness + 0.1);
            }
            const latencyLatency = getLatencySummary(cpu, gpu);
            const perfLabel = buildPerformanceLabel(avgFps);
            const bottleneckLabel = buildBottleneckLabel(cpuFactor, ramFactor, coolingStability);
            const qualityTag = qualityLabels[selectedQuality] || 'Ультра';
            let gameLabel = 'Игра';
            if (Array.isArray(games)) {
                const game = games.find(g => String(g.id) === String(gameId));
                if (game) {
                    gameLabel = game.name || gameLabel;
                }
            }
            const meta = `\n${gameLabel} / ${resolutionLabels[selectedResolution]} / ${qualityTag}`;
            const fpsMetaEl = document.getElementById('fpsMeta');
            if (fpsMetaEl) fpsMetaEl.textContent = meta.trim();
            const fpsLatencyEl = document.getElementById('fpsLatency');
            if (fpsLatencyEl) fpsLatencyEl.textContent = latencyLatency;
            const fpsStatusEl = document.getElementById('fpsStatus');
            if (fpsStatusEl) fpsStatusEl.textContent = bottleneckLabel;
            
            document.getElementById('fpsValue').textContent = avgFps;
            document.getElementById('minFps').textContent = minFps;
            document.getElementById('maxFps').textContent = maxFps;

            const rating = document.getElementById('fpsRating');
            rating.className = `fps-rating ${perfLabel.className}`;
            rating.innerHTML = perfLabel.text + (data.estimated ? ' <span style="font-size: 11px; opacity: 0.7;">(примерно)</span>' : '');

            fpsResult.style.display = 'block';
            fpsPlaceholder.style.display = 'none';
        } else {
            fpsResult.style.display = 'none';
            fpsPlaceholder.style.display = 'block';
        }
    } catch (error) {
        console.error('FPS calculation error:', error);
        fpsResult.style.display = 'none';
        fpsPlaceholder.style.display = 'block';
    }
}

function getQualityPenalty(quality, gameId) {
    const tuning = getGameTuning(gameId);
    if (tuning.quality && tuning.quality[quality]) {
        return tuning.quality[quality];
    }

    switch (quality) {
        case 'low':
            return 1.35;
        case 'medium':
            return 1.15;
        case 'high':
            return 0.95;
        case 'ultra':
        default:
            return 0.80;
    }
}

function stripLeadingEmoji(text) {
    return String(text || '').replace(/^[\p{Extended_Pictographic}\uFE0F\u200D]+\s*/u, '');
}

function applyAutoFilters(categoryId) {
    const cpu = currentBuild[1];
    if (categoryId == 3 && cpu) {
        const cpuSocket = normalizeSocket(cpu.socket_type) || extractSocket(cpu.name, cpu.specs);
        if (cpuSocket) {
            applyAutoCheckbox('socket_type', cpuSocket);
        }
    }

    const mobo = currentBuild[3];
    if (!mobo) return;

    if (categoryId == 4) {
        const moboRamType = normalizeRAMType(mobo.mobo_ram_type) || extractRAMType(mobo.name, mobo.specs);
        if (moboRamType) {
            applyAutoCheckbox('ram_type', moboRamType);
        }
    } else if (categoryId == 1) {
        const moboSocket = normalizeSocket(mobo.socket_type) || extractSocket(mobo.name, mobo.specs);
        if (moboSocket) {
            applyAutoCheckbox('cpu_socket', moboSocket);
        }
    } else if (categoryId == 8) {
        const moboSocket = normalizeSocket(mobo.socket_type) || extractSocket(mobo.name, mobo.specs);
        if (moboSocket) {
            applyAutoCheckbox('cooler_socket', moboSocket);
        }
    }
}

function applyAutoCheckbox(filterName, desiredValue) {
    const inputs = Array.from(document.querySelectorAll(`input[name="${filterName}"]`));
    if (!inputs.length || !desiredValue) return;
    const alreadySelected = inputs.some(input => input.checked);
    if (alreadySelected) return;

    const normalizedDesired = String(desiredValue).toUpperCase();
    const target = inputs.find(input => String(input.value).toUpperCase() === normalizedDesired);
    if (target) {
        target.checked = true;
    }
}

function getCpuBottleneckFactor(cpu, gpu, gameId) {
    if (cpu && cpu.performance && gpu && gpu.performance) {
        const ratio = cpu.performance / gpu.performance;
        // More realistic CPU bottleneck curve
        let base = 0.58;
        if (ratio >= 1.0) base = 1.0;
        else if (ratio >= 0.85) base = 0.98;
        else if (ratio >= 0.70) base = 0.92;
        else if (ratio >= 0.55) base = 0.84;
        else if (ratio >= 0.40) base = 0.72;

        const tuning = getGameTuning(gameId);
        const adjusted = base * tuning.cpuBias;
        return Math.min(1.05, Math.max(0.55, adjusted));
    }
    return 0.88; // Unknown CPU, assume slight penalty
}

function getGameTuning(gameId) {
    const profileById = {
        // CS2
        '4': {
            multiplier: 1.08,
            quality: { low: 1.50, medium: 1.25, high: 1.05, ultra: 0.92 },
            cpuBias: 1.08,
            softCap: { value: 700, softness: 0.30 }
        },
        // Starfield
        '5': {
            multiplier: 0.90,
            quality: { low: 1.08, medium: 0.98, high: 0.88, ultra: 0.78 },
            cpuBias: 1.00,
            softCap: { value: 190, softness: 0.65 }
        }
    };

    return profileById[String(gameId)] || {
        multiplier: 1.0,
        quality: null,
        cpuBias: 1.0,
        softCap: null
    };
}

function getFpsSpread(gameId) {
    const profileById = {
        '4': { min: 0.88, max: 1.20 },
        '5': { min: 0.78, max: 1.12 }
    };
    return profileById[String(gameId)] || { min: 0.84, max: 1.12 };
}

function applySoftCap(value, cap, softness) {
    if (value <= cap) return value;
    const overflow = value - cap;
    const reduced = overflow * Math.max(0, 1 - softness);
    return Math.round(cap + reduced);
}

function getRamPenalty() {
    const ramEntry = currentBuild[4];
    const mobo = currentBuild[3];
    const cpu = currentBuild[1];
    const kits = Array.isArray(ramEntry) ? ramEntry : (ramEntry ? [ramEntry] : []);
    if (!kits.length) return 0.92;

    const capacities = [];
    const speeds = [];
    let sticks = 0;

    kits.forEach((kit) => {
        const specs = parseComponentSpecs(kit.specs);
        const capacityRaw = kit.ram_capacity || specs.capacity || parseRamCapacity(kit.name);
        const capMatch = capacityRaw ? String(capacityRaw).match(/(\d+)/) : null;
        const capacity = capMatch ? parseInt(capMatch[1], 10) : (capacityRaw ? parseInt(capacityRaw, 10) : null);
        if (capacity) capacities.push(capacity);

        const speedRaw = kit.ram_speed || specs.speed || parseRamSpeed(kit.name);
        const speedMatch = speedRaw ? String(speedRaw).match(/(\d+)/) : null;
        const speed = speedMatch ? parseInt(speedMatch[1], 10) : (speedRaw ? parseInt(speedRaw, 10) : null);
        if (speed) speeds.push(speed);

        const modules = extractRamModuleCount(kit.name, kit.specs);
        if (modules) {
            sticks += modules;
        } else if (capacity) {
            sticks += capacity >= 16 ? 2 : 1;
        }
    });

    const capacity = capacities.reduce((sum, value) => sum + value, 0);
    const speed = speeds.length ? Math.min(...speeds) : null;
    const moboSlots = mobo ? (getMoboRamSlots(mobo) || 4) : 4;
    const moboMaxSpeed = mobo ? (getMoboMaxRamSpeed(mobo) || 4800) : 4800;

    let factor = 1.0;
    
    // Capacity impact (modern games need 16GB+)
    if (capacity) {
        if (capacity < 8) factor *= 0.75;
        else if (capacity < 16) factor *= 0.88;
        else if (capacity >= 32) factor *= 1.02; // Slight bonus for 32GB+
    }
    
    // Dual-channel bonus (critical for Ryzen)
    if (sticks && moboSlots >= 2) {
        if (sticks === 1) factor *= 0.85; // Single channel penalty
        else if (sticks === 2 || sticks === 4) factor *= 1.0; // Dual channel
    }
    
    // Speed impact (especially for CPU-bound scenarios)
    if (speed) {
        if (speed < 2400) factor *= 0.88;
        else if (speed < 3000) factor *= 0.94;
        else if (speed >= 3600) factor *= 1.03; // Sweet spot for Ryzen
        else if (speed >= 4800) factor *= 1.05; // High-end DDR5
    }
    
    // Speed mismatch with motherboard
    if (speed && speed > moboMaxSpeed * 1.1) factor *= 0.96; // Downclocked

    return factor;
}

function getCoolingStability() {
    const cooling = currentBuild[8];
    const cpu = currentBuild[1];
    if (!cooling) return 0.94; // No cooler = thermal throttling
    
    const coolerName = (cooling.name || '').toUpperCase();
    const coolerType = (cooling.cooler_type || '').toUpperCase();
    const text = coolerName + ' ' + coolerType;
    const parseSpecs = (specs) => {
        if (!specs) return {};
        if (typeof specs === 'object') return specs;
        try {
            return JSON.parse(specs);
        } catch (error) {
            return {};
        }
    };
    const coolingSpecs = parseSpecs(cooling.specs);
    const cpuSpecs = parseSpecs(cpu?.specs);
    const coolerTdp = normalizeNumber(cooling.cooler_tdp)
        || normalizeNumber(coolingSpecs.tdp)
        || normalizeNumber(coolingSpecs.max_tdp)
        || normalizeNumber(coolingSpecs.maxTdp);
    
    let stability = 1.0;
    
    // AIO liquid cooling
    if (text.includes('360') || text.includes('420')) stability = 1.03;
    else if (text.includes('280') || text.includes('240')) stability = 1.02;
    else if (text.includes('120') || text.includes('AIO')) stability = 1.0;
    // Tower air coolers
    else if (text.includes('TOWER') || text.includes('БАШЕН')) stability = 0.99;
    else if (text.includes('STOCK') || text.includes('BOX')) stability = 0.95;
    else stability = 0.98; // Generic cooler
    
    if (cpu) {
        const cpuPower = normalizeNumber(cpu.power)
            || normalizeNumber(cpu.tdp)
            || normalizeNumber(cpuSpecs.tdp)
            || normalizeNumber(cpuSpecs.base_tdp);
        if (coolerTdp && cpuPower) {
            if (coolerTdp >= cpuPower * 1.2) stability = Math.max(stability, 1.02);
            else if (coolerTdp >= cpuPower * 1.05) stability = Math.max(stability, 1.0);
            else if (coolerTdp < cpuPower) stability *= 0.92;
        } else if (coolerTdp) {
            if (coolerTdp >= 240) stability = Math.max(stability, 1.02);
            else if (coolerTdp >= 180) stability = Math.max(stability, 1.0);
        }

        // High-end CPU needs better cooling
        if (cpu.performance > 8000 && (!coolerTdp || (cpuPower && coolerTdp < cpuPower * 1.05))) {
            if (stability < 1.0) stability *= 0.97;
        }
    }
    
    return stability;
}

function parseRamCapacity(name) {
    const match = String(name).match(/(\d+)\s*GB/i);
    return match ? parseInt(match[1]) : null;
}

function parseRamSpeed(name) {
    const match = String(name).match(/(\d{4,5})\s?MHz/i);
    return match ? parseInt(match[1]) : null;
}

function getRamStickCount(ram) {
    const direct = parseInt(ram.ram_sticks);
    if (Number.isFinite(direct) && direct > 0) return direct;
    return extractRamModuleCount(ram.name, ram.specs);
}

function buildPerformanceLabel(avgFps) {
    if (avgFps >= 300) return { className: 'excellent', text: '🚀 Экстремальная производительность' };
    if (avgFps >= 240) return { className: 'excellent', text: '⚡ Идеально для 240Hz мониторов' };
    if (avgFps >= 165) return { className: 'excellent', text: '🎮 Идеально для 165Hz мониторов' };
    if (avgFps >= 144) return { className: 'excellent', text: '🏆 Отлично для киберспорта' };
    if (avgFps >= 90) return { className: 'good', text: '⚡ Очень плавная игра' };
    if (avgFps >= 60) return { className: 'good', text: '✨ Комфортная игра' };
    if (avgFps >= 45) return { className: 'average', text: '👌 Приемлемо' };
    if (avgFps >= 30) return { className: 'average', text: '⚠️ Играбельно' };
    return { className: 'poor', text: '❌ Низкая производительность' };
}

function buildBottleneckLabel(cpuFactor, ramFactor, coolingStability) {
    const issues = [];
    if (cpuFactor < 0.70) issues.push('🔻 Критическое узкое место: CPU');
    else if (cpuFactor < 0.85) issues.push('⚠️ CPU ограничивает производительность');
    
    if (ramFactor < 0.85) issues.push('🧠 Недостаточно RAM или низкая скорость');
    else if (ramFactor < 0.93) issues.push('💾 RAM можно улучшить');
    
    if (coolingStability < 0.96) issues.push('🌡️ Риск троттлинга из-за охлаждения');
    else if (coolingStability < 0.99) issues.push('❄️ Охлаждение можно улучшить');
    
    if (issues.length === 0) return '✅ Отлично сбалансированная сборка';
    return issues.join(' • ');
}

function getStorageBonus() {
    const storage = currentBuild[5];
    if (!storage || !Array.isArray(storage)) return 1.0;
    
    let hasNVMe = false;
    let hasSATA = false;
    
    for (const drive of storage) {
        const text = ((drive.name || '') + ' ' + JSON.stringify(drive.specs || '')).toUpperCase();
        if (text.includes('NVME') || text.includes('M.2')) hasNVMe = true;
        if (text.includes('SATA') || text.includes('HDD')) hasSATA = true;
    }
    
    // NVMe reduces loading times and texture streaming stutters
    if (hasNVMe) return 1.02;
    if (hasSATA) return 1.0;
    return 1.0;
}

function calculateTotalPower() {
    let total = 0;
    Object.values(currentBuild).forEach(item => {
        if (Array.isArray(item)) {
            item.forEach(component => {
                total += parseInt(component.power) || 0;
            });
        } else if (item) {
            total += parseInt(item.power) || 0;
        }
    });
    return total;
}

function getPsuEfficiency() {
    const psu = currentBuild[7];
    if (!psu) return 0.98; // No PSU = unstable power
    
    const wattage = extractPSUWattage(psu);
    const totalPower = calculateTotalPower();
    
    if (!wattage || !totalPower) return 1.0;
    
    const loadPercent = (totalPower / wattage) * 100;
    
    // PSU efficiency curve (best at 50-80% load)
    if (loadPercent < 30) return 0.98; // Underloaded
    if (loadPercent >= 30 && loadPercent < 50) return 1.0;
    if (loadPercent >= 50 && loadPercent <= 80) return 1.01; // Sweet spot
    if (loadPercent > 80 && loadPercent <= 90) return 0.99;
    if (loadPercent > 90) return 0.96; // Overloaded, voltage drops
    
    return 1.0;
}

function getLatencySummary(cpu, gpu) {
    const cpuPerf = (cpu && cpu.performance) ? cpu.performance : 0;
    const gpuPerf = (gpu && gpu.performance) ? gpu.performance : 0;
    if (!cpuPerf || !gpuPerf) return 'Недостаточно данных';
    const ratio = gpuPerf / cpuPerf;
    if (ratio > 1.4) return 'Ожидается задержка от CPU';
    if (ratio < 0.8) return 'GPU станет узким местом';
    return 'Баланс CPU/GPU в норме';
}

function selectResolution(btn) {
    document.querySelectorAll('.resolution-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    selectedResolution = btn.dataset.resolution;
    calculateFPS();
}

function selectQuality(btn) {
    document.querySelectorAll('.quality-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    selectedQuality = btn.dataset.quality || 'ultra';
    calculateFPS();
}

// Clear build
function clearBuild() {
    currentBuild = {};
    localStorage.setItem('currentBuild', JSON.stringify(currentBuild));
    
    categories.forEach(cat => {
        const slot = document.getElementById(`slot-${cat.id}`);
        if (slot) {
            slot.innerHTML = `
                <button class="btn-add-component" onclick="openComponentSelector(${cat.id}, '${cat.name}')">
                    <i class="fas fa-plus"></i>
                    Выбрать ${cat.name}
                </button>
            `;
        }
    });
    
    updateSummary();
    checkCompatibility();
    calculateFPS();
    showToast('Сборка очищена', 'info');
}

// Save build
async function saveBuild(buildName, purpose = 'other', ghostCode = null) {
    if (!loggedInUserId) {
        showToast('Войдите в аккаунт, чтобы сохранять сборки', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php?redirect=builder.php';
        }, 1200);
        return;
    }

    const flatComponents = flattenBuildComponents(currentBuild);
    if (!flatComponents.length) {
        showToast('Добавьте хотя бы один компонент', 'warning');
        return;
    }

    const totalPrice = flatComponents.reduce((sum, component) => sum + (Number(component.price) || 0), 0);
    const totalPower = flatComponents.reduce((sum, component) => sum + (Number(component.power) || 0), 0);
    const summary = getBuildSummary();
    summary.purpose = purpose;

    try {
        const response = await fetch('api/save_build.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: buildName,
                purpose,
                components: currentBuild,
                totalPrice,
                totalPower,
                summary,
                ghost_code: ghostCode
            })
        });
        
        if (!response.ok) {
            throw new Error('Server error');
        }
        
        const data = await response.json();
        if (data.success) {
            const savedName = data.ghost_name || buildName;
            showToast(`Сборка сохранена как ${savedName}`, 'success');
            localStorage.setItem('lastSavedBuildId', data.build_id);
            openSaveResultModal({
                name: savedName,
                buildId: data.build_id,
                message: loggedInUserId ? 'Сборка доступна в разделе «Мои сборки».' : 'Авторизуйтесь позже, чтобы перенести сборку в профиль.'
            });
        } else {
            showToast(data.error || 'Ошибка сохранения', 'error');
        }
    } catch (error) {
        console.error('Save build error:', error);
        showToast('Ошибка сохранения', 'error');
    }
}

function getBuildSummary() {
    const summary = {};
    const componentsSummary = {};
    Object.entries(currentBuild).forEach(([categoryId, component]) => {
        if (!component) return;
        const slug = ((Array.isArray(component) ? component[0]?.slug : component.slug) || categorySlugMap[String(categoryId)] || '').toLowerCase();
        let name = '';
        if (Array.isArray(component)) {
            const names = component.map((item) => item?.name || item?.title).filter(Boolean);
            if (names.length === 1) {
                name = names[0];
            } else if (names.length > 1) {
                name = `${names[0]} +${names.length - 1}`;
            }
        } else {
            name = component.name || component.title || '';
        }
        if (!name) {
            name = 'Компонент';
        }
        if (slug.includes('cpu')) summary.cpu = name;
        if (slug.includes('gpu')) summary.gpu = name;
        if (slug.includes('ram')) summary.ram = name;
        if (slug.includes('storage')) summary.storage = name;
        if (slug.includes('psu')) summary.psu = name;
        if (slug.includes('case')) summary.case = name;
        if (slug.includes('cool')) summary.cooling = name;
        componentsSummary[slug || categoryId] = name;
    });
    summary.components = componentsSummary;
    summary.total_components = Object.keys(currentBuild).length;
    summary.saved_at = new Date().toISOString();
    return summary;
}

function loadSavedBuilds() {
    window.location.href = 'profile.php#my-builds';
}

// Share build
function shareBuild() {
    const components = Object.values(currentBuild);
    if (components.length === 0) {
        showToast('Добавьте компоненты для создания ссылки', 'warning');
        return;
    }
    
    const buildData = btoa(JSON.stringify(currentBuild));
    const shareUrl = `${window.location.origin}/HyperPC/builder.php?build=${buildData}`;
    const input = document.getElementById('shareLinkInput');
    if (input) {
        input.value = shareUrl;
    }
    const status = document.getElementById('shareStatus');
    if (status) {
        status.textContent = 'Ссылка готова';
    }
    openShareModal();
}

function openShareModal() {
    const modal = document.getElementById('shareModal');
    if (modal) modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeShareModal() {
    const modal = document.getElementById('shareModal');
    if (modal) modal.classList.remove('active');
    document.body.style.overflow = '';
}

function copyShareLink() {
    const input = document.getElementById('shareLinkInput');
    const status = document.getElementById('shareStatus');
    if (!input || !input.value) return;
    const setStatus = (ok) => {
        if (!status) return;
        status.textContent = ok ? 'Скопировано!' : 'Не удалось скопировать';
        status.style.color = ok ? 'var(--success)' : 'var(--error)';
    };
    const fallbackCopy = () => {
        input.focus();
        input.select();
        const ok = document.execCommand('copy');
        setStatus(!!ok);
        return ok;
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.value)
            .then(() => setStatus(true))
            .catch(() => fallbackCopy());
    } else {
        fallbackCopy();
    }
}

// Helper functions
function formatPrice(price) {
    const safePrice = normalizeNumber(price);
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0
    }).format(safePrice);
}

function normalizeNumber(value) {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return value;
    }
    if (typeof value === 'string') {
        const cleaned = value.replace(',', '.').replace(/[^\d.-]/g, '');
        const parsed = Number(cleaned);
        return Number.isFinite(parsed) ? parsed : 0;
    }
    return 0;
}

function normalizeComponent(component) {
    if (!component || typeof component !== 'object') {
        return null;
    }

    const normalized = { ...component };
    const numericFields = [
        'price',
        'power',
        'performance',
        'psu_wattage',
        'mobo_max_ram_speed',
        'mobo_ram_slots',
        'mobo_m2_slots',
        'ram_speed',
        'ram_capacity',
        'case_max_gpu_length',
        'case_max_cooler_height',
        'cooler_height',
        'pcie_version',
        'gpu_memory'
    ];

    numericFields.forEach(field => {
        if (Object.prototype.hasOwnProperty.call(normalized, field)) {
            const raw = normalized[field];
            if (raw === null || raw === '') {
                normalized[field] = null;
            } else {
                const parsed = normalizeNumber(raw);
                normalized[field] = Number.isFinite(parsed) ? parsed : null;
            }
        }
    });

    return normalized;
}

function normalizeBuildData(buildState) {
    const normalized = {};
    if (!buildState || typeof buildState !== 'object') {
        return normalized;
    }

    Object.entries(buildState).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            const list = value.map(normalizeComponent).filter(Boolean);
            if (list.length) {
                normalized[key] = list;
            }
            return;
        }
        const item = normalizeComponent(value);
        if (item) {
            normalized[key] = item;
        }
    });

    return normalized;
}

function updateBuildCounter() {
    const counter = document.getElementById('buildCounter');
    const total = Object.keys(currentBuild).length;
    if (counter) {
        counter.textContent = total;
        counter.style.display = total > 0 ? 'flex' : 'none';
    }
}

function showToast(message, type = 'info') {
    if (!toastContainerEl) {
        toastContainerEl = document.createElement('div');
        toastContainerEl.className = 'toast-container';
        document.body.appendChild(toastContainerEl);
    }

    const iconByType = {
        success: 'check-circle',
        warning: 'exclamation-triangle',
        error: 'times-circle',
        info: 'info-circle'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${iconByType[type] || iconByType.info}"></i>
        <span>${message}</span>
    `;

    toastContainerEl.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 250);
    }, 3200);
}

// Load shared build from URL
const urlParams = new URLSearchParams(window.location.search);
const sharedBuild = urlParams.get('build');
if (sharedBuild) {
    try {
        currentBuild = JSON.parse(atob(sharedBuild));
        localStorage.setItem('currentBuild', JSON.stringify(currentBuild));
        showToast('Сборка загружена из ссылки', 'success');
    } catch (e) {
        console.error('Invalid build data');
    }
}

// Export build
function openExportModal() {
    const modal = document.getElementById('exportModal');
    if (!modal) return;

    const components = flattenBuildComponents(normalizeBuildData(currentBuild));
    if (!components.length) {
        showToast('Добавьте компоненты для экспорта', 'warning');
        return;
    }

    modal.classList.add('is-visible');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeExportModal() {
    const modal = document.getElementById('exportModal');
    if (!modal) return;
    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function openClearModal() {
    const modal = document.getElementById('clearModal');
    if (!modal) return;

    const components = flattenBuildComponents(normalizeBuildData(currentBuild));
    if (!components.length) {
        showToast('Сборка уже пустая', 'info');
        return;
    }

    modal.classList.add('is-visible');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeClearModal() {
    const modal = document.getElementById('clearModal');
    if (!modal) return;
    modal.classList.remove('is-visible');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

function confirmClearBuild() {
    closeClearModal();
    clearBuild();
}

function buildExportRows(buildState) {
    const rows = [];
    Object.entries(buildState || {}).forEach(([catId, comp]) => {
        const category = categories.find(c => String(c.id) === String(catId));
        const categoryName = category ? category.name : 'Категория';
        const addRow = (item) => {
            if (!item) return;
            rows.push({
                category: categoryName,
                name: item.name || 'Без названия',
                manufacturer: item.manufacturer || '',
                price: formatPrice(item.price),
                power: `${normalizeNumber(item.power)} Вт`
            });
        };

        if (Array.isArray(comp)) {
            comp.forEach(addRow);
        } else {
            addRow(comp);
        }
    });
    return rows;
}

function exportBuildJson() {
    const normalizedBuild = normalizeBuildData(currentBuild);
    const components = Object.values(normalizedBuild);
    if (components.length === 0) {
        showToast('Добавьте компоненты для экспорта', 'warning');
        return;
    }

    const flatComponents = flattenBuildComponents(normalizedBuild);
    const totalPrice = flatComponents.reduce((sum, c) => sum + normalizeNumber(c?.price), 0);
    const totalPower = flatComponents.reduce((sum, c) => sum + normalizeNumber(c?.power), 0);

    const exportPayload = {
        version: 1,
        exportedAt: new Date().toISOString(),
        totals: {
            price: totalPrice,
            power: totalPower
        },
        build: normalizedBuild
    };

    const blob = new Blob([JSON.stringify(exportPayload, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `pc-build-${Date.now()}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    closeExportModal();
    showToast('Сборка экспортирована в JSON', 'success');
}

function exportBuildDoc() {
    const normalizedBuild = normalizeBuildData(currentBuild);
    const components = Object.values(normalizedBuild);
    if (components.length === 0) {
        showToast('Добавьте компоненты для экспорта', 'warning');
        return;
    }

    const flatComponents = flattenBuildComponents(normalizedBuild);
    const totalPrice = flatComponents.reduce((sum, c) => sum + normalizeNumber(c?.price), 0);
    const totalPower = flatComponents.reduce((sum, c) => sum + normalizeNumber(c?.power), 0);
    const totalComponents = flatComponents.length;
    const brandName = typeof siteName !== 'undefined' && siteName ? siteName : 'HyperPC';
    const brandUrl = 'https://hyperpc.ru';
    const exportedAt = new Date();
    const exportId = exportedAt.getTime().toString(36).toUpperCase();
    const rows = buildExportRows(normalizedBuild)
        .map((row) => `
            <tr>
                <td>${row.category}</td>
                <td>
                    <strong>${row.name}</strong><br/>
                    <span style="color:#6b7280;">${row.manufacturer}</span>
                </td>
                <td>${row.price}</td>
                <td>${row.power}</td>
            </tr>
        `)
        .join('');

    const docContent = `<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8" />
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; color: #0f172a; margin: 0; padding: 32px; }
                .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 20px; }
                .brand { display: flex; flex-direction: column; gap: 4px; }
                .brand-name { font-size: 22px; font-weight: 700; color: #1e3a8a; letter-spacing: 0.5px; }
                .brand-sub { font-size: 12px; color: #475569; }
                .doc-title { font-size: 20px; font-weight: 700; color: #0f172a; margin: 4px 0; }
                .doc-meta { font-size: 12px; color: #475569; }
                .meta-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin: 18px 0 24px; }
                .meta-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }
                .meta-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin-bottom: 6px; }
                .meta-value { font-size: 14px; font-weight: 600; color: #0f172a; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background: #eef2ff; color: #1e3a8a; padding: 10px; text-align: left; border-bottom: 2px solid #c7d2fe; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
                td { padding: 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 13px; }
                .totals { background: #f1f5f9; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
                .totals .label { color: #475569; font-size: 12px; }
                .totals .value { font-weight: 700; color: #0f172a; font-size: 16px; }
                .footer { margin-top: 20px; font-size: 12px; color: #64748b; display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 12px; }
                .note { font-size: 12px; color: #475569; margin-top: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="brand">
                    <div class="brand-name">HyperPC</div>
                    <div class="brand-sub">${brandName}</div>
                </div>
                <div>
                    <div class="doc-title">Конфигурация ПК</div>
                    <div class="doc-meta">Экспорт № ${exportId} · ${exportedAt.toLocaleString('ru-RU')}</div>
                </div>
            </div>
            <div class="meta-grid">
                <div class="meta-card">
                    <div class="meta-label">Сборка</div>
                <div class="meta-value">Сборка ПК / Конфигуратор</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Компонентов</div>
                    <div class="meta-value">${totalComponents}</div>
                </div>
                <div class="meta-card">
                    <div class="meta-label">Источник</div>
                    <div class="meta-value">${brandUrl}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Категория</th>
                        <th>Компонент</th>
                        <th>Стоимость</th>
                        <th>Потребление</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                </tbody>
            </table>
            <div class="totals">
                <div>
                    <div class="label">Итого</div>
                    <div class="value">${formatPrice(totalPrice)}</div>
                </div>
                <div>
                    <div class="label">Суммарное потребление</div>
                    <div class="value">${totalPower} Вт</div>
                </div>
                <div>
                    <div class="label">Дата экспорта</div>
                    <div class="value">${exportedAt.toLocaleDateString('ru-RU')}</div>
                </div>
            </div>
            <div class="note">Примечание: итоговая стоимость зависит от актуальных цен поставщиков. Сохраните JSON‑экспорт, если планируете продолжить редактирование конфигурации.</div>
            <div class="footer">
                <span>HyperPC</span>
                <span>${brandUrl}</span>
            </div>
        </body>
        </html>`;

    const blob = new Blob(['\ufeff', docContent], { type: 'application/msword' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `pc-build-${Date.now()}.doc`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    closeExportModal();
    showToast('Документ Word сформирован', 'success');
}

// Import build
function importBuild() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = (e) => {
        const file = e.target.files[0];
        const reader = new FileReader();
        reader.onload = (event) => {
            try {
                const imported = JSON.parse(event.target.result);
                const buildData = imported && typeof imported === 'object' && imported.build ? imported.build : imported;
                const normalizedBuild = normalizeBuildData(buildData);
                if (!Object.keys(normalizedBuild).length) {
                    showToast('Файл не содержит данных сборки', 'error');
                    return;
                }
                currentBuild = normalizedBuild;
                localStorage.setItem('currentBuild', JSON.stringify(currentBuild));
                renderBuild();
                updateSummary();
                checkCompatibility();
                calculateFPS();
                showToast('Сборка импортирована!', 'success');
            } catch (error) {
                showToast('Ошибка импорта файла', 'error');
            }
        };
        reader.readAsText(file);
    };
    input.click();
}

// Print build
function printBuild() {
    const normalizedBuild = normalizeBuildData(currentBuild);
    const flatComponents = flattenBuildComponents(normalizedBuild);
    if (flatComponents.length === 0) {
        showToast('Добавьте компоненты для печати', 'warning');
        return;
    }

    const totalPrice = flatComponents.reduce((sum, c) => sum + normalizeNumber(c?.price), 0);
    const totalPower = flatComponents.reduce((sum, c) => sum + normalizeNumber(c?.power), 0);
    const exportedAt = new Date();
    const brandUrl = 'https://hyperpc.ru';
    
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Сборка ПК - HyperPC</title>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; padding: 36px; color: #0f172a; }
                .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 20px; }
                .brand { display: flex; flex-direction: column; gap: 4px; }
                .brand-name { font-size: 20px; font-weight: 700; color: #1e3a8a; letter-spacing: 0.4px; }
                .brand-sub { font-size: 12px; color: #475569; }
                .doc-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 4px 0; }
                .doc-meta { font-size: 12px; color: #475569; }
                table { width: 100%; border-collapse: collapse; margin: 16px 0 20px; }
                th { background: #eef2ff; color: #1e3a8a; padding: 10px; text-align: left; border-bottom: 2px solid #c7d2fe; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
                td { padding: 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 13px; }
                .totals { background: #f1f5f9; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
                .totals .label { color: #475569; font-size: 12px; }
                .totals .value { font-weight: 700; color: #0f172a; font-size: 16px; }
                .footer { margin-top: 20px; font-size: 12px; color: #64748b; display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="brand">
                    <div class="brand-name">HyperPC</div>
                    <div class="brand-sub">${brandUrl}</div>
                </div>
                <div>
                    <div class="doc-title">Сборка ПК</div>
                    <div class="doc-meta">Отчет от ${exportedAt.toLocaleString('ru-RU')}</div>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Категория</th>
                        <th>Компонент</th>
                        <th>Цена</th>
                        <th>Потребление</th>
                    </tr>
                </thead>
                <tbody>
                    ${buildExportRows(normalizedBuild).map((row) => `
                        <tr>
                            <td>${row.category}</td>
                            <td>
                                <strong>${row.name}</strong><br>
                                <span style="color:#64748b;">${row.manufacturer || ''}</span>
                            </td>
                            <td>${row.price}</td>
                            <td>${row.power}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            <div class="totals">
                <div>
                    <div class="label">Итого</div>
                    <div class="value">${formatPrice(totalPrice)}</div>
                </div>
                <div>
                    <div class="label">Суммарное потребление</div>
                    <div class="value">${totalPower} Вт</div>
                </div>
                <div>
                    <div class="label">Дата</div>
                    <div class="value">${exportedAt.toLocaleDateString('ru-RU')}</div>
                </div>
            </div>
            <div class="footer">
                <span>HyperPC</span>
                <span>${brandUrl}</span>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Load saved builds
function loadSavedBuilds() {
    showToast('Функция в разработке', 'info');
}

// Update build progress
function updateBuildProgress() {
    const progress = document.getElementById('buildProgress');
    const total = Object.keys(currentBuild).length;
    const maxCategories = categories.length;
    if (progress) {
        progress.textContent = `${total}/${maxCategories} компонентов`;
        progress.style.background = total > 0 ? 'linear-gradient(135deg, var(--primary), var(--secondary))' : '';
        progress.style.color = total > 0 ? 'white' : '';
    }
}

// Update power indicator
function updatePowerIndicator() {
    const components = Object.values(currentBuild);
    const totalPower = components.reduce((sum, c) => sum + c.power, 0);
    const psu = currentBuild[6]; // Category 6 = PSU
    
    const indicator = document.getElementById('powerIndicator');
    if (!psu || totalPower === 0) {
        indicator.style.display = 'none';
        return;
    }
    
    const psuPower = parseInt(psu.specs?.wattage) || 0;
    if (psuPower === 0) {
        indicator.style.display = 'none';
        return;
    }
    
    const percentage = Math.round((totalPower / psuPower) * 100);
    const fill = document.getElementById('powerFill');
    const percentageEl = document.getElementById('powerPercentage');
    const recommendation = document.getElementById('powerRecommendation');
    
    percentageEl.textContent = percentage + '%';
    fill.style.width = Math.min(percentage, 100) + '%';
    
    fill.className = 'power-fill';
    if (percentage > 90) {
        fill.classList.add('danger');
        recommendation.textContent = '⚠️ Рекомендуется более мощный БП';
    } else if (percentage > 75) {
        fill.classList.add('warning');
        recommendation.textContent = '⚡ Запас мощности небольшой';
    } else {
        recommendation.textContent = '✓ Достаточный запас мощности';
    }
    
    indicator.style.display = 'block';
}

// Enhanced update summary
const originalUpdateSummary = updateSummary;
updateSummary = function() {
    originalUpdateSummary();
    updateBuildProgress();
    updatePowerIndicator();
    
    // Update slot visual state
    categories.forEach(cat => {
        const slot = document.querySelector(`.component-slot[data-category="${cat.id}"]`);
        if (slot) {
            if (currentBuild[cat.id]) {
                slot.classList.add('filled');
            } else {
                slot.classList.remove('filled');
            }
        }
    });
};

// View component details
function viewComponentDetails(categoryId) {
    const component = currentBuild[categoryId];
    if (!component) return;
    
    const category = categories.find(c => c.id == categoryId);
    
    const detailsHTML = `
        <div class="component-details-modal">
            <h3>${component.name}</h3>
            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-industry"></i> Производитель:</span>
                    <span class="detail-value">${component.manufacturer || 'Неизвестно'}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-ruble-sign"></i> Цена:</span>
                    <span class="detail-value">${formatPrice(component.price)}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-bolt"></i> Потребление:</span>
                    <span class="detail-value">${component.power}W</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-tag"></i> Категория:</span>
                    <span class="detail-value">${category?.name || 'Неизвестно'}</span>
                </div>
                ${component.performance ? `
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-gauge-high"></i> Производительность:</span>
                    <span class="detail-value">${component.performance}</span>
                </div>
                ` : ''}
            </div>
        </div>
    `;
    
    showModal('Информация о компоненте', detailsHTML);
}

// Change component
function changeComponent(categoryId, categoryName) {
    openComponentSelector(categoryId, categoryName);
}

// Show custom modal
function showModal(title, content) {
    const existingModal = document.getElementById('customModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    const modal = document.createElement('div');
    modal.id = 'customModal';
    modal.className = 'modal active';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeCustomModal()"></div>
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>${title}</h3>
                <button class="modal-close" onclick="closeCustomModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

// Close custom modal
function closeCustomModal() {
    const modal = document.getElementById('customModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

// Initialize
initBuild();
