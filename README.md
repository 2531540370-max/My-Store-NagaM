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

## Hướng dẫn chỉnh sửa và chú thích chức năng code

### Các file đang xử lý chức năng chính

Các thư mục trong sơ đồ phía trên mô tả hướng tổ chức dự kiến; một số thư mục hiện mới có `.gitkeep` để giữ chỗ. Những file dưới đây đang chứa các chức năng có thể chạy trong bản hiện tại:

| File | Chức năng |
|---|---|
| `config/database.php` | Tạo và tái sử dụng kết nối MySQL bằng PDO. Sửa host, tên database và tài khoản tại đây khi cấu hình môi trường chạy. |
| `resources/views/auth/login.php` | Hiển thị và xử lý đăng nhập; tạo tài khoản demo khi bảng `users` chưa có dữ liệu; cập nhật IP, vị trí, thời gian đăng nhập và session. |
| `resources/views/auth/logout.php` | Xóa dữ liệu session và chuyển người dùng về trang đăng nhập. |
| `app/Middleware/AdminMiddleware.php` | Kiểm tra đăng nhập, quyền Admin và tạo/xác minh CSRF token cho thao tác quản trị. |
| `app/Helpers/IpHelper.php` | Lấy IP người dùng và tra cứu vị trí qua dịch vụ bên ngoài. |
| `app/Services/SystemLogger.php` | Ghi sự kiện đăng nhập và thao tác ứng dụng vào bảng `system_logs`; IP nhật ký lấy từ kết nối trực tiếp, không tin các header chuyển tiếp do client gửi. |
| `resources/views/admin/index.php` | Trang quản trị: thống kê, coupon, đơn hàng, tài khoản, tin tức và trạng thái máy chủ. Quản lý sản phẩm/danh mục không nằm trong phạm vi trang admin hiện tại. |
| `database/migrations/database.sql` | Tạo database, các bảng và dữ liệu mẫu cho bản demo. Sao lưu database trước khi thay đổi hoặc chạy lại SQL. |
| `public/test_db.php` | Endpoint kiểm tra kết nối và trả về tên bảng cùng một số sản phẩm mẫu; chỉ dùng khi phát triển cục bộ, không để công khai khi triển khai thật. |

### Quy trình sửa chức năng

1. Xác định trang hoặc chức năng cần sửa, rồi lần theo file ở bảng trên. Giao diện đăng nhập nằm trong `resources/views/auth/login.php`; các mục quản trị hiện được xử lý chung trong `resources/views/admin/index.php`.
2. Với thay đổi dữ liệu, kiểm tra các cột và kiểu dữ liệu trong `database/migrations/database.sql`. Nếu cần đổi cấu trúc, tạo migration thay đổi riêng và sao lưu trước; không xóa hoặc tạo lại bảng có dữ liệu đang dùng.
3. Khi thêm thao tác quản trị, cập nhật phần xử lý POST và giao diện tương ứng trong trang admin. Giữ kiểm tra quyền Admin và CSRF; dùng câu lệnh PDO `prepare()`/`execute()` cho dữ liệu đầu vào.
4. Khi hiển thị dữ liệu do người dùng nhập, escape nội dung bằng `htmlspecialchars()`. Không tin giá, quyền hoặc trạng thái do trình duyệt gửi lên; kiểm tra và quyết định ở phía máy chủ.
5. Chạy thử trên database local: đăng nhập, kiểm tra cả thao tác thành công và dữ liệu sai/thiếu, sau đó xác nhận dữ liệu trong phpMyAdmin. Kiểm tra thêm tài khoản thường không truy cập được trang admin.

### Cách viết chú thích trong code

Chú thích nên giải thích **lý do hoặc quy tắc nghiệp vụ** của đoạn code, không cần diễn giải lại từng dòng. Đặt chú thích ngay trước đoạn liên quan và cập nhật/xóa chú thích khi logic thay đổi.

```php
// Đổi session ID sau khi xác thực để giảm nguy cơ session fixation.
session_regenerate_id(true);
```

```sql
-- Lưu trạng thái khóa tài khoản để backend từ chối đăng nhập.
status ENUM('active', 'banned') DEFAULT 'active'
```

Trong PHP dùng `//` cho chú thích ngắn và `/* ... */` cho chú thích nhiều dòng; trong SQL dùng `--`; trong HTML dùng `<!-- ... -->`; trong CSS và JavaScript dùng `/* ... */` hoặc `//` (JavaScript). Không ghi mật khẩu, khóa API, token hoặc dữ liệu cá nhân thật vào chú thích hay mã nguồn.

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

