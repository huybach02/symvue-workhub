import i18n from "@/plugins/i18n";
import { NAME_ROUTES_CONFIG } from "./nameRouteConfig";

export const menuSidebar = [
    {
        key: "home",
        title: i18n.global.t("sidebar.home"),
        icon: "mdi-home-city",
        value: NAME_ROUTES_CONFIG.dashboard,
        to: NAME_ROUTES_CONFIG.dashboard,
    },
    {
        title: i18n.global.t("sidebar.user_management"),
        icon: "mdi-account",
        value: "account",
        // Menu có children sẽ không có thuộc tính 'to'
        children: [],
    },
];
