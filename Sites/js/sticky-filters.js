// Smart sticky filters - stick to top or bottom depending on scroll direction
let lastScrollTop = 0;
let filtersSidebar = null;
let stickyOffset = 0;
const headerHeight = 90;

function initStickyFilters() {
    filtersSidebar = document.querySelector('.filters-sidebar');
    if (!filtersSidebar) return;
    
    window.addEventListener('scroll', handleStickyFilters, { passive: true });
}

function handleStickyFilters() {
    if (!filtersSidebar) return;
    
    const scrollTop = window.scrollY;
    const scrollDirection = scrollTop > lastScrollTop ? 'down' : 'up';
    const windowHeight = window.innerHeight;
    const filtersHeight = filtersSidebar.offsetHeight;
    
    // If filters are shorter than viewport, always stick to top
    if (filtersHeight <= windowHeight - headerHeight - 20) {
        filtersSidebar.style.top = headerHeight + 'px';
        filtersSidebar.style.bottom = 'auto';
        lastScrollTop = scrollTop;
        return;
    }
    
    // For tall filters, adjust sticky position based on scroll
    const currentTop = parseInt(filtersSidebar.style.top) || headerHeight;
    const scrollDelta = scrollTop - lastScrollTop;
    
    if (scrollDirection === 'down') {
        // Scrolling down - move filters up (decrease top value)
        const newTop = Math.max(
            -(filtersHeight - windowHeight + 20),
            currentTop - scrollDelta
        );
        filtersSidebar.style.top = newTop + 'px';
        filtersSidebar.style.bottom = 'auto';
    } else {
        // Scrolling up - move filters down (increase top value)
        const newTop = Math.min(
            headerHeight,
            currentTop - scrollDelta
        );
        filtersSidebar.style.top = newTop + 'px';
        filtersSidebar.style.bottom = 'auto';
    }
    
    lastScrollTop = scrollTop;
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStickyFilters);
} else {
    initStickyFilters();
}
