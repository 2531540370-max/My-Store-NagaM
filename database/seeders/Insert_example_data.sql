USE defaultdb;

-- =========================================================================
-- 0. LÀM SẠCH DỮ LIỆU CŨ & RESET ID VỀ 1 (Tránh lỗi trùng khóa khi chạy lại)
-- =========================================================================
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE transactions;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE cart_items;
TRUNCATE TABLE ai_chat_logs;
TRUNCATE TABLE news;
TRUNCATE TABLE coupons;
TRUNCATE TABLE products;
TRUNCATE TABLE categories;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================================
-- 1. TÀI KHOẢN ADMIN & USER MẪU (Mật khẩu chung: 123456)
-- =========================================================================
INSERT INTO users (id, username, email, password_hash, full_name, ingame_name, role, status) VALUES
(1, 'admin', 'admin@nagamc.live', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe16/4m1H/fT8A1/mQpI6G.v4G/w2l/S.', 'Quản Trị Viên NagaMC', 'NagaAdmin', 'admin', 'active'),
(2, 'player01', 'player01@gmail.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe16/4m1H/fT8A1/mQpI6G.v4G/w2l/S.', 'Nguyễn Văn A', 'DragonSlayer', 'user', 'active'),
(3, 'player02', 'player02@gmail.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe16/4m1H/fT8A1/mQpI6G.v4G/w2l/S.', 'Trần Minh B', 'ShadowKnight', 'user', 'active');

-- =========================================================================
-- 2. DANH MỤC VẬT PHẨM (4 Danh mục: Rank, Vật phẩm hỗ trợ, Crate Key, Sharp)
-- =========================================================================
INSERT INTO categories (id, name, slug, description, display_order, is_active) VALUES
(1, 'Rank', 'rank', 'Các gói danh hiệu VIP, Plus và MVP mở khóa đặc quyền máy chủ trong 30 ngày.', 1, 1),
(2, 'Vật phẩm hỗ trợ', 'vat-pham-ho-tro', 'Bộ trang bị và công cụ Thạch Anh Tím (Amethyst) hỗ trợ khai thác, chiến đấu.', 2, 1),
(3, 'Crate Key', 'crate-key', 'Chìa khóa mở rương Spawner và Red Item nhận vật phẩm quý hiếm.', 3, 1),
(4, 'Sharp', 'sharp', 'Đơn vị tiền tệ cao cấp dùng để giao dịch và nâng cấp trong máy chủ NagaMC.', 4, 1);

-- =========================================================================
-- 3. DANH SÁCH 17 VẬT PHẨM THEO TỪNG DANH MỤC
-- =========================================================================
INSERT INTO products (
    id, category_id, name, slug, short_description, description, 
    price, sale_price, image_url, stock, sold_count, 
    delivery_command, ai_context_tags, attributes, is_featured, is_active
) VALUES
-- --- DANH MỤC 1: RANK (ID 1 -> 4) ---
(1, 1, 'VIP (30 Ngày)', 'vip-30-ngay', 
 'Gói Rank khởi đầu cho tân thủ với quyền lợi sethome x3 và kit VIP mỗi ngày.', 
 '<p><strong>Đặc quyền gói VIP (30 Ngày):</strong></p><ul><li>Danh hiệu [VIP] nổi bật trên khung chat.</li><li>Tối đa 3 điểm /sethome.</li><li>Nhận bộ quà /kit vip mỗi 24 giờ.</li><li>Truy cập khu vực chợ VIP.</li></ul>', 
 50000.00, NULL, '/assets/images/products/rank-vip.png', 999, 45, 
 'lp user {player} parent addtemp vip 30d', 
 'rank, vip, 30 ngày, tân thủ, người mới, giá rẻ, 50k, dưới 100k', 
 '{"duration": "30 ngày", "sethome": 3, "kit": "VIP"}', 1, 1),

(2, 1, 'VIP+ (30 Ngày)', 'vip-plus-30-ngay', 
 'Nâng cấp đặc quyền với lệnh /workbench, /hat và x1.2 kinh nghiệm.', 
 '<p><strong>Đặc quyền gói VIP+ (30 Ngày):</strong></p><ul><li>Danh hiệu [VIP+] rực rỡ.</li><li>Tối đa 5 điểm /sethome.</li><li>Mở bàn chế tạo di động (/workbench).</li><li>Tăng 20% EXP khi cày cấp.</li></ul>', 
 100000.00, 89000.00, '/assets/images/products/rank-vip-plus.png', 999, 38, 
 'lp user {player} parent addtemp vip_plus 30d', 
 'rank, vip+, vip plus, 30 ngày, cày cấp, tầm trung, dưới 100k', 
 '{"duration": "30 ngày", "sethome": 5, "exp_boost": "1.2x"}', 1, 1),

(3, 1, 'Plus (30 Ngày)', 'plus-30-ngay', 
 'Mở khóa quyền bay (/fly) tại khu vực đảo/vùng đất cá nhân và x1.5 EXP.', 
 '<p><strong>Đặc quyền gói Plus (30 Ngày):</strong></p><ul><li>Danh hiệu [PLUS] cao cấp.</li><li>Bật/tắt bay (/fly) trong vùng đất bảo hộ.</li><li>Tối đa 8 điểm /sethome và /enderchest di động.</li><li>Nhận /kit plus cực khủng mỗi ngày.</li></ul>', 
 200000.00, 179000.00, '/assets/images/products/rank-plus.png', 999, 25, 
 'lp user {player} parent addtemp plus 30d', 
 'rank, plus, 30 ngày, quyền bay, fly, enderchest, cày nhanh', 
 '{"duration": "30 ngày", "sethome": 8, "fly": true}', 1, 1),

(4, 1, 'MVP (30 Ngày)', 'mvp-30-ngay', 
 'Rank tối thượng máy chủ: giữ đồ khi chết, x2 EXP, hiệu ứng cánh và quyền lợi cao nhất.', 
 '<p><strong>Đặc quyền gói MVP (30 Ngày):</strong></p><ul><li>Danh hiệu [MVP] hoàng kim + Hiệu ứng hạt xung quanh nhân vật.</li><li>Không rơi đồ và kinh nghiệm khi tử trận.</li><li>Tối đa 15 điểm /sethome, /fly, /heal, /feed, /repair.</li><li>Nhân đôi (x2) EXP toàn máy chủ.</li></ul>', 
 350000.00, 299000.00, '/assets/images/products/rank-mvp.png', 999, 60, 
 'lp user {player} parent addtemp mvp 30d', 
 'rank, mvp, 30 ngày, cao nhất, mạnh nhất, giữ đồ, x2 exp, đại gia, vip nhất', 
 '{"duration": "30 ngày", "sethome": 15, "keep_inventory": true, "exp_boost": "2.0x"}', 1, 1),

-- --- DANH MỤC 2: VẬT PHẨM HỖ TRỢ (ID 5 -> 10) ---
(5, 2, 'Amethyst Pickaxe', 'amethyst-pickaxe', 
 'Cúp Thạch Anh Tím đào nhanh 3x3, tự động nung quặng và gia tài cấp cao.', 
 '<p><strong>Chỉ số Amethyst Pickaxe:</strong></p><ul><li>Hiệu suất (Efficiency) VII, Gia tài (Fortune) V, Bền bỉ (Unbreaking) VI.</li><li>Nội tại: Đào 3x3 ô và tự động nung chảy quặng sắt/vàng.</li></ul>', 
 120000.00, 99000.00, '/assets/images/products/amethyst-pickaxe.png', 500, 52, 
 'customitems give {player} amethyst_pickaxe 1', 
 'vật phẩm hỗ trợ, amethyst pickaxe, cúp, đào mỏ, mine, đào 3x3, dưới 100k', 
 '{"tier": "Amethyst", "enchantments": ["Efficiency VII", "Fortune V", "Unbreaking VI"]}', 1, 1),

(6, 2, 'Amethyst Axe', 'amethyst-axe', 
 'Rìu Thạch Anh Tím đốn hạ toàn bộ thân cây chỉ trong 1 nhát chặt (Tree Capitator).', 
 '<p><strong>Chỉ số Amethyst Axe:</strong></p><ul><li>Hiệu suất VII, Sắc bén (Sharpness) VI, Bền bỉ VI.</li><li>Nội tại: Chặt gốc tự động đổ cả cây gỗ ngay lập tức.</li></ul>', 
 100000.00, NULL, '/assets/images/products/amethyst-axe.png', 500, 31, 
 'customitems give {player} amethyst_axe 1', 
 'vật phẩm hỗ trợ, amethyst axe, rìu, chặt cây, khai thác gỗ, vũ khí', 
 '{"tier": "Amethyst", "enchantments": ["Efficiency VII", "Sharpness VI", "TreeCapitator"]}', 0, 1),

(7, 2, 'Amethyst Shovel', 'amethyst-shovel', 
 'Xẻng Thạch Anh Tím san lấp mặt bằng 3x3 tốc độ cao kèm Mềm mại (Silk Touch).', 
 '<p><strong>Chỉ số Amethyst Shovel:</strong></p><ul><li>Hiệu suất VII, Mềm mại (Silk Touch) II, Bền bỉ VI.</li><li>Hỗ trợ đào đất, cát, sỏi diện rộng 3x3 cực nhanh để xây căn cứ.</li></ul>', 
 80000.00, NULL, '/assets/images/products/amethyst-shovel.png', 500, 19, 
 'customitems give {player} amethyst_shovel 1', 
 'vật phẩm hỗ trợ, amethyst shovel, xẻng, đào đất, xây dựng, giá rẻ, dưới 100k', 
 '{"tier": "Amethyst", "enchantments": ["Efficiency VII", "Silk Touch II", "Unbreaking VI"]}', 0, 1),

(8, 2, 'Amethyst Sword', 'amethyst-sword', 
 'Kiếm Thạch Anh Tím sát thương chuẩn, hút máu và tăng tỷ lệ rơi đồ hiếm khi săn Boss.', 
 '<p><strong>Chỉ số Amethyst Sword (Vật phẩm hỗ trợ #4):</strong></p><ul><li>Sắc bén (Sharpness) VII, Nhặt nhạnh (Looting) V, Khía cạnh lửa III.</li><li>Nội tại: Hút 10% máu mỗi đòn đánh, cực mạnh khi đi PvP và săn Boss.</li></ul>', 
 150000.00, 129000.00, '/assets/images/products/amethyst-sword.png', 300, 64, 
 'customitems give {player} amethyst_sword 1', 
 'vật phẩm hỗ trợ, amethyst sword, kiếm, vũ khí, pvp, săn boss, đánh nhau, hút máu', 
 '{"tier": "Amethyst", "enchantments": ["Sharpness VII", "Looting V", "Lifesteal II"]}', 1, 1),

(9, 2, 'Amethyst Armor Set', 'amethyst-armor-set', 
 'Trọn bộ 4 món Giáp Thạch Anh Tím tăng 20 máu tối đa và kháng bật lùi.', 
 '<p><strong>Chỉ số Amethyst Armor Set (Vật phẩm hỗ trợ #5):</strong></p><ul><li>Bao gồm: Mũ, Áo giáp, Quần, Giày Thạch Anh Tím.</li><li>Bảo vệ (Protection) VI, Bền bỉ VI, Sửa chữa (Mending).</li><li>Hiệu ứng mặc đủ bộ: Tăng thêm 10 tim máu và miễn nhiễm sát thương lửa.</li></ul>', 
 250000.00, 199000.00, '/assets/images/products/amethyst-armor.png', 200, 40, 
 'customitems give {player} amethyst_armor_set 1', 
 'vật phẩm hỗ trợ, amethyst armor, giáp, bộ giáp, phòng thủ, tăng máu, pvp, săn boss', 
 '{"tier": "Amethyst", "pieces": 4, "set_bonus": "+10 Hearts & Fire Immunity"}', 1, 1),

(10, 2, 'Amethyst Wings (Elytra)', 'amethyst-wings-elytra', 
 'Cánh cứng Thạch Anh Tím bay lượn siêu bền, tích hợp giáp ngực và tự hồi độ bền.', 
 '<p><strong>Chỉ số Amethyst Wings (Vật phẩm hỗ trợ #6):</strong></p><ul><li>Bền bỉ (Unbreaking) X, Sửa chữa (Mending), Bảo vệ V.</li><li>Cho phép vừa bay lượn khám phá thế giới vừa giữ nguyên chỉ số giáp phòng thủ.</li></ul>', 
 180000.00, 150000.00, '/assets/images/products/amethyst-wings.png', 250, 29, 
 'customitems give {player} amethyst_elytra 1', 
 'vật phẩm hỗ trợ, amethyst wings, elytra, cánh bay, di chuyển, khám phá', 
 '{"tier": "Amethyst", "enchantments": ["Unbreaking X", "Mending", "Protection V"]}', 0, 1),

-- --- DANH MỤC 3: CRATE KEY (ID 11 -> 14) ---
(11, 3, 'Spawner Key (1x)', 'spawner-key-1x', 
 '1 Chìa khóa mở Rương Spawner nhận ngẫu nhiên lồng triệu hồi quái vật giá trị cao.', 
 '<p>Dùng tại khu vực <strong>/warp crates</strong> để mở Rương Spawner (Iron Golem, Blaze, Enderman, Creeper...).</p>', 
 30000.00, NULL, '/assets/images/products/spawner-key-1x.png', 2000, 110, 
 'crate key give {player} spawner 1', 
 'crate key, chìa khóa, mở rương, spawner key, lồng спам quái, farm quái, giá rẻ, dưới 50k', 
 '{"crate_type": "Spawner", "quantity": 1}', 0, 1),

(12, 3, 'Spawner Key (5x)', 'spawner-key-5x', 
 'Combo 5 Chìa khóa Rương Spawner tiết kiệm 20% chi phí, xây dựng máy farm cực nhanh.', 
 '<p>Nhận ngay 5x Spawner Key với mức giá ưu đãi. Tăng cơ hội mở trúng Lồng Iron Golem và Villager hiếm.</p>', 
 150000.00, 120000.00, '/assets/images/products/spawner-key-5x.png', 1000, 75, 
 'crate key give {player} spawner 5', 
 'crate key, chìa khóa, mở rương, spawner key 5x, combo tiết kiệm, xây máy farm', 
 '{"crate_type": "Spawner", "quantity": 5}', 1, 1),

(13, 3, 'Red Item Key (1x)', 'red-item-key-1x', 
 '1 Chìa khóa mở Rương Đỏ (Red Item) chứa vũ khí thần thoại và vật phẩm sự kiện.', 
 '<p>Dùng để mở Rương Red Item tại sảnh chính, cơ hội sở hữu trang bị cấp độ Đỏ (Mythic) cực hiếm.</p>', 
 50000.00, NULL, '/assets/images/products/red-key-1x.png', 2000, 88, 
 'crate key give {player} red_item 1', 
 'crate key, chìa khóa, red item key, mở rương đỏ, đồ thần thoại, nhân phẩm, 50k', 
 '{"crate_type": "Red Item", "quantity": 1}', 0, 1),

(14, 3, 'Red Item Key (5x)', 'red-item-key-5x', 
 'Combo 5 Chìa khóa Rương Đỏ (Red Item) tăng tỷ lệ nổ hũ vật phẩm giới hạn.', 
 '<p>Gói 5 chìa khóa Red Item tiết kiệm 50.000đ. Đảm bảo nhận tối thiểu 1 trang bị hoặc nguyên liệu cấp cao.</p>', 
 250000.00, 200000.00, '/assets/images/products/red-key-5x.png', 1000, 54, 
 'crate key give {player} red_item 5', 
 'crate key, chìa khóa, red item key 5x, combo mở rương, đồ hiếm, đại gia', 
 '{"crate_type": "Red Item", "quantity": 5}', 1, 1),

-- --- DANH MỤC 4: SHARP (ID 15 -> 17) ---
(15, 4, '100 Sharp', '100-sharp', 
 'Nạp ngay 100 Sharp vào tài khoản trong game để mua sắm tại Black Market.', 
 '<p>Cộng trực tiếp <strong>100 Sharp</strong> vào ví nhân vật ngay sau khi thanh toán thành công.</p>', 
 20000.00, NULL, '/assets/images/products/sharp-100.png', 9999, 150, 
 'points give {player} 100', 
 'sharp, tiền tệ, nạp xu, 100 sharp, giá rẻ, 20k, dưới 50k', 
 '{"currency": "Sharp", "amount": 100}', 0, 1),

(16, 4, '500 Sharp', '500-sharp', 
 'Gói 500 Sharp tầm trung tiết kiệm 10% dành cho người chơi nâng cấp kỹ năng.', 
 '<p>Cộng trực tiếp <strong>500 Sharp</strong> vào tài khoản nhân vật trong máy chủ NagaMC.</p>', 
 100000.00, 90000.00, '/assets/images/products/sharp-500.png', 9999, 95, 
 'points give {player} 500', 
 'sharp, tiền tệ, nạp xu, 500 sharp, tiết kiệm, dưới 100k', 
 '{"currency": "Sharp", "amount": 500}', 1, 1),

(17, 4, '1500 Sharp', '1500-sharp', 
 'Gói Đại Gia 1500 Sharp ưu đãi lớn nhất, thỏa sức giao dịch và đấu giá vật phẩm.', 
 '<p>Cộng trực tiếp <strong>1500 Sharp</strong> vào tài khoản nhân vật với mức chiết khấu tốt nhất cửa hàng.</p>', 
 300000.00, 250000.00, '/assets/images/products/sharp-1500.png', 9999, 62, 
 'points give {player} 1500', 
 'sharp, tiền tệ, nạp xu, 1500 sharp, gói lớn, đại gia, khuyến mãi', 
 '{"currency": "Sharp", "amount": 1500}', 1, 1);

-- =========================================================================
-- 4. MÃ GIẢM GIÁ MẪU (COUPONS)
-- =========================================================================
INSERT INTO coupons (id, code, description, discount_type, discount_value, min_order_value, max_discount_amount, usage_limit, used_count, start_date, end_date, is_active) VALUES
(1, 'TANTHU20', 'Giảm 20% (tối đa 50.000đ) cho đơn hàng từ 50.000đ', 'percent', 20.00, 50000.00, 50000.00, 200, 1, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1),
(2, 'GIAM30K', 'Giảm trực tiếp 30.000đ cho đơn hàng từ 150.000đ', 'fixed', 30000.00, 150000.00, NULL, 100, 1, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 1),
(3, 'NAGAMC50K', 'Giảm 50.000đ mừng máy chủ mới cho đơn từ 300.000đ', 'fixed', 50000.00, 300000.00, NULL, 50, 0, NOW(), DATE_ADD(NOW(), INTERVAL 10 DAY), 1);

-- =========================================================================
-- 5. 3 TIN TỨC MẪU (NEWS)
-- =========================================================================
INSERT INTO news (id, author_id, title, slug, summary, content, thumbnail_url, category, views, is_published) VALUES
(1, 1, 'Chính thức khai mở Máy chủ NagaMC Mùa Mới & Ra mắt Bộ Trang Bị Amethyst', 'khai-mo-may-chu-nagamc-mua-moi', 
 'Chào đón toàn thể người chơi đến với mùa giải mới trên NagaMC cùng hệ thống trang bị Thạch Anh Tím độc quyền.', 
 '<p>Máy chủ <strong>NagaMC</strong> chính thức mở cửa chào đón các chiến binh! Trong bản cập nhật lần này, hệ thống ra mắt trọn bộ công cụ và vũ khí <em>Amethyst</em> cùng rương Spawner thế hệ mới.</p>', 
 '/assets/images/news/server-open.jpg', 'server_info', 340, 1),

(2, 1, 'Sự kiện Săn Boss Cuối Tuần: Thu thập Red Item Key & Đua Top Sharp', 'su-kien-san-boss-cuoi-tuan-red-item-key', 
 'Tham gia tiêu diệt Boss Hắc Ám vào 20h00 Thứ Bảy hàng tuần để nhận miễn phí Red Item Key và hàng trăm Sharp.', 
 '<p>Vào lúc 20h00 tối Thứ Bảy và Chủ Nhật, Boss Thế Giới sẽ xuất hiện tại đấu trường trung tâm. Người chơi gây sát thương cao nhất sẽ nhận ngay <strong>Red Item Key (5x)</strong> và <strong>500 Sharp</strong>.</p>', 
 '/assets/images/news/weekend-boss.jpg', 'event', 215, 1),

(3, 1, 'Hướng dẫn nạp tự động qua VietQR 24/7 và Nhận vật phẩm trong 5 giây', 'huong-dan-nap-tu-dong-vietqr-24-7', 
 'Hệ thống Web Shop NagaMC tích hợp đối soát thanh toán tự động VietQR, trao thẳng Rank và Vật phẩm vào nhân vật trong game.', 
 '<p>Chỉ cần chọn vật phẩm, nhập đúng tên nhân vật (Ingame Name) và quét mã QR ngân hàng, hệ thống sẽ tự động đối soát giao dịch và gửi vật phẩm vào kho đồ của bạn sau 5 giây mà không cần chờ Admin duyệt.</p>', 
 '/assets/images/news/payment-guide.jpg', 'game_news', 180, 1);

-- =========================================================================
-- 6. 2 ĐƠN HÀNG MẪU (1 ĐƠN HOÀN TẤT, 1 ĐƠN ĐANG CHỜ THANH TOÁN)
-- =========================================================================
-- Đơn 1 (DH1001): Mua VIP+ (89k) + Amethyst Pickaxe (99k) + 2x Spawner Key 1x (60k) = 248.000đ
-- Áp mã GIAM30K (coupon_id = 2) giảm 30.000đ -> Còn phải thanh toán: 218.000đ (Đã hoàn tất)
-- Đơn 2 (DH1002): Mua MVP (299k) + Red Item Key 5x (200k) = 499.000đ (Đang chờ thanh toán)
INSERT INTO orders (
    id, order_code, user_id, ingame_name, 
    total_amount, discount_amount, final_amount, coupon_id, 
    payment_method, payment_status, order_status, paid_at
) VALUES
(1, 'DH1001', 2, 'DragonSlayer', 248000.00, 30000.00, 218000.00, 2, 'vietqr', 'paid', 'completed', NOW()),
(2, 'DH1002', 2, 'DragonSlayer', 499000.00, 0.00, 499000.00, NULL, 'vietqr', 'pending', 'pending', NULL);

-- =========================================================================
-- 7. CHI TIẾT CÁC VẬT PHẨM TRONG 2 ĐƠN HÀNG MẪU (ORDER_ITEMS)
-- =========================================================================
INSERT INTO order_items (id, order_id, product_id, product_name, quantity, unit_price, subtotal) VALUES
-- Chi tiết của Đơn 1 (order_id = 1, tổng tiền hàng = 248.000đ)
(1, 1, 2,  'VIP+ (30 Ngày)',    1, 89000.00,  89000.00),
(2, 1, 5,  'Amethyst Pickaxe',  1, 99000.00,  99000.00),
(3, 1, 11, 'Spawner Key (1x)',  2, 30000.00,  60000.00),

-- Chi tiết của Đơn 2 (order_id = 2, tổng tiền hàng = 499.000đ)
(4, 2, 4,  'MVP (30 Ngày)',     1, 299000.00, 299000.00),
(5, 2, 14, 'Red Item Key (5x)', 1, 200000.00, 200000.00);

-- =========================================================================
-- 8. BIÊN LAI GIAO DỊCH NGÂN HÀNG CHO ĐƠN ĐÃ THANH TOÁN (TRANSACTIONS)
-- =========================================================================
INSERT INTO transactions (
    id, order_id, user_id, transaction_code, payment_gateway, 
    amount, transfer_content, status, raw_webhook_data
) VALUES
(1, 1, 2, 'FT26271889900', 'VietQR', 218000.00, 'NAP DH1001', 'success', 
 '{"gateway": "VietQR", "bank": "MBBank", "accountNumber": "0123456789", "amount": 218000, "content": "NAP DH1001", "referenceCode": "FT26271889900"}');