import { destinationsData, newsData, partnersData } from './data.js';
import { renderCards, renderPartners, initHeader, initModals, initScrollToTop, initHeroSlider } from './ui.js';
import { initSearchWidget } from './searchWidget.js';
import { initPopups } from './popups.js';
import { initParticles, initParallax, initScrollReveal, initSmoothScroll, initRippleEffect, initCardTilt } from './particles.js';

async function initApp() {
    // 1. Particles
    initParticles();

    // 2. Render dynamic data cards (no component loading needed — HTML inline)
    renderCards(destinationsData, 'destinations-grid');
    renderCards(newsData, 'news-grid');
    renderPartners();

    // 3. UI Logic
    initHeader();
    initModals();
    initScrollToTop();

    // 4. Search + Popups
    initSearchWidget();
    initPopups();
    initHeroSlider();

    // 5. Effects
    initParallax();
    initSmoothScroll();
    initRippleEffect();

    // 6. Animations after paint
    setTimeout(() => {
        initScrollReveal();
        initCardTilt();
    }, 500);

    // 7. Hide loader
    setTimeout(() => {
        const loader = document.getElementById('global-loader');
        if (loader) {
            loader.classList.add('loader-overlay--hidden');
            setTimeout(() => {
                loader.style.display = 'none';
                const app = document.getElementById('app');
                if (app) app.style.animation = 'fadeIn 0.8s ease-in-out';
            }, 500);
        }
    }, 1200);
}

initApp();
