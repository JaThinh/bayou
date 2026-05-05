// =========================================
// BAYOU OTA - Flight Search Page (Async / AJAX)
// Trang kết quả với search summary, date carousel, dual-range price slider,
// sidebar filters và flight card layout chuyên nghiệp.
// =========================================
import { loadComponent, initHeader } from './ui.js';

const params = window.__BAYOU_SEARCH__ || {};

// Logo dự phòng theo mã hãng (khi backend không trả về logoUrl).
const FALLBACK_AIRLINE_LOGOS = {
    VN: '/assets/logos/airline-vn.png',
    VJ: '/assets/logos/airline-vj.png',
    QH: '/assets/logos/airline-qh.png',
    VU: '/assets/logos/airline-vu.png',
};

// Icon SVG đơn giản cho amenities (wifi/meal/usb/entertainment).
const AMENITY_ICONS = {
    wifi: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>',
    meal: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11h18M3 11a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4M3 11v2a4 4 0 0 0 4 4h10a4 4 0 0 0 4-4v-2M8 17v4M16 17v4"/></svg>',
    usb: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="2" r="1"/><line x1="12" y1="3" x2="12" y2="20"/><polyline points="9 6 12 3 15 6"/><path d="M9 14h6v4a3 3 0 0 1-3 3 3 3 0 0 1-3-3z"/></svg>',
    entertainment: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="14" rx="2"/><line x1="7" y1="22" x2="17" y2="22"/><line x1="12" y1="18" x2="12" y2="22"/></svg>',
};

// Trạng thái tổng cho bộ lọc — single source of truth, mọi event đều cập nhật state này
// rồi gọi applyFilters() để tính lại danh sách hiển thị.
const state = {
    allFlights: [],     // dữ liệu gốc từ API
    priceMin: 0,        // bounds tự động tính từ data
    priceMax: 0,
    priceLow: 0,        // selected range
    priceHigh: 0,
    stops: 'all',
    airlines: new Set(),
    sort: 'price-asc',
    cheapestPrice: 0,   // để gắn badge "Rẻ nhất"
};

// =========================================
// 1. Bootstrap header chung
// =========================================
async function bootstrapSharedUi() {
    try {
        await loadComponent('header-container', '/components/header/header.html');
        initHeader();
    } catch (err) {
        console.warn('Không thể tải header chung:', err);
    }
}

// =========================================
// 2. Animate Progress Bar (0% -> 99%)
// =========================================
function createProgressController(barEl) {
    let current = 0;
    let raf = null;
    let active = false;
    const targetCap = 99;

    const tick = () => {
        if (!active) return;
        const remaining = targetCap - current;
        const step = Math.max(0.1, remaining * 0.012);
        current = Math.min(targetCap, current + step);
        barEl.style.width = current.toFixed(2) + '%';
        if (current < targetCap) {
            raf = requestAnimationFrame(tick);
        }
    };

    return {
        start() {
            if (active) return;
            active = true;
            current = 0;
            barEl.style.width = '0%';
            raf = requestAnimationFrame(tick);
        },
        finish() {
            active = false;
            if (raf) cancelAnimationFrame(raf);
            barEl.style.width = '100%';
        },
    };
}

