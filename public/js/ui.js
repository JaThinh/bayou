import { destinationsData, newsData, partnersData } from './data.js';
import { updateLanguage } from './i18n.js';

// =========================================
// COMPONENT LOADER & RENDERERS
// =========================================
export async function loadComponent(id, url) {
    try {
        const response = await fetch(url);
        const html = await response.text();
        document.getElementById(id).innerHTML = html;
    } catch (error) {
        console.error(`Error loading component ${url}:`, error);
    }
}

export function renderCards(data, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    let html = '';
    data.forEach(item => {
        html += `
            <div class="article-card">
                <img src="${item.img}" alt="${item.title}" class="article-card__img">
                <div class="article-card__content">
                    <h3 class="article-card__heading">${item.title}</h3>
                    <div class="article-card__meta">
                        <span class="article-card__icon">⏱</span> ${item.date}
                    </div>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;

    // Attach click events
    container.querySelectorAll('.article-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
            const title = card.querySelector('.article-card__heading').innerText;
            alert('Đang xem chi tiết: ' + title);
        });
    });
}

export function renderPartners() {
    const container = document.getElementById('partners-grid');
    if (!container) return;

    let html = '';
    partnersData.forEach(partner => {
        if (partner.url) {
            html += `<a href="${partner.url}" target="_blank" title="${partner.name}" class="partners__link">
                        <img src="${partner.img}" alt="${partner.name}" class="partners__logo">
                     </a>`;
        } else {
            html += `<img src="${partner.img}" alt="${partner.name}" class="partners__logo" title="${partner.name}">`;
        }
    });
    container.innerHTML = html;
}

// =========================================
// HEADER & GLOBAL UI
// =========================================
export function initHeader() {
    // Language Dropdown
    const btnLang = document.getElementById('btn-lang');
    const langDropdown = document.getElementById('lang-dropdown');

    if (btnLang && langDropdown) {
        btnLang.addEventListener('click', (e) => {
            e.stopPropagation();
            langDropdown.classList.toggle('lang-selector__dropdown--show');
        });

        document.addEventListener('click', (e) => {
            if (!langDropdown.contains(e.target) && !btnLang.contains(e.target)) {
                langDropdown.classList.remove('lang-selector__dropdown--show');
            }
        });

        const langOptions = langDropdown.querySelectorAll('.lang-selector__option');
        langOptions.forEach(option => {
            option.addEventListener('click', () => {
                langOptions.forEach(opt => opt.classList.remove('lang-selector__option--active'));
                option.classList.add('lang-selector__option--active');

                const selectedLangCode = option.querySelector('.lang-selector__flag').alt;
                const btnText = document.getElementById('current-lang-text');
                if (btnText) {
                    btnText.textContent = selectedLangCode;
                }

                langDropdown.classList.remove('lang-selector__dropdown--show');
                
                // Trigger Language Update
                updateLanguage(selectedLangCode);
            });
        });
    }

    // Live Exchange Rate
    const btnExchange = document.getElementById('btn-exchange');
    if (btnExchange) {
        fetch('/api/currency/rate.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.success && data.data && data.data.rate) {
                    const vndRate = data.data.rate;
                    const formattedRate = new Intl.NumberFormat('vi-VN').format(Math.round(vndRate));
                    btnExchange.innerHTML = `$ 1,00 = ₫ ${formattedRate}`;
                }
            })
            .catch(() => {
                btnExchange.innerHTML = `Lỗi mạng`;
            });
    }

    // Login Redirect
    const btnLogin = document.getElementById('btn-login');
    if (btnLogin) {
        btnLogin.addEventListener('click', () => {
            window.location.href = 'login.html';
        });
    }
}

// =========================================
// MODALS
// =========================================
export function initModals() {
    const setupModal = (btnId, modalId, closeId) => {
        const btn = document.getElementById(btnId);
        const modal = document.getElementById(modalId);
        const closeBtn = document.getElementById(closeId);

        if (btn && modal && closeBtn) {
            btn.addEventListener('click', () => modal.classList.add('modal--show'));
            closeBtn.addEventListener('click', () => modal.classList.remove('modal--show'));
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('modal--show');
            });
        }
    };

    setupModal('btn-booking', 'booking-modal', 'booking-modal-close');
    
    // Booking Form Submission
    const bookingSubmit = document.getElementById('booking-submit');
    if (bookingSubmit) {
        bookingSubmit.addEventListener('click', () => {
            const bookingCode = document.getElementById('booking-code').value;
            if(!bookingCode) {
                alert('Vui lòng nhập mã Booking!');
                return;
            }
            bookingSubmit.textContent = 'Đang tìm kiếm...';
            setTimeout(() => {
                alert('Đã tìm thấy thông tin đặt chỗ: ' + bookingCode);
                bookingSubmit.textContent = 'Tìm Kiếm 🔍';
                document.getElementById('booking-modal').classList.remove('modal--show');
            }, 1000);
        });
    }
}

// =========================================
// SCROLL TO TOP
// =========================================
export function initScrollToTop() {
    const btnScrollTop = document.getElementById('btn-scroll-top');
    if (btnScrollTop) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                btnScrollTop.style.display = 'flex';
            } else {
                btnScrollTop.style.display = 'none';
            }
        });

        btnScrollTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}

// =========================================
// HERO SLIDER
// =========================================
export function initHeroSlider() {
    const bgSlider = document.querySelector('.hero-slider');
    const bgSlides = document.querySelectorAll('.hero-slide');
    const dotsContainer = document.querySelector('.slider-dots');
    
    if (bgSlider && bgSlides && bgSlides.length > 0 && dotsContainer) {
        let currentBg = 0;
        let slideInterval;

        // Create dots dynamically
        bgSlides.forEach((_, index) => {
            const dot = document.createElement('div');
            dot.classList.add('dot');
            if (index === 0) dot.classList.add('active');
            
            dot.addEventListener('click', () => {
                goToSlide(index);
                resetInterval();
            });
            
            dotsContainer.appendChild(dot);
        });

        const dots = document.querySelectorAll('.dot');

        function goToSlide(index) {
            currentBg = index;
            bgSlider.style.transform = `translateX(-${currentBg * 100}%)`;
            
            // Update dots
            dots.forEach(dot => dot.classList.remove('active'));
            dots[currentBg].classList.add('active');
        }

        function nextSlide() {
            currentBg = (currentBg + 1) % bgSlides.length;
            goToSlide(currentBg);
        }

        function resetInterval() {
            clearInterval(slideInterval);
            slideInterval = setInterval(nextSlide, 5000);
        }

        // Start initial interval
        slideInterval = setInterval(nextSlide, 5000);
    }
}
