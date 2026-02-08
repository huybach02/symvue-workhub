import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";
import { functionHelper } from "@/helpers/functionHelper";
import i18n from "@/plugins/i18n";

export const routes = [
    {
        path: "/",
        redirect: { name: NAME_ROUTES_CONFIG.login },
    },
    {
        path: "/auth",
        component: () => import("../components/layouts/AuthLayout.vue"),
        children: [
            {
                path: "login",
                name: NAME_ROUTES_CONFIG.login,
                component: () => import("../pages/LoginPage.vue"),
                meta: {
                    title: i18n.global.t("auth.login"),
                },
            },
            {
                path: "verify-otp",
                name: NAME_ROUTES_CONFIG.verifyOtp,
                component: () => import("../pages/VerifyOtpPage.vue"),
                meta: {
                    title: i18n.global.t("auth.verify_otp"),
                },
            },
            {
                path: "forgot-password",
                name: NAME_ROUTES_CONFIG.forgotPassword,
                component: () => import("../pages/ForgotPasswordPage.vue"),
                meta: {
                    title: i18n.global.t("auth.forgot_password"),
                },
            },
            {
                path: "change-password",
                name: NAME_ROUTES_CONFIG.changePassword,
                component: () => import("../pages/ChangePasswordPage.vue"),
                meta: {
                    title: i18n.global.t("auth.change_password"),
                },
            },
        ],
    },
    {
        path: "/system",
        component: () => import("../components/layouts/MainLayout.vue"),
        children: [
            {
                path: "dashboard",
                name: NAME_ROUTES_CONFIG.dashboard,
                component: () => import("../pages/DashboardPage.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.dashboard,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.dashboard,
                        ).icon || "",
                },
            },
            {
                path: "config/general",
                name: NAME_ROUTES_CONFIG.systemConfigGeneral,
                component: () =>
                    import("../pages/CauHinhChung/CauHinhChung.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.systemConfigGeneral,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.systemConfigGeneral,
                        ).icon || "",
                },
            },
            {
                path: "config/working-time",
                name: NAME_ROUTES_CONFIG.systemConfigWorkingTime,
                component: () =>
                    import("../pages/ThoiGianLamViec/ThoiGianLamViec.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.systemConfigWorkingTime,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.systemConfigWorkingTime,
                        ).icon || "",
                },
            },
            {
                path: "user-management/user",
                name: NAME_ROUTES_CONFIG.userManagementUser,
                component: () => import("../pages/NguoiDung/NguoiDung.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.userManagementUser,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.userManagementUser,
                        ).icon || "",
                },
            },
            {
                path: "lich-su-import",
                name: NAME_ROUTES_CONFIG.lichSuImport,
                component: () =>
                    import("../pages/LichSuImport/LichSuImport.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.lichSuImport,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.lichSuImport,
                        ).icon || "",
                },
            },
        ],
    },
];
