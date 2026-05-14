/**
 * flightResults.js
 * Module hiển thị kết quả vé máy bay từ Multi-Source API
 * Glassmorphism UI + Price Comparison + Airline Verification
 */

// ================================================================
// AIRLINE CONFIG — Logo + màu thương hiệu
// ================================================================
const AIRLINE_CONFIG = {
    VN: { name: 'Vietnam Airlines', color: '#003087', logo: '/assets/logos/airline-vn.png' },
    VJ: { name: 'VietJet Air',      color: '#d82027', logo: '/assets/logos/airline-vj.png' },
    QH: { name: 'Bamboo Airways',   color: '#1a6b3c', logo: '/assets/logos/airline-qh.png' },
    VU: { name: 'Vietravel Airlines',color: '#ff6600', logo: '/assets/logos/airline-vu.png' },
};

// ================================================================
// MAIN EXPORT — gọi từ searchWidget.js
// ================================================================
export function renderFlightResults(apiResult) {
    // Xóa container cũ nếu có
    let container = document.getElementById('flight-results-container');
    if (!container) {
        container = document.createElement('section');
        container.id = 'flight-results-container';
        const hero = document.getElementById('hero-container');
        if (hero && hero.parentNode) {
            hero.parentNode.insertBefore(container, hero.nextSibling);
        } else {
            document.body.appendChild(container);
        }
    }

    // Skeleton loading
    container.innerHTML = buildSkeletons(4);

    // Cuộn mượt xuống khu vực kết quả
    setTimeout(() => container.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);

    // Render thực sau 400ms (giả lập UX tốt hơn)
    setTimeout(() => {
        container.innerHTML = buildResultsHTML(apiResult);
        bindEvents(container, apiResult);
    }, 400);
}

// ================================================================
// BUILD HTML
// ================================================================
function buildResultsHTML(result) {
    const flights = result.data || [];
    const sources = result.sources || {};

    // Header
    let html = `
    <div class="fr-header">
        <h2 class="fr-title">✈ ${result.route || ''} &nbsp;—&nbsp; ${formatDateVI(result.date)}</h2>
        <div class="fr-meta">
            <span class="fr-badge google ${sources.google?.ok ? 'ok' : 'fail'}">
                🔵 Google Flights &nbsp;·&nbsp; ${sources.google?.count ?? 0} chuyến
            </span>
            <span class="fr-badge skyscanner ${sources.skyscanner?.ok ? 'ok' : 'fail'}">
                🔷 Skyscanner &nbsp;·&nbsp; ${sources.skyscanner?.count ?? 0} chuyến
            </span>
        </div>
    </div>`;

    // Sort toolbar
    html += `
    <div class="fr-toolbar">
        <button class="fr-sort-btn active" data-sort="price">💰 Giá thấp nhất</button>
        <button class="fr-sort-btn" data-sort="duration">⏱ Bay nhanh nhất</button>
        <button class="fr-sort-btn" data-sort="depart">🌅 Giờ khởi hành</button>
        <button class="fr-sort-btn" data-sort="nonstop">🎯 Bay thẳng</button>
    </div>`;

    // Danh sách vé
    if (flights.length === 0) {
        html += `
        <div class="fr-empty">
            <div class="fr-empty-icon">🔍</div>
            <h3 class="fr-empty-title">Không tìm thấy chuyến bay</h3>
            <p class="fr-empty-sub">${(result.errors || []).join(' | ') || 'Thử lại với ngày khác hoặc tuyến khác.'}</p>
        </div>`;
    } else {
        html += `<div id="fr-list">`;
        flights.forEach((f, i) => {
            html += buildFlightCard(f, i);
        });
        html += `</div>`;
    }

    // Panel xác minh giá từ hãng bay
    const verifications = result.verifications || {};
    if (Object.keys(verifications).length > 0) {
        html += buildVerificationPanel(verifications);
    }

    return html;
}

