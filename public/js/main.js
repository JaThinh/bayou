import { destinationsData, newsData, partnersData } from './data.js';
import { loadComponent, renderCards, renderPartners, initHeader, initModals, initScrollToTop, initHeroSlider } from './ui.js';
import { initSearchWidget } from './searchWidget.js';
import { initPopups } from './popups.js';

// =========================================
// INIT APPLICATION
// =========================================
async function initApp() {
    // 1. Tải tất cả HTML Components
    await Promise.all([
        loadComponent('header-container', './components/header/header.html'),
        loadComponent('hero-container', './components/hero/hero.html'),
        loadComponent('destinations-container', './components/destinations/destinations.html'),
        loadComponent('news-container', './components/news/news.html'),
        loadComponent('partners-container', './components/partners/partners.html'),
        loadComponent('footer-container', './components/footer/footer.html'),
        loadComponent('modals-container', './components/modals/modals.html')
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
