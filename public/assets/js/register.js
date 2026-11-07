/**
 * =============================================================================
 * NAGAMC STORE — XỬ LÝ ĐĂNG KÝ (register.js)
 * Tương thích chuẩn thiết kế Figma "danhki" (Node 108:135)
 * Tích hợp xác thực Cloudflare Turnstile thật và kết nối Backend PHP AuthController
 * =============================================================================
 */

(function () {
    'use strict';

    const form = document.getElementById('form-register');
    const alertBox = document.getElementById('auth-alert');

    if (!form) return;

    // Hiển thị thông báo trạng thái
    function showAlert(message, type) {
        if (!alertBox) return;
        alertBox.className = 'register-alert alert-' + (type || 'error');
        alertBox.textContent = message;
        alertBox.hidden = false;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        if (!alertBox) return;
        alertBox.hidden = true;
        alertBox.textContent = '';
    }

    // Lấy token xác thực Cloudflare Turnstile
    function getTurnstileToken() {
        const cfInput = form.querySelector('input[name="cf-turnstile-response"]');
        if (cfInput && cfInput.value) {
            return cfInput.value;
        }
        if (typeof window.turnstile !== 'undefined' && typeof window.turnstile.getResponse === 'function') {
            try {
                return window.turnstile.getResponse() || '';
            } catch (err) {
                console.warn('[Turnstile] getResponse error:', err);
            }
        }
        return '';
    }

    // Reset lại widget Cloudflare khi đăng ký thất bại hoặc cần thử lại
    function resetTurnstile() {
        if (typeof window.turnstile !== 'undefined' && typeof window.turnstile.reset === 'function') {
            try {
                window.turnstile.reset();
            } catch (err) {
                console.warn('[Turnstile] reset error:', err);
            }
        }
    }

    // Xử lý gửi biểu mẫu đăng ký
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        hideAlert();

        const email = document.getElementById('reg-email').value.trim();
        const username = document.getElementById('reg-username').value.trim();
        const password = document.getElementById('reg-password').value;
        const confirm = document.getElementById('reg-confirm').value;
        const terms = document.getElementById('reg-terms');
        const submitBtn = form.querySelector('button[type="submit"]');

        // 1. Kiểm tra đầy đủ trường
        if (!email || !username || !password || !confirm) {
            showAlert('Vui lòng điền đầy đủ tất cả các thông tin.', 'error');
            return;
        }

        // 2. Kiểm tra định dạng Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showAlert('Địa chỉ email không đúng định dạng.', 'error');
            return;
        }

        // 3. Kiểm tra độ dài mật khẩu
        if (password.length < 6) {
            showAlert('Mật khẩu phải có tối thiểu 6 ký tự.', 'error');
            return;
        }

        // 4. Kiểm tra khớp mật khẩu
        if (password !== confirm) {
            showAlert('Mật khẩu xác nhận không khớp. Vui lòng nhập lại.', 'error');
            return;
        }

        // 5. Kiểm tra điều khoản
        if (terms && !terms.checked) {
            showAlert('Vui lòng đồng ý với Điều khoản của NagaMC để tiếp tục.', 'error');
            return;
        }

        // 6. Kiểm tra token xác thực Cloudflare Turnstile thật
        const turnstileToken = getTurnstileToken();
        if (!turnstileToken) {
            showAlert('Vui lòng hoàn tất xác minh bảo mật Cloudflare bên dưới.', 'error');
            const cfBox = document.getElementById('cf-turnstile-container');
            if (cfBox) cfBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        if (submitBtn) submitBtn.disabled = true;
        showAlert('Đang khởi tạo tài khoản...', 'info');

        try {
            const payload = {
                username: username,
                email: email,
                password: password,
                ingame_name: '',
                'cf-turnstile-response': turnstileToken
            };

            const result = (typeof ApiConfig !== 'undefined' && ApiConfig.request)
                ? await ApiConfig.request('REGISTER', { method: 'POST', data: payload })
                : await fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload)
                }).then(r => r.json()).catch(() => null);

            const isSuccess = Boolean(result && (result.success === true || result.status === true));

            if (isSuccess) {
                showAlert((result && result.message) || 'Tạo tài khoản thành công! Đang chuyển về Đăng nhập...', 'success');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 1200);
            } else {
                showAlert((result && result.message) || 'Đăng ký không thành công. Vui lòng kiểm tra lại.', 'error');
                resetTurnstile();
            }
        } catch (err) {
            console.error('[Register] Lỗi:', err);
            showAlert('Đã có lỗi xảy ra khi kết nối máy chủ. Vui lòng thử lại sau.', 'error');
            resetTurnstile();
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });

})();