## Hướng dẫn cài đặt và sử dụng cho bạn bè

### Yêu cầu

- PHP 8 trở lên, bật extension `pdo_mysql`.
- MySQL hoặc MariaDB.
- Apache (có thể dùng XAMPP) và trình duyệt web.
- Kết nối Internet để tải Bootstrap từ CDN khi mở giao diện.

### Chạy dự án trên máy cá nhân

1. Tải hoặc clone thư mục dự án vào `htdocs` của XAMPP, ví dụ `C:\xampp\htdocs\Store-NagaMC`.
2. Mở XAMPP Control Panel và khởi động **Apache** cùng **MySQL**.
3. Mở phpMyAdmin tại `http://localhost/phpmyadmin`, chọn **Import**, rồi nhập file `database/migrations/database.sql`. File này tạo database `store_nagamc`, các bảng và dữ liệu minh họa. Dùng file này khi tạo database demo mới.
   Nếu đã có database từ phiên bản trước, không import lại toàn bộ file trên vì có thể tạo trùng dữ liệu mẫu. Thay vào đó, chọn tab **Import** trong phpMyAdmin và nhập `database/migrations/002_create_system_logs.sql` để thêm bảng nhật ký giám sát.
4. Kiểm tra thông tin kết nối trong `config/database.php`. Mặc định dự án dùng `127.0.0.1:3306`, database `store_nagamc`, user `root` và mật khẩu trống theo cấu hình XAMPP phổ biến. Nếu MySQL trong XAMPP dùng cổng hoặc mật khẩu khác, cập nhật `$port`, `$username` và `$password` trong file này.
5. Mở trang đăng nhập. Với thư mục dự án hiện tại, dùng `http://localhost/Store-NagaMC%20b%E1%BA%A3n%20c%E1%BA%ADp%20nh%E1%BA%ADt%202/Store-NagaMC/resources/views/auth/login.php`. Nếu bạn đặt dự án trực tiếp ở `htdocs/Store-NagaMC`, dùng `http://localhost/Store-NagaMC/resources/views/auth/login.php`.
6. Đăng nhập tài khoản demo quản trị: **admin** / **123456**. Khi bảng `users` còn trống, trang đăng nhập cũng có thể tự tạo tài khoản demo này.

### Dùng trang quản trị

#### Truy cập

1. Mở XAMPP Control Panel và khởi động **Apache** cùng **MySQL**.
2. Với thư mục dự án hiện tại, truy cập `http://localhost/Store-NagaMC%20b%E1%BA%A3n%20c%E1%BA%ADp%20nh%E1%BA%ADt%202/Store-NagaMC/resources/views/auth/login.php`. Nếu bạn chuyển dự án, thay phần đường dẫn sau `localhost/` theo vị trí mới bên trong `htdocs`.
3. Đăng nhập bằng tài khoản có quyền `admin`. Trong database demo mới, tài khoản mẫu `admin` / `123456` chỉ được tạo khi bảng `users` chưa có tài khoản nào; nếu bảng đã có dữ liệu, dùng tài khoản Admin hiện có.
4. Đăng nhập thành công sẽ chuyển tới trang Admin. Dùng menu bên trái để mở từng mục; chọn **Đăng xuất** để kết thúc phiên.

Tài khoản thường không có quyền truy cập Admin. Không dùng tài khoản/mật khẩu mẫu trên website công khai.

#### Chức năng từng mục

- **Bảng điều khiển:** các thẻ thống kê hiển thị tổng người chơi, vật phẩm, đơn hàng và doanh thu của đơn `PAID`/`COMPLETED`. Bảng đăng nhập và nhật ký ứng dụng hiển thị các sự kiện gần đây, tự tải lại mỗi 10 giây khi dashboard đang mở. Đây là nhật ký ứng dụng, không phải log Apache/PHP.
- **Mã giảm giá:** tạo coupon bằng mã, phần trăm giảm, số lượt tối đa và ngày hết hạn; bảng bên cạnh hiển thị lượt dùng, hạn và trạng thái. Xóa coupon bằng nút **Xóa**. Coupon quá hạn được chuyển sang trạng thái hết hạn khi trang Admin được tải sau ngày hết hạn; đây không phải tác vụ nền chạy liên tục. Bản demo chưa có luồng thanh toán để áp dụng/kiểm chứng coupon tại checkout.
- **Đơn hàng:** xem mã đơn, nhân vật Minecraft, số tiền, thời gian và trạng thái. Chọn trạng thái mới rồi bấm **Lưu** ở đúng hàng để cập nhật. **Tạo Đơn Hàng Mẫu Để Test** chỉ tạo dữ liệu thử, không phải giao dịch thật.
- **Tài khoản người dùng:** xem tên/email, tên nhân vật, IP/vị trí đăng nhập gần nhất, quyền và trạng thái. **Khóa/Mở khóa** chặn hoặc cho phép đăng nhập; **Đổi Quyền** chuyển đổi User/Admin. Chỉ cấp quyền Admin cho người được tin cậy; không tự hạ quyền tài khoản Admin cuối cùng.
- **Tin tức & máy chủ:** tạo thông báo bằng tiêu đề, nội dung và trạng thái máy chủ `ONLINE`, `MAINTENANCE` hoặc `OFFLINE`. Dùng **Xóa bài** để xóa thông báo.
- **Hướng dẫn sử dụng:** xem nhanh cách truy cập, công dụng các mục và cách chỉnh sửa dữ liệu hoặc mã nguồn ngay trong trang Admin.

