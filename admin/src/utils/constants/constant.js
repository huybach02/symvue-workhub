export const constant = {
    GENDER: [
        { value: "male", key: "gender.male" },
        { value: "female", key: "gender.female" },
    ],
    STATUS: [
        { value: 1, key: "status.active" },
        { value: 0, key: "status.inactive" },
    ],
    ROUTE_PUBLIC: ["dashboard", "profile", "lich-su-import"],
    SEND_TO_OPTIONS: [
        { value: "all", key: "thong_bao.options.sendTo.all" },
        { value: "department", key: "thong_bao.options.sendTo.department" },
        { value: "user", key: "thong_bao.options.sendTo.user" },
    ],
    TYPE_THONG_BAO: [
        { value: "primary", key: "thong_bao.options.type.primary" },
        { value: "info", key: "thong_bao.options.type.info" },
        { value: "warning", key: "thong_bao.options.type.warning" },
        { value: "error", key: "thong_bao.options.type.error" },
    ],
};
