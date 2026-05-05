<?php
/**
 * Bayou OTA - Trang kết quả tìm kiếm chuyến bay (Async / AJAX flow)
 *
 * Trang này KHÔNG render danh sách chuyến bay đồng bộ ở phía PHP nữa.
 * Thay vào đó nó render giao diện skeleton + loading screen,
 * sau đó JavaScript sẽ fetch API JSON và đổ kết quả vào DOM.
 */

$rawFrom = trim((string) ($_GET['from'] ?? $_GET['origin'] ?? 'SGN'));
$rawTo = trim((string) ($_GET['to'] ?? $_GET['destination'] ?? 'HAN'));
$rawDate = trim((string) ($_GET['date'] ?? $_GET['departDate'] ?? date('Y-m-d', strtotime('+7 days'))));
$rawReturnDate = trim((string) ($_GET['returnDate'] ?? ''));
$tripType = strtolower((string) ($_GET['tripType'] ?? 'oneway'));
$adults = max(1, (int) ($_GET['adults'] ?? 1));
$children = max(0, (int) ($_GET['children'] ?? 0));
$infants = max(0, (int) ($_GET['infants'] ?? 0));
$seatClass = (string) ($_GET['seatClass'] ?? 'Phổ thông');

$normalizeDateForView = static function (string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) === 1) {
        return $m[3] . '/' . $m[2] . '/' . $m[1];
    }
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value) === 1) {
        return $value;
    }
    $ts = strtotime($value);
    return $ts !== false ? date('d/m/Y', $ts) : $value;
};

$displayDate = $normalizeDateForView($rawDate);
$displayReturnDate = $rawReturnDate !== '' ? $normalizeDateForView($rawReturnDate) : '';

$cityNames = [
    'SGN' => 'TP. Hồ Chí Minh',
    'HAN' => 'Hà Nội',
    'DAD' => 'Đà Nẵng',
    'CXR' => 'Nha Trang',
    'PQC' => 'Phú Quốc',
    'VCA' => 'Cần Thơ',
    'DLI' => 'Đà Lạt',
    'HPH' => 'Hải Phòng',
    'BKK' => 'Bangkok',
    'ICN' => 'Seoul',
    'NRT' => 'Tokyo',
    'HND' => 'Tokyo',
    'SIN' => 'Singapore',
    'CDG' => 'Paris',
    'LAX' => 'Los Angeles',
    'JFK' => 'New York',
];

$destinationImages = [
    'SGN' => 'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=600&q=80&auto=format',
    'HAN' => 'https://images.unsplash.com/photo-1509030450996-dd1a26dda07a?w=600&q=80&auto=format',
    'DAD' => 'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?w=600&q=80&auto=format',
    'PQC' => 'https://images.unsplash.com/photo-1528127269322-539801943592?w=600&q=80&auto=format',
    'NRT' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600&q=80&auto=format',
    'HND' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600&q=80&auto=format',
    'CDG' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&q=80&auto=format',
    'BKK' => 'https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=600&q=80&auto=format',
];

$fromCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rawFrom) ?: 'SGN');
$toCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rawTo) ?: 'HAN');

$fromCity = $cityNames[$fromCode] ?? $rawFrom;
$toCity = $cityNames[$toCode] ?? $rawTo;
$destinationImage = $destinationImages[$toCode]
    ?? 'https://images.unsplash.com/photo-1488085061387-422e29b40080?w=600&q=80&auto=format';

