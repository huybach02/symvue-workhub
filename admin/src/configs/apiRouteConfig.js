export const API_ROUTES_CONFIG = {
    login: "/auth/login",
    verifyOtp: "/auth/verify-otp",
    getMe: "/auth/me",
    logout: "/auth/logout",
    forgotPassword: "/auth/forgot-password",
    changePassword: "/auth/change-password",
    cauHinhChung: "/cau-hinh-chung",
    thoiGianLamViec: {
        findAll: "/thoi-gian-lam-viec/fulltime",
        findById: "/thoi-gian-lam-viec/fulltime/:id",
        updateFulltime: "/thoi-gian-lam-viec/fulltime/:id",
        createParttime: "/thoi-gian-lam-viec/parttime",
        findAllParttimeByThoiGianLamViecId: "/thoi-gian-lam-viec/parttime/:id",
    },
};
