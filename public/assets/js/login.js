// Xử lý đăng nhập cho trang login.html (sử dụng ApiConfig để kết nối backend PHP)
(function () {
    'use strict';

    const form = document.getElementById('form-login');
    const alertBox = document.getElementById('auth-alert');
    if (!form) return;

    function showAlert(message, type) {
        if (!alertBox) return;
        alertBox.className = 'login-alert alert-' + (type || 'error');
        alertBox.textContent = message;
        alertBox.hidden = false;
    }

    function hideAlert() {
        if (!alertBox) return;
        alertBox.hidden = true;
        alertBox.textContent = '';
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideAlert();

        const usernameInput = document.getElementById('login-username');
        const passwordInput = document.getElementById('login-password');
        const rememberInput = document.getElementById('login-remember');
        const submitBtn = form.querySelector('button[type="submit"]');

        const username = usernameInput ? usernameInput.value.trim() : '';
        const password = passwordInput ? passwordInput.value : '';
        const remember = rememberInput ? rememberInput.checked : true;

        if (!username || !password) {
            showAlert('Vui lòng nhập đầy đủ tên tài khoản/email và mật khẩu.', 'error');
            return;
        }

        if (submitBtn) submitBtn.disabled = true;

        try {
            // Gửi dữ liệu qua ApiConfig helper với credentials tự động
            const payload = {
                username: username,
                login: username,
                email: username,
                password: password,
                remember: remember
            };

            const result = (typeof ApiConfig !== 'undefined' && ApiConfig.request)
                ? await ApiConfig.request('LOGIN', { method: 'POST', data: payload })
                : await fetch('api/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                }).then(r => r.json()).catch(() => null);

            const isSuccess = Boolean(result && (result.success === true || result.status === true));

            if (isSuccess) {
                showAlert('Đăng nhập thành công! Đang chuyển hướng về trang chủ...', 'success');
                const userData = result.data || result.user;
                if (userData) {
                    localStorage.setItem('nagamc_user', JSON.stringify(userData));
                }
                if (result.token) {
                    localStorage.setItem('nagamc_token', result.token);
                }
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 1000);
            } else {
                const errorMsg = (result && result.message) || 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin.';
                showAlert(errorMsg, 'error');
            }
        } catch (err) {
            console.error('[Login] Lỗi:', err);
            showAlert('Đã có lỗi xảy ra khi kết nối đến máy chủ. Vui lòng thử lại sau.', 'error');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
})();