function buildFlightCard(flight, index) {
    const code    = extractCode(flight.airline, flight.flightNumber);
    const config  = AIRLINE_CONFIG[code] || {};
    const isCheap = index === 0;
    const logo    = flight.logo || config.logo || '';

    const logoHtml = logo
        ? `<img class="fr-airline-logo" src="${logo}" alt="${flight.airline}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
           <div class="fr-airline-logo-fallback" style="display:none">${code || flight.airline?.substring(0,2) || '??'}</div>`
        : `<div class="fr-airline-logo-fallback">${code || flight.airline?.substring(0,2) || '??'}</div>`;

    const sourceBadge = flight.source_badge === 'google'
        ? `<span class="fr-source-icon google">G</span>`
        : `<span class="fr-source-icon skyscanner">S</span>`;

    const bookUrl = flight.book_url || '#';
    const priceDisplay = flight.price?.formatted || '—';
    const priceAmt = flight.price?.amount || 0;

    return `
    <div class="fr-card ${isCheap ? 'cheapest' : ''}" data-price="${priceAmt}" data-depart="${flight.departureTime}" data-duration="${flight.duration_minutes || 0}" data-stops="${flight.stops?.includes('thẳng') ? 0 : 1}">
        ${isCheap ? '<div class="cheapest-label">🏷 Rẻ nhất</div>' : ''}

        <div class="fr-airline">
            ${logoHtml}
            <span class="fr-airline-name">${flight.airline || 'Unknown'}</span>
            <span class="fr-flight-num">${flight.flightNumber || ''}</span>
            ${sourceBadge}
        </div>

        <div class="fr-route">
            <div class="fr-time-block">
                <div class="fr-time">${flight.departureTime || '--:--'}</div>
                <div class="fr-iata">${flight.source?.includes('→') ? flight.source.split('→')[0].trim() : ''}</div>
            </div>

            <div class="fr-timeline">
                <span class="fr-duration">${flight.duration || ''}</span>
                <div class="fr-line-wrap">
                    <div class="fr-line"></div>
                    <span class="fr-plane-icon">✈</span>
                    <div class="fr-line"></div>
                </div>
                <span class="fr-stops">${flight.stops || ''}</span>
            </div>

            <div class="fr-time-block">
                <div class="fr-time">${flight.arrivalTime || '--:--'}</div>
                <div class="fr-iata"></div>
            </div>
        </div>

        <div class="fr-price-block">
            <div class="fr-price">${priceDisplay}</div>
            <div class="fr-price-note">1 người · ${flight.source || 'Online'}</div>
        </div>

        <div class="fr-cta">
            <a class="fr-btn fr-btn-primary" href="${bookUrl}" target="_blank" rel="noopener">Đặt vé</a>
            <button class="fr-btn fr-btn-secondary btn-verify-price" data-code="${code}" data-airline="${flight.airline}">
                🔎 Kiểm tra phí
            </button>
        </div>
    </div>`;
}

function buildVerificationPanel(verifications) {
    const entries = Object.entries(verifications);
    if (!entries.length) return '';

    let cardsHtml = '';
    entries.forEach(([code, v]) => {
        const fees = v.fees || {};
        const totalAmt = v.total || 0;
        const hasPrice = totalAmt > 0;

        cardsHtml += `
        <div class="fr-verify-card">
            <div class="fr-verify-airline">✈ ${v.airline}</div>

            <div class="fr-fee-row">
                <span class="fr-fee-label">Giá vé cơ bản</span>
                <span class="fr-fee-value">${hasPrice ? fmt(v.base_price) + ' ₫' : '—'}</span>
            </div>
            ${fees.baggage !== undefined ? `
            <div class="fr-fee-row">
                <span class="fr-fee-label">🧳 Hành lý ký gửi</span>
                <span class="fr-fee-value">${fees.baggage > 0 ? fmt(fees.baggage) + ' ₫' : 'Miễn phí'}</span>
            </div>` : ''}
            ${fees.seat !== undefined ? `
            <div class="fr-fee-row">
                <span class="fr-fee-label">💺 Chọn chỗ ngồi</span>
                <span class="fr-fee-value">${fees.seat > 0 ? 'từ ' + fmt(fees.seat) + ' ₫' : 'Miễn phí'}</span>
            </div>` : ''}
            ${fees.payment !== undefined ? `
            <div class="fr-fee-row">
                <span class="fr-fee-label">💳 Phí thanh toán thẻ</span>
                <span class="fr-fee-value">${fees.payment > 0 ? '~' + fmt(fees.payment) + ' ₫' : 'Miễn phí'}</span>
            </div>` : ''}

            <div class="fr-verify-total">
                <span>TỔNG CỘNG</span>
                <span class="fr-fee-value">${hasPrice ? fmt(totalAmt) + ' ₫' : 'Xem trực tiếp'}</span>
            </div>

            ${fees.note ? `<div class="fr-verify-note">📌 ${fees.note}</div>` : ''}
            <a class="fr-verify-link" href="${v.book_url || '#'}" target="_blank">
                Đặt trực tiếp trên web hãng →
            </a>
        </div>`;
    });

    return `
    <div class="fr-verify-panel">
        <h3 class="fr-verify-title">🔍 Kiểm tra chi phí thực từ web hãng bay (Vé + Hành lý + Ghế + Phí thẻ)</h3>
        <div class="fr-verify-grid">${cardsHtml}</div>
    </div>`;
}

