import i18n from "@/plugins/i18n";

export const ATTENDANCE_SECTION_KEYS = Object.freeze({
    FULL_TIME: "FULL_TIME",
    PART_TIME: "PART_TIME",
});

export const ATTENDANCE_TYPES = Object.freeze({
    CHECK_IN: "check_in",
    CHECK_OUT: "check_out",
});

export const ATTENDANCE_STATUS = Object.freeze({
    SCHEDULED: "scheduled",
});

export const ATTENDANCE_UI = Object.freeze({
    actions: [
        {
            key: "check_in",
            recordKey: "checkIn",
            labelKey: "attendance.labels.check_in",
            color: "success",
            icon: "mdi-login",
        },
        {
            key: "check_out",
            recordKey: "checkOut",
            labelKey: "attendance.labels.check_out",
            color: "warning",
            icon: "mdi-logout",
        },
    ],
    sections: [
        {
            key: ATTENDANCE_SECTION_KEYS.FULL_TIME,
            titleKey: "attendance.sections.full_time",
            icon: "mdi-briefcase-clock-outline",
        },
        {
            key: ATTENDANCE_SECTION_KEYS.PART_TIME,
            titleKey: "attendance.sections.part_time",
            icon: "mdi-calendar-clock-outline",
        },
    ],
    defaultFilterParams: {
        limit: -1,
        realtime_view: 1,
        sort_column: "id",
        sort_direction: "desc",
    },
    shiftWrapperProps: {
        partTime: {
            rounded: "md",
            class: "ma-1",
            elevation: 2,
        },
    },
    status: {
        labels: {
            undefined: "attendance.status.unknown",
            null: "attendance.status.unknown",
            scheduled: "attendance.status.scheduled",
            on_time: "attendance.status.on_time",
            late: "attendance.status.late",
            early_leave: "attendance.status.early_leave",
            absent: "attendance.status.absent",
            late_check_out: "attendance.status.late_check_out",
            early_check_in: "attendance.status.early_check_in",
        },
        colors: {
            undefined: "grey",
            null: "grey",
            scheduled: "grey",
            on_time: "success",
            late: "warning",
            early_leave: "orange",
            absent: "error",
            late_check_out: "warning",
            early_check_in: "deep-orange",
        },
    },
});

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
    ],
};
