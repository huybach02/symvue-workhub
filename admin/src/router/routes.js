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
                    title: functionHelper.findMenuItemByKey("home").title,
                    icon: functionHelper.findMenuItemByKey("home").icon,
                },
            },
        ],
    },
];
