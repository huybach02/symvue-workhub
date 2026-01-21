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
        // Menu có children sẽ không có thuộc tính 'to'
        children: [
            {
                title: "Danh sách nhân viên",
                icon: "mdi-account-multiple",
                value: "employee-list",
                to: "/account/employees",
            },
            {
                title: "Phòng ban",
                icon: "mdi-office-building",
                value: "departments",
                to: "/account/departments",
            },
            {
                title: "Chức vụ",
                icon: "mdi-account-tie",
                value: "positions",
                to: "/account/positions",
            },
        ],
    },
    {
        title: "Users",
        icon: "mdi-account-group-outline",
        value: "users",
        to: "/users",
    },
];
