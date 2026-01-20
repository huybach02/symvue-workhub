import { NAME_ROUTES_CONFIG } from "./nameRouteConfig";

export const menuSidebar = [
    {
        key: "home",
        title: "TRANG CHỦ",
        icon: "mdi-home-city",
        value: NAME_ROUTES_CONFIG.dashboard,
        to: NAME_ROUTES_CONFIG.dashboard,
    },
    {
        title: "QUẢN LÝ NHÂN SỰ",
        icon: "mdi-account",
        value: "account",
        to: "/account",
    },
    {
        title: "Users",
        icon: "mdi-account-group-outline",
        value: "users",
        to: "/users",
    },
];
