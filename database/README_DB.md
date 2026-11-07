# Cơ sở dữ liệu Web Shop NagaMC (`defaultdb`)

Tài liệu này hướng dẫn cấu trúc, cách khởi tạo và quy tắc đồng bộ Cơ sở dữ liệu (MySQL 8.0+) cho dự án **Store-NagaMC** — Hệ thống Web Shop bán vật phẩm Minecraft tích hợp thanh toán tự động (VietQR), trả đồ qua RCON và Trợ lý AI tư vấn theo ngữ cảnh.

---

## 1. Cấu trúc thư mục

```text
database/
├── migrations/
│   └── 001_create_webshop_tables.sql   # Khởi tạo cấu trúc 10 bảng, khóa ngoại & chỉ mục (FULLTEXT)
├── seeders/
│   └── 001_seed_initial_data.sql       # Dữ liệu mẫu (Tài khoản, 4 Danh mục, 17 Vật phẩm, Mã giảm, Đơn hàng)
└── README.md                           # Tài liệu hướng dẫn sử dụng Database
```

---

##  2. Kiến trúc 10 Bảng & Sơ đồ liên kết (ERD)

Hệ thống sử dụng **MySQL 8.0+**, bộ mã hóa `utf8mb4_unicode_ci` và `ENGINE=InnoDB` (hỗ trợ khóa ngoại và Database Transaction chống lỗi cộng tiền đúp).

### Sơ đồ luồng dữ liệu theo chiều ngang (3 tầng)

```text
[TẦNG 1: BẢNG GỐC]               [TẦNG 2: NGHIỆP VỤ CHÍNH]              [TẦNG 3: CHI TIẾT & ĐỐI SOÁT]

[categories] (1) ──────────────► (N) [products] (1) ──────────────────► (N) [cart_items]
                                         │ (1)                                  ▲ (N)
[coupons] (0..1) ───────┐                └─────────────────────────┐            │
                        ▼ (N)                                      ▼ (N)        │
[users] (1) ──────────► [orders] (1) ────────────────────────────► [order_items]│
   │ (1)                   │ (1)                                                │
   ├───────────────────────┼─────────────────────────────────────► (N) [transactions]
   ├──────────────────► (N) [news]                                              │
   ├──────────────────► (N) [ai_chat_logs]                                      │
   └────────────────────────────────────────────────────────────────────────────┘
```

### Danh sách 10 bảng trong `defaultdb`

| STT | Tên bảng | Nhóm nghiệp vụ | Vai trò chính |
|---|---|---|---|
| 1 | `users` | Tài khoản | Lưu thông tin Người chơi (`user`) và Quản trị viên (`admin`), mật khẩu mã hóa `Bcrypt` (`password_hash`) và tên nhân vật (`ingame_name`). |
| 2 | `categories` | Cửa hàng | Phân loại danh mục vật phẩm (`Rank`, `Vật phẩm hỗ trợ`, `Crate Key`, `Sharp`). |
| 3 | `products` | Cửa hàng | Lưu thông tin vật phẩm, giá bán, tồn kho, lệnh trả đồ RCON (`delivery_command`) và từ khóa cho AI (`ai_context_tags`). |
| 4 | `coupons` | Khuyến mãi | Quản lý mã giảm giá theo phần trăm (`percent`) hoặc số tiền cố định (`fixed`), giới hạn lượt dùng và hạn sử dụng. |
| 5 | `cart_items` | Giỏ hàng | Lưu các vật phẩm người chơi đang chọn trong giỏ hàng trước khi đặt đơn. |
| 6 | `orders` | Đơn hàng | Lưu thông tin tổng quát của đơn hàng (`order_code`, tổng tiền, giảm giá, trạng thái thanh toán & trạng thái giao vật phẩm). |
| 7 | `order_items` | Đơn hàng | Lưu chi tiết từng vật phẩm trong đơn hàng (lưu cứng `product_name` và `unit_price` tại thời điểm mua để bảo toàn lịch sử). |
| 8 | `transactions` | Thanh toán | Lưu biên lai đối soát Webhook ngân hàng (VietQR/MoMo/VNPay), khóa `UNIQUE(transaction_code)` chống cộng tiền 2 lần. |
| 9 | `news` | Nội dung | Lưu bài viết tin tức máy chủ, sự kiện, hướng dẫn; tích hợp `FULLTEXT INDEX` (`ft_news_search`) hỗ trợ tìm kiếm nhanh. |
| 10 | `ai_chat_logs` | Trợ lý AI | Lưu lịch sử hội thoại AI Chatbot, ngữ cảnh tìm kiếm (`context_json`), loại tìm kiếm (`search_type`) và danh sách ID vật phẩm đã gợi ý. |

---

##  3. Dữ liệu mẫu mặc định (Seed Data)

Sau khi chạy file `seeders/001_seed_initial_data.sql`, hệ thống sẽ có sẵn bộ dữ liệu sau để các thành viên test Frontend & Backend:

###  Tài khoản đăng nhập thử nghiệm
> **Mật khẩu chung cho tất cả tài khoản mẫu:** `123456`

