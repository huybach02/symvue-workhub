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
                name: NAME_ROUTES_CONFIG.system_config_general,
                component: () =>
                    import("../pages/CauHinhChung/CauHinhChung.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.system_config_general,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.system_config_general,
                        ).icon || "",
                },
            },
            {
                path: "config/working-time",
                name: NAME_ROUTES_CONFIG.system_config_working_time,
                component: () =>
                    import("../pages/ThoiGianLamViec/ThoiGianLamViec.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.system_config_working_time,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.system_config_working_time,
                        ).icon || "",
                },
            },
        ],
    },
];
