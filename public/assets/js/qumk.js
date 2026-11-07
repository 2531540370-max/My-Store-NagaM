// Xử lý gửi yêu cầu quên mật khẩu cho trang qumk.html (kết nối backend PHP qua ApiConfig)
(function () {
    'use strict';

    const form = document.getElementById('form-forgot');
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

        const emailInput = document.getElementById('forgot-email');
        const submitBtn = form.querySelector('button[type="submit"]');
        const email = emailInput ? emailInput.value.trim() : '';

        if (!email) {
            showAlert('Vui lòng nhập Email hoặc Tên tài khoản đã đăng ký.', 'error');
            return;
        }

        if (submitBtn) submitBtn.disabled = true;

        try {
            // Lưu email/tên tài khoản vào sessionStorage để trang OTP tự động hiển thị
            sessionStorage.setItem('reset_email', email);

            const payload = {
                username: email,
                email: email
            };

            const result = (typeof ApiConfig !== 'undefined' && ApiConfig.request)
                ? await ApiConfig.request('FORGOT_PASSWORD', { method: 'POST', data: payload })
                : await fetch('api/forgot-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                }).then(r => r.json()).catch(() => null);

            const isSuccess = Boolean(result && (result.success === true || result.status === true));

            if (isSuccess) {
                showAlert((result && result.message) || 'Mã OTP đã được gửi đến email! Đang chuyển tiếp...', 'success');
                setTimeout(() => {
                    window.location.href = 'otp.html';
                }, 1000);
            } else {
                showAlert((result && result.message) || 'Không tìm thấy tài khoản hoặc không thể gửi mã OTP.', 'error');
            }
        } catch (err) {
            console.error('[Forgot Password] Lỗi:', err);
            showAlert('Đã có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại sau.', 'error');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
})();
