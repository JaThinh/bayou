// =========================================
// BAYOU OTA - Flight Search Page (Async / AJAX)
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

// =========================================
// 1. Load các component dùng chung (header)
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

        // Tốc độ giảm dần khi gần 99% để cảm giác "chậm lại" tự nhiên.
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
        reset() {
            active = false;
            if (raf) cancelAnimationFrame(raf);
            current = 0;
            barEl.style.width = '0%';
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
// 4. Render danh sách kết quả từ JSON
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

function resolveLogo(flight) {
    if (flight.Logo) return flight.Logo;
    const code = (flight.AirlineCode || '').toUpperCase();
    return FALLBACK_AIRLINE_LOGOS[code] || '';
}

function renderResults(payload) {
    const summaryEl = document.getElementById('flight-results-summary');
    const listEl = document.getElementById('flight-results-list');
    const emptyEl = document.getElementById('flight-results-empty');
    const errorEl = document.getElementById('flight-results-error');
    const tpl = document.getElementById('flight-card-template');

    listEl.innerHTML = '';
    emptyEl.hidden = true;
    errorEl.hidden = true;

    const flights = Array.isArray(payload.flights) ? payload.flights.slice() : [];

    summaryEl.innerHTML = '<span>Tìm thấy <strong>' + flights.length + '</strong> chuyến bay '
        + '<strong>' + (params.from || '') + '</strong> ✈ '
        + '<strong>' + (params.to || '') + '</strong> '
        + 'ngày <strong>' + (params.displayDate || params.date || '') + '</strong></span>'
        + '<span style="color:#8a96a6;font-size:13px;">Nguồn: ' + (payload.source || '—') + '</span>';

    if (flights.length === 0) {
        emptyEl.hidden = false;
        return;
    }

    // Mặc định sắp xếp theo giá tăng dần.
    flights.sort((a, b) => (Number(a.Price) || 0) - (Number(b.Price) || 0));

    const fragment = document.createDocumentFragment();
    flights.forEach((flight) => {
        const node = tpl.content.firstElementChild.cloneNode(true);
        node.dataset.airlineCode = flight.AirlineCode || '';
        node.dataset.stops = String(flight.Stops || 0);
        node.dataset.price = String(flight.Price || 0);

        const logoEl = node.querySelector('.flight-card__logo');
        const logoSrc = resolveLogo(flight);
        if (logoSrc) {
            logoEl.src = logoSrc;
            logoEl.alt = flight.AirlineName || flight.AirlineCode || '';
        } else {
            logoEl.remove();
            node.querySelector('.flight-card__logo-box').textContent = flight.AirlineCode || 'AIR';
            node.querySelector('.flight-card__logo-box').style.fontWeight = '800';
            node.querySelector('.flight-card__logo-box').style.color = '#0066cc';
        }

        node.querySelector('.flight-card__airline-name').textContent = flight.AirlineName || 'Hãng hàng không';
        node.querySelector('.flight-card__flight-number').textContent =
            (flight.FlightNumber || '') + (flight.AirlineCode ? ' • ' + flight.AirlineCode : '');

        node.querySelector('.flight-card__depart-time').textContent = formatTime(flight.DepartTime);
        node.querySelector('.flight-card__arrive-time').textContent = formatTime(flight.ArriveTime);
        node.querySelector('.flight-card__origin').textContent = flight.Origin || params.from || '';
        node.querySelector('.flight-card__destination').textContent = flight.Destination || params.to || '';
        node.querySelector('.flight-card__stops').textContent = formatStops(flight.Stops);
        node.querySelector('.flight-card__duration').textContent = formatDuration(flight.DurationMinutes);
        node.querySelector('.flight-card__price').textContent = formatPrice(flight);

        node.querySelector('.flight-card__select-btn').addEventListener('click', () => {
            const detail = {
                ...flight,
                from: params.from,
                to: params.to,
                date: params.date,
            };
            console.log('[Bayou] Chọn vé:', detail);
            window.dispatchEvent(new CustomEvent('bayou:select-flight', { detail }));
        });

        fragment.appendChild(node);
    });
    listEl.appendChild(fragment);

    populateAirlineFilter(flights);
    bindFiltersAndSort(flights);
}

function populateAirlineFilter(flights) {
    const wrap = document.getElementById('filter-airlines');
    if (!wrap) return;
    const seen = new Map();
    flights.forEach((f) => {
        const code = (f.AirlineCode || '').toUpperCase();
        if (!code) return;
        if (!seen.has(code)) seen.set(code, f.AirlineName || code);
    });

    if (seen.size === 0) {
        wrap.innerHTML = '<small style="color:#8a96a6;">Không có dữ liệu hãng.</small>';
        return;
    }

    wrap.innerHTML = '';
    seen.forEach((name, code) => {
        const id = 'air-' + code;
        const label = document.createElement('label');
        label.innerHTML = '<input type="checkbox" value="' + code + '" id="' + id + '" checked> '
            + name + ' (' + code + ')';
        wrap.appendChild(label);
    });
}

function bindFiltersAndSort(originalFlights) {
    const listEl = document.getElementById('flight-results-list');
    const emptyEl = document.getElementById('flight-results-empty');
    const sortEl = document.getElementById('sort-flights');
    const stopsRadios = document.querySelectorAll('input[name="stops"]');
    const airlineCheckboxes = document.querySelectorAll('#filter-airlines input[type="checkbox"]');

    const apply = () => {
        const sortMode = sortEl.value;
        const stopsValue = (Array.from(stopsRadios).find((r) => r.checked) || {}).value || 'all';
        const allowedAirlines = Array.from(airlineCheckboxes)
            .filter((cb) => cb.checked)
            .map((cb) => cb.value);

        let filtered = originalFlights.slice();

        if (allowedAirlines.length > 0) {
            filtered = filtered.filter((f) =>
                allowedAirlines.includes((f.AirlineCode || '').toUpperCase())
            );
        }
        if (stopsValue !== 'all') {
            const wanted = Number(stopsValue);
            filtered = filtered.filter((f) => Number(f.Stops || 0) === wanted);
        }

        switch (sortMode) {
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

        // Toggle visibility nhanh trên DOM hiện có thay vì re-render hoàn toàn.
        const allCards = listEl.querySelectorAll('.flight-card');
        allCards.forEach((card) => { card.style.display = 'none'; });

        const visibleCodes = filtered.map((f, idx) =>
            (f.AirlineCode || '') + '-' + idx
        );

        // Re-render bằng cách clone từ template để giữ thứ tự sort mới.
        renderResultsFromList(filtered);
        emptyEl.hidden = filtered.length !== 0;
    };

    sortEl.addEventListener('change', apply);
    stopsRadios.forEach((r) => r.addEventListener('change', apply));
    airlineCheckboxes.forEach((cb) => cb.addEventListener('change', apply));
}

function renderResultsFromList(flights) {
    const listEl = document.getElementById('flight-results-list');
    const tpl = document.getElementById('flight-card-template');
    listEl.innerHTML = '';
    const fragment = document.createDocumentFragment();

    flights.forEach((flight) => {
        const node = tpl.content.firstElementChild.cloneNode(true);
        node.dataset.airlineCode = flight.AirlineCode || '';
        node.dataset.stops = String(flight.Stops || 0);
        node.dataset.price = String(flight.Price || 0);

        const logoEl = node.querySelector('.flight-card__logo');
        const logoSrc = resolveLogo(flight);
        if (logoSrc) {
            logoEl.src = logoSrc;
            logoEl.alt = flight.AirlineName || flight.AirlineCode || '';
        } else {
            logoEl.remove();
            node.querySelector('.flight-card__logo-box').textContent = flight.AirlineCode || 'AIR';
        }

        node.querySelector('.flight-card__airline-name').textContent = flight.AirlineName || 'Hãng hàng không';
        node.querySelector('.flight-card__flight-number').textContent =
            (flight.FlightNumber || '') + (flight.AirlineCode ? ' • ' + flight.AirlineCode : '');
        node.querySelector('.flight-card__depart-time').textContent = formatTime(flight.DepartTime);
        node.querySelector('.flight-card__arrive-time').textContent = formatTime(flight.ArriveTime);
        node.querySelector('.flight-card__origin').textContent = flight.Origin || params.from || '';
        node.querySelector('.flight-card__destination').textContent = flight.Destination || params.to || '';
        node.querySelector('.flight-card__stops').textContent = formatStops(flight.Stops);
        node.querySelector('.flight-card__duration').textContent = formatDuration(flight.DurationMinutes);
        node.querySelector('.flight-card__price').textContent = formatPrice(flight);

        node.querySelector('.flight-card__select-btn').addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('bayou:select-flight', {
                detail: { ...flight, from: params.from, to: params.to, date: params.date },
            }));
        });

        fragment.appendChild(node);
    });

    listEl.appendChild(fragment);
}

// =========================================
// 5. Hiển thị lỗi
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
// 6. Orchestration: load -> fetch -> render
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

        renderResults(data);
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