// =========================================
// 3. Fetch API kết quả chuyến bay
// =========================================
async function fetchFlights(searchParams) {
    const url = new URL(searchParams.apiUrl || '/api/flights/search.php', window.location.origin);
    url.searchParams.set('from', searchParams.from);
    url.searchParams.set('to', searchParams.to);
    url.searchParams.set('date', searchParams.date);
    if (searchParams.adults) url.searchParams.set('adults', String(searchParams.adults));
    if (searchParams.children) url.searchParams.set('children', String(searchParams.children));
    if (searchParams.infants) url.searchParams.set('infants', String(searchParams.infants));

    const response = await fetch(url.toString(), {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('HTTP ' + response.status);
    }

    const data = await response.json();
    if (!data || data.success === false) {
        throw new Error((data && data.message) || 'Phản hồi không hợp lệ từ máy chủ.');
    }
    return data;
}

// =========================================
// 4. Helpers format
// =========================================
function formatTime(iso) {
    if (!iso) return '--:--';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

function formatDuration(minutes) {
    if (!minutes || Number.isNaN(Number(minutes))) return '';
    const total = Math.max(0, Math.round(Number(minutes)));
    const h = Math.floor(total / 60);
    const m = total % 60;
    if (h === 0) return m + 'm';
    if (m === 0) return h + 'h';
    return h + 'h ' + m + 'm';
}

function formatStops(stopCount) {
    const n = Number(stopCount) || 0;
    if (n === 0) return 'Bay thẳng';
    return n + ' điểm dừng';
}

function formatPrice(flight) {
    if (flight.PriceDisplay) return flight.PriceDisplay;
    if (typeof flight.Price === 'number') {
        return new Intl.NumberFormat('vi-VN').format(Math.round(flight.Price)) + ' VND';
    }
    return 'Liên hệ';
}

function formatPriceShort(vnd) {
    const n = Number(vnd) || 0;
    if (n >= 1000000) {
        const v = n / 1000000;
        const s = v.toFixed(2).replace(/\.?0+$/, '');
        return s.replace('.', ',') + ' Tr';
    }
    if (n >= 1000) {
        return Math.round(n / 1000) + 'K';
    }
    return new Intl.NumberFormat('vi-VN').format(n);
}

function resolveLogo(flight) {
    if (flight.Logo) return flight.Logo;
    const code = (flight.AirlineCode || '').toUpperCase();
    return FALLBACK_AIRLINE_LOGOS[code] || '';
}

// =========================================
// 5. Date Carousel
// =========================================
function renderDateCarousel(dateRange) {
    const listEl = document.getElementById('date-carousel-list');
    const prevBtn = document.querySelector('.date-carousel__nav--prev');
    const nextBtn = document.querySelector('.date-carousel__nav--next');
    if (!listEl) return;

    listEl.innerHTML = '';
    if (!Array.isArray(dateRange) || dateRange.length === 0) {
        return;
    }

    const fragment = document.createDocumentFragment();
    dateRange.forEach((day) => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'date-carousel__item' + (day.isCenter ? ' date-carousel__item--active' : '');
        item.setAttribute('role', 'tab');
        item.setAttribute('aria-selected', day.isCenter ? 'true' : 'false');
        item.dataset.date = day.date;
        item.innerHTML = ''
            + '<span class="date-carousel__day">' + escapeHtml(day.dayLabel || '') + '</span>'
            + '<span class="date-carousel__date">' + escapeHtml(day.dayShort || '') + '</span>'
            + '<span class="date-carousel__price">' + escapeHtml(day.displayPrice || '') + '</span>';

        item.addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('date', day.date);
            window.location.href = url.toString();
        });
        fragment.appendChild(item);
    });
    listEl.appendChild(fragment);

    // Cuộn ngang khi nhấn arrow.
    if (prevBtn) prevBtn.addEventListener('click', () => listEl.scrollBy({ left: -240, behavior: 'smooth' }));
    if (nextBtn) nextBtn.addEventListener('click', () => listEl.scrollBy({ left: 240, behavior: 'smooth' }));
}

