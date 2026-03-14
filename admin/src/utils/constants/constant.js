import i18n from "@/plugins/i18n";

export const constant = {
    ACTIONS: [
        { key: "index", label: i18n.global.t("bo_phan.actions.index") },
        { key: "create", label: i18n.global.t("bo_phan.actions.create") },
        { key: "show", label: i18n.global.t("bo_phan.actions.show") },
        { key: "edit", label: i18n.global.t("bo_phan.actions.edit") },
        { key: "delete", label: i18n.global.t("bo_phan.actions.delete") },
        { key: "export", label: i18n.global.t("bo_phan.actions.export") },
        { key: "import", label: i18n.global.t("bo_phan.actions.import") },
        { key: "showMenu", label: i18n.global.t("bo_phan.actions.showMenu") },
    ],
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
    MAX_IMAGE_UPLOAD: 10,
    MAX_FILE_UPLOAD: 10,
};
