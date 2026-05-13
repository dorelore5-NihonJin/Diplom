// Category-specific filter definitions
const categoryFilters = {
    'cpu': {
        title: 'Процессоры',
        filters: [
            { type: 'range', name: 'cpu_cores', label: 'Количество ядер', min: 'cpu_cores_min', max: 'cpu_cores_max', placeholder: ['2', '32'], icon: 'fa-microchip' },
            { type: 'range', name: 'cpu_threads', label: 'Количество потоков', min: 'cpu_threads_min', max: 'cpu_threads_max', placeholder: ['4', '64'], icon: 'fa-stream' },
            { type: 'range', name: 'cpu_base_clock', label: 'Базовая частота (ГГц)', min: 'cpu_base_clock_min', max: 'cpu_base_clock_max', placeholder: ['2.0', '5.0'], icon: 'fa-gauge-high' },
            { type: 'range', name: 'cpu_tdp', label: 'TDP (Вт)', min: 'cpu_tdp_min', max: 'cpu_tdp_max', placeholder: ['35', '250'], icon: 'fa-bolt' },
            { type: 'checkbox', name: 'cpu_socket', label: 'Сокет', options: ['AM4', 'AM5', 'LGA1700', 'LGA1851', 'LGA1200'], icon: 'fa-plug' }
        ]
    },
    'gpu': {
        title: 'Видеокарты',
        filters: [
            { type: 'range', name: 'gpu_memory', label: 'Объем памяти (ГБ)', min: 'gpu_memory_min', max: 'gpu_memory_max', placeholder: ['4', '24'], icon: 'fa-memory' },
            { type: 'range', name: 'gpu_tdp', label: 'TDP (Вт)', min: 'gpu_tdp_min', max: 'gpu_tdp_max', placeholder: ['75', '450'], icon: 'fa-bolt' },
            { type: 'checkbox', name: 'gpu_memory_type', label: 'Тип памяти', options: ['GDDR6', 'GDDR6X', 'GDDR5'], icon: 'fa-sd-card' },
            { type: 'range', name: 'gpu_boost_clock', label: 'Boost частота (МГц)', min: 'gpu_boost_clock_min', max: 'gpu_boost_clock_max', placeholder: ['1500', '2800'], icon: 'fa-gauge-high' }
        ]
    },
    'motherboard': {
        title: 'Материнские платы',
        filters: [
            { type: 'checkbox', name: 'mobo_form_factor', label: 'Форм-фактор', options: ['ATX', 'Micro-ATX', 'Mini-ITX', 'E-ATX'], icon: 'fa-table-cells' },
            { type: 'checkbox', name: 'mobo_chipset', label: 'Чипсет', options: ['B550', 'B650', 'X570', 'X670E', 'Z690', 'Z790'], icon: 'fa-microchip' },
            { type: 'checkbox', name: 'mobo_socket', label: 'Сокет', options: ['AM4', 'AM5', 'LGA1700', 'LGA1851', 'LGA1200'], icon: 'fa-plug' },
            { type: 'range', name: 'mobo_ram_slots', label: 'Слотов RAM', min: 'mobo_ram_slots_min', max: 'mobo_ram_slots_max', placeholder: ['2', '4'], icon: 'fa-memory' },
            { type: 'range', name: 'mobo_m2_slots', label: 'Слотов NVMe', min: 'mobo_m2_slots_min', max: 'mobo_m2_slots_max', placeholder: ['1', '5'], icon: 'fa-sd-card' }
        ]
    },
    'ram': {
        title: 'Оперативная память',
        filters: [
            { type: 'range', name: 'ram_capacity', label: 'Объем (ГБ)', min: 'ram_capacity_min', max: 'ram_capacity_max', placeholder: ['8', '128'], icon: 'fa-memory' },
            { type: 'range', name: 'ram_speed', label: 'Частота (МГц)', min: 'ram_speed_min', max: 'ram_speed_max', placeholder: ['2400', '7200'], icon: 'fa-gauge-high' },
            { type: 'checkbox', name: 'ram_type', label: 'Тип', options: ['DDR4', 'DDR5'], icon: 'fa-sd-card' },
            { type: 'range', name: 'ram_latency', label: 'CAS Latency', min: 'ram_latency_min', max: 'ram_latency_max', placeholder: ['14', '40'], icon: 'fa-clock' }
        ]
    },
    'storage': {
        title: 'Накопители',
        filters: [
            { type: 'range', name: 'storage_capacity', label: 'Объем (ГБ)', min: 'storage_capacity_min', max: 'storage_capacity_max', placeholder: ['256', '4096'], icon: 'fa-hard-drive' },
            { type: 'checkbox', name: 'storage_type', label: 'Тип', options: ['SSD', 'HDD', 'NVMe'], icon: 'fa-database' },
            { type: 'checkbox', name: 'storage_interface', label: 'Интерфейс', options: ['NVMe', 'SATA', 'PCIe 4.0', 'PCIe 5.0'], icon: 'fa-plug' },
            { type: 'range', name: 'storage_read_speed', label: 'Скорость чтения (МБ/с)', min: 'storage_read_speed_min', max: 'storage_read_speed_max', placeholder: ['500', '7000'], icon: 'fa-gauge-high' }
        ]
    },
    'psu': {
        title: 'Блоки питания',
        filters: [
            { type: 'range', name: 'psu_wattage', label: 'Мощность (Вт)', min: 'psu_wattage_min', max: 'psu_wattage_max', placeholder: ['450', '1600'], icon: 'fa-bolt' },
            { type: 'checkbox', name: 'psu_efficiency', label: 'Сертификат', options: ['80+ Bronze', '80+ Silver', '80+ Gold', '80+ Platinum', '80+ Titanium'], icon: 'fa-certificate' },
            { type: 'checkbox', name: 'psu_modular', label: 'Модульность', options: ['Полностью модульный', 'Частично модульный', 'Немодульный'], icon: 'fa-plug-circle-bolt' }
        ]
    },
    'case': {
        title: 'Корпуса',
        filters: [
            { type: 'range', name: 'case_gpu_length', label: 'Макс. длина GPU (мм)', min: 'case_gpu_length_min', max: 'case_gpu_length_max', placeholder: ['250', '450'], icon: 'fa-ruler-horizontal' },
            { type: 'checkbox', name: 'case_form_factor', label: 'Форм-фактор', options: ['Full Tower', 'Mid Tower', 'Mini Tower', 'Mini-ITX'], icon: 'fa-box' },
            { type: 'range', name: 'case_fans', label: 'Мест под вентиляторы', min: 'case_fans_min', max: 'case_fans_max', placeholder: ['2', '10'], icon: 'fa-fan' },
            { type: 'checkbox', name: 'case_side_panel', label: 'Боковая панель', options: ['Стекло', 'Акрил', 'Металл'], icon: 'fa-window-maximize' }
        ]
    },
    'cooling': {
        title: 'Охлаждение',
        filters: [
            { type: 'checkbox', name: 'cooler_type', label: 'Тип', options: ['Башенный кулер', 'AIO 120мм', 'AIO 240мм', 'AIO 280мм', 'AIO 360мм'], icon: 'fa-fan' },
            { type: 'range', name: 'cooler_height', label: 'Высота (мм)', min: 'cooler_height_min', max: 'cooler_height_max', placeholder: ['50', '180'], icon: 'fa-ruler-vertical' },
            { type: 'range', name: 'cooler_tdp', label: 'TDP (Вт)', min: 'cooler_tdp_min', max: 'cooler_tdp_max', placeholder: ['65', '250'], icon: 'fa-temperature-high' },
            { type: 'checkbox', name: 'cooler_socket', label: 'Совместимость', options: ['AM4', 'AM5', 'LGA1700', 'LGA1200'], icon: 'fa-plug' }
        ]
    }
};

