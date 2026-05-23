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
                key: "cau-hinh-chung",
                icon: "mdi-cog-outline",
                value: NAME_ROUTES_CONFIG.systemConfigGeneral,
                to: { name: NAME_ROUTES_CONFIG.systemConfigGeneral },
            },
            {
                title: i18n.global.t("sidebar.system_config_working_time"),
                key: "thoi-gian-lam-viec",
                icon: "mdi-clock-time-four-outline",
                value: NAME_ROUTES_CONFIG.systemConfigWorkingTime,
                to: { name: NAME_ROUTES_CONFIG.systemConfigWorkingTime },
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
                key: "nguoi-dung",
                icon: "mdi-account-outline",
                value: NAME_ROUTES_CONFIG.userManagementUser,
                to: { name: NAME_ROUTES_CONFIG.userManagementUser },
            },
        ],
    },
    {
        title: i18n.global.t("sidebar.lich_su_import"),
        key: "lich-su-import",
        icon: "mdi-history",
        value: NAME_ROUTES_CONFIG.lichSuImport,
        to: { name: NAME_ROUTES_CONFIG.lichSuImport },
    },
    {
        title: i18n.global.t("sidebar.thong_bao"),
        key: "thong-bao",
        icon: "mdi-bell",
        value: NAME_ROUTES_CONFIG.thongBao,
        to: { name: NAME_ROUTES_CONFIG.thongBao },
    },
    {
        title: i18n.global.t("sidebar.work_schedule"),
        key: "lich-lam-viec",
        icon: "mdi-calendar-clock",
        value: NAME_ROUTES_CONFIG.workSchedule,
        to: { name: NAME_ROUTES_CONFIG.workSchedule },
    },
];
