/**
 * =============================================================================
 * NAGAMC STORE — TẠO LẠI MẬT KHẨU (nlmk.js)
 * Tương tác: Xác thực mật khẩu mới, kiểm tra khớp mật khẩu, kết nối Backend PHP qua ApiConfig
 * =============================================================================
 */

(function () {
    'use strict';

    const form = document.getElementById('form-nlmk');
    const inputPass = document.getElementById('nlmk-password');
    const inputConfirm = document.getElementById('nlmk-confirm');
    const submitBtn = form ? form.querySelector('.login-submit') : null;
    const alertBox = document.getElementById('auth-alert');

    // Hiển thị thông báo (error, success, info)
    function showAlert(message, type = 'error') {
        if (!alertBox) return;
        alertBox.textContent = message;
        alertBox.className = 'login-alert alert-' + type;
        alertBox.hidden = false;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        if (!alertBox) return;
        alertBox.hidden = true;
        alertBox.textContent = '';
        alertBox.className = 'login-alert';
    }

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();

            const password = inputPass ? inputPass.value.trim() : '';
            const confirmPass = inputConfirm ? inputConfirm.value.trim() : '';

            // 1. Kiểm tra tính hợp lệ cơ bản
            if (!password) {
                showAlert('Vui lòng nhập mật khẩu mới.', 'error');
                if (inputPass) inputPass.focus();
                return;
            }

            if (password.length < 6) {
                showAlert('Mật khẩu phải có tối thiểu 6 ký tự.', 'error');
                if (inputPass) inputPass.focus();
                return;
            }

            if (!confirmPass) {
                showAlert('Vui lòng xác nhận lại mật khẩu mới.', 'error');
                if (inputConfirm) inputConfirm.focus();
                return;
            }

            if (password !== confirmPass) {
                showAlert('Mật khẩu xác nhận không khớp. Vui lòng kiểm tra lại.', 'error');
                if (inputConfirm) {
                    inputConfirm.focus();
                    inputConfirm.select();
                }
                return;
            }

            // 2. Khóa nút submit và gửi yêu cầu tới Backend qua ApiConfig
            if (submitBtn) submitBtn.disabled = true;
            showAlert('Đang cập nhật mật khẩu mới...', 'info');

            try {
                const payload = {
                    new_password: password,
                    new_password_confirm: confirmPass,
                    password: password,
                    confirm_password: confirmPass
                };

                const result = (typeof ApiConfig !== 'undefined' && ApiConfig.request)
                    ? await ApiConfig.request('RESET_PASSWORD', { method: 'POST', data: payload })
                    : await fetch('api/reset-password.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    }).then(r => r.json()).catch(() => null);

                const isSuccess = Boolean(result && (result.success === true || result.status === true));

                if (isSuccess) {
                    showAlert('Tạo lại mật khẩu thành công! Đang chuyển về Đăng nhập...', 'success');
                    sessionStorage.removeItem('reset_email');
                    sessionStorage.removeItem('reset_verified');
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 1200);
                } else {
                    const msg = (result && result.message) || 'Không thể cập nhật mật khẩu. Phiên xác thực có thể đã hết hạn.';
                    showAlert(msg, 'error');
                }
            } catch (err) {
                console.error('[Reset Password] Lỗi:', err);
                showAlert('Đã có lỗi xảy ra. Vui lòng thử lại sau.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    // Tự động focus ô nhập mật khẩu khi tải trang
    document.addEventListener('DOMContentLoaded', () => {
        if (inputPass) {
            inputPass.focus();
        }
    });

})();
