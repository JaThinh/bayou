# 🗄️ Hướng dẫn Setup PostgreSQL cho Bayou Travel

## Bước 1 — Cài đặt PostgreSQL + pgAdmin

Tải về tại: https://www.postgresql.org/download/windows/
→ Chọn **PostgreSQL 16** (bao gồm pgAdmin 4)

Trong quá trình cài:
- Port: `5432` (mặc định)
- Password superuser: đặt mật khẩu và **ghi nhớ lại**
- Locale: `Vietnamese, Vietnam`

---

## Bước 2 — Tạo Database trong pgAdmin

1. Mở **pgAdmin 4**
2. Đăng nhập với password vừa đặt
3. Click chuột phải vào **Databases** → **Create → Database**
4. Đặt tên: `bayou_db` → Save

---

## Bước 3 — Chạy Schema SQL

Trong pgAdmin, click chuột phải vào **bayou_db** → **Query Tool**  
Mở file và chạy theo thứ tự:

```sql
-- 1. Tạo toàn bộ bảng
\i database/schema.sql

-- 2. Nhập dữ liệu sân bay
\i database/seeds/airports.sql
```

Hoặc copy nội dung từng file vào Query Tool và nhấn **F5** để chạy.

---

## Bước 4 — Cấu hình .env

Mở file `.env` tại thư mục gốc project và điền thông tin:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=bayou_db
DB_USER=postgres
DB_PASS=<mật_khẩu_của_bạn>
```

---

## Bước 5 — Kiểm tra kết nối

Truy cập: `http://localhost/api/search_flights.php?origin=SGN&destination=HAN&date=2026-06-15`

Response mẫu:
```json
{
  "success": true,
  "route": "SGN → HAN",
  "count": 8,
  "data": [...],
  "_from_cache": false
}
```

Lần 2 gọi cùng tuyến trong 10 phút → `"_from_cache": true` (lấy từ PostgreSQL)

---

## Cấu trúc Database

| Bảng | Tác dụng |
|---|---|
| `airports` | Danh sách 28 sân bay + entityId Skyscanner |
| `users` | Tài khoản khách hàng & nhân viên |
| `search_logs` | Log mọi lần tìm kiếm (analytics) |
| `price_cache` | Cache kết quả vé 10 phút (tránh gọi API lặp) |
| `bookings` | Đặt chỗ + phí hành lý/ghế/thẻ |

## Xem dữ liệu trong pgAdmin

- **search_logs**: Xem ai tìm tuyến nào, lúc nào
- **price_cache**: Cache nào đang còn hiệu lực  
- **bookings**: Quản lý đặt chỗ, cập nhật status/PNR

## API Endpoints

| Endpoint | Method | Tác dụng |
|---|---|---|
| `/api/search_flights.php` | POST/GET | Tìm vé (có cache) |
| `/api/bookings.php` | POST | Tạo booking mới |
| `/api/bookings.php?ref=&phone=` | GET | Tra cứu booking |
| `/api/currency/rate.php` | GET | Tỷ giá ngoại tệ |