$totalPassengers = $adults + $children + $infants;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang tìm chuyến bay <?= htmlspecialchars($fromCode) ?> - <?= htmlspecialchars($toCode) ?> | Bayou Travel</title>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/components/header/header.css">
    <link rel="stylesheet" href="/css/flight-search.css">

    <link rel="icon" href="/assets/logos/favicon.png.png" type="image/png">

    <script>
        // Truyền tham số tìm kiếm từ PHP -> JS để fetch API.
        window.__BAYOU_SEARCH__ = <?= json_encode([
            'from' => $fromCode,
            'to' => $toCode,
            'fromCity' => $fromCity,
            'toCity' => $toCity,
            'date' => $rawDate,
            'displayDate' => $displayDate,
            'returnDate' => $rawReturnDate,
            'displayReturnDate' => $displayReturnDate,
            'tripType' => $tripType,
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'seatClass' => $seatClass,
            'totalPassengers' => $totalPassengers,
            'apiUrl' => '/api/flights/search.php',
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
</head>
<body class="flight-search-page">
    <div id="header-container"></div>

    <!-- Step Progress Bar (luôn hiển thị ở đầu trang, kể cả sau khi có kết quả) -->
    <div class="step-progress" aria-label="Tiến trình đặt vé">
        <div class="step-progress__inner">
            <div class="step-progress__item step-progress__item--active">
                <span class="step-progress__circle">1</span>
                <span class="step-progress__label">Lựa chọn chuyến bay</span>
            </div>
            <span class="step-progress__divider"></span>
            <div class="step-progress__item">
                <span class="step-progress__circle">2</span>
                <span class="step-progress__label">Điền thông tin</span>
            </div>
            <span class="step-progress__divider"></span>
            <div class="step-progress__item">
                <span class="step-progress__circle">3</span>
                <span class="step-progress__label">Thanh toán</span>
            </div>
        </div>
    </div>

    <main class="flight-search-layout">
        <!-- ========================================
             LOADING / SKELETON SCREEN
             ======================================== -->
        <div id="loading-screen" class="loading-screen" role="status" aria-live="polite">
            <!-- Sidebar bộ lọc - skeleton shimmer -->
            <aside class="loading-screen__sidebar" aria-hidden="true">
                <div class="skeleton-card">
                    <div class="skeleton skeleton--title"></div>
                    <div class="skeleton skeleton--line"></div>
                    <div class="skeleton skeleton--line skeleton--short"></div>
                    <div class="skeleton skeleton--line"></div>
                </div>
                <div class="skeleton-card">
                    <div class="skeleton skeleton--title"></div>
                    <div class="skeleton skeleton--line"></div>
                    <div class="skeleton skeleton--line skeleton--short"></div>
                    <div class="skeleton skeleton--line"></div>
                    <div class="skeleton skeleton--line skeleton--short"></div>
                </div>
                <div class="skeleton-card">
                    <div class="skeleton skeleton--title"></div>
                    <div class="skeleton skeleton--line"></div>
                    <div class="skeleton skeleton--line"></div>
                </div>
                <div class="skeleton-card">
                    <div class="skeleton skeleton--title"></div>
                    <div class="skeleton skeleton--line skeleton--short"></div>
                    <div class="skeleton skeleton--line"></div>
                </div>
            </aside>

            <!-- Main content - progress bar + ảnh + text -->
            <section class="loading-screen__main">
                <div class="loading-progress" aria-hidden="true">
                    <div class="loading-progress__bar" id="loading-progress-bar"></div>
                </div>

                <div class="loading-card">
                    <div class="loading-destination">
                        <img
                            src="<?= htmlspecialchars($destinationImage) ?>"
                            alt="Điểm đến <?= htmlspecialchars($toCity) ?>"
                            class="loading-destination__img"
                            id="loading-destination-img"
                        >
                        <span class="loading-destination__pulse"></span>
                    </div>

                    <h2 class="loading-card__title">
                        Vui lòng đợi trong giây lát, chúng tôi đang tìm chuyến bay
                        và mức giá tốt nhất cho bạn...
                    </h2>

                    <p class="loading-card__route">
                        <strong><?= htmlspecialchars($fromCity) ?> (<?= htmlspecialchars($fromCode) ?>)</strong>
                        <span class="loading-card__plane">✈</span>
                        <strong><?= htmlspecialchars($toCity) ?> (<?= htmlspecialchars($toCode) ?>)</strong>
                    </p>

                    <p class="loading-card__meta">
                        Ngày đi: <strong><?= htmlspecialchars($displayDate) ?></strong>
                        <?php if ($displayReturnDate !== ''): ?>
                            &nbsp;·&nbsp; Ngày về: <strong><?= htmlspecialchars($displayReturnDate) ?></strong>
                        <?php endif; ?>
                        &nbsp;·&nbsp; <?= (int) $totalPassengers ?> hành khách
                        &nbsp;·&nbsp; <?= htmlspecialchars($seatClass) ?>
                    </p>

                    <div class="loading-card__hint">
                        <span class="loading-card__dot"></span>
                        <span>Đang so sánh giá vé từ Vietnam Airlines, VietJet, Bamboo Airways...</span>
                    </div>
                </div>

                <!-- Skeleton placeholders cho card kết quả -->
                <div class="loading-screen__cards" aria-hidden="true">
                    <div class="skeleton-card skeleton-card--row">
                        <div class="skeleton skeleton--avatar"></div>
                        <div class="skeleton-card__body">
                            <div class="skeleton skeleton--line"></div>
                            <div class="skeleton skeleton--line skeleton--short"></div>
                        </div>
                        <div class="skeleton skeleton--price"></div>
                    </div>
                    <div class="skeleton-card skeleton-card--row">
                        <div class="skeleton skeleton--avatar"></div>
                        <div class="skeleton-card__body">
                            <div class="skeleton skeleton--line"></div>
                            <div class="skeleton skeleton--line skeleton--short"></div>
                        </div>
                        <div class="skeleton skeleton--price"></div>
                    </div>
                    <div class="skeleton-card skeleton-card--row">
                        <div class="skeleton skeleton--avatar"></div>
                        <div class="skeleton-card__body">
                            <div class="skeleton skeleton--line"></div>
                            <div class="skeleton skeleton--line skeleton--short"></div>
                        </div>
                        <div class="skeleton skeleton--price"></div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ========================================
             KẾT QUẢ THẬT (mặc định ẨN, JS sẽ fade-in)
             ======================================== -->
        <div id="flight-results" class="flight-results" hidden>
            <!-- Search summary bar - các pills tóm tắt yêu cầu tìm + nút sửa nhanh -->
            <div class="search-summary" id="search-summary">
                <button type="button" class="search-summary__pill" data-edit="route">
                    <span class="search-summary__icon" aria-hidden="true">📍</span>
                    <span class="search-summary__text">
                        <strong><?= htmlspecialchars($fromCity) ?> (<?= htmlspecialchars($fromCode) ?>)</strong>
                        <span class="search-summary__sep">→</span>
                        <strong><?= htmlspecialchars($toCity) ?> (<?= htmlspecialchars($toCode) ?>)</strong>
                    </span>
                </button>
                <button type="button" class="search-summary__pill" data-edit="date">
                    <span class="search-summary__icon" aria-hidden="true">📅</span>
                    <span class="search-summary__text">
                        <?= htmlspecialchars($displayDate) ?>
                        <?php if ($displayReturnDate !== ''): ?>
                            <span class="search-summary__sep">→</span> <?= htmlspecialchars($displayReturnDate) ?>
                        <?php endif; ?>
                    </span>
                </button>
                <button type="button" class="search-summary__pill" data-edit="passengers">
                    <span class="search-summary__icon" aria-hidden="true">👤</span>
                    <span class="search-summary__text">
                        <?= (int) $totalPassengers ?> Hành khách
                    </span>
                </button>
                <button type="button" class="search-summary__pill" data-edit="seat-class">
                    <span class="search-summary__icon" aria-hidden="true">💺</span>
                    <span class="search-summary__text">
                        <?= htmlspecialchars($seatClass) ?>
                    </span>
                </button>
                <a class="search-summary__edit" href="/" title="Sửa tìm kiếm" aria-label="Sửa tìm kiếm">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                    </svg>
                </a>
            </div>

            <div class="results-grid">
                <aside class="filters" id="flight-filters">
                    <div class="filters__header">
                        <h3 class="filters__title">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="4" y1="6" x2="20" y2="6"></line>
                                <line x1="7" y1="12" x2="20" y2="12"></line>
                                <line x1="10" y1="18" x2="20" y2="18"></line>
                                <circle cx="6" cy="12" r="2"></circle>
                                <circle cx="9" cy="18" r="2"></circle>
                                <circle cx="3" cy="6" r="2"></circle>
                            </svg>
                            Bộ lọc
                        </h3>
                        <button type="button" id="clear-filters-btn" class="filters__clear">Xóa bộ lọc</button>
                    </div>

                    <!-- Card: Giá vé (dual range slider) -->
                    <div class="filter-card">
                        <h4 class="filter-card__title">Giá vé</h4>
                        <div class="price-slider" id="price-slider">
                            <div class="price-slider__track">
                                <div class="price-slider__range" id="price-slider-range"></div>
                            </div>
                            <input type="range" id="price-slider-min" min="0" max="100" value="0" step="1" aria-label="Giá tối thiểu">
                            <input type="range" id="price-slider-max" min="0" max="100" value="100" step="1" aria-label="Giá tối đa">
                        </div>
                        <div class="price-slider__labels">
                            <div>
                                <span class="price-slider__label">Tối thiểu</span>
                                <strong id="price-slider-min-display">—</strong>
                            </div>
                            <div class="price-slider__labels-right">
                                <span class="price-slider__label">Tối đa</span>
                                <strong id="price-slider-max-display">—</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Card: Hãng hàng không -->
                    <div class="filter-card">
                        <h4 class="filter-card__title">Hãng hàng không</h4>
                        <div id="filter-airlines" class="filter-options"></div>
                    </div>

                    <!-- Card: Số điểm dừng -->
                    <div class="filter-card">
                        <h4 class="filter-card__title">Số điểm dừng</h4>
                        <div id="filter-stops" class="filter-options">
                            <label class="filter-option">
                                <input type="radio" name="stops" value="all" checked>
                                <span class="filter-option__label">Tất cả</span>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="stops" value="0">
                                <span class="filter-option__label">Bay thẳng</span>
                            </label>
                            <label class="filter-option">
                                <input type="radio" name="stops" value="1">
                                <span class="filter-option__label">1 điểm dừng</span>
                            </label>
                        </div>
                    </div>
                </aside>

                <section class="results-main">
                    <!-- Date carousel: 7 ngày liền kề với giá thấp nhất ước tính -->
                    <div class="date-carousel" id="date-carousel">
                        <button type="button" class="date-carousel__nav date-carousel__nav--prev" aria-label="Tuần trước">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <div class="date-carousel__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <div class="date-carousel__list" id="date-carousel-list" role="tablist"></div>
                        <button type="button" class="date-carousel__nav date-carousel__nav--next" aria-label="Tuần sau">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>

                    <!-- Banner cảnh báo (hiện khi fallback mock data) -->
                    <div id="flight-results-notice" class="flight-notice" hidden>
                        <span class="flight-notice__icon" aria-hidden="true">⚠️</span>
                        <p class="flight-notice__text" id="flight-results-notice-text"></p>
                    </div>

                    <!-- Toolbar: toggle riêng lẻ + sort + reset -->
                    <div class="results-toolbar">
                        <label class="toggle-switch">
                            <input type="checkbox" id="separate-flights-toggle">
                            <span class="toggle-switch__slider"></span>
                            <span class="toggle-switch__text">Chọn chuyến bay riêng lẻ</span>
                        </label>
                        <div class="results-toolbar__right">
                            <div class="results-toolbar__sort">
                                <label for="sort-flights" class="results-toolbar__sort-label">Sắp xếp:</label>
                                <select id="sort-flights" class="results-toolbar__select">
                                    <option value="price-asc">Giá thấp nhất</option>
                                    <option value="price-desc">Giá cao nhất</option>
                                    <option value="depart-asc">Giờ đi: Sớm nhất</option>
                                    <option value="duration-asc">Thời gian bay ngắn nhất</option>
                                </select>
                            </div>
                            <button type="button" id="reset-toolbar-btn" class="results-toolbar__reset">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="1 4 1 10 7 10"></polyline>
                                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                                </svg>
                                Cài đặt lại
                            </button>
                        </div>
                    </div>

                    <!-- Section title bar -->
                    <h2 class="results-section-title">
                        <span class="results-section-title__plane" aria-hidden="true">✈</span>
                        Chọn chuyến bay
                        <span class="results-section-title__route">
                            <?= htmlspecialchars($fromCity) ?> (<?= htmlspecialchars($fromCode) ?>)
                            <span class="results-section-title__arrow">→</span>
                            <?= htmlspecialchars($toCity) ?> (<?= htmlspecialchars($toCode) ?>)
                        </span>
                    </h2>

                    <div class="flight-results__summary" id="flight-results-summary"></div>
                    <div class="flight-results__list" id="flight-results-list"></div>

                    <template id="flight-card-template">
                        <article class="flight-card" data-airline-code="">
                            <div class="flight-card__main">
                                <header class="flight-card__head">
                                    <div class="flight-card__airline">
                                        <div class="flight-card__logo-box">
                                            <img class="flight-card__logo" alt="">
                                        </div>
                                        <div class="flight-card__airline-info">
                                            <strong class="flight-card__airline-name"></strong>
                                            <span class="flight-card__flight-number"></span>
                                        </div>
                                    </div>
                                    <div class="flight-card__meta">
                                        <span class="flight-card__aircraft"></span>
                                        <span class="flight-card__amenities" aria-hidden="true"></span>
                                        <span class="flight-card__seats-left"></span>
                                    </div>
                                </header>

                                <div class="flight-card__schedule">
                                    <div class="flight-card__time">
                                        <strong class="flight-card__depart-time"></strong>
                                        <span class="flight-card__origin"></span>
                                    </div>
                                    <div class="flight-card__path">
                                        <span class="flight-card__stops"></span>
                                        <div class="flight-card__line">
                                            <span class="flight-card__plane" aria-hidden="true">✈</span>
                                        </div>
                                        <span class="flight-card__duration"></span>
                                    </div>
                                    <div class="flight-card__time flight-card__time--arrive">
                                        <strong class="flight-card__arrive-time"></strong>
                                        <span class="flight-card__destination"></span>
                                    </div>
                                </div>
                            </div>

                            <aside class="flight-card__pricing">
                                <select class="flight-card__class-select" aria-label="Chọn hạng ghế">
                                    <option>Phổ thông</option>
                                    <option>Phổ thông Đặc biệt</option>
                                    <option>Thương gia</option>
                                </select>
                                <div class="flight-card__price-area">
                                    <span class="flight-card__cheapest-badge" hidden>Rẻ nhất</span>
                                    <strong class="flight-card__price"></strong>
                                    <small class="flight-card__price-note">Đã bao gồm thuế phí</small>
                                </div>
                                <button type="button" class="flight-card__select-btn">
                                    Chọn
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </button>
                            </aside>
                        </article>
                    </template>

                    <div id="flight-results-empty" class="flight-empty" hidden>
                        <div class="flight-empty__icon" aria-hidden="true">🛫</div>
                        <h3>Không tìm thấy chuyến bay phù hợp.</h3>
                        <p>Vui lòng thử ngày bay khác, đổi bộ lọc, hoặc thay đổi điểm đi / điểm đến.</p>
                    </div>

                    <div id="flight-results-error" class="flight-error" hidden>
                        <div class="flight-error__icon" aria-hidden="true">⚠️</div>
                        <h3>Đã có lỗi xảy ra khi tìm chuyến bay.</h3>
                        <p id="flight-results-error-message"></p>
                        <button type="button" id="flight-retry-btn" class="flight-error__btn">Thử lại</button>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script type="module" src="/js/flight-search.js"></script>
</body>
</html>