function escapeHtml(s) {
    return String(s || '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// =========================================
// 6. Dual-range price slider
// =========================================
function setupPriceSlider() {
    const minInput = document.getElementById('price-slider-min');
    const maxInput = document.getElementById('price-slider-max');
    const rangeEl = document.getElementById('price-slider-range');
    const minDisplay = document.getElementById('price-slider-min-display');
    const maxDisplay = document.getElementById('price-slider-max-display');
    if (!minInput || !maxInput || !rangeEl) return;

    const updateUI = () => {
        const lo = Number(minInput.value);
        const hi = Number(maxInput.value);
        rangeEl.style.left = lo + '%';
        rangeEl.style.width = Math.max(0, hi - lo) + '%';

        const range = state.priceMax - state.priceMin;
        state.priceLow = Math.round(state.priceMin + (range * lo) / 100);
        state.priceHigh = Math.round(state.priceMin + (range * hi) / 100);
        if (minDisplay) minDisplay.textContent = formatPriceShort(state.priceLow);
        if (maxDisplay) maxDisplay.textContent = formatPriceShort(state.priceHigh);
    };

    const onMinChange = () => {
        if (Number(minInput.value) > Number(maxInput.value) - 1) {
            minInput.value = String(Number(maxInput.value) - 1);
        }
        updateUI();
        applyFilters();
    };

    const onMaxChange = () => {
        if (Number(maxInput.value) < Number(minInput.value) + 1) {
            maxInput.value = String(Number(minInput.value) + 1);
        }
        updateUI();
        applyFilters();
    };

    minInput.addEventListener('input', onMinChange);
    maxInput.addEventListener('input', onMaxChange);
    minInput.addEventListener('change', onMinChange);
    maxInput.addEventListener('change', onMaxChange);

    updateUI();
}

function resetPriceSlider() {
    const minInput = document.getElementById('price-slider-min');
    const maxInput = document.getElementById('price-slider-max');
    if (minInput) minInput.value = '0';
    if (maxInput) maxInput.value = '100';
    state.priceLow = state.priceMin;
    state.priceHigh = state.priceMax;
    const rangeEl = document.getElementById('price-slider-range');
    if (rangeEl) {
        rangeEl.style.left = '0%';
        rangeEl.style.width = '100%';
    }
    const minDisplay = document.getElementById('price-slider-min-display');
    const maxDisplay = document.getElementById('price-slider-max-display');
    if (minDisplay) minDisplay.textContent = formatPriceShort(state.priceMin);
    if (maxDisplay) maxDisplay.textContent = formatPriceShort(state.priceMax);
}

// =========================================
// 7. Sidebar filters: airline + stops
// =========================================
function populateAirlineFilter(flights) {
    const wrap = document.getElementById('filter-airlines');
    if (!wrap) return;

    // Tính giá thấp nhất theo mã hãng để hiển thị "Từ X.XTr".
    const byCode = new Map();
    flights.forEach((f) => {
        const code = (f.AirlineCode || '').toUpperCase();
        if (!code) return;
        const existing = byCode.get(code);
        const price = Number(f.Price) || 0;
        if (!existing) {
            byCode.set(code, { code, name: f.AirlineName || code, minPrice: price });
        } else if (price < existing.minPrice) {
            existing.minPrice = price;
        }
    });

    if (byCode.size === 0) {
        wrap.innerHTML = '<small style="color:#8a96a6;">Không có dữ liệu hãng.</small>';
        return;
    }

    wrap.innerHTML = '';
    state.airlines = new Set(byCode.keys());
    Array.from(byCode.values())
        .sort((a, b) => a.minPrice - b.minPrice)
        .forEach((item) => {
            const label = document.createElement('label');
            label.className = 'filter-option filter-option--checked';
            label.innerHTML = ''
                + '<input type="checkbox" value="' + escapeHtml(item.code) + '" checked>'
                + '<span class="filter-option__label">' + escapeHtml(item.name) + '</span>'
                + '<span class="filter-option__price">Từ ' + formatPriceShort(item.minPrice) + '</span>';

            const checkbox = label.querySelector('input');
            checkbox.addEventListener('change', () => {
                if (checkbox.checked) {
                    state.airlines.add(item.code);
                    label.classList.add('filter-option--checked');
                } else {
                    state.airlines.delete(item.code);
                    label.classList.remove('filter-option--checked');
                }
                applyFilters();
            });
            wrap.appendChild(label);
        });
}

function populateStopsFilter(flights) {
    // Tô đậm option có giá min và thêm "Từ X.XTr" vào nhãn.
    const byStops = new Map();
    flights.forEach((f) => {
        const s = Number(f.Stops || 0);
        const price = Number(f.Price) || 0;
        const cur = byStops.get(s);
        if (!cur || price < cur) {
            byStops.set(s, price);
        }
    });

    document.querySelectorAll('#filter-stops .filter-option').forEach((labelEl) => {
        const input = labelEl.querySelector('input');
        const value = input.value;
        const labelTextEl = labelEl.querySelector('.filter-option__label');

        // Xoá price cũ nếu có.
        const oldPrice = labelEl.querySelector('.filter-option__price');
        if (oldPrice) oldPrice.remove();

        let priceText = '';
        if (value === 'all') {
            const allMin = Math.min(...Array.from(byStops.values()));
            if (Number.isFinite(allMin)) priceText = 'Từ ' + formatPriceShort(allMin);
        } else {
            const v = byStops.get(Number(value));
            if (typeof v === 'number') priceText = 'Từ ' + formatPriceShort(v);
        }

        if (priceText) {
            const span = document.createElement('span');
            span.className = 'filter-option__price';
            span.textContent = priceText;
            labelEl.appendChild(span);
        }

        // Highlight option đang chọn.
        if (input.checked) labelEl.classList.add('filter-option--checked');
        input.addEventListener('change', () => {
            document.querySelectorAll('#filter-stops .filter-option').forEach((el) => {
                el.classList.remove('filter-option--checked');
            });
            labelEl.classList.add('filter-option--checked');
            state.stops = value;
            applyFilters();
        });
    });
}

// =========================================
// 8. Apply filter + sort + render
// =========================================
function applyFilters() {
    let filtered = state.allFlights.slice();

    // Lọc theo hãng.
    if (state.airlines.size > 0) {
        filtered = filtered.filter((f) =>
            state.airlines.has((f.AirlineCode || '').toUpperCase())
        );
    } else {
        filtered = [];
    }

    // Lọc theo điểm dừng.
    if (state.stops !== 'all') {
        const wanted = Number(state.stops);
        filtered = filtered.filter((f) => Number(f.Stops || 0) === wanted);
    }

    // Lọc theo giá.
    filtered = filtered.filter((f) => {
        const p = Number(f.Price) || 0;
        return p >= state.priceLow && p <= state.priceHigh;
    });

    // Sort.
    switch (state.sort) {
        case 'price-desc':
            filtered.sort((a, b) => (Number(b.Price) || 0) - (Number(a.Price) || 0));
            break;
        case 'depart-asc':
            filtered.sort((a, b) => new Date(a.DepartTime || 0) - new Date(b.DepartTime || 0));
            break;
        case 'duration-asc':
            filtered.sort((a, b) => (Number(a.DurationMinutes) || 0) - (Number(b.DurationMinutes) || 0));
            break;
        case 'price-asc':
        default:
            filtered.sort((a, b) => (Number(a.Price) || 0) - (Number(b.Price) || 0));
    }

    renderFlightList(filtered);
    updateSummary(filtered.length);
}

function updateSummary(count) {
    const summaryEl = document.getElementById('flight-results-summary');
    if (!summaryEl) return;
    const totalText = state.allFlights.length === count
        ? '<strong>' + count + '</strong> chuyến bay'
        : 'Hiển thị <strong>' + count + '</strong> / ' + state.allFlights.length + ' chuyến';
    summaryEl.innerHTML = ''
        + '<span>' + totalText + ' phù hợp với bộ lọc</span>'
        + '<span style="color:#8a96a6;font-size:13px;">Đã sắp xếp: ' + sortLabel(state.sort) + '</span>';
}

function sortLabel(mode) {
    switch (mode) {
        case 'price-desc': return 'Giá cao nhất';
        case 'depart-asc': return 'Giờ đi: Sớm nhất';
        case 'duration-asc': return 'Thời gian bay ngắn nhất';
        case 'price-asc':
        default: return 'Giá thấp nhất';
    }
}

// =========================================
// 9. Render flight cards
// =========================================
function renderFlightList(flights) {
    const listEl = document.getElementById('flight-results-list');
    const emptyEl = document.getElementById('flight-results-empty');
    const tpl = document.getElementById('flight-card-template');
    if (!listEl || !tpl) return;

    listEl.innerHTML = '';

    if (flights.length === 0) {
        if (emptyEl) emptyEl.hidden = false;
        return;
    }
    if (emptyEl) emptyEl.hidden = true;

    const fragment = document.createDocumentFragment();
    flights.forEach((flight, idx) => {
        const node = tpl.content.firstElementChild.cloneNode(true);
        node.dataset.airlineCode = flight.AirlineCode || '';
        node.dataset.stops = String(flight.Stops || 0);
        node.dataset.price = String(flight.Price || 0);
        node.style.animationDelay = (idx * 50) + 'ms';

        // Logo + airline.
        const logoBox = node.querySelector('.flight-card__logo-box');
        const logoEl = node.querySelector('.flight-card__logo');
        const logoSrc = resolveLogo(flight);
        if (logoSrc) {
            logoEl.src = logoSrc;
            logoEl.alt = flight.AirlineName || flight.AirlineCode || '';
            logoEl.addEventListener('error', () => {
                logoEl.remove();
                logoBox.textContent = (flight.AirlineCode || 'AIR').toUpperCase();
                logoBox.style.fontWeight = '800';
                logoBox.style.color = '#0066cc';
            });
        } else {
            logoEl.remove();
            logoBox.textContent = (flight.AirlineCode || 'AIR').toUpperCase();
            logoBox.style.fontWeight = '800';
            logoBox.style.color = '#0066cc';
        }

        node.querySelector('.flight-card__airline-name').textContent = flight.AirlineName || 'Hãng hàng không';
        node.querySelector('.flight-card__flight-number').textContent =
            (flight.FlightNumber || '') + (flight.AirlineCode ? ' • ' + flight.AirlineCode : '');

        // Meta row: aircraft + amenities + seats.
        const aircraftEl = node.querySelector('.flight-card__aircraft');
        if (flight.Aircraft) {
            aircraftEl.textContent = flight.Aircraft;
        } else {
            aircraftEl.remove();
        }

        const amenitiesEl = node.querySelector('.flight-card__amenities');
        if (Array.isArray(flight.Amenities) && flight.Amenities.length > 0) {
            amenitiesEl.innerHTML = flight.Amenities
                .map((a) => AMENITY_ICONS[a] || '')
                .filter(Boolean)
                .join('');
        } else {
            amenitiesEl.remove();
        }

        const seatsEl = node.querySelector('.flight-card__seats-left');
        if (typeof flight.SeatsLeft === 'number' && flight.SeatsLeft > 0) {
            seatsEl.textContent = 'Còn ' + flight.SeatsLeft + ' ghế';
            if (flight.SeatsLeft <= 5) {
                seatsEl.classList.add('flight-card__seats-left--low');
            }
        } else {
            seatsEl.remove();
        }

        // Schedule.
        node.querySelector('.flight-card__depart-time').textContent = formatTime(flight.DepartTime);
        node.querySelector('.flight-card__arrive-time').textContent = formatTime(flight.ArriveTime);
        node.querySelector('.flight-card__origin').textContent = flight.Origin || params.from || '';
        node.querySelector('.flight-card__destination').textContent = flight.Destination || params.to || '';
        node.querySelector('.flight-card__stops').textContent = formatStops(flight.Stops);
        node.querySelector('.flight-card__duration').textContent = formatDuration(flight.DurationMinutes);

        // Pricing.
        node.querySelector('.flight-card__price').textContent = formatPrice(flight);
        const badge = node.querySelector('.flight-card__cheapest-badge');
        if (badge) {
            const isCheapest = state.cheapestPrice > 0 && Number(flight.Price) === state.cheapestPrice;
            badge.hidden = !isCheapest;
            if (isCheapest) node.classList.add('flight-card--cheapest');
        }

        // Class select default.
        const seatClassDefault = (params.seatClass || '').trim();
        if (seatClassDefault) {
            const selectEl = node.querySelector('.flight-card__class-select');
            const opt = Array.from(selectEl.options).find((o) =>
                o.value === seatClassDefault || o.textContent.trim() === seatClassDefault
            );
            if (opt) opt.selected = true;
        }

        node.querySelector('.flight-card__select-btn').addEventListener('click', () => {
            const detail = {
                ...flight,
                from: params.from,
                to: params.to,
                date: params.date,
                seatClass: node.querySelector('.flight-card__class-select').value,
            };
            console.log('[Bayou] Chọn vé:', detail);
            window.dispatchEvent(new CustomEvent('bayou:select-flight', { detail }));
        });

        fragment.appendChild(node);
    });
    listEl.appendChild(fragment);
}

// =========================================
// 10. Notice banner (fallback / upstream error)
// =========================================
function renderNotice(payload) {
    const noticeEl = document.getElementById('flight-results-notice');
    const noticeTextEl = document.getElementById('flight-results-notice-text');
    if (!noticeEl || !noticeTextEl) return;

    if (payload.fallback_kind === 'upstream_error') {
        noticeEl.hidden = false;
        noticeEl.classList.add('flight-notice--warning');
        noticeTextEl.textContent = payload.fallback_message
            || 'Hệ thống đặt vé đang gặp sự cố tạm thời. Đây là kết quả tham khảo.';
    } else if (payload.fallback_kind === 'no_provider') {
        noticeEl.hidden = false;
        noticeEl.classList.remove('flight-notice--warning');
        noticeTextEl.textContent = payload.fallback_message
            || 'Đang hiển thị dữ liệu mẫu để minh hoạ giao diện.';
    } else {
        noticeEl.hidden = true;
    }
}

// =========================================
// 11. Khởi tạo state từ data + bind controls
// =========================================
function initFromPayload(payload) {
    const flights = Array.isArray(payload.flights) ? payload.flights.slice() : [];
    state.allFlights = flights;

    if (flights.length > 0) {
        const prices = flights.map((f) => Number(f.Price) || 0).filter((p) => p > 0);
        state.priceMin = prices.length > 0 ? Math.min(...prices) : 0;
        state.priceMax = prices.length > 0 ? Math.max(...prices) : 0;
        // Mở rộng nhẹ một chút để slider thoải mái di chuyển.
        const padding = Math.max(50000, Math.round((state.priceMax - state.priceMin) * 0.05));
        state.priceMin = Math.max(0, state.priceMin - padding);
        state.priceMax = state.priceMax + padding;
        state.priceLow = state.priceMin;
        state.priceHigh = state.priceMax;
        state.cheapestPrice = Math.min(...prices);
    }

    renderNotice(payload);
    renderDateCarousel(payload.dateRange || []);
    setupPriceSlider();
    populateAirlineFilter(flights);
    populateStopsFilter(flights);
    bindToolbar();
    bindClearFilters();

    applyFilters();
}

function bindToolbar() {
    const sortEl = document.getElementById('sort-flights');
    const resetBtn = document.getElementById('reset-toolbar-btn');
    const toggleEl = document.getElementById('separate-flights-toggle');

    if (sortEl) {
        sortEl.addEventListener('change', () => {
            state.sort = sortEl.value;
            applyFilters();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            // Reset toolbar = sort về mặc định + price slider về full range.
            if (sortEl) sortEl.value = 'price-asc';
            state.sort = 'price-asc';
            resetPriceSlider();
            applyFilters();
        });
    }

    if (toggleEl) {
        toggleEl.addEventListener('change', () => {
            // Toggle UI thuần — đã có animation cho card, giữ chỗ cho future feature.
            document.body.classList.toggle('flight-results--separate', toggleEl.checked);
        });
    }
}

function bindClearFilters() {
    const btn = document.getElementById('clear-filters-btn');
    if (!btn) return;
    btn.addEventListener('click', () => {
        // Clear filters = check lại tất cả hãng + stops "all" + giá full range.
        document.querySelectorAll('#filter-airlines input[type="checkbox"]').forEach((cb) => {
            cb.checked = true;
            cb.closest('.filter-option')?.classList.add('filter-option--checked');
        });
        state.airlines = new Set(state.allFlights.map((f) => (f.AirlineCode || '').toUpperCase()));

        document.querySelectorAll('#filter-stops .filter-option').forEach((el) => {
            el.classList.remove('filter-option--checked');
            const input = el.querySelector('input');
            if (input.value === 'all') {
                input.checked = true;
                el.classList.add('filter-option--checked');
            } else {
                input.checked = false;
            }
        });
        state.stops = 'all';

        resetPriceSlider();
        applyFilters();
    });
}

// =========================================
// 12. Hiển thị lỗi
// =========================================
function showError(message) {
    const errorEl = document.getElementById('flight-results-error');
    const msgEl = document.getElementById('flight-results-error-message');
    const emptyEl = document.getElementById('flight-results-empty');
    const summaryEl = document.getElementById('flight-results-summary');

    if (summaryEl) summaryEl.innerHTML = '';
    if (emptyEl) emptyEl.hidden = true;
    if (errorEl) errorEl.hidden = false;
    if (msgEl) msgEl.textContent = message || 'Vui lòng thử lại sau.';
}

// =========================================
// 13. Orchestration: load -> fetch -> render
// =========================================
async function run() {
    const loadingEl = document.getElementById('loading-screen');
    const resultsEl = document.getElementById('flight-results');
    const barEl = document.getElementById('loading-progress-bar');
    const progress = createProgressController(barEl);

    progress.start();
    bootstrapSharedUi();

    try {
        const data = await fetchFlights(params);
        progress.finish();

        // Một chút delay để người dùng thấy hiệu ứng "vèo lên 100%".
        await new Promise((resolve) => setTimeout(resolve, 350));

        initFromPayload(data);
    } catch (err) {
        console.error('[Bayou] Lỗi tìm chuyến bay:', err);
        progress.finish();
        await new Promise((resolve) => setTimeout(resolve, 200));
        if (resultsEl) resultsEl.hidden = false;
        showError(err && err.message ? err.message : 'Không thể kết nối tới máy chủ.');
    } finally {
        // Fade out loading-screen, fade in results.
        if (loadingEl) {
            loadingEl.classList.add('loading-screen--hidden');
            setTimeout(() => {
                loadingEl.style.display = 'none';
                if (resultsEl) {
                    resultsEl.hidden = false;
                    requestAnimationFrame(() => {
                        resultsEl.classList.add('flight-results--visible');
                    });
                }
            }, 500);
        }
    }
}

// Nút "Thử lại" khi lỗi.
document.addEventListener('click', (e) => {
    const target = e.target;
    if (target && target.id === 'flight-retry-btn') {
        window.location.reload();
    }
});

run();
