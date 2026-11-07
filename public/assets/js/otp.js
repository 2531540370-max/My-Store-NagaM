/**
 * =============================================================================
 * NAGAMC STORE — XỬ LÝ NHẬP MÃ OTP (otp.js)
 * Tính năng:
 * - Tự động nhảy ô khi gõ 6 chữ số OTP
 * - Hỗ trợ dán (paste) toàn bộ 6 ký tự cùng lúc
 * - Tự động xóa lùi khi bấm Backspace
 * - Hiển thị email người dùng từ trang quên mật khẩu
 * - Kết nối Backend PHP qua ApiConfig
 * - Gửi lại mã với đếm ngược thời gian chờ (cooldown 60s)
 * =============================================================================
 */

(function () {
    'use strict';

    const form = document.getElementById('form-otp');
    const alertBox = document.getElementById('auth-alert');
    const emailSpan = document.getElementById('otp-user-email');
    const inputs = document.querySelectorAll('.otp-digit');
    const btnResend = document.getElementById('btn-resend-otp');

    // 1. Hiển thị email đã nhập từ bước Quên Mật Khẩu
    const storedEmail = sessionStorage.getItem('reset_email') || '';
    if (emailSpan) {
        if (storedEmail) {
            emailSpan.textContent = storedEmail;
        } else {
            emailSpan.textContent = 'email của bạn';
        }
    }

    function showAlert(message, type = 'error') {
        if (!alertBox) return;
        alertBox.className = 'login-alert alert-' + type;
        alertBox.textContent = message;
        alertBox.hidden = false;
    }

    function hideAlert() {
        if (!alertBox) return;
        alertBox.hidden = true;
        alertBox.textContent = '';
    }

    // 2. Logic tương tác cho 6 ô nhập mã OTP
    if (inputs.length > 0) {
        inputs.forEach((input, index) => {
            // Khi gõ số: tự động nhảy sang ô tiếp theo
            input.addEventListener('input', (e) => {
                const val = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = val;

                if (val) {
                    input.classList.add('filled');
                    if (index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                } else {
                    input.classList.remove('filled');
                }
            });

            // Khi bấm Backspace: tự động lùi về ô trước
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                    inputs[index - 1].value = '';
                    inputs[index - 1].classList.remove('filled');
                }
            });

            // Hỗ trợ Paste mã 6 số một chạm
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim();
                const digits = pasteData.replace(/[^0-9]/g, '').slice(0, inputs.length);

                if (digits) {
                    digits.split('').forEach((digit, i) => {
                        if (inputs[i]) {
                            inputs[i].value = digit;
                            inputs[i].classList.add('filled');
                        }
                    });
                    const nextIndex = Math.min(digits.length, inputs.length - 1);
                    inputs[nextIndex].focus();
                }
            });
        });

        // Tự động focus vào ô đầu tiên
        setTimeout(() => {
            if (inputs[0]) inputs[0].focus();
        }, 200);
    }

    // 3. Xử lý gửi Form xác thực OTP
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            hideAlert();

            let otpCode = '';
            inputs.forEach(input => {
                otpCode += input.value.trim();
            });

            if (otpCode.length < inputs.length) {
                showAlert('Vui lòng nhập đầy đủ 6 chữ số mã OTP.');
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const payload = {
                    token: otpCode,
                    otp: otpCode,
                    email: storedEmail
                };

                const result = (typeof ApiConfig !== 'undefined' && ApiConfig.request)
                    ? await ApiConfig.request('VERIFY_OTP', { method: 'POST', data: payload })
                    : await fetch('api/verify-otp.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload)
                    }).then(r => r.json()).catch(() => null);

                const isSuccess = Boolean(result && (result.success === true || result.status === true));

                if (isSuccess) {
                    showAlert('Xác thực OTP thành công! Đang chuyển hướng sang tạo mật khẩu mới...', 'success');
                    sessionStorage.setItem('reset_verified', 'true');
                    setTimeout(() => {
                        window.location.href = 'nlmk.html';
                    }, 1000);
                } else {
                    const msg = (result && result.message) || 'Mã OTP không chính xác hoặc đã hết hạn.';
                    showAlert(msg, 'error');
                }
            } catch (err) {
                console.error('[OTP Verify] Lỗi:', err);
                showAlert('Đã có lỗi xảy ra khi xác thực OTP. Vui lòng thử lại.', 'error');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    // 4. Gửi lại mã OTP (kèm đếm ngược cooldown)
    let resendCooldown = 0;
    if (btnResend) {
        btnResend.addEventListener('click', async (e) => {
            e.preventDefault();
            if (resendCooldown > 0) return;

            if (!storedEmail) {
                showAlert('Không tìm thấy thông tin email. Vui lòng quay lại bước Quên Mật Khẩu.', 'error');
                return;
            }

            btnResend.style.pointerEvents = 'none';
            btnResend.style.opacity = '0.5';

            try {
                showAlert('Đang gửi lại mã OTP mới...', 'info');

                const payload = {
                    username: storedEmail,
                    email: storedEmail
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
                    showAlert('Mã OTP mới đã được gửi lại vào email của bạn!', 'success');
                } else {
                    showAlert((result && result.message) || 'Không thể gửi lại mã OTP. Vui lòng thử lại.', 'error');
                }
            } catch (err) {
                showAlert('Đã có lỗi xảy ra khi yêu cầu gửi lại mã.', 'error');
            }

            resendCooldown = 60;
            const timer = setInterval(() => {
                resendCooldown--;
                btnResend.textContent = `Gửi lại mã (${resendCooldown}s)`;
                if (resendCooldown <= 0) {
                    clearInterval(timer);
                    btnResend.textContent = 'Gửi lại mã';
                    btnResend.style.pointerEvents = 'auto';
                    btnResend.style.opacity = '1';
                }
            }, 1000);
        });
    }

})();