// ================================================================
// EVENTS
// ================================================================
function bindEvents(container, originalResult) {
    // Sort buttons
    container.querySelectorAll('.fr-sort-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            container.querySelectorAll('.fr-sort-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            sortCards(container, btn.dataset.sort);
        });
    });

    // "Kiểm tra phí" button
    container.querySelectorAll('.btn-verify-price').forEach(btn => {
        btn.addEventListener('click', () => {
            const code    = btn.dataset.code;
            const airline = btn.dataset.airline;
            const urls = {
                VN: `https://www.vietnamairlines.com/vn/vi/search-booking/book-flight`,
                VJ: `https://www.vietjetair.com/vi/flight`,
                QH: `https://www.bambooairways.com/flight`,
                VU: `https://booking.vietravelairlines.vn/vn/vi/`,
            };
            if (urls[code]) {
                window.open(urls[code], '_blank', 'noopener');
            } else {
                alert(`Không tìm thấy link đặt vé cho hãng ${airline}. Mã hãng: ${code}`);
            }
        });
    });
}

// ================================================================
// SORT
// ================================================================
function sortCards(container, sortBy) {
    const list = container.querySelector('#fr-list');
    if (!list) return;

    const cards = Array.from(list.querySelectorAll('.fr-card'));
    cards.sort((a, b) => {
        if (sortBy === 'price')    return +a.dataset.price    - +b.dataset.price;
        if (sortBy === 'duration') return +a.dataset.duration - +b.dataset.duration;
        if (sortBy === 'depart')   return a.dataset.depart.localeCompare(b.dataset.depart);
        if (sortBy === 'nonstop')  return +a.dataset.stops    - +b.dataset.stops;
        return 0;
    });

    // Re-render với animation
    list.innerHTML = '';
    cards.forEach((c, i) => {
        c.classList.toggle('cheapest', i === 0 && sortBy === 'price');
        const cheapLabel = c.querySelector('.cheapest-label');
        if (i === 0 && sortBy === 'price') {
            if (!cheapLabel) {
                const div = document.createElement('div');
                div.className = 'cheapest-label';
                div.textContent = '🏷 Rẻ nhất';
                c.prepend(div);
            }
        } else {
            if (cheapLabel) cheapLabel.remove();
        }
        c.style.animationDelay = `${i * 60}ms`;
        list.appendChild(c);
    });
}

// ================================================================
// HELPERS
// ================================================================
function buildSkeletons(n) {
    return Array(n).fill('<div class="fr-skeleton"></div>').join('');
}

function extractCode(airlineName = '', flightNum = '') {
    const name = airlineName.toLowerCase();
    if (name.includes('vietnam'))  return 'VN';
    if (name.includes('vietjet'))  return 'VJ';
    if (name.includes('bamboo'))   return 'QH';
    if (name.includes('vietravel'))return 'VU';
    const match = flightNum.match(/^([A-Z]{2})/);
    return match ? match[1] : '';
}

function fmt(num) {
    return Number(num).toLocaleString('vi-VN');
}

function formatDateVI(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
}
