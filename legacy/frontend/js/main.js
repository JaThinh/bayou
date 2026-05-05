import { destinationsData, newsData, partnersData } from './data.js';
import { loadComponent, renderCards, renderPartners, initHeader, initModals, initScrollToTop, initHeroSlider } from './ui.js';
import { initSearchWidget } from './searchWidget.js';
import { initPopups } from './popups.js';

// =========================================
// INIT APPLICATION
// =========================================
async function initApp() {
    // 1. Tải tất cả HTML Components (Có thêm cache buster để tránh lỗi lưu cache cũ)
    const v = Date.now();
    await Promise.all([
        loadComponent('header-container', `./components/header/header.html?v=${v}`),
        loadComponent('hero-container', `./components/hero/hero.html?v=${v}`),
        loadComponent('destinations-container', `./components/destinations/destinations.html?v=${v}`),
        loadComponent('news-container', `./components/news/news.html?v=${v}`),
        loadComponent('partners-container', `./components/partners/partners.html?v=${v}`),
        loadComponent('footer-container', `./components/footer/footer.html?v=${v}`),
        loadComponent('modals-container', `./components/modals/modals.html?v=${v}`)
    ]);

    // 2. Render Data Cards
    renderCards(destinationsData, 'destinations-grid');
    renderCards(newsData, 'news-grid');
    renderPartners();

    // 3. Khởi tạo Logic UI
    initHeader();
    initModals();
    initScrollToTop();
    
    // 4. Khởi tạo Widget tìm kiếm và Popup
    initSearchWidget();
    initPopups();
    initHeroSlider();

    // 5. Ẩn hiệu ứng Loading (Giả lập delay)
    setTimeout(() => {
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.classList.add('loader-overlay--hidden');
            setTimeout(() => loader.style.display = 'none', 500);
        }
    }, 800);
}

// Chạy ứng dụng
initApp();
