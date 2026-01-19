import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";

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
                    title: "ĐĂNG NHẬP",
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
                    title: "Dashboard",
                },
            },
        ],
    },
];