#### Chỉnh sửa dữ liệu và chức năng

Trong giao diện hiện tại, đơn hàng có thể đổi trạng thái; tài khoản có thể khóa/mở khóa và đổi quyền. Coupon và tin tức chưa có chức năng sửa bản ghi: muốn đổi coupon thì xóa rồi tạo lại; muốn sửa tin thì lưu nội dung cần giữ, xóa bài cũ và đăng lại. Không nhập SQL trực tiếp nếu thao tác tương ứng đã có trên giao diện.

Để thay đổi mã nguồn bằng VS Code:

1. Mở thư mục gốc `Store-NagaMC` trong VS Code và sao lưu file trước khi sửa.
2. Sửa các trang, tab, nội dung và xử lý quản trị trong `resources/views/admin/index.php`.
3. Sửa địa chỉ máy chủ, cổng, tên database, username hoặc password MySQL trong `config/database.php`; cấu hình ở đây phải khớp với XAMPP.
4. Sửa cấu trúc database bằng migration SQL trong `database/migrations/`. Sao lưu database từ phpMyAdmin trước khi chạy migration; không import lại file khởi tạo đầy đủ lên database đang có dữ liệu nếu chưa hiểu tác động.
5. Lưu file, tải lại trang và thử thao tác trên database phát triển. Nếu thay đổi database, xác nhận bảng/cột và dữ liệu trong phpMyAdmin.

Nếu cần sửa dữ liệu trực tiếp trong phpMyAdmin, mở `http://localhost/phpmyadmin`, chọn database `store_nagamc`, chọn đúng bảng (`coupons`, `orders`, `users` hoặc `news`) rồi dùng **Browse/Edit**. Sao lưu database trước; chỉnh sai giá trị có thể làm hỏng đăng nhập hoặc trang quản trị. Mọi thay đổi quyền vẫn phải tuân theo nguyên tắc chỉ cấp Admin cho người tin cậy.

> Đây là bản demo tập trung vào đăng nhập và trang quản trị; các trang cửa hàng, đăng ký người chơi, thanh toán và kết nối cấp vật phẩm Minecraft chưa hoàn thiện. Tài khoản/mật khẩu mặc định chỉ dùng để chạy thử trên máy cá nhân. Đổi mật khẩu và cấu hình database trước khi chia sẻ máy chủ hoặc đưa ứng dụng lên Internet; không dùng dữ liệu thật trong bản demo.

> Nhật ký dashboard chỉ ghi sự kiện của ứng dụng (đăng nhập và thao tác admin), không đọc log Apache/PHP và không thay thế hệ thống IDS/SIEM. IP được ghi là địa chỉ kết nối trực tiếp tới web server; nếu triển khai sau reverse proxy, cần cấu hình proxy tin cậy trước khi dùng IP này để điều tra.

### Chia sẻ để bạn bè chạy thử

Mỗi người có thể làm theo các bước trên máy của mình. Để nhiều người truy cập cùng một máy chủ, cần triển khai web server và database trên máy chủ có thể truy cập được; không nên mở website ra Internet với tài khoản demo hoặc cấu hình mặc định.

## Trạng thái hiện tại

Dự án hiện có đăng nhập demo, phân quyền trang quản trị, thống kê cơ bản và các màn quản lý coupon, đơn hàng, tài khoản, tin tức và trạng thái máy chủ. Phần quản lý sản phẩm/danh mục do thành viên khác phụ trách; cửa hàng dành cho người chơi, thanh toán và tích hợp Minecraft vẫn là các phần cần phát triển tiếp.