const enableRemoteImages = Boolean(window.ENABLE_REMOTE_IMAGES);
const categoryImageKeywords = {
    cpu: 'cpu processor',
    gpu: 'graphics card',
    motherboard: 'motherboard',
    ram: 'ram memory',
    storage: 'ssd storage',
    psu: 'power supply unit',
    case: 'pc case',
    cooling: 'cpu cooler'
};

function buildImageQuery(card) {
    const rawQuery = (card.dataset.imageQuery || '').trim();
    const categorySlug = (card.dataset.categorySlug || '').trim();
    const categoryHint = categoryImageKeywords[categorySlug] || '';
    const parts = [rawQuery, categoryHint].filter(Boolean);
    if (parts.length === 0) {
        return 'pc component';
    }
    return parts.join(' ').trim();
}

function applyRemoteImages(scope = document) {
        if (!enableRemoteImages) return;

    const cards = scope.querySelectorAll('.component-card');
    cards.forEach(card => {
        if (card.dataset.imageLoaded) return;

        const query = buildImageQuery(card);
        const img = card.querySelector('.component-photo');
        const icon = card.querySelector('.component-icon');

        if (!query || !img) {
            card.dataset.imageLoaded = 'fallback';
            return;
        }

        const cacheKey = `componentImg:${query.toLowerCase()}`;
        const cachedUrl = sessionStorage.getItem(cacheKey);
        if (cachedUrl) {
            img.src = cachedUrl;
            img.onload = () => {
                img.classList.add('is-loaded');
                icon?.classList.add('is-hidden');
            };
            img.onerror = () => {
                card.dataset.imageLoaded = 'fallback';
            };
            card.dataset.imageLoaded = 'cached';
            return;
        }

        fetch(`api/get_component_image.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (!data || !data.url) {
                    if (data && data.error) {
                        console.warn('Image API error:', data.error, query);
                    }
                    card.dataset.imageLoaded = 'fallback';
                    return;
                }
                sessionStorage.setItem(cacheKey, data.url);
                img.src = data.url;
                img.onload = () => {
                    img.classList.add('is-loaded');
                    icon?.classList.add('is-hidden');
                };
                img.onerror = () => {
                    card.dataset.imageLoaded = 'fallback';
                };
                card.dataset.imageLoaded = 'loaded';
            })
            .catch((error) => {
                console.warn('Image API request failed:', error);
                card.dataset.imageLoaded = 'fallback';
            });
    });
}

// Load price range for category
async function loadPriceRange(categorySlug) {
    try {
        const response = await fetch(`api/get_price_range.php?category=${categorySlug || ''}`);
        const data = await response.json();
        
        if (data.success) {
            const minInput = document.getElementById('minPriceInput');
            const maxInput = document.getElementById('maxPriceInput');
            
            if (minInput && maxInput) {
                // Update placeholders with actual range
                minInput.placeholder = data.min_price.toLocaleString('ru-RU');
                maxInput.placeholder = data.max_price.toLocaleString('ru-RU');
            }
        }
    } catch (error) {
        console.error('Error loading price range:', error);
    }
}

// Load manufacturers by category
async function loadManufacturersByCategory(categorySlug) {
    const container = document.getElementById('manufacturerFilters');
    const showMoreBtn = container.parentElement.querySelector('.btn-show-more');
    
    try {
        const response = await fetch(`api/get_manufacturers.php?category=${categorySlug || ''}`);
        const data = await response.json();
        
        if (data.success) {
            container.innerHTML = data.html;
            
            // Update or hide "Show more" button
            if (showMoreBtn) {
                if (data.count > 5) {
                    showMoreBtn.style.display = 'block';
                    showMoreBtn.innerHTML = `<i class="fas fa-chevron-down"></i> Показать все (${data.count})`;
                    showMoreBtn.classList.remove('expanded');
                    container.classList.remove('expanded');
                } else {
                    showMoreBtn.style.display = 'none';
                }
            }
        }
    } catch (error) {
        console.error('Error loading manufacturers:', error);
    }
}

// Track current category to avoid unnecessary re-renders
let currentCategorySlug = '';
let currentManufacturerCategory = '';

// Render category-specific filters
function renderCategoryFilters(categorySlug) {
    const container = document.getElementById('categorySpecificFilters');
    
    // Don't re-render if category hasn't changed
    if (categorySlug === currentCategorySlug) {
        return;
    }
    
    currentCategorySlug = categorySlug;
    
    if (!categorySlug || !categoryFilters[categorySlug]) {
        container.innerHTML = '';
        return;
    }
    
    const config = categoryFilters[categorySlug];
    let html = '';
    
    config.filters.forEach(filter => {
        if (filter.type === 'range') {
            html += `
                <div class="filter-section category-filter">
                    <h3><i class="fas ${filter.icon || 'fa-sliders-h'}"></i> ${filter.label}</h3>
                    <div class="price-range-inputs">
                        <input type="number" class="numeric-input price-range-input category-filter-input" 
                               name="${filter.min || filter.name + '_min'}" placeholder="${filter.placeholder[0]}" 
                               oninput="applyFilters()" step="any">
                        <input type="number" class="numeric-input price-range-input category-filter-input" 
                               name="${filter.max || filter.name + '_max'}" placeholder="${filter.placeholder[1]}" 
                               oninput="applyFilters()" step="any">
                    </div>
                </div>
            `;
        } else if (filter.type === 'checkbox') {
            html += `
                <div class="filter-section category-filter">
                    <h3><i class="fas ${filter.icon || 'fa-filter'}"></i> ${filter.label}</h3>
                    <div class="filter-options category-filter-options">
                        ${filter.options.map(option => `
                            <label class="filter-option category-checkbox-option">
                                <input type="checkbox" class="category-filter-checkbox" 
                                       name="${filter.name}" value="${option}" 
                                       onchange="applyFilters()">
                                <span>${option}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
        }
    });
    
    container.innerHTML = html;
}

// Toggle filter section (show more/less)
function toggleFilterSection(sectionId, button) {
    const section = document.getElementById(sectionId);
    const icon = button.querySelector('i');
    
    if (section.classList.contains('expanded')) {
        section.classList.remove('expanded');
        button.classList.remove('expanded');
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Показать все';
    } else {
        section.classList.add('expanded');
        button.classList.add('expanded');
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Скрыть';
    }
}

// Toggle manufacturers with letter grouping
function toggleManufacturers(button) {
    const section = document.getElementById('manufacturerFilters');
    
    if (section.classList.contains('expanded')) {
        section.classList.remove('expanded');
        button.classList.remove('expanded');
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Показать все';
    } else {
        section.classList.add('expanded');
        button.classList.add('expanded');
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Свернуть';
    }
}

// Toggle manufacturer selection
function toggleManufacturer(checkbox) {
    const container = document.getElementById('manufacturerFilters');
    const label = checkbox.closest('.filter-option');
    
    // Reorder: move checked items to top
    reorderManufacturers();
    
    // Apply filters after reordering
    setTimeout(() => {
        applyFilters();
    }, 350);
}

// Reorder manufacturers - checked first
function reorderManufacturers() {
    const container = document.getElementById('manufacturerFilters');
    const labels = Array.from(container.querySelectorAll('.filter-option.logo-option'));
    const letters = Array.from(container.querySelectorAll('.manufacturer-letter'));
    
    // Separate checked and unchecked
    const checked = labels.filter(label => label.querySelector('input[type="checkbox"]').checked);
    const unchecked = labels.filter(label => !label.querySelector('input[type="checkbox"]').checked);
    
    // Sort unchecked by manufacturer name to restore original order
    unchecked.sort((a, b) => {
        const nameA = a.dataset.manufacturer.toLowerCase();
        const nameB = b.dataset.manufacturer.toLowerCase();
        return nameA.localeCompare(nameB);
    });
    
    // Clear container
    labels.forEach(label => label.remove());
    letters.forEach(letter => letter.remove());
    
    // Add checked first (always visible)
    checked.forEach(label => {
        label.classList.remove('filter-hidden');
        container.appendChild(label);
    });
    
    // Add unchecked after with letter dividers
    let currentLetter = '';
    let addedCount = 0;
    const showLimit = 5 - checked.length; // Adjust limit based on checked items
    
    unchecked.forEach((label) => {
        const manufacturerName = label.dataset.manufacturer;
        const firstLetter = manufacturerName.charAt(0).toUpperCase();
        const hasLogo = label.querySelector('img') !== null;
        
        // Add letter divider for items without logos
        if (!hasLogo && firstLetter !== currentLetter) {
            currentLetter = firstLetter;
            const letterDiv = document.createElement('div');
            letterDiv.className = 'manufacturer-letter';
            if (addedCount >= showLimit) {
                letterDiv.classList.add('filter-hidden');
            }
            letterDiv.textContent = currentLetter;
            container.appendChild(letterDiv);
        }
        
        // Add manufacturer label
        if (addedCount >= showLimit) {
            label.classList.add('filter-hidden');
        } else {
            label.classList.remove('filter-hidden');
        }
        container.appendChild(label);
        addedCount++;
    });
}

// Price preset handler
function applyPricePreset(radio) {
    const value = radio.value;
    const minInput = document.getElementById('minPriceInput');
    const maxInput = document.getElementById('maxPriceInput');
    
    if (value) {
        const [min, max] = value.split('-');
        minInput.value = min || '';
        maxInput.value = max || '';
    } else {
        minInput.value = '';
        maxInput.value = '';
    }
    
    // Immediately apply filter
    loadFilteredComponents();
}

// Handle manual price input
function handlePriceInput() {
    // Uncheck all presets when manually entering price
    document.querySelectorAll('input[name="price_preset"]').forEach(radio => {
        radio.checked = false;
    });
    
    const minInput = document.getElementById('minPriceInput');
    const maxInput = document.getElementById('maxPriceInput');
    
    // Check "Неважно" if both inputs are empty
    if (!minInput.value && !maxInput.value) {
        const noPreferenceRadio = document.querySelector('input[name="price_preset"][value=""]');
        if (noPreferenceRadio) {
            noPreferenceRadio.checked = true;
        }
    }
    
    // Apply filter with debounce
    applyFilters();
}

// AJAX filter function
let filterTimeout = null;
function applyFilters() {
    // Debounce for search input
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        loadFilteredComponents();
    }, 300);
}

async function loadFilteredComponents() {
    const category = document.querySelector('input[name="category"]:checked')?.value || '';
    
    // Render category-specific filters
    renderCategoryFilters(category);
    
    // Load manufacturers and price range only if category changed
    if (category !== currentManufacturerCategory) {
        currentManufacturerCategory = category;
        await Promise.all([
            loadManufacturersByCategory(category),
            loadPriceRange(category)
        ]);
    }
    
    // Collect all checked manufacturers
    const checkedManufacturers = Array.from(document.querySelectorAll('.manufacturer-checkbox:checked'))
        .map(cb => cb.value);
    const manufacturer = checkedManufacturers.join(',');
    
    const minPriceInput = document.getElementById('minPriceInput');
    const maxPriceInput = document.getElementById('maxPriceInput');
    const minPrice = minPriceInput?.value || '';
    const maxPrice = maxPriceInput?.value || '';
    const minYear = document.querySelector('input[name="min_year"]')?.value || '';
    const maxYear = document.querySelector('input[name="max_year"]')?.value || '';
    const search = document.querySelector('input[name="search"]')?.value || '';
    const sort = document.querySelector('select[name="sort"]')?.value || 'name_asc';
    
    // Collect category-specific filter values
    const categoryFilterInputs = document.querySelectorAll('.category-filter-input');
    const categoryFilterCheckboxes = document.querySelectorAll('.category-filter-checkbox:checked');
    
    const categorySpecificParams = {};
    categoryFilterInputs.forEach(input => {
        if (input.value) {
            categorySpecificParams[input.name] = input.value;
        }
    });
    
    // Group checkboxes by name
    const checkboxGroups = {};
    categoryFilterCheckboxes.forEach(cb => {
        if (!checkboxGroups[cb.name]) {
            checkboxGroups[cb.name] = [];
        }
        checkboxGroups[cb.name].push(cb.value);
    });
    
    Object.keys(checkboxGroups).forEach(name => {
        categorySpecificParams[name] = checkboxGroups[name].join(',');
    });
    
    // Build URL
    const params = new URLSearchParams();
    if (category) params.append('category', category);
    if (manufacturer) params.append('manufacturer', manufacturer);
    // Always send price params to avoid empty results
    params.append('min_price', minPrice || '0');
    params.append('max_price', maxPrice || '999999');
    if (minYear) params.append('min_year', minYear);
    if (maxYear) params.append('max_year', maxYear);
    if (search) params.append('search', search);
    params.append('sort', sort);
    
    // Add category-specific params
    Object.keys(categorySpecificParams).forEach(key => {
        params.append(key, categorySpecificParams[key]);
    });
    
    // Show loading state
    const productsGrid = document.querySelector('.products-grid');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    productsGrid.classList.add('loading');
    loadingOverlay.classList.add('active');
    
    try {
        const url = 'api/filter_components.php?' + params.toString();
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const text = await response.text();
        
        const data = JSON.parse(text);
        
        if (data.success) {
            // Smooth transition
            await new Promise(resolve => setTimeout(resolve, 200));
            
            productsGrid.innerHTML = data.html;
            
            // Update both counts in the results display
            const loadedCountEl = document.getElementById('loadedCount');
            const totalCountEl = document.getElementById('totalCount');
            
            if (loadedCountEl) {
                loadedCountEl.textContent = data.count;
            }
            
            // Update total count (second number)
            if (totalCountEl) {
                totalCountEl.textContent = data.total;
            }
            
            // Update counts and reset pagination
            productsGrid.dataset.page = '1';
            productsGrid.dataset.loaded = data.count;
            productsGrid.dataset.total = data.total;
            
            // Reset infinite scroll state
            hasMoreItems = data.count < data.total;
            isLoadingMore = false;
            
            // Remove "all loaded" message if exists
            const allLoadedMsg = document.getElementById('allLoadedMessage');
            if (allLoadedMsg) {
                allLoadedMsg.remove();
            }
            
            // Ensure loader exists and show/hide based on if there are more items
            let loader = document.getElementById('infiniteLoader');
            if (!loader && hasMoreItems) {
                // Create loader if it doesn't exist
                loader = document.createElement('div');
                loader.id = 'infiniteLoader';
                loader.className = 'infinite-scroll-loader';
                loader.innerHTML = `
                    <div class="loader-content">
                        <div class="loader-spinner">
                            <div class="spinner-dot"></div>
                            <div class="spinner-dot"></div>
                            <div class="spinner-dot"></div>
                        </div>
                        <span class="loader-text">Загружаем еще товары...</span>
                    </div>
                `;
                productsGrid.parentElement.appendChild(loader);
                
                // Re-initialize observer for new loader
                initInfiniteScroll();
            }
            
            if (loader) {
                loader.style.display = hasMoreItems ? 'flex' : 'none';
            }
            
            // Re-initialize add to build buttons
            initAddToBuildButtons(productsGrid);
            applyRemoteImages(productsGrid);
            applyRemoteImages(productsGrid);
        } else {
            showToast('Ошибка загрузки', 'error');
        }
    } catch (error) {
        console.error('Filter error:', error);
        showToast('Ошибка загрузки', 'error');
    } finally {
        productsGrid.classList.remove('loading');
        loadingOverlay.classList.remove('active');
    }
    
    // Update URL without reload
    const newUrl = 'catalog.php?' + params.toString();
    window.history.pushState({}, '', newUrl);
}

function resetFilters() {
    // Reset all inputs
    document.querySelectorAll('input[name="category"]')[0].checked = true;
    
    // Uncheck all manufacturers
    document.querySelectorAll('.manufacturer-checkbox').forEach(cb => cb.checked = false);
    
    document.querySelector('input[name="price_preset"][value=""]').checked = true;
    document.getElementById('minPriceInput').value = '';
    document.getElementById('maxPriceInput').value = '';
    document.querySelector('input[name="min_year"]').value = '';
    document.querySelector('input[name="max_year"]').value = '';
    
    // Reset search inputs
    const searchInputs = document.querySelectorAll('input[name="search"]');
    searchInputs.forEach(input => input.value = '');
    
    document.querySelector('select[name="sort"]').value = 'name_asc';
    
    // Clear category-specific filters
    currentCategorySlug = '';
    document.getElementById('categorySpecificFilters').innerHTML = '';
    
    // Reorder manufacturers back to original
    reorderManufacturers();
    
    applyFilters();
}

// legacy initAddToBuild removed - use BuildSync helpers below

let toastContainerEl = null;

// Initialize category filters and price range on page load
document.addEventListener('DOMContentLoaded', function() {
    const selectedCategory = document.querySelector('input[name="category"]:checked')?.value || '';
    renderCategoryFilters(selectedCategory);
    loadPriceRange(selectedCategory);
    currentManufacturerCategory = selectedCategory;
    
    // Initialize infinite scroll
    initInfiniteScroll();

    applyRemoteImages(document);
});

// Infinite scroll variables
let isLoadingMore = false;
let hasMoreItems = true;
let infiniteScrollObserver = null;

// Initialize infinite scroll
function initInfiniteScroll() {
    // Disconnect old observer if exists
    if (infiniteScrollObserver) {
        infiniteScrollObserver.disconnect();
    }
    
    infiniteScrollObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !isLoadingMore && hasMoreItems) {
                loadMoreComponents();
            }
        });
    }, {
        root: null,
        rootMargin: '200px',
        threshold: 0.1
    });
    
    const loader = document.getElementById('infiniteLoader');
    if (loader) {
        infiniteScrollObserver.observe(loader);
    }
}

// Load more components
async function loadMoreComponents() {
    if (isLoadingMore || !hasMoreItems) return;
    
    isLoadingMore = true;
    const loader = document.getElementById('infiniteLoader');
    const productsGrid = document.querySelector('.products-grid');
    const currentPage = parseInt(productsGrid.dataset.page) || 1;
    const totalLoaded = parseInt(productsGrid.dataset.loaded) || 0;
    const totalCount = parseInt(productsGrid.dataset.total) || 0;
    
    // Check if we have more items to load
    if (totalLoaded >= totalCount) {
        hasMoreItems = false;
        loader.style.display = 'none';
        
        // Show "all loaded" message
        if (!document.getElementById('allLoadedMessage')) {
            const message = document.createElement('div');
            message.id = 'allLoadedMessage';
            message.className = 'all-loaded-message';
            message.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>Все товары загружены</span>
            `;
            productsGrid.parentElement.appendChild(message);
        }
        return;
    }
    
    // Show loader (it's already visible, just make sure)
    loader.style.display = 'flex';
    
    // Collect current filters
    const category = document.querySelector('input[name="category"]:checked')?.value || '';
    const checkedManufacturers = Array.from(document.querySelectorAll('.manufacturer-checkbox:checked'))
        .map(cb => cb.value);
    const manufacturer = checkedManufacturers.join(',');
    const minPrice = document.getElementById('minPriceInput')?.value || '';
    const maxPrice = document.getElementById('maxPriceInput')?.value || '';
    const minYear = document.querySelector('input[name="min_year"]')?.value || '';
    const maxYear = document.querySelector('input[name="max_year"]')?.value || '';
    const search = document.querySelector('input[name="search"]')?.value || '';
    const sort = document.querySelector('select[name="sort"]')?.value || 'name_asc';
    
    // Collect category-specific filters
    const categoryFilterInputs = document.querySelectorAll('.category-filter-input');
    const categoryFilterCheckboxes = document.querySelectorAll('.category-filter-checkbox:checked');
    const categorySpecificParams = {};
    
    categoryFilterInputs.forEach(input => {
        if (input.value) {
            categorySpecificParams[input.name] = input.value;
        }
    });
    
    const checkboxGroups = {};
    categoryFilterCheckboxes.forEach(cb => {
        if (!checkboxGroups[cb.name]) {
            checkboxGroups[cb.name] = [];
        }
        checkboxGroups[cb.name].push(cb.value);
    });
    
    Object.keys(checkboxGroups).forEach(name => {
        categorySpecificParams[name] = checkboxGroups[name].join(',');
    });
    
    // Build params
    const params = new URLSearchParams();
    params.append('page', currentPage + 1);
    if (category) params.append('category', category);
    if (manufacturer) params.append('manufacturer', manufacturer);
    if (minPrice) params.append('min_price', minPrice);
    if (maxPrice) params.append('max_price', maxPrice);
    if (minYear) params.append('min_year', minYear);
    if (maxYear) params.append('max_year', maxYear);
    if (search) params.append('search', search);
    if (sort) params.append('sort', sort);
    
    Object.keys(categorySpecificParams).forEach(key => {
        params.append(key, categorySpecificParams[key]);
    });
    
    try {
        const response = await fetch('api/load_more_components.php?' + params.toString());
        const data = await response.json();
        
        if (data.success && data.html) {
            // Add delay for smooth animation
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Create temporary container for new items
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;
            
            // Animate each new card
            const newCards = Array.from(tempDiv.children);
            newCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                productsGrid.appendChild(card);
                
                // Stagger animation
                setTimeout(() => {
                    card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
            
            // Update page and loaded count
            productsGrid.dataset.page = currentPage + 1;
            productsGrid.dataset.loaded = totalLoaded + data.count;
            
            // Update counter
            const loadedCountEl = document.getElementById('loadedCount');
            if (loadedCountEl) {
                loadedCountEl.textContent = totalLoaded + data.count;
            }
            
            // Check if there are more items
            hasMoreItems = data.hasMore;
            
            // Hide loader if no more items
            if (!hasMoreItems) {
                loader.style.display = 'none';
                
                // Show "all loaded" message
                if (!document.getElementById('allLoadedMessage')) {
                    const message = document.createElement('div');
                    message.id = 'allLoadedMessage';
                    message.className = 'all-loaded-message';
                    message.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        <span>Все товары загружены</span>
                    `;
                    productsGrid.parentElement.appendChild(message);
                }
            }
            
            // Re-initialize add to build buttons
            initAddToBuildButtons(productsGrid);
        } else {
            hasMoreItems = false;
            loader.style.display = 'none';
        }
    } catch (error) {
        console.error('Load more error:', error);
        hasMoreItems = false;
        loader.style.display = 'none';
    } finally {
        isLoadingMore = false;
    }
}

const BuildSync = (() => {
    const STORAGE_KEY = 'currentBuild';

    function load() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            console.warn('Failed to parse build storage', error);
            localStorage.removeItem(STORAGE_KEY);
            return {};
        }
    }

    function save(state) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    function emitUpdate(state) {
        window.dispatchEvent(new CustomEvent('build:updated', { detail: state }));
    }

    function getComponents() {
        return load();
    }

    function addComponent(component) {
        const state = load();
        const categoryId = String(component.categoryId);
        if (!categoryId) return { added: false, reason: 'missing_category' };

        if (!state[categoryId]) {
            state[categoryId] = categoryId === '5' ? [] : null;
        }

        const componentData = { ...component, categoryId };

        if (categoryId === '5') {
            const exists = Array.isArray(state[categoryId]) && state[categoryId].some(item => String(item.id) === String(component.id));
            if (exists) {
                return { added: false, reason: 'duplicate' };
            }
            state[categoryId].push(componentData);
        } else {
            if (state[categoryId] && state[categoryId].id && String(state[categoryId].id) === String(component.id)) {
                return { added: false, reason: 'duplicate' };
            }
            state[categoryId] = componentData;
        }

        save(state);
        emitUpdate(state);
        return { added: true, state };
    }

    function removeComponent(categoryId, componentId) {
        const state = load();
        const targetId = String(componentId);
        const categoryKey = categoryId ? String(categoryId) : null;
        let removed = false;

        const removeFromCategory = (catId) => {
            const bucket = state[catId];
            if (!bucket) return false;

            if (Array.isArray(bucket)) {
                const index = bucket.findIndex(item => String(item.id) === targetId);
                if (index !== -1) {
                    bucket.splice(index, 1);
                    if (bucket.length === 0) {
                        delete state[catId];
                    }
                    return true;
                }
            } else if (bucket && bucket.id && String(bucket.id) === targetId) {
                delete state[catId];
                return true;
            }
            return false;
        };

        if (categoryKey) {
            removed = removeFromCategory(categoryKey);
        } else {
            Object.keys(state).some(catId => {
                if (removeFromCategory(catId)) {
                    removed = true;
                    return true;
                }
                return false;
            });
        }

        if (removed) {
            save(state);
            emitUpdate(state);
            return { removed: true, state };
        }

        return { removed: false, state };
    }

    function getFlatIds() {
        const state = load();
        const ids = new Set();
        Object.values(state).forEach(value => {
            if (Array.isArray(value)) {
                value.forEach(item => ids.add(String(item.id)));
            } else if (value && value.id) {
                ids.add(String(value.id));
            }
        });
        return ids;
    }

    return { load, getComponents, addComponent, removeComponent, getFlatIds };
})();

const componentModal = document.getElementById('componentModal');
const modalCategory = document.getElementById('componentModalCategory');
const modalTitle = document.getElementById('componentModalTitle');
const modalSubtitle = document.getElementById('componentModalSubtitle');
const modalPrice = document.getElementById('componentModalPrice');
const modalSpecs = document.getElementById('componentModalSpecs');
const modalReviews = document.getElementById('componentModalReviews');
const modalReviewsSummary = document.getElementById('componentModalReviewsSummary');

function formatCurrency(value) {
    const number = Number(value || 0);
    return number.toLocaleString('ru-RU') + ' ₽';
}

function openComponentModal() {
    if (!componentModal) return;
    componentModal.classList.add('is-visible');
    document.body.classList.add('no-scroll');
    componentModal.setAttribute('aria-hidden', 'false');
}

function closeComponentModal() {
    if (!componentModal) return;
    componentModal.classList.remove('is-visible');
    document.body.classList.remove('no-scroll');
    componentModal.setAttribute('aria-hidden', 'true');
}

function setActiveTab(tabName) {
    if (!componentModal) return;
    componentModal.querySelectorAll('.modal-tab').forEach(tab => {
        tab.classList.toggle('is-active', tab.dataset.tab === tabName);
    });
    componentModal.querySelectorAll('.component-modal-panel').forEach(panel => {
        panel.classList.toggle('is-active', panel.dataset.panel === tabName);
    });
}

function renderSpecs(specs) {
    if (!modalSpecs) return;
    if (!specs || specs.length === 0) {
        modalSpecs.innerHTML = '<div class="spec-item empty">Нет данных</div>';
        return;
    }
    modalSpecs.innerHTML = specs.map(section => `
        <div class="spec-section">
            <div class="spec-section-title">${section.title}</div>
            ${section.items.map(spec => `
                <div class="spec-item">
                    <span>${spec.label}</span>
                    <strong>${spec.value}</strong>
                </div>
            `).join('')}
        </div>
    `).join('');
}

function renderReviews(reviews, stats) {
    if (!modalReviews) return;
    const total = stats?.total || 0;
    const avg = stats?.avg_rating || 0;
    if (modalReviewsSummary) {
        modalReviewsSummary.textContent = total ? `${avg}/5 — средняя оценка · ${total} обзор(ов)` : 'Пока нет обзоров';
    }
    if (!reviews || reviews.length === 0) {
        modalReviews.innerHTML = '<div class="review-empty">Обзоры пока не добавлены.</div>';
        return;
    }
    const clampText = (text) => {
        if (!text) return '';
        const trimmed = text.trim();
        if (trimmed.length <= 220) return trimmed;
        return trimmed.slice(0, 220).trim() + '…';
    };
    modalReviews.innerHTML = reviews.map(review => `
        <a class="review-item" href="reviews.php">
            <div class="review-item-header">
                <div class="review-author">
                    ${review.avatar ? `<img src="${review.avatar}" alt="${review.username}">` : '<i class="fas fa-user"></i>'}
                    <span>${review.username}</span>
                </div>
                <div class="review-rating">Оценка: ${review.rating}/5</div>
            </div>
            <div class="review-title">${review.title}</div>
            <p>${clampText(review.summary)}</p>
            <div class="review-mini">
                ${review.pros ? `<span class="review-chip positive">+ ${clampText(review.pros)}</span>` : ''}
                ${review.cons ? `<span class="review-chip negative">- ${clampText(review.cons)}</span>` : ''}
                ${review.usage_context ? `<span class="review-chip">Сценарий: ${clampText(review.usage_context)}</span>` : ''}
            </div>
            <div class="review-cta">Открыть обзор →</div>
        </a>
    `).join('');
}

async function loadComponentDetails(componentId, categoryId) {
    const response = await fetch(`api/get_component_details.php?id=${componentId}&category_id=${categoryId}`);
    const data = await response.json();
    if (!data.success) {
        throw new Error(data.error || 'load_failed');
    }
    return data;
}

function handleComponentCardClick(event) {
    const card = event.target.closest('.component-card');
    if (!card) return;
    if (event.target.closest('.btn-add-to-build')) {
        return;
    }
    const componentId = card.dataset.componentId;
    const categoryId = card.dataset.categoryId;
    if (!componentId || !categoryId) return;

    if (modalTitle) modalTitle.textContent = 'Загрузка...';
    if (modalSubtitle) modalSubtitle.textContent = '';
    if (modalCategory) modalCategory.textContent = '';
    if (modalPrice) modalPrice.textContent = '';
    renderSpecs([]);
    renderReviews([], { total: 0, avg_rating: 0 });
    setActiveTab('specs');
    openComponentModal();

    loadComponentDetails(componentId, categoryId)
        .then(data => {
            const component = data.component;
            if (modalTitle) modalTitle.textContent = component.name;
            if (modalCategory) modalCategory.textContent = component.category;
            if (modalSubtitle) {
                const subtitle = [component.manufacturer, component.model].filter(Boolean).join(' · ');
                modalSubtitle.textContent = subtitle;
            }
            if (modalPrice) modalPrice.textContent = formatCurrency(component.price);
            renderSpecs(component.specs);
            renderReviews(data.reviews, data.review_stats);
        })
        .catch(() => {
            if (modalTitle) modalTitle.textContent = 'Не удалось загрузить данные';
        });
}

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-modal-close]')) {
        closeComponentModal();
        return;
    }
    const tabButton = event.target.closest('.modal-tab');
    if (tabButton) {
        setActiveTab(tabButton.dataset.tab);
        return;
    }
    if (event.target.closest('.component-card')) {
        handleComponentCardClick(event);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeComponentModal();
    }
});

function initAddToBuildButtons(scope = document) {
    const addedIds = BuildSync.getFlatIds();
    scope.querySelectorAll('.btn-add-to-build').forEach(btn => {
        const isAdded = addedIds.has(String(btn.dataset.id));
        setButtonState(btn, isAdded);
        btn.removeEventListener('click', handleBuildToggle);
        btn.addEventListener('click', handleBuildToggle);
    });
}

function handleBuildToggle(event) {
    const btn = event.currentTarget;
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    const price = parseFloat(btn.dataset.price) || 0;
    const categoryId = btn.dataset.category;
    const isAdded = btn.dataset.state === 'added';

    if (isAdded) {
        const result = BuildSync.removeComponent(categoryId, id);
        if (result.removed) {
            setButtonState(btn, false);
            showToast(`${name} удалён из сборки`, 'info');
            updateBuildCounter(result.state);
        } else {
            showToast('Не удалось удалить компонент', 'error');
        }
    } else {
        const result = BuildSync.addComponent({ id, name, price, categoryId });

        if (result.added) {
            setButtonState(btn, true);
            showToast(`${name} добавлен в сборку`, 'success');
            updateBuildCounter(result.state);
        } else {
            showToast('Компонент уже в сборке', 'warning');
            setButtonState(btn, true);
        }
    }
}

function setButtonState(button, isAdded) {
    if (isAdded) {
        button.innerHTML = '<i class="fas fa-check"></i> Добавлено';
        button.classList.add('btn-success', 'btn-added');
        button.dataset.state = 'added';
        button.dataset.hoverText = 'Удалить';
        button.disabled = false;
    } else {
        button.innerHTML = '<i class="fas fa-plus"></i> В сборку';
        button.classList.remove('btn-success', 'btn-added');
        button.dataset.state = 'idle';
        delete button.dataset.hoverText;
        button.disabled = false;
    }
}

function updateBuildCounter(state = null) {
    const buildState = state || BuildSync.getComponents();
    const totalComponents = Object.values(buildState).reduce((sum, value) => {
        if (Array.isArray(value)) {
            return sum + value.length;
        }
        return value ? sum + 1 : sum;
    }, 0);

    const counter = document.getElementById('buildCounter');
    if (counter) {
        counter.textContent = totalComponents;
        counter.style.display = totalComponents > 0 ? 'flex' : 'none';
    }
}

function showToast(message, type = 'info') {
    if (!toastContainerEl) {
        toastContainerEl = document.createElement('div');
        toastContainerEl.className = 'toast-container toast-container--catalog';
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

initAddToBuildButtons(document);
updateBuildCounter();

window.addEventListener('build:updated', (event) => {
    initAddToBuildButtons(document);
    updateBuildCounter(event.detail);
});

window.addEventListener('storage', event => {
    if (event.key === 'currentBuild') {
        initAddToBuildButtons(document);
        updateBuildCounter();
    }
});

// Keep success buttons disabled visual
const successButtonStyles = document.createElement('style');
successButtonStyles.textContent = `
    .btn-success {
        background: var(--success) !important;
        cursor: not-allowed !important;
    }
`;
document.head.appendChild(successButtonStyles);
