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
                icon: "mdi-domain",
                value: NAME_ROUTES_CONFIG.department,
                to: { name: NAME_ROUTES_CONFIG.department },
            },
            {
                title: i18n.global.t("sidebar.branch"),
                icon: "mdi-store-outline",
                value: NAME_ROUTES_CONFIG.branch,
                to: { name: NAME_ROUTES_CONFIG.branch },
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
        title: i18n.global.t("sidebar.goods_management"),
        icon: "mdi-cube",
        value: NAME_ROUTES_CONFIG.goodsManagement,
        children: [
            {
                title: i18n.global.t("sidebar.category"),
                icon: "mdi-format-list-bulleted",
                value: NAME_ROUTES_CONFIG.category,
                to: { name: NAME_ROUTES_CONFIG.category },
            },
            {
                title: i18n.global.t("sidebar.unit"),
                icon: "mdi-scale",
                value: NAME_ROUTES_CONFIG.unit,
                to: { name: NAME_ROUTES_CONFIG.unit },
            },
            {
                title: i18n.global.t("sidebar.provider"),
                icon: "mdi-truck-delivery-outline",
                value: NAME_ROUTES_CONFIG.provider,
                to: { name: NAME_ROUTES_CONFIG.provider },
            },
            {
                title: i18n.global.t("sidebar.merchandise"),
                icon: "mdi-package-variant-closed",
                value: NAME_ROUTES_CONFIG.merchandise,
                to: { name: NAME_ROUTES_CONFIG.merchandise },
            },
            {
                title: i18n.global.t("sidebar.business_product"),
                icon: "mdi-basket-check-outline",
                value: NAME_ROUTES_CONFIG.businessProduct,
                to: { name: NAME_ROUTES_CONFIG.businessProduct },
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
        icon: "mdi-file-document-edit",
        value: NAME_ROUTES_CONFIG.requests,
        to: { name: NAME_ROUTES_CONFIG.requests },
    },
    {
        title: i18n.global.t("sidebar.attendance"),
        icon: "mdi-account-check",
        value: NAME_ROUTES_CONFIG.attendance,
        to: { name: NAME_ROUTES_CONFIG.attendance },
    },

    {
        title: i18n.global.t("sidebar.warehouse_management"),
        icon: "mdi-warehouse",
        value: "warehouse_management",
        children: [
            {
                title: i18n.global.t("sidebar.warehouse"),
                icon: "mdi-home-floor-g",
                value: NAME_ROUTES_CONFIG.warehouse,
                to: { name: NAME_ROUTES_CONFIG.warehouse },
            },
            {
                title: i18n.global.t("sidebar.stock_receipt"),
                icon: "mdi-file-import-outline",
                value: NAME_ROUTES_CONFIG.stockReceipt,
                to: { name: NAME_ROUTES_CONFIG.stockReceipt },
            },
        ],
    },
    {
        title: i18n.global.t("sidebar.production_order"),
        icon: "mdi-view-dashboard",
        value: NAME_ROUTES_CONFIG.productionOrder,
        to: { name: NAME_ROUTES_CONFIG.productionOrder },
    },
];
