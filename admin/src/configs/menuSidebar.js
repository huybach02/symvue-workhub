import i18n from "@/plugins/i18n";
import { NAME_ROUTES_CONFIG } from "./nameRouteConfig";

export const menuSidebar = [
    {
        title: i18n.global.t("sidebar.home"),
        icon: "mdi-home-city",
        value: NAME_ROUTES_CONFIG.dashboard,
        to: { name: NAME_ROUTES_CONFIG.dashboard },
    },
    {
        title: i18n.global.t("sidebar.system_config"),
        icon: "mdi-cog",
        value: NAME_ROUTES_CONFIG.systemConfig,
        children: [
            {
                title: i18n.global.t("sidebar.system_config_general"),
                icon: "mdi-cog-outline",
                value: NAME_ROUTES_CONFIG.systemConfigGeneral,
                to: { name: NAME_ROUTES_CONFIG.systemConfigGeneral },
            },
            {
                title: i18n.global.t("sidebar.system_config_working_time"),
                icon: "mdi-clock-time-four-outline",
                value: NAME_ROUTES_CONFIG.systemConfigWorkingTime,
                to: { name: NAME_ROUTES_CONFIG.systemConfigWorkingTime },
            },
        ],
    },
    {
        title: i18n.global.t("sidebar.user_management"),
        icon: "mdi-account-group",
        value: NAME_ROUTES_CONFIG.userManagement,
        children: [
            {
                title: i18n.global.t("sidebar.user_management_user"),
                icon: "mdi-account-outline",
                value: NAME_ROUTES_CONFIG.userManagementUser,
                to: { name: NAME_ROUTES_CONFIG.userManagementUser },
            },
        ],
    },
];
