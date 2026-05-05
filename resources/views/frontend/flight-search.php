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
            <aside class="flight-results__sidebar" id="flight-filters">
                <div class="filter-card">
                    <h4 class="filter-card__title">Bộ lọc</h4>
                    <div class="filter-card__group">
                        <label class="filter-card__label">Hãng hàng không</label>
                        <div id="filter-airlines" class="filter-card__options"></div>
                    </div>
                    <div class="filter-card__group">
                        <label class="filter-card__label">Số điểm dừng</label>
                        <div class="filter-card__options">
                            <label><input type="radio" name="stops" value="all" checked> Tất cả</label>
                            <label><input type="radio" name="stops" value="0"> Bay thẳng</label>
                            <label><input type="radio" name="stops" value="1"> 1 điểm dừng</label>
                        </div>
                    </div>
                    <div class="filter-card__group">
                        <label class="filter-card__label">Sắp xếp theo</label>
                        <select id="sort-flights" class="filter-card__select">
                            <option value="price-asc">Giá: Thấp → Cao</option>
                            <option value="price-desc">Giá: Cao → Thấp</option>
                            <option value="depart-asc">Giờ đi: Sớm nhất</option>
                            <option value="duration-asc">Thời gian bay ngắn nhất</option>
                        </select>
                    </div>
                </div>
            </aside>

            <section class="flight-results__main">
                <div class="flight-results__summary" id="flight-results-summary"></div>
                <div class="flight-results__list" id="flight-results-list"></div>
                <template id="flight-card-template">
                    <article class="flight-card" data-airline-code="">
                        <div class="flight-card__airline">
                            <div class="flight-card__logo-box">
                                <img class="flight-card__logo" alt="">
                            </div>
                            <div>
                                <strong class="flight-card__airline-name"></strong>
                                <span class="flight-card__flight-number"></span>
                            </div>
                        </div>

                        <div class="flight-card__schedule">
                            <div class="flight-card__time">
                                <strong class="flight-card__depart-time"></strong>
                                <span class="flight-card__origin"></span>
                            </div>
                            <div class="flight-card__path">
                                <span class="flight-card__stops"></span>
                                <div class="flight-card__line"><span>✈</span></div>
                                <span class="flight-card__duration"></span>
                            </div>
                            <div class="flight-card__time">
                                <strong class="flight-card__arrive-time"></strong>
                                <span class="flight-card__destination"></span>
                            </div>
                        </div>

                        <div class="flight-card__pricing">
                            <div class="flight-card__price"></div>
                            <div class="flight-card__price-note">Đã bao gồm thuế phí</div>
                            <button type="button" class="flight-card__select-btn">Chọn vé</button>
                        </div>
                    </article>
                </template>

                <div id="flight-results-empty" class="flight-empty" hidden>
                    <h3>Không tìm thấy chuyến bay phù hợp.</h3>
                    <p>Vui lòng thử ngày bay khác hoặc thay đổi điểm đi / điểm đến.</p>
                </div>

                <div id="flight-results-error" class="flight-error" hidden>
                    <h3>Đã có lỗi xảy ra khi tìm chuyến bay.</h3>
                    <p id="flight-results-error-message"></p>
                    <button type="button" id="flight-retry-btn" class="flight-error__btn">Thử lại</button>
                </div>
            </section>
        </div>
    </main>

    <script type="module" src="/js/flight-search.js"></script>
</body>
</html>
