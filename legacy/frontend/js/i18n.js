export const translations = {
    "VN": {
        "lang_title": "Ngôn ngữ",
        "btn_booking": "Tìm kiếm đặt chỗ",
        "btn_login": "👤 Đăng nhập",
        "hero_title": "TÌM KIẾM CHUYẾN BAY",
        "tab_roundtrip": "Khứ hồi",
        "tab_oneway": "1 chiều",
        "tab_multicity": "Đa chặng",
        "seat_class": "Hạng ghế",
        "seat_economy": "Phổ thông", "seat_premium": "Phổ thông đặc biệt", "seat_first": "Hạng nhất", "seat_all": "Tất cả hạng ghế",
        "seat_business": "Thương gia",
        "passenger": "Hành khách",
        "adult": "Người lớn", "adult_desc": "(từ 12 tuổi)",
        "child": "Trẻ em", "child_desc": "(Từ 2 - 12 tuổi)",
        "infant": "Trẻ sơ sinh", "infant_desc": "(Dưới 2 tuổi)",
        "from": "Điểm đi",
        "to": "Điểm đến",
        "depart_date": "Ngày đi",
        "return_date": "Ngày về",
        "confirm": "Đồng ý",
        "search_btn": "Tìm chuyến bay",
        "add_flight": "+ Thêm chuyến bay",
        "destinations_title": "ĐIỂM ĐẾN PHỔ BIẾN",
        "news_title": "TIN TỨC - KHUYẾN MÃI",
        "placeholder_airport": "Thành phố hoặc sân bay",
        // Footer
        "footer_company": "CÔNG TY TNHH BAYOU",
        "footer_license": "Giấy chứng nhận đăng ký kinh doanh số 0315086120 được cấp vào ngày 02 tháng 11 năm 2021, nơi cấp Sở Kế hoạch & Đầu tư TP HCM - Phòng Đăng Kí Kinh Doanh.",
        "footer_address": "Trụ sở chính: Số 121 Đinh Tiên Hoàng, Phường Tân Định, Thành phố Hồ Chí Minh, Việt Nam",
        "footer_phone_label": "Điện thoại quản lý (Facetime / Zalo / Viber):",
        "footer_policy_payment": "Chính sách thanh toán",
        "footer_policy_delivery": "Chính sách giao nhận",
        "footer_policy_return": "Chính sách kiểm hàng, đổi trả/ hoàn tiền",
        "footer_policy_privacy": "Chính sách bảo mật thông tin",
        "footer_bank_title": "TÀI KHOẢN NGÂN HÀNG",
        "footer_credit": "Thiết kế & phát triển bởi leap.vn",
        "promo_btn": "Vé giá tốt"
    },
    "EN": {
        "lang_title": "Language",
        "btn_booking": "Manage Booking",
        "btn_login": "👤 Login",
        "hero_title": "FLIGHT SEARCH",
        "tab_roundtrip": "Round Trip",
        "tab_oneway": "One Way",
        "tab_multicity": "Multi-City",
        "seat_class": "Seat Class",
        "seat_economy": "Economy", "seat_premium": "Premium Economy", "seat_first": "First Class", "seat_all": "All Classes",
        "seat_business": "Business",
        "passenger": "Passengers",
        "adult": "Adults", "adult_desc": "(from 12 yrs)",
        "child": "Children", "child_desc": "(2 - 12 yrs)",
        "infant": "Infants", "infant_desc": "(under 2 yrs)",
        "from": "From",
        "to": "To",
        "depart_date": "Departure",
        "return_date": "Return",
        "confirm": "Confirm",
        "search_btn": "Search Flights",
        "add_flight": "+ Add Flight",
        "destinations_title": "POPULAR DESTINATIONS",
        "news_title": "NEWS & OFFERS",
        "placeholder_airport": "City or Airport",
        // Footer
        "footer_company": "BAYOU CO., LTD",
        "footer_license": "Business Registration Certificate No. 0315086120, issued on November 2, 2021, by the Department of Planning & Investment of HCMC - Business Registration Division.",
        "footer_address": "Head Office: 121 Dinh Tien Hoang, Tan Dinh Ward, Ho Chi Minh City, Vietnam",
        "footer_phone_label": "Management Phone (Facetime / Zalo / Viber):",
        "footer_policy_payment": "Payment Policy",
        "footer_policy_delivery": "Delivery Policy",
        "footer_policy_return": "Return & Refund Policy",
        "footer_policy_privacy": "Privacy Policy",
        "footer_bank_title": "BANK ACCOUNTS",
        "footer_credit": "Designed & developed by leap.vn",
        "promo_btn": "Best Deals"
    },
    
    
    
};

export function updateLanguage(langCode) {
    const t = translations[langCode] || translations["VN"];
    
    // 1. Update all elements with data-i18n attribute
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (t[key]) {
            el.textContent = t[key];
        }
    });

    // 2. Update search widget tabs
    const tabMap = {
        'roundtrip': 'tab_roundtrip',
        'oneway': 'tab_oneway', 
        'multicity': 'tab_multicity'
    };
    document.querySelectorAll('.search-widget__tab').forEach(tab => {
        const trip = tab.getAttribute('data-trip');
        if (trip && tabMap[trip] && t[tabMap[trip]]) {
            tab.textContent = t[tabMap[trip]];
        }
    });

    // 3. Update dropdowns
    const btnSeat = document.getElementById('btn-seat');
    if (btnSeat) btnSeat.innerHTML = `💺 ${t.seat_economy} <span class="arrow">▼</span>`;
    
    const btnPassenger = document.getElementById('btn-passenger');
    if (btnPassenger) btnPassenger.innerHTML = `👥 1 ${t.passenger} <span class="arrow">▼</span>`;

    // 5. Update placeholders
    document.querySelectorAll('.search-widget__input').forEach(input => {
        const ph = input.placeholder;
        if (ph.includes('Thành phố') || ph.includes('City') || ph.includes('도시') || ph.includes('都市') || ph.includes('城市')) {
            input.placeholder = t.placeholder_airport;
        }
    });

    // 6. Update section titles
    const destTitle = document.querySelector('#destinations-container .section-title');
    if (destTitle) destTitle.textContent = t.destinations_title;
    
    const newsTitle = document.querySelector('#news-container .section-title');
    if (newsTitle) newsTitle.textContent = t.news_title;

    // 7. Update header buttons
    const btnBooking = document.getElementById('btn-booking');
    if (btnBooking) btnBooking.textContent = t.btn_booking;
    
    const btnLogin = document.getElementById('btn-login');
    if (btnLogin) btnLogin.textContent = t.btn_login;

    // 8. Update promo button
    const promoBtn = document.querySelector('.widget-btn--promo');
    if (promoBtn) {
        promoBtn.title = t.promo_btn;
        const img = promoBtn.querySelector('img');
        if (img) img.alt = t.promo_btn;
    }

    // 9. Dispatch event for other modules
    document.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang: langCode, dict: t } }));
}
