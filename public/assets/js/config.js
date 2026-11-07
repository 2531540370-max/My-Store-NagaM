/**
 * =============================================================================
 * NAGA MC STORE — CẤU HÌNH KẾT NỐI API BACKEND (CLASS CONFIG)
 * Quản lý tập trung endpoint API, đường dẫn và giao tiếp dữ liệu Frontend-Backend
 * =============================================================================
 */

class ApiConfig {
    /**
     * Base URL của máy chủ Backend
     * - Để rỗng '' nếu Frontend và Backend chạy cùng domain / thư mục (tự động nhận diện)
     * - Hoặc gán 'http://localhost:8000', 'http://localhost/Store-NagaMC/public' khi tách server
     */
    static BASE_URL = '';

    /**
     * Danh mục các endpoint backend chính
     */
    static ENDPOINTS = {
        // Xác thực người dùng (Auth)
        LOGIN: 'api/login.php',
        REGISTER: 'api/register.php',
        LOGOUT: 'api/logout.php',
        FORGOT_PASSWORD: 'api/forgot-password.php',
        VERIFY_OTP: 'api/verify-otp.php',
        RESET_PASSWORD: 'api/reset-password.php',
        ME: 'api/me.php',

        // Tương thích router chung
        AUTH_ROUTER: 'api/auth.php',

        // Sản phẩm & Cửa hàng
        GET_PRODUCTS: 'api/products.php',
        FILTER_PRODUCTS: 'api/products.php',
        CHECKOUT_MOMO: 'api/checkout-momo.php',
    };

    /**
     * Tự động tính toán đường dẫn cơ sở tới thư mục public/
     */
    static getDetectedBaseUrl() {
        if (this.BASE_URL) {
            return this.BASE_URL.replace(/\/+$/, '');
        }

        if (typeof window !== 'undefined' && window.location) {
            const pathname = window.location.pathname;
            // Nếu chạy trong subfolder như /Store-NagaMC/public/...
            const publicIndex = pathname.indexOf('/public/');
            if (publicIndex !== -1) {
                return window.location.origin + pathname.substring(0, publicIndex + 7);
            }
            // Nếu root trỏ thẳng vào thư mục public (ví dụ php -S localhost:8000 -t public)
            return window.location.origin;
        }

        return '';
    }

    /**
     * Lấy đường dẫn URL hoàn chỉnh tới một endpoint
     * @param {string} endpointKey - Tên khóa endpoint (ví dụ: 'LOGIN', 'FORGOT_PASSWORD')
     * @returns {string} URL đầy đủ
     */
    static getUrl(endpointKey) {
        const path = this.ENDPOINTS[endpointKey];
        if (!path) {
            console.warn(`[ApiConfig] Không tìm thấy endpoint: "${endpointKey}"`);
            return this.getDetectedBaseUrl();
        }

        // Nếu path đã là URL tuyệt đối
        if (/^https?:\/\//i.test(path)) {
            return path;
        }

        const base = this.getDetectedBaseUrl();
        if (!base) {
            return path;
        }

        return `${base}/${path.replace(/^\/+/, '')}`;
    }

    /**
     * Cập nhật Base URL khi cần đổi môi trường
     * @param {string} newBaseUrl 
     */
    static setBaseUrl(newBaseUrl) {
        if (typeof newBaseUrl === 'string') {
            this.BASE_URL = newBaseUrl.replace(/\/+$/, '');
        }
    }

    /**
     * Helper gửi request tiện lợi tới Backend, tự động bảo lưu Session Cookie
     * @param {string} endpointKey Hoặc URL trực tiếp
     * @param {object} options Các tùy chọn method, data, headers
     * @returns {Promise<object>} Kết quả JSON chuẩn hoá
     */
    static async request(endpointKey, options = {}) {
        const url = this.ENDPOINTS[endpointKey] ? this.getUrl(endpointKey) : endpointKey;
        const method = (options.method || 'GET').toUpperCase();
        const headers = { ...(options.headers || {}) };

        const fetchOptions = {
            method: method,
            credentials: 'same-origin',
        };

        if (options.data !== undefined && options.data !== null) {
            if (options.data instanceof FormData) {
                fetchOptions.body = options.data;
            } else {
                headers['Content-Type'] = 'application/json';
                fetchOptions.body = JSON.stringify(options.data);
            }
        }

        fetchOptions.headers = headers;

        try {
            const res = await fetch(url, fetchOptions);
            const text = await res.text();
            let json = null;

            try {
                json = JSON.parse(text);
            } catch (err) {
                console.error('[ApiConfig] Lỗi phân giải phản hồi JSON từ Backend:', text);
                return {
                    success: false,
                    status: false,
                    message: 'Phản hồi từ máy chủ không đúng định dạng JSON.',
                    raw: text
                };
            }

            // Chuẩn hóa định dạng cờ trạng thái
            const isSuccess = Boolean(json.success ?? json.status ?? (res.status >= 200 && res.status < 300));
            json.success = isSuccess;
            json.status = isSuccess;
            json.data = json.data ?? json.user ?? null;
            json.user = json.user ?? json.data ?? null;

            return json;
        } catch (error) {
            console.error('[ApiConfig] Lỗi kết nối mạng:', error);
            return {
                success: false,
                status: false,
                message: 'Không thể kết nối đến máy chủ Backend. Vui lòng kiểm tra lại mạng hoặc server PHP.'
            };
        }
    }
}

// Xuất ra phạm vi toàn cục window để các script khác sử dụng trực tiếp
if (typeof window !== 'undefined') {
    window.ApiConfig = ApiConfig;
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiConfig;
}