| Username | Email | Quyền (`role`) | Nhân vật (`ingame_name`) | Trạng thái |
|---|---|---|---|---|
| `admin` | `admin@nagamc.live` | `admin` | `NagaAdmin` | `active` |
| `player01` | `player01@gmail.com` | `user` | `DragonSlayer` | `active` |
| `player02` | `player02@gmail.com` | `user` | `ShadowKnight` | `active` |

###  4 Danh mục & 17 Vật phẩm mẫu
1. **Rank (ID 1 – 4):** `VIP (30 Ngày)`, `VIP+ (30 Ngày)`, `Plus (30 Ngày)`, `MVP (30 Ngày)`.
2. **Vật phẩm hỗ trợ (ID 5 – 10):** `Amethyst Pickaxe`, `Amethyst Axe`, `Amethyst Shovel`, `Amethyst Sword`, `Amethyst Armor Set`, `Amethyst Wings (Elytra)`.
3. **Crate Key (ID 11 – 14):** `Spawner Key (1x)`, `Spawner Key (5x)`, `Red Item Key (1x)`, `Red Item Key (5x)`.
4. **Sharp (ID 15 – 17):** `100 Sharp`, `500 Sharp`, `1500 Sharp`.

###  Mã giảm giá & Đơn hàng mẫu
* **Mã giảm giá (`coupons`):**
  * `TANTHU20`: Giảm $20\%$ (tối đa $50.000\text{đ}$) cho đơn từ $50.000\text{đ}$.
  * `GIAM30K`: Giảm trực tiếp $30.000\text{đ}$ cho đơn từ $150.000\text{đ}$.
  * `NAGAMC50K`: Giảm trực tiếp $50.000\text{đ}$ cho đơn từ $300.000\text{đ}$.
* **Đơn hàng mẫu (`orders`):**
  * `DH1001` (của `player01`): Tổng $248.000\text{đ}$, áp mã `GIAM30K` còn **$218.000\text{đ}$** — Trạng thái: `paid` / `completed` (đã có biên lai `FT26271889900` trong bảng `transactions`).
  * `DH1002` (của `player01`): Tổng **$499.000\text{đ}$** — Trạng thái: `pending` (dùng để test quét mã VietQR và Webhook thanh toán).

---

##  4. Hướng dẫn cài đặt & Khởi tạo Database

### Cách 1: Kết nối vào Database dùng chung trên Cloud (Aiven MySQL)
Nhóm đã triển khai sẵn MySQL 8.0 trên **Aiven Cloud** để cả 4 thành viên dùng chung dữ liệu thời gian thực:
1. Nhận thông số `DB_HOST`, `DB_PORT`, `DB_USER` (`avnadmin`), `DB_PASS` từ nhóm trưởng.
2. Điền thông số vào file `config/database.php` (hoặc biến môi trường `.env`).
3. **Lưu ý quan trọng khi chạy lệnh SQL trên Aiven:** Do Aiven bật mặc định chế độ `ANSI`, luôn đặt dòng sau ở đầu script SQL trước khi chạy `CREATE` hoặc `INSERT`:
   ```sql
   SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';
   USE webshop_db;
   ```

### Cách 2: Khởi tạo trên máy cá nhân (Localhost)
Nếu muốn chạy thử nghiệm độc lập trên MySQL cá nhân (MySQL Workbench / Tab Database của PhpStorm):
1. Chạy toàn bộ file `database/migrations/001_create_webshop_tables.sql` để tạo `webshop_db` và 10 bảng.
2. Chạy tiếp file `database/seeders/001_seed_initial_data.sql` để nạp dữ liệu mẫu.

---

##  5. Yêu cầu cấu hình PHP & Kiểm tra kết nối

### Bật PHP Extensions trong PhpStorm / `php.ini`
Đảm bảo môi trường PHP trên máy đã bật đủ 3 thư viện sau:
* `extension=pdo_mysql` (Kết nối MySQL qua PDO)
* `extension=curl` (Gọi API VietQR và AI Chatbot)
* `extension=openssl` (Hỗ trợ kết nối bảo mật SSL lên Cloud DB & HTTPS API)

### Kiểm tra kết nối nhanh
Chạy file kiểm tra từ Terminal của PhpStorm:
```bash
php public/test_db.php
```
Nếu kết quả trả về JSON có `"status": "success"` và `"tables_count": 10` nghĩa là môi trường đã sẵn sàng.

---

##  6. Quy tắc làm việc nhóm khi thay đổi Database

1. **Không tự ý xóa/sửa tên cột trực tiếp trên Cloud DB** mà không thông báo cho nhóm.
2. Khi cần thêm bảng hoặc thêm cột mới (ví dụ dùng `ALTER TABLE`), hãy tạo một file mới trong thư mục `database/migrations/` theo thứ tự tăng dần:
   * Ví dụ: `database/migrations/002_update_ai_chat_logs.sql`
3. Commit và Push file `.sql` mới lên GitHub để các thành viên khác nắm được lịch sử thay đổi cấu trúc bảng.