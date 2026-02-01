export const API_ROUTES_CONFIG = {
    login: "/auth/login",
    verifyOtp: "/auth/verify-otp",
    getMe: "/auth/me",
    logout: "/auth/logout",
    forgotPassword: "/auth/forgot-password",
    changePassword: "/auth/change-password",
    cauHinhChung: "/cau-hinh-chung",
    thoiGianLamViec: {
        fulltime: "/thoi-gian-lam-viec/fulltime",
        parttime: "/thoi-gian-lam-viec/parttime",
    },
    media: {
        upload: "/media/upload",
        getAll: "/media",
        delete: "/media/delete",
        getMediaTrash: "/media/trash",
        restore: "/media/restore",
        deletePermanently: "/media/delete-permanently",
    },
    user: "/user",
};
