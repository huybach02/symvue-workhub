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
                component: () => import("../pages/WorkingTime/WorkingTime.vue"),
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
                component: () =>
                    import("../pages/Notification/Notification.vue"),
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
                path: "work-schedules",
                name: NAME_ROUTES_CONFIG.workSchedules,
                component: () =>
                    import("../pages/WorkSchedule/WorkSchedule.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.workSchedules,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.workSchedules,
                        ).icon || "",
                },
            },
            {
                path: "requests",
                name: NAME_ROUTES_CONFIG.requests,
                component: () => import("../pages/Request/Request.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.requests,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.requests,
                        ).icon || "",
                },
            },
            {
                path: "attendance",
                name: NAME_ROUTES_CONFIG.attendance,
                component: () => import("../pages/Attendance/Attendance.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.attendance,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.attendance,
                        ).icon || "",
                },
            },
                    {
                path: "branch",
                name: NAME_ROUTES_CONFIG.branch,
                component: () => import("../pages/Branch/Branch.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.branch,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.branch,
                        ).icon || "",
                },
            },
            {
                path: "category",
                name: NAME_ROUTES_CONFIG.category,
                component: () => import("../pages/Category/Category.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.category,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.category,
                        ).icon || "",
                },
            },
            {
                path: "unit",
                name: NAME_ROUTES_CONFIG.unit,
                component: () => import("../pages/Unit/Unit.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.unit,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.unit,
                        ).icon || "",
                },
            },
            {
                path: "provider",
                name: NAME_ROUTES_CONFIG.provider,
                component: () => import("../pages/Provider/Provider.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.provider,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.provider,
                        ).icon || "",
                },
            },
            {
                path: "profile",
                name: NAME_ROUTES_CONFIG.profile,
                component: () => import("../pages/Profile/Profile.vue"),
                meta: {
                    title: i18n.global.t("auth.profile") || "Trang cá nhân",
                    icon: "mdi-account",
                },
            },
            {
                path: "merchandise",
                name: NAME_ROUTES_CONFIG.merchandise,
                component: () => import("../pages/Merchandise/Merchandise.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.merchandise,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.merchandise,
                        ).icon || "",
                },
            },
            {
                path: "business-product",
                name: NAME_ROUTES_CONFIG.businessProduct,
                component: () => import("../pages/BusinessProduct/BusinessProduct.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.businessProduct,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.businessProduct,
                        ).icon || "",
                },
            },
            {
                path: "warehouse",
                name: NAME_ROUTES_CONFIG.warehouse,
                component: () => import("../pages/Warehouse/Warehouse.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.warehouse,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.warehouse,
                        ).icon || "",
                },
            },
            {
                path: "stock-receipt",
                name: NAME_ROUTES_CONFIG.stockReceipt,
                component: () => import("../pages/StockReceipt/StockReceipt.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.stockReceipt,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.stockReceipt,
                        ).icon || "",
                },
            },
            {
                path: "production-order",
                name: NAME_ROUTES_CONFIG.productionOrder,
                component: () => import("../pages/ProductionOrder/ProductionOrder.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.productionOrder,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.productionOrder,
                        ).icon || "",
                },
            },
            {
                path: "dining-table",
                name: NAME_ROUTES_CONFIG.diningTable,
                component: () => import("../pages/DiningTable/DiningTable.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.diningTable,
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.diningTable,
                        ).icon || "",
                },
            },
],
    },
    {
        path: "/qr-attendance",
        name: NAME_ROUTES_CONFIG.qrAttendance,
        component: () => import("../pages/QRAttendance.vue"),
    },
];
