import i18n from "@/plugins/i18n";
import axios from "axios";
import { PUBLIC_URL } from "@/utils/constants/publicRoute";

// Tạo axios instance
const axiosInstance = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api",
    timeout: 10000,
    headers: {
        "Content-Type": "application/json",
    },
});

let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
    failedQueue.forEach((prom) => {
        if (error) {
            prom.reject(error);
        } else {
            prom.resolve(token);
        }
    });
    failedQueue = [];
};

// 1. Request Interceptor: Gắn Token
axiosInstance.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem("token");
        const deviceId = localStorage.getItem("device_id");
        const currentLang = i18n.global.locale;

        config.headers["Accept-Language"] = currentLang;
        config.headers["Device-Id"] = deviceId;

        // Không gắn token nếu đang gọi API refresh
        if (token && !config.url?.includes(PUBLIC_URL.refresh)) {
            config.headers["Authorization"] = `Bearer ${token}`;
        }

        return config;
    },
    (error) => Promise.reject(error),
);

// 2. Response Interceptor: Xử lý 401 & Refresh Token
axiosInstance.interceptors.response.use(
    (response) => {
        if (response.config.responseType === "blob") {
            return response;
        }
        return response.data ? response.data : response;
    },
    async (error) => {
        const originalRequest = error.config;

        // Bỏ qua nếu lỗi không phải 401 hoặc request đã được retry rồi
        if (
            !error.response ||
            error.response.status !== 401 ||
            originalRequest._retry
        ) {
            return Promise.reject(error);
        }

        // Bỏ qua nếu lỗi 401 xảy ra ngay tại các API công khai (tránh lặp)
        const isPublicUrl = Object.values(PUBLIC_URL).some((url) =>
            originalRequest.url?.includes(url),
        );
        if (isPublicUrl) {
            // Xóa token rác nếu có
            handleLogout();
            return Promise.reject(error);
        }

        // --- BẮT ĐẦU QUY TRÌNH REFRESH TOKEN ---

        // Nếu đang có 1 tiến trình refresh chạy rồi, các request khác xếp hàng đợi
        if (isRefreshing) {
            return new Promise(function (resolve, reject) {
                failedQueue.push({ resolve, reject });
            })
                .then((token) => {
                    originalRequest.headers["Authorization"] =
                        "Bearer " + token;
                    return axiosInstance(originalRequest);
                })
                .catch((err) => Promise.reject(err));
        }

        originalRequest._retry = true;
        isRefreshing = true;

        try {
            const refreshToken = localStorage.getItem("refresh_token");

            if (!refreshToken) {
                throw new Error("No refresh token available");
            }

            // Gọi API refresh (Dùng axios thường để tránh dính interceptor của instance)
            // LƯU Ý: Backend yêu cầu POST và body JSON
            const deviceId = localStorage.getItem("device_id");
            const currentLang = i18n.global.locale;

            const headers = {
                "Accept-Language": currentLang,
            };

            // Thêm Device-Id header nếu có
            if (deviceId) {
                headers["Device-Id"] = deviceId;
            }

            const response = await axios.post(
                import.meta.env.VITE_API_BASE_URL + PUBLIC_URL.refresh,
                {
                    refresh_token: refreshToken,
                },
                {
                    headers: headers,
                },
            );

            // Backend trả về cấu trúc: { success: true, data: { token: "...", refresh_token: "..." } }
            // Axios bọc thêm 1 lớp data nữa bên ngoài.
            // => response.data.data.token

            const newAccessToken = response.data?.data?.token;
            const newRefreshToken = response.data?.data?.refresh_token;

            if (!newAccessToken) {
                throw new Error("Failed to receive new token");
            }

            // Lưu token mới
            localStorage.setItem("token", newAccessToken);
            if (newRefreshToken) {
                localStorage.setItem("refresh_token", newRefreshToken);
            }

            // Gắn token mới vào header mặc định của instance
            axiosInstance.defaults.headers.common["Authorization"] =
                "Bearer " + newAccessToken;

            // Xử lý hàng đợi đang chờ
            processQueue(null, newAccessToken);

            // Gọi lại request ban đầu với token mới
            originalRequest.headers["Authorization"] =
                "Bearer " + newAccessToken;
            return axiosInstance(originalRequest);
        } catch (refreshError) {
            processQueue(refreshError, null);
            handleLogout(); // Logout nếu refresh thất bại
            return Promise.reject(refreshError);
        } finally {
            isRefreshing = false;
        }
    },
);

// Hàm logout helper
function handleLogout() {
    localStorage.removeItem("token");
    localStorage.removeItem("refresh_token");
    // localStorage.removeItem("device_id");

    // Chuyển hướng về trang login (nếu không phải đang ở trang login)
    if (window.location.pathname !== PUBLIC_URL.login) {
        window.location.href = PUBLIC_URL.login;
    }
}

export default axiosInstance;
