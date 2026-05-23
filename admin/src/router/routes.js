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
                path: "general-settings",
                name: NAME_ROUTES_CONFIG.systemGeneralSettings,
                component: () =>
                    import("../pages/GeneralSettings/GeneralSettings.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.systemGeneralSettings,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.systemGeneralSettings,
                        ).icon || "",
                },
            },
            {
                path: "working-times",
                name: NAME_ROUTES_CONFIG.workingTimes,
                component: () =>
                    import("../pages/WorkingTime/WorkingTime.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.workingTimes,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.workingTimes,
                        ).icon || "",
                },
            },
            {
                path: "users",
                name: NAME_ROUTES_CONFIG.users,
                component: () => import("../pages/User/User.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.users,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.users,
                        ).icon || "",
                },
            },
            {
                path: "import-history",
                name: NAME_ROUTES_CONFIG.importHistory,
                component: () =>
                    import("../pages/ImportHistory/ImportHistory.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.importHistory,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.importHistory,
                        ).icon || "",
                },
            },
            {
                path: "departments",
                name: NAME_ROUTES_CONFIG.department,
                component: () => import("../pages/Department/Department.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.department,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.department,
                        ).icon || "",
                },
            },
                    {
                path: "notifications",
                name: NAME_ROUTES_CONFIG.notifications,
                component: () => import("../pages/Notification/Notification.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.notifications,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.notifications,
                        ).icon || "",
                },
            },
            {
                path: "lich-lam-viec",
                name: NAME_ROUTES_CONFIG.workSchedule,
                component: () => import("../pages/WorkSchedule/WorkSchedule.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.workSchedule,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.workSchedule,
                        ).icon || "",
                },
            },
],
    },
];
