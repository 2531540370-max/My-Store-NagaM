# Store-NagaMC

Website cửa hàng vật phẩm trong game Minecraft của NagaMC.

Dự án được xây dựng theo hướng PHP + MySQL, có thể mở rộng thêm hệ thống đăng nhập, bán vật phẩm, quản lý đơn hàng, trang quản trị và kết nối với máy chủ Minecraft.

## Mục tiêu dự án

- Hiển thị các vật phẩm và gói dịch vụ trong game.
- Cho phép người dùng đăng ký, đăng nhập và quản lý tài khoản.
- Hỗ trợ mua vật phẩm, tạo đơn hàng và theo dõi lịch sử giao dịch.
- Cung cấp trang quản trị cho quản lý người dùng, sản phẩm, đơn hàng và thanh toán.
- Có khả năng kết nối với máy chủ Minecraft để cấp vật phẩm sau khi thanh toán thành công.

## Cấu trúc thư mục

```text
Store-NagaMC/
│
├── app/
│   ├── Controllers/     # Tiếp nhận request và điều phối nghiệp vụ.
│   ├── Models/          # Đại diện cho dữ liệu và thao tác với database.
│   ├── Services/        # Chứa các nghiệp vụ phức tạp của hệ thống.
│   ├── Middleware/      # Kiểm tra đăng nhập, phân quyền và bảo mật request.
│   └── Helpers/         # Các hàm hỗ trợ dùng chung.
│
├── config/              # Cấu hình ứng dụng, database và dịch vụ bên ngoài.
│
├── database/
│   ├── migrations/      # Các file tạo hoặc thay đổi cấu trúc database.
│   └── seeders/         # Dữ liệu mẫu dùng cho môi trường phát triển.
│
├── public/
│   ├── assets/
│   │   ├── css/         # Các file giao diện CSS.
│   │   ├── js/          # Các file JavaScript phía trình duyệt.
│   │   └── images/      # Logo, banner và hình ảnh sản phẩm.
│   └── uploads/
│       └── products/    # Hình ảnh sản phẩm do admin tải lên.
│
├── resources/
│   └── views/
│       ├── layouts/     # Layout dùng chung cho các trang.
│       ├── components/  # Thành phần giao diện dùng lại nhiều nơi.
│       ├── home/        # Giao diện trang chủ.
│       ├── auth/        # Giao diện đăng nhập, đăng ký và khôi phục mật khẩu.
│       ├── shop/        # Giao diện cửa hàng, giỏ hàng và thanh toán.
│       ├── account/     # Giao diện tài khoản và lịch sử đơn hàng.
│       └── admin/       # Giao diện khu vực quản trị.
│           ├── users/       # Quản lý người dùng.
│           ├── products/    # Quản lý sản phẩm.
│           ├── categories/  # Quản lý danh mục.
│           ├── orders/      # Quản lý đơn hàng.
│           └── payments/    # Quản lý giao dịch và thanh toán.
│
├── routes/              # Khai báo các route của website và API.
│
├── storage/
│   ├── logs/            # Log lỗi và hoạt động của hệ thống.
│   ├── cache/           # Dữ liệu cache tạm thời.
│   └── backups/         # File backup database hoặc dữ liệu quan trọng.
│
└── tests/               # Các bài kiểm thử tự động.
```

## Mô tả chi tiết

### `app/`

Đây là khu vực chứa phần xử lý chính của ứng dụng.

- `Controllers/`: nhận yêu cầu từ người dùng, gọi service/model phù hợp và trả về kết quả.
- `Models/`: xử lý dữ liệu như người dùng, sản phẩm, danh mục, đơn hàng và thanh toán.
- `Services/`: xử lý các luồng nghiệp vụ như đăng nhập, tạo đơn hàng, thanh toán và cấp vật phẩm Minecraft.
- `Middleware/`: bảo vệ route bằng cách kiểm tra session, quyền admin, CSRF hoặc các điều kiện khác.
- `Helpers/`: chứa các hàm dùng chung như định dạng tiền tệ, kiểm tra dữ liệu và tạo response.

### `config/`

