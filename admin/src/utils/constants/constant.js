import i18n from "@/plugins/i18n";

export const constant = {
    GENDER: [
        { value: "male", key: "gender.male" },
        { value: "female", key: "gender.female" },
    ],
    STATUS: [
        { value: 1, key: "status.active" },
        { value: 0, key: "status.inactive" },
    ],
    REQUEST_STATUS: [
        { value: "pending", key: "request.status.pending", color: "warning" },
        { value: "rejected", key: "request.status.rejected", color: "error" },
        { value: "approved", key: "request.status.approved", color: "success" },
        { value: "cancelled", key: "request.status.cancelled", color: "grey" },
    ],
    ROUTE_PUBLIC: ["dashboard", "profile", "import-history"],
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
    MAX_IMAGE_UPLOAD: 10,
    MAX_FILE_UPLOAD: 10,
    CURRENCY_OPTIONS: [
        { value: "VND", title: "VND" },
        { value: "USD", title: "USD" },
        { value: "EUR", title: "EUR" },
    ],
    EMPLOYMENT_TYPE_OPTIONS: [
        {
            value: "FULL_TIME",
            text: i18n.global.t("bo_phan.employmentType.fullTime"),
        },
        {
            value: "PART_TIME",
            text: i18n.global.t("bo_phan.employmentType.partTime"),
        },
        {
            value: "INTERN",
            text: i18n.global.t("bo_phan.employmentType.intern"),
        },
        {
            value: "CONTRACTOR",
            text: i18n.global.t("bo_phan.employmentType.contractor"),
        },
    ],
};
