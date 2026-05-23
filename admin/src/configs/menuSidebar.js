import i18n from "@/plugins/i18n";
import { NAME_ROUTES_CONFIG } from "./nameRouteConfig";

export const menuSidebar = [
    {
        title: i18n.global.t("sidebar.home"),
        key: "dashboard",
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
                key: "general-settings",
                icon: "mdi-cog-outline",
                value: NAME_ROUTES_CONFIG.systemGeneralSettings,
                to: { name: NAME_ROUTES_CONFIG.systemGeneralSettings },
            },
            {
                title: i18n.global.t("sidebar.system_config_working_time"),
                key: "working-times",
                icon: "mdi-clock-time-four-outline",
                value: NAME_ROUTES_CONFIG.workingTimes,
                to: { name: NAME_ROUTES_CONFIG.workingTimes },
            },
            {
                title: i18n.global.t("sidebar.bo_phan"),
                key: "departments",
                icon: "mdi-account-multiple-outline",
                value: NAME_ROUTES_CONFIG.department,
                to: { name: NAME_ROUTES_CONFIG.department },
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
                key: "users",
                icon: "mdi-account-outline",
                value: NAME_ROUTES_CONFIG.users,
                to: { name: NAME_ROUTES_CONFIG.users },
            },
        ],
    },
    {
        title: i18n.global.t("sidebar.lich_su_import"),
        key: "import-history",
        icon: "mdi-history",
        value: NAME_ROUTES_CONFIG.importHistory,
        to: { name: NAME_ROUTES_CONFIG.importHistory },
    },
    {
        title: i18n.global.t("sidebar.thong_bao"),
        key: "notifications",
        icon: "mdi-bell",
        value: NAME_ROUTES_CONFIG.notifications,
        to: { name: NAME_ROUTES_CONFIG.notifications },
    },
    {
        title: i18n.global.t("sidebar.work_schedule"),
        key: "work-schedules",
        icon: "mdi-calendar-clock",
        value: NAME_ROUTES_CONFIG.workSchedules,
        to: { name: NAME_ROUTES_CONFIG.workSchedules },
    },
    {
        title: i18n.global.t("sidebar.request"),
        key: "requests",
        icon: "mdi-file-document-edit-outline",
        value: NAME_ROUTES_CONFIG.requests,
        to: { name: NAME_ROUTES_CONFIG.requests },
    },
];
