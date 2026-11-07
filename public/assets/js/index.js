/**
 * =============================================================================
 * NAGAMC STORE — HOMEPAGE JAVASCRIPT (index.js)
 * Tương tác: Sao chép IP Server, Toast thông báo, Kiểm tra phiên đăng nhập Backend
 * =============================================================================
 */

(function () {
    'use strict';

    // 1. Khởi tạo Toast thông báo tiện ích
    const toast = document.getElementById('app-toast');

    function showToast(message, duration = 2500) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, duration);
    }

    // 2. Chức năng sao chép IP Server (IP: NAGAMC.LIVE)
    const ipWidget = document.getElementById('ip-copy-widget');
    if (ipWidget) {
        ipWidget.addEventListener('click', async () => {
            const ipAddress = 'nagamc.live';
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(ipAddress);
                } else {
                    const tempInput = document.createElement('input');
                    tempInput.value = ipAddress;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                }
                showToast('✓ Đã sao chép IP máy chủ: ' + ipAddress);
            } catch (err) {
                showToast('IP máy chủ: ' + ipAddress);
            }
        });
    }

    // 3. Cập nhật giao diện người dùng dựa trên thông tin phiên
    function renderUserUI(user) {
        const usernameEl = document.getElementById('profile-display-name');
        const actionBtn = document.getElementById('profile-action-btn');
        const logoutBtn = document.getElementById('profile-logout-btn');

        if (user && (user.username || user.email || user.name)) {
            const displayName = user.username || user.ingame_name || user.name || user.email;
            if (usernameEl) usernameEl.textContent = displayName;
            if (actionBtn) {
                actionBtn.textContent = 'Quản lý tài khoản';
                actionBtn.href = 'index.html#account';
            }
            if (logoutBtn) {
                logoutBtn.style.display = 'inline-flex';
                logoutBtn.title = 'Đăng xuất (' + displayName + ')';
            }
        } else {
            if (usernameEl) usernameEl.textContent = 'Khách';
            if (actionBtn) {
                actionBtn.textContent = 'Đăng nhập';
                actionBtn.href = 'login.html';
            }
            if (logoutBtn) {
                logoutBtn.style.display = 'none';
            }
        }
    }

    // 4. Đồng bộ thông tin người dùng từ Backend PHP và localStorage
    async function syncUserData() {
        // Thử đọc từ cache localStorage trước để UI hiển thị tức thì
        let cachedUser = null;
        try {
            const raw = localStorage.getItem('nagamc_user');
            if (raw) cachedUser = JSON.parse(raw);
        } catch (e) {}

        if (cachedUser) {
            renderUserUI(cachedUser);
        } else {
            renderUserUI(null);
        }

        // Xác thực lại với Backend qua session PHP
        if (typeof ApiConfig !== 'undefined' && ApiConfig.request) {
            try {
                const res = await ApiConfig.request('ME');
                if (res && (res.success || res.status) && (res.data || res.user)) {
                    const freshUser = res.data || res.user;
                    localStorage.setItem('nagamc_user', JSON.stringify(freshUser));
                    renderUserUI(freshUser);
                } else if (cachedUser && (!res || (!res.success && !res.status))) {
                    // Phiên Backend đã hết hạn
                    localStorage.removeItem('nagamc_user');
                    renderUserUI(null);
                }
            } catch (err) {
                // Giữ nguyên cachedUser nếu lỗi mạng tạm thời
            }
        }
    }

    // 5. Xử lý Đăng xuất
    const logoutBtn = document.getElementById('profile-logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            if (confirm('Bạn có chắc chắn muốn đăng xuất tài khoản?')) {
                try {
                    if (typeof ApiConfig !== 'undefined' && ApiConfig.request) {
                        await ApiConfig.request('LOGOUT', { method: 'POST' });
                    }
                } catch (err) {}

                localStorage.removeItem('nagamc_user');
                localStorage.removeItem('nagamc_token');
                renderUserUI(null);
                showToast('✓ Bạn đã đăng xuất thành công.');
            }
        });
    }

    // 6. Nút thông báo
    const notifBtn = document.getElementById('notif-btn');
    if (notifBtn) {
        notifBtn.addEventListener('click', () => {
            showToast('Bạn có 36 thông báo mới từ hệ thống!');
        });
    }

    // Chạy khi trang tải xong
    document.addEventListener('DOMContentLoaded', () => {
        syncUserData();
    });

})();
