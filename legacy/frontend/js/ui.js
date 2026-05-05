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
        // Toggle dropdown
        btnLang.addEventListener('click', (e) => {
            e.stopPropagation();
            langDropdown.classList.toggle('lang-selector__dropdown--show');
            btnLang.classList.toggle('open');
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!langDropdown.contains(e.target) && !btnLang.contains(e.target)) {
                langDropdown.classList.remove('lang-selector__dropdown--show');
                btnLang.classList.remove('open');
            }
        });

        const langOptions = langDropdown.querySelectorAll('.lang-selector__option');
        const currentLangText = document.getElementById('current-lang-text');
        const currentLangFlag = document.getElementById('current-lang-flag');

        // Restore from localStorage
        const savedLang = localStorage.getItem('bayou_lang') || 'VN';
        
        function setLanguage(langCode) {
            langOptions.forEach(opt => {
                if (opt.getAttribute('data-lang') === langCode) {
                    opt.classList.add('lang-selector__option--active');
                    if (currentLangText) currentLangText.textContent = langCode;
                    if (currentLangFlag) currentLangFlag.src = opt.querySelector('.lang-selector__flag').src;
                } else {
                    opt.classList.remove('lang-selector__option--active');
                }
            });
            localStorage.setItem('bayou_lang', langCode);
            updateLanguage(langCode);
        }

        // Handle click on options
        langOptions.forEach(option => {
            option.addEventListener('click', () => {
                const selectedLangCode = option.getAttribute('data-lang');
                setLanguage(selectedLangCode);
                langDropdown.classList.remove('lang-selector__dropdown--show');
                btnLang.classList.remove('open');
            });
        });

        // Initial load
        setLanguage(savedLang);
    }

    // Live Exchange Rate - Continuous Update from OUR Backend
    const btnExchange = document.getElementById('btn-exchange');
    let lastVndRate = 0;
    
    const updateExchangeRate = () => {
        if (!btnExchange) return;
        
        // Gọi về chính Backend của chúng ta thay vì API ngoài
        fetch('http://localhost:5000/api/currency/rate')
            .then(response => response.json())
            .then(res => {
                if (res.success && res.data && res.data.rate) {
                    const vndRate = res.data.rate;
                    
                    if (vndRate !== lastVndRate) {
                        window.BAYOU_EXCHANGE_RATE = vndRate;
                        const formattedRate = new Intl.NumberFormat('vi-VN').format(vndRate);
                        const newText = `$ 1,00 = ₫ ${formattedRate}`;
                        
                        if (lastVndRate !== 0) {
                            btnExchange.innerHTML = `<span style="color: #4ade80; transition: color 0.5s;">${newText}</span>`;
                            setTimeout(() => {
                                btnExchange.innerHTML = newText;
                            }, 1500);
                        } else {
                            btnExchange.innerHTML = newText;
                        }
                        
                        lastVndRate = vndRate;
                        document.dispatchEvent(new CustomEvent('exchangeRateUpdated', { detail: { rate: vndRate } }));
                    }
                }
            })
            .catch(() => {
                if (lastVndRate === 0) btnExchange.innerHTML = `Lỗi Backend`;
            });
    };

    if (btnExchange) {
        updateExchangeRate();
        // Kiểm tra biến động từ Server mỗi 30 giây
        setInterval(updateExchangeRate, 30000);
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