Chứa các cấu hình của ứng dụng. Thông tin nhạy cảm như mật khẩu database, khóa API và secret thanh toán nên được lưu bằng biến môi trường, không commit trực tiếp lên GitHub.

### `database/`

Quản lý cấu trúc và dữ liệu khởi tạo của MySQL.

- `migrations/`: lưu lịch sử thay đổi bảng và cột trong database.
- `seeders/`: chứa dữ liệu mẫu như danh mục, sản phẩm mẫu hoặc tài khoản quản trị trong môi trường phát triển.

### `public/`

Đây là khu vực có thể được web server truy cập trực tiếp.

- `assets/css/`: CSS của website.
- `assets/js/`: JavaScript chạy trên trình duyệt.
- `assets/images/`: hình ảnh tĩnh của website.
- `uploads/products/`: hình ảnh sản phẩm được tải lên. Khi deploy cần kiểm soát định dạng file và quyền ghi thư mục.

### `resources/views/`

Chứa các giao diện PHP hiển thị cho người dùng.

- `layouts/`: khung giao diện chung như header, footer và layout admin.
- `components/`: các thành phần tái sử dụng như navbar, thông báo, phân trang và thẻ sản phẩm.
- `home/`: trang chủ.
- `auth/`: các trang xác thực tài khoản.
- `shop/`: các trang cửa hàng và thanh toán.
- `account/`: các trang thông tin tài khoản.
- `admin/`: toàn bộ giao diện quản trị.

### `routes/`

Khai báo đường dẫn của website và API. Có thể tách riêng route giao diện người dùng, API và khu vực admin để dễ phân quyền và bảo trì.

### `storage/`

Lưu các dữ liệu phát sinh trong quá trình chạy hệ thống. Không nên cho phép người dùng truy cập trực tiếp vào thư mục này từ trình duyệt.

### `tests/`

Chứa các bài kiểm thử cho chức năng đăng nhập, sản phẩm, đơn hàng, thanh toán và phân quyền.

## Quy tắc phát triển

- Không lưu mật khẩu dạng văn bản thuần.
- Sử dụng `password_hash()` và `password_verify()` cho mật khẩu.
- Sử dụng PDO và prepared statements khi truy vấn MySQL.
- Kiểm tra quyền ở backend, không chỉ ẩn nút trên giao diện.
- Không tin giá sản phẩm do frontend gửi lên; backend phải tự lấy giá từ database.
- Không commit file `.env`, mật khẩu database hoặc khóa API.
- Mọi thay đổi lớn nên được thực hiện trên branch riêng và tạo Pull Request.

## Luồng mua vật phẩm dự kiến

```text
Người dùng đăng nhập
        ↓
Chọn vật phẩm và nhập tên Minecraft
        ↓
Tạo đơn hàng
        ↓
Thanh toán
        ↓
Backend xác minh thanh toán
        ↓
Đơn hàng chuyển sang PAID
        ↓
Tạo lệnh cấp vật phẩm
        ↓
Plugin Minecraft nhận và thực thi lệnh
        ↓
Website cập nhật đơn hàng thành COMPLETED
```

## Trạng thái đơn hàng đề xuất

```text
PENDING      # Đang chờ thanh toán hoặc xử lý.
PAID         # Đã xác nhận thanh toán.
PROCESSING   # Đang cấp vật phẩm.
COMPLETED    # Đã cấp vật phẩm thành công.
FAILED       # Cấp vật phẩm hoặc thanh toán thất bại.
CANCELLED    # Đơn hàng đã bị hủy.
```

## Phân công phát triển

- Frontend: giao diện người dùng, cửa hàng, giỏ hàng và thanh toán.
- Backend: xác thực, API, nghiệp vụ tài khoản và đơn hàng.
- Database/Shop: thiết kế MySQL, sản phẩm, thanh toán và cấp vật phẩm.
- Admin/Deploy/QA: trang quản trị, triển khai, backup và kiểm thử.

## Trạng thái hiện tại

Repository hiện mới có cấu trúc thư mục nền tảng và các file `.gitkeep` để Git lưu các thư mục rỗng. Các chức năng PHP, MySQL, đăng nhập, thanh toán và admin sẽ được phát triển ở các bước tiếp theo.
