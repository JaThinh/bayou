<?php
/**
 * BAYOU OTA - Quản lý Booking (Amadeus 1A Style)
 * Khớp với schema thực tế: bookings(id, order_code, pnr_code, user_id, airline_code, 
 *   total_price, payment_status, ticket_status, contact_email, contact_phone, created_at)
 */
require_once __DIR__ . '/db.php';

// === TÌM KIẾM ===
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE b.pnr_code LIKE :q1 OR b.order_code LIKE :q2 OR u.fullname LIKE :q3";
    $params[':q1'] = "%{$search}%";
    $params[':q2'] = "%{$search}%";
    $params[':q3'] = "%{$search}%";
}

// === QUERY ===
// Detect user name column
$userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
$nameCol = in_array('fullname', $userCols) ? 'u.fullname' : (in_array('full_name', $userCols) ? 'u.full_name' : "'N/A'");

$sql = "SELECT 
            b.id, b.pnr_code, b.order_code, b.airline_code,
            b.total_price, b.payment_status, b.ticket_status,
            b.contact_email, b.contact_phone, b.created_at,
            {$nameCol} AS agent_name,
            a.name AS airline_name, a.logo_url
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN airlines a ON b.airline_code = a.code
        {$where}
        ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// === THỐNG KÊ ===
$stats = $pdo->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN payment_status='paid' THEN 1 ELSE 0 END) as paid,
    SUM(CASE WHEN ticket_status='issued' THEN 1 ELSE 0 END) as issued,
    SUM(CASE WHEN payment_status='paid' THEN total_price ELSE 0 END) as revenue
    FROM bookings")->fetch();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayou Admin — Quản lý Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --bayou: #003366; --accent: #0077cc; --bg: #f0f2f5; }
        * { font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: var(--bg); }
        .sidebar { position: fixed; top: 0; left: 0; width: 230px; height: 100vh; background: var(--bayou); color: #fff; padding-top: 20px; z-index: 100; }
        .sidebar .brand { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar .brand h4 { margin: 0; font-weight: 800; }
        .sidebar .brand small { color: #8eafc8; font-size: 11px; }
        .sidebar .nav-link { color: #b0c4d8; padding: 10px 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; border-left: 3px solid transparent; transition: .2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,.08); border-left-color: #00aaff; }
        .main { margin-left: 230px; padding: 25px; }
        .stat-card { background: #fff; border-radius: 10px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .stat-card .val { font-size: 26px; font-weight: 800; color: var(--bayou); }
        .stat-card .lbl { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: .5px; }
        .stat-card .ico { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .search-box { background: #fff; border-radius: 10px; padding: 12px 18px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .tbl-wrap { background: #fff; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,.06); overflow: hidden; }
        .tbl-wrap .table { margin: 0; font-size: 13.5px; }
        .tbl-wrap thead th { background: var(--bayou); color: #fff; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .4px; padding: 11px 14px; border: none; white-space: nowrap; }
        .tbl-wrap tbody td { padding: 11px 14px; vertical-align: middle; border-color: #f0f0f0; }
        .tbl-wrap tbody tr:hover { background: #f8fafc; }
        .airline-logo { width: 30px; height: 30px; object-fit: contain; border-radius: 4px; }
        .pnr { font-family: 'Courier New', monospace; font-weight: 700; color: var(--accent); font-size: 14px; letter-spacing: 1px; }
        .price { font-weight: 700; color: #222; }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="brand"><h4>BAYOU</h4><small>Admin Panel v1.0</small></div>
    <a href="bookings.php" class="nav-link active"><i class="bi bi-journal-text"></i> Quản lý Booking</a>
    <a href="#" class="nav-link"><i class="bi bi-ticket-perforated"></i> Xuất vé</a>
    <a href="#" class="nav-link"><i class="bi bi-people"></i> Đại lý</a>
    <a href="#" class="nav-link"><i class="bi bi-airplane"></i> Hãng bay</a>
    <a href="#" class="nav-link"><i class="bi bi-currency-dollar"></i> Chính sách giá</a>
    <a href="#" class="nav-link"><i class="bi bi-bar-chart-line"></i> Báo cáo</a>
    <a href="#" class="nav-link"><i class="bi bi-shield-lock"></i> Bảo mật</a>
    <a href="#" class="nav-link"><i class="bi bi-gear"></i> Cài đặt</a>
</nav>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h4 class="fw-bold mb-0" style="color:var(--bayou)">Quản lý Booking</h4><small class="text-muted">Tổng quan đơn đặt chỗ</small></div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-download"></i> Xuất Excel</button>
            <button class="btn btn-sm text-white" style="background:var(--accent)"><i class="bi bi-plus-lg"></i> Tạo Booking</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card d-flex justify-content-between align-items-center">
                <div><div class="val"><?= number_format($stats['total'] ?? 0) ?></div><div class="lbl">Tổng Booking</div></div>
                <div class="ico" style="background:#e8f4fd;color:#0077cc"><i class="bi bi-journal-text"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card d-flex justify-content-between align-items-center">
                <div><div class="val"><?= number_format($stats['paid'] ?? 0) ?></div><div class="lbl">Đã Thanh Toán</div></div>
                <div class="ico" style="background:#e6f9f0;color:#10b981"><i class="bi bi-check-circle"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card d-flex justify-content-between align-items-center">
                <div><div class="val"><?= number_format($stats['issued'] ?? 0) ?></div><div class="lbl">Đã Xuất Vé</div></div>
                <div class="ico" style="background:#eef0ff;color:#6366f1"><i class="bi bi-ticket-perforated"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card d-flex justify-content-between align-items-center">
                <div><div class="val"><?= number_format($stats['revenue'] ?? 0, 0, ',', '.') ?>₫</div><div class="lbl">Doanh Thu</div></div>
                <div class="ico" style="background:#fff7e6;color:#f59e0b"><i class="bi bi-cash-stack"></i></div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="search-box mb-4">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <i class="bi bi-search text-muted"></i>
            <input type="text" name="q" class="form-control form-control-sm border-0" style="box-shadow:none" placeholder="Tìm theo PNR, Mã đơn hàng, hoặc Tên..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-sm text-white" style="background:var(--accent);white-space:nowrap">Tìm</button>
            <?php if ($search): ?><a href="bookings.php" class="btn btn-sm btn-outline-secondary">✕</a><?php endif; ?>
        </form>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Hãng</th>
                    <th>Mã PNR</th>
                    <th>Mã Đơn Hàng</th>
                    <th>Người Đặt</th>
                    <th>Liên Hệ</th>
                    <th class="text-end">Tổng Tiền</th>
                    <th class="text-center">Thanh Toán</th>
                    <th class="text-center">Vé</th>
                    <th class="text-center">Ngày Tạo</th>
                    <th class="text-center">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($bookings)): ?>
                <tr><td colspan="10" class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size:36px"></i><br>
                    <span class="mt-2 d-block">Chưa có booking nào<?= $search ? " với \"<b>{$search}</b>\"" : '' ?>.</span>
                    <small>Hãy chạy <a href="setup_admin_db.php">setup_admin_db.php</a> để tạo dữ liệu mẫu.</small>
                </td></tr>
            <?php else: foreach ($bookings as $b): ?>
                <tr>
                    <td>
                        <?php if (!empty($b['logo_url'])): ?>
                            <img src="<?= htmlspecialchars($b['logo_url']) ?>" alt="<?= $b['airline_code'] ?>" class="airline-logo" title="<?= htmlspecialchars($b['airline_name'] ?? '') ?>">
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= $b['airline_code'] ?? '—' ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="pnr"><?= $b['pnr_code'] ?></span></td>
                    <td><small class="text-muted"><?= $b['order_code'] ?></small></td>
                    <td><strong style="font-size:13px"><?= htmlspecialchars($b['agent_name'] ?? '—') ?></strong></td>
                    <td>
                        <small><?= htmlspecialchars($b['contact_email'] ?? '') ?></small><br>
                        <small class="text-muted"><?= $b['contact_phone'] ?? '' ?></small>
                    </td>
                    <td class="text-end price"><?= number_format($b['total_price'] ?? 0, 0, ',', '.') ?>₫</td>
                    <td class="text-center"><?php
                        echo match($b['payment_status'] ?? '') {
                            'paid'   => '<span class="badge bg-success">Đã TT</span>',
                            'failed' => '<span class="badge bg-danger">Thất bại</span>',
                            default  => '<span class="badge bg-warning text-dark">Chờ TT</span>',
                        };
                    ?></td>
                    <td class="text-center"><?php
                        echo match($b['ticket_status'] ?? '') {
                            'issued'    => '<span class="badge bg-primary">Đã xuất</span>',
                            'cancelled' => '<span class="badge bg-secondary">Đã hủy</span>',
                            default     => '<span class="badge bg-warning text-dark">Xử lý</span>',
                        };
                    ?></td>
                    <td class="text-center"><small><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></small></td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" title="Xem"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-outline-success" title="Xuất vé"><i class="bi bi-ticket-perforated"></i></button>
                            <button class="btn btn-outline-danger" title="Hủy"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-center text-muted mt-4" style="font-size:12px">BAYOU Admin v1.0 — <?= date('d/m/Y H:i') ?></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
