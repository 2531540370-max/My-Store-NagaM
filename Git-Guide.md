# Hướng dẫn sử dụng Git cho nhóm 4 người

Tài liệu này áp dụng cho repository:

```
https://github.com/nagacatt/Store-NagaMC
```

Mục tiêu là để 4 thành viên có thể làm việc song song mà không ghi đè hoặc làm hỏng code của nhau.

---

## Mục lục

1. [Git và GitHub là gì?](#1-git-và-github-là-gì)
2. [Cài đặt Git](#2-cài-đặt-git)
3. [Cấu trúc branch cho nhóm](#3-cấu-trúc-branch-cho-nhóm)
4. [Lấy repository về máy](#4-lấy-repository-về-máy)
5. [Quy trình làm việc hằng ngày](#5-quy-trình-làm-việc-hằng-ngày)
6. [Quy trình đầy đủ khi làm một chức năng](#6-quy-trình-đầy-đủ-khi-làm-một-chức-năng)
7. [Cách viết Pull Request](#7-cách-viết-pull-request)
8. [Review Pull Request](#8-review-pull-request)
9. [Cập nhật branch của mình](#9-cập-nhật-branch-của-mình)
10. [Xử lý conflict](#10-xử-lý-conflict)
11. [Các lệnh Git quan trọng](#11-các-lệnh-git-quan-trọng)
12. [Quy tắc commit](#12-quy-tắc-commit)
13. [Không commit các file nhạy cảm](#13-không-commit-các-file-nhạy-cảm)
14. [Quy tắc merge của nhóm](#14-quy-tắc-merge-của-nhóm)
15. [Quy trình làm việc mẫu cho 4 người](#15-quy-trình-làm-việc-mẫu-cho-4-người)
16. [Trường hợp làm nhầm trên main](#16-trường-hợp-làm-nhầm-trên-main)
17. [Trường hợp muốn bỏ toàn bộ thay đổi chưa commit](#17-trường-hợp-muốn-bỏ-toàn-bộ-thay-đổi-chưa-commit)
18. [Trường hợp Git báo branch bị lệch](#18-trường-hợp-git-báo-branch-bị-lệch)
19. [Checklist trước khi tạo Pull Request](#19-checklist-trước-khi-tạo-pull-request)
20. [Checklist trước khi merge](#20-checklist-trước-khi-merge)
21. [Quy trình ngắn gọn cần nhớ](#21-quy-trình-ngắn-gọn-cần-nhớ)

---

## 1. Git và GitHub là gì?

### Git

Git là công cụ quản lý phiên bản trên máy tính cá nhân.

Git giúp nhóm:

- Lưu lịch sử thay đổi.
- Khôi phục code cũ.
- Làm việc trên nhiều nhánh.
- So sánh các thay đổi.
- Xử lý khi nhiều người sửa cùng một file.

### GitHub

GitHub là nơi lưu repository online.

Nhóm dùng GitHub để:

- Lưu code.
- Chia sẻ code.
- Tạo branch.
- Tạo Pull Request.
- Review code.
- Theo dõi Issues và tiến độ.

---

## 2. Cài đặt Git

Tải Git tại:

```
https://git-scm.com/downloads
```

Sau khi cài đặt, mở Terminal, Git Bash hoặc PowerShell và kiểm tra:

```bash
git --version
```

Nếu thấy kết quả tương tự:

```
git version 2.x.x
```

thì Git đã được cài thành công.

### Cấu hình tên và email

Kiểm tra thông tin Git:

```bash
git config --global user.name "Tên của bạn"
git config --global user.email "email-github-cua-ban@example.com"
```

Ví dụ:

```bash
git config --global user.name "Nguyen Van A"
git config --global user.email "nguyenvana@example.com"
```

Email nên là email đã liên kết với tài khoản GitHub.

Kiểm tra lại:

```bash
git config --global user.name
git config --global user.email
```

---

## 3. Cấu trúc branch cho nhóm

Không nên để cả nhóm làm trực tiếp trên branch `main`.

Đề xuất:

```
main
├── feature/frontend
├── feature/backend
├── feature/database
└── feature/admin
```

### Phân công

| Thành viên | Branch đề xuất | Công việc |
|---|---|---|
| Người 1 | `feature/frontend` | Giao diện website |
| Người 2 | `feature/backend` | PHP, đăng nhập, API |
| Người 3 | `feature/database` | MySQL, sản phẩm, đơn hàng |
| Người 4 | `feature/admin` | Trang admin, kiểm thử, deploy |

Khi làm một chức năng nhỏ, có thể tạo branch cụ thể hơn:

```
feature/login
feature/product-list
feature/shopping-cart
feature/admin-products
```

Tên branch nên viết bằng tiếng Anh, chữ thường và dùng dấu gạch ngang.

---

## 4. Lấy repository về máy

Mỗi thành viên mở Terminal tại thư mục muốn lưu dự án và chạy:

```bash
git clone https://github.com/nagacatt/Store-NagaMC.git
```

Di chuyển vào thư mục dự án:

```bash
cd Store-NagaMC
```

Kiểm tra repository đang kết nối tới đâu:

```bash
git remote -v
```

Kết quả thường có dạng:

```
origin  https://github.com/nagacatt/Store-NagaMC.git (fetch)
origin  https://github.com/nagacatt/Store-NagaMC.git (push)
```

Kiểm tra branch hiện tại:

```bash
git branch
```

Nếu đang ở `main`, kết quả sẽ có dấu `*`:

```
* main
```

---

## 5. Quy trình làm việc hằng ngày

Mỗi lần bắt đầu làm việc, hãy cập nhật code mới nhất:

```bash
git checkout main
git pull origin main
```

Sau đó tạo branch riêng cho chức năng:

```bash
git checkout -b feature/login
```

Kiểm tra branch:

```bash
git branch
```

Kết quả:

```
* feature/login
  main
```

Từ thời điểm này, bạn làm việc trên branch `feature/login`, không làm trực tiếp trên `main`.

---

## 6. Quy trình đầy đủ khi làm một chức năng

Ví dụ người 2 làm chức năng đăng nhập.

### Bước 1: Cập nhật `main`

```bash
git checkout main
git pull origin main
```

### Bước 2: Tạo branch

```bash
git checkout -b feature/login
```

### Bước 3: Viết code

Sau khi thay đổi code, kiểm tra trạng thái:

```bash
git status
```

Ví dụ kết quả:

```
modified:   login.php
new file:   app/Controllers/AuthController.php
```

### Bước 4: Xem nội dung thay đổi

```bash
git diff
```

Nếu muốn xem danh sách file thay đổi:

```bash
git diff --stat
```

### Bước 5: Thêm file vào vùng chuẩn bị commit

Thêm toàn bộ file:

```bash
git add .
```

Hoặc thêm từng file:

```bash
git add login.php
git add app/Controllers/AuthController.php
```

Kiểm tra lại:

```bash
git status
```

### Bước 6: Commit

```bash
git commit -m "feat: add user login"
```

### Bước 7: Push branch lên GitHub

```bash
git push -u origin feature/login
```

### Bước 8: Tạo Pull Request

Vào repository trên GitHub:

```
https://github.com/nagacatt/Store-NagaMC
```

GitHub thường hiển thị nút:

```
Compare & pull request
```

Nhấn nút đó và tạo Pull Request vào branch:

```
main
```

---

## 7. Cách viết Pull Request

### Tiêu đề

Tiêu đề nên mô tả ngắn gọn thay đổi:

```
feat: add user login
feat: create products page
fix: repair cart total calculation
docs: update Git guide
```

### Nội dung Pull Request

Mẫu nội dung:

```markdown
## Mô tả

Thêm chức năng đăng nhập người dùng.

## Thay đổi

- Thêm form đăng nhập.
- Kiểm tra email và mật khẩu.
- Lưu user ID vào session.
- Thêm chức năng đăng xuất.

## Cách kiểm thử

- Đăng nhập bằng tài khoản hợp lệ.
- Đăng nhập bằng mật khẩu sai.
- Kiểm tra đăng xuất.
- Kiểm tra truy cập trang cần đăng nhập.

## Checklist

- [ ] Đã tự kiểm tra chức năng
- [ ] Không commit mật khẩu hoặc file .env
- [ ] Đã kiểm tra lỗi PHP
- [ ] Đã cập nhật tài liệu nếu cần
```

---

## 8. Review Pull Request

Trước khi merge, ít nhất một thành viên khác nên kiểm tra:

- Code có dễ hiểu không?
- Có lỗi bảo mật không?
- Có ảnh hưởng chức năng cũ không?
- Có truy vấn SQL không an toàn không?
- Có kiểm tra dữ liệu đầu vào không?
- Có dùng đúng tên biến và tên file không?
- Có code thừa không?
- Giao diện có hoạt động không?

Không nên merge ngay khi chưa kiểm tra.

---

## 9. Cập nhật branch của mình

Trong lúc bạn làm việc, thành viên khác có thể đã merge code vào `main`.

Trước khi tạo Pull Request hoặc trước khi tiếp tục làm, hãy cập nhật branch:

```bash
git checkout main
git pull origin main
```

Quay lại branch của mình:

```bash
git checkout feature/login
```

Đưa thay đổi mới từ `main` vào branch:

```bash
git merge main
```

Nếu không có xung đột, Git sẽ hoàn tất merge.

Sau đó chạy thử dự án:

```bash
git status
```

Nếu mọi thứ ổn, push branch:

```bash
git push origin feature/login
```

---

## 10. Xử lý conflict

Conflict xảy ra khi hai người cùng sửa một phần giống nhau trong cùng file.

Ví dụ Git báo:

```
CONFLICT (content): Merge conflict in login.php
```

Mở file bị lỗi, bạn sẽ thấy:

```php
<<<<<<< HEAD
Code trên branch của bạn
=======
Code trên branch main
>>>>>>> main
```

Bạn cần chọn nội dung đúng, sau đó xóa các dòng đánh dấu:

```
<<<<<<< HEAD
=======
>>>>>>> main
```

Sau khi sửa xong:

```bash
git add login.php
git commit -m "fix: resolve login merge conflict"
git push origin feature/login
```

### Quy trình xử lý conflict đầy đủ

```bash
git status
```

Xem file nào bị conflict.

Mở file và sửa thủ công.

Sau đó:

```bash
git add .
git commit -m "fix: resolve merge conflict"
git push
```

Nếu chưa hiểu phần conflict, không nên tự ý xóa code. Hãy trao đổi với người đã viết phần code đó.

---

## 11. Các lệnh Git quan trọng

### Xem trạng thái

```bash
git status
```

### Xem branch hiện tại

```bash
git branch
```

### Xem tất cả branch trên GitHub

```bash
git branch -a
```

### Chuyển branch

```bash
git checkout main
git checkout feature/login
```

Hoặc dùng cú pháp mới:

```bash
git switch main
git switch feature/login
```

### Tạo branch mới

```bash
git checkout -b feature/new-feature
```

Hoặc:

```bash
git switch -c feature/new-feature
```

### Cập nhật code từ GitHub

```bash
git pull origin main
```

### Thêm file vào commit

```bash
git add .
```

### Commit

```bash
git commit -m "feat: add product list"
```

### Push

```bash
git push
```

Lần đầu push branch mới:

```bash
git push -u origin feature/product-list
```

### Xem lịch sử commit

```bash
git log --oneline
```

Ví dụ:

```
bb63889 docs: add project structure and development guide
f1a287f chore: create empty project directory structure
```

### Xem thay đổi chưa commit

```bash
git diff
```

### Hủy thay đổi trong một file chưa commit

```bash
git restore ten-file.php
```

Cẩn thận: lệnh này sẽ xóa thay đổi chưa commit trong file đó.

### Bỏ file khỏi vùng chuẩn bị commit

```bash
git restore --staged ten-file.php
```

---

## 12. Quy tắc commit

Mỗi commit chỉ nên tập trung vào một thay đổi.

### Tốt

```bash
git commit -m "feat: add user registration"
git commit -m "fix: validate duplicate email"
git commit -m "style: improve product card"
```

### Không nên

```bash
git commit -m "update"
git commit -m "fix everything"
git commit -m "abc"
```

### Quy ước commit

| Prefix | Ý nghĩa |
|---|---|
| `feat` | Thêm chức năng |
| `fix` | Sửa lỗi |
| `style` | Thay đổi giao diện, định dạng |
| `refactor` | Cải thiện code nhưng không đổi chức năng |
| `docs` | Cập nhật tài liệu |
| `test` | Thêm hoặc sửa kiểm thử |
| `chore` | Cấu hình hoặc công việc phụ |

Ví dụ:

```
feat: add product detail page
fix: prevent empty cart checkout
docs: update installation guide
test: add order validation test
chore: update gitignore
```

---

## 13. Không commit các file nhạy cảm

Không được đưa những file sau lên GitHub:

```
.env
database-password.txt
config/secret.php
private-key.pem
backup.sql
```

### Tạo file .gitignore

Tạo file `.gitignore` ở thư mục gốc:

```gitignore
.env
.env.*
!.env.example

/vendor/
/node_modules/

storage/logs/*
storage/cache/*
storage/backups/*

public/uploads/*
!public/uploads/.gitkeep

*.log
*.zip
*.sql

.DS_Store
Thumbs.db
.vscode/
.idea/
```

### Tạo file mẫu

Nếu cần chia sẻ cấu hình, tạo file mẫu:

```
.env.example
```

File này chỉ chứa tên biến, không chứa mật khẩu thật:

```env
DB_HOST=localhost
DB_NAME=store_nagamc
DB_USER=root
DB_PASSWORD=
```

---

## 14. Quy tắc merge của nhóm

Nên thống nhất các quy tắc sau:

1. Không push trực tiếp vào `main`.
2. Mỗi chức năng làm trên một branch riêng.
3. Mỗi Pull Request cần ít nhất một người review.
4. Chạy thử trước khi tạo Pull Request.
5. Không merge khi website đang lỗi.
6. Không commit file `.env`.
7. Không dùng commit message quá chung chung.
8. Không sửa code của người khác nếu chưa trao đổi.
9. Sau khi merge, xóa branch đã hoàn thành.
10. Trước khi bắt đầu task mới, luôn pull `main`.

---

## 15. Quy trình làm việc mẫu cho 4 người

### Ngày đầu tiên

Một người có quyền quản lý repository chuẩn bị branch:

```bash
git checkout main
git pull origin main
```

Các thành viên clone repository:

```bash
git clone https://github.com/nagacatt/Store-NagaMC.git
cd Store-NagaMC
```

### Mỗi người tạo branch riêng

Người 1:

```bash
git checkout -b feature/frontend-homepage
```

Người 2:

```bash
git checkout -b feature/authentication
```

Người 3:

```bash
git checkout -b feature/database-schema
```

Người 4:

```bash
git checkout -b feature/admin-layout
```

### Sau khi làm xong

```bash
git status
git add .
git commit -m "feat: add homepage layout"
git push -u origin feature/frontend-homepage
```

Sau đó tạo Pull Request vào `main`.

---

## 16. Trường hợp làm nhầm trên main

Nếu bạn đã sửa code trên `main` nhưng chưa commit:

```bash
git status
git switch -c feature/my-change
```

Các thay đổi hiện tại sẽ được giữ lại trên branch mới.

Sau đó:

```bash
git add .
git commit -m "feat: add my change"
git push -u origin feature/my-change
```

Nếu đã commit nhầm trên `main`, hãy báo nhóm trước khi dùng các lệnh như `reset`. Không nên tự ý xóa lịch sử chung.

---

## 17. Trường hợp muốn bỏ toàn bộ thay đổi chưa commit

Kiểm tra trước:

```bash
git status
```

Nếu chắc chắn không cần các thay đổi đó:

```bash
git restore .
```

Lệnh này xóa các thay đổi chưa commit trong các file đã tồn tại.

Xóa file mới chưa được Git theo dõi:

```bash
git clean -n
```

Lệnh trên chỉ xem trước file sẽ bị xóa.

Nếu chắc chắn:

```bash
git clean -f
```

**Cẩn thận với các lệnh này vì dữ liệu chưa commit có thể không khôi phục được.**

---

## 18. Trường hợp Git báo branch bị lệch

Nếu Git báo branch hiện tại khác với branch trên GitHub, chạy:

```bash
git status
```

Cập nhật branch:

```bash
git pull --rebase origin main
```

Nếu đang ở branch tính năng:

```bash
git pull --rebase origin feature/login
```

### Xử lý conflict trong rebase

Nếu xảy ra conflict trong quá trình rebase:

```bash
git status
```

Sửa file conflict, sau đó:

```bash
git add .
git rebase --continue
```

Nếu muốn hủy rebase:

```bash
git rebase --abort
```

Người mới nên dùng `merge` trước. Chỉ dùng `rebase` khi cả nhóm đã hiểu rõ cách hoạt động.

---

## 19. Checklist trước khi tạo Pull Request

```
[ ] Đang làm đúng branch
[ ] Đã pull main mới nhất
[ ] Code chạy được trên máy
[ ] Không có lỗi PHP nghiêm trọng
[ ] Không commit file .env
[ ] Không commit mật khẩu
[ ] Đã kiểm tra giao diện
[ ] Đã kiểm tra dữ liệu đầu vào
[ ] Đã kiểm tra quyền truy cập
[ ] Commit message rõ ràng
[ ] Pull Request có mô tả
[ ] Đã chỉ định người review
```

---

## 20. Checklist trước khi merge

Người review cần kiểm tra:

```
[ ] Chức năng đúng với yêu cầu
[ ] Không ảnh hưởng chức năng cũ
[ ] Không có code thừa
[ ] Không có thông tin nhạy cảm
[ ] Query database an toàn
[ ] Có xử lý lỗi
[ ] Có kiểm tra quyền
[ ] Đã chạy thử thành công
```

### Sau khi merge

```bash
git checkout main
git pull origin main
```

Xóa branch local nếu không còn dùng:

```bash
git branch -d feature/login
```

Xóa branch trên GitHub nếu cần:

```bash
git push origin --delete feature/login
```

---

## 21. Quy trình ngắn gọn cần nhớ

Mỗi lần làm chức năng mới:

```bash
git checkout main
git pull origin main
git checkout -b feature/ten-chuc-nang

# Viết code

git status
git add .
git commit -m "feat: mo ta chuc nang"
git push -u origin feature/ten-chuc-nang
```

Sau đó:

```
Tạo Pull Request
        ↓
Thành viên khác review
        ↓
Sửa lỗi nếu có
        ↓
Merge vào main
        ↓
Xóa branch cũ
```

### Công thức quan trọng nhất

```
Pull → Branch → Code → Status → Add → Commit → Push → Pull Request → Review → Merge
```

Chỉ cần cả nhóm tuân thủ đúng quy trình này, repository `Store-NagaMC` sẽ dễ quản lý hơn rất nhiều và giảm đáng kể nguy cơ mất code hoặc ghi đè code của nhau.

---

**Phiên bản:** 1.0  
**Ngày cập nhật:** 20/09/2026  
**Áp dụng cho:** Repository nagacatt/Store-NagaMC
