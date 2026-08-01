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

export const STOCK_RECEIPT_PROVIDER_STATUS = Object.freeze({
    CREATED: "CREATED",
    AWAITING_SHIPMENT: "AWAITING_SHIPMENT",
    IN_TRANSIT: "IN_TRANSIT",
    ARRIVED: "ARRIVED",
    INSPECTING: "INSPECTING",
    COMPLETED: "COMPLETED",
    CANCELLED: "CANCELLED",
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
    CATEGORY_TABS: [
        { value: "ingredient", key: "category.tabs.ingredient" },
        { value: "finished_product", key: "category.tabs.finished_product" },
        { value: "business_product", key: "category.tabs.business_product" },
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
    DIALOG_REQUEST_FORM_WIDTH: [
        {
            type: "leave",
            width: 900,
        },
        {
            type: "stock:stock-in",
            width: 1600,
        },
        {
            type: "stock:production",
            width: 1600,
        }
    ],
    CURRENCIES: [
        { value: "VND", title: "VND (Đồng Việt Nam)" },
        { value: "USD", title: "USD (Đô la Mỹ)" },
        { value: "EUR", title: "EUR (Euro)" },
        { value: "JPY", title: "JPY (Yên Nhật)" },
        { value: "SGD", title: "SGD (Đô la Singapore)" },
    ],
    STOCK_RECEIPT_PROVIDER_STATUS_STEPS: [
        { value: "CREATED", key: "stock_receipt.provider_status.CREATED" },
        { value: "AWAITING_SHIPMENT", key: "stock_receipt.provider_status.AWAITING_SHIPMENT" },
        { value: "IN_TRANSIT", key: "stock_receipt.provider_status.IN_TRANSIT" },
        { value: "ARRIVED", key: "stock_receipt.provider_status.ARRIVED" },
        { value: "INSPECTING", key: "stock_receipt.provider_status.INSPECTING" },
        { value: "COMPLETED", key: "stock_receipt.provider_status.COMPLETED" },
    ],
    STOCK_RECEIPT_STATUS_COLORS: {
        CREATED: "grey",
        AWAITING_SHIPMENT: "info",
        IN_TRANSIT: "warning",
        ARRIVED: "info",
        INSPECTING: "warning",
        COMPLETED: "success",
        CANCELLED: "error",
        IN_PROGRESS: "warning",
        PARTIALLY_COMPLETED: "warning"
    },
    FULFILLMENT_STATUS_COLORS: {
        PENDING: "warning",
        FULL: "success",
        PARTIAL_CLOSED: "error",
        BACKORDER_OPEN: "info"
    },
    STOCK_RECEIPT_EVENT_COLORS: {
        CREATED: "primary",
        STATUS_CHANGED: "info",
        INSPECTION_STARTED: "warning",
        INSPECTION_SAVED: "warning",
        INVENTORY_POSTED: "success",
        SHORTAGE_ACCEPTED: "orange",
        BACKORDER_CREATED: "deep-purple",
        CANCELLED: "error",
        REVERSED: "error",
    },
    STOCK_RECEIPT_EVENT_ICONS: {
        CREATED: "mdi-plus-circle-outline",
        STATUS_CHANGED: "mdi-swap-horizontal",
        INSPECTION_STARTED: "mdi-clipboard-search-outline",
        INSPECTION_SAVED: "mdi-clipboard-check-outline",
        INVENTORY_POSTED: "mdi-package-down",
        SHORTAGE_ACCEPTED: "mdi-alert-circle-outline",
        BACKORDER_CREATED: "mdi-file-plus-outline",
        CANCELLED: "mdi-close-circle-outline",
        REVERSED: "mdi-undo",
    },
    STOCK_RECEIPT_EVENT_TITLE_KEYS: {
        CREATED: "stock_receipt.event.types.CREATED",
        STATUS_CHANGED: "stock_receipt.event.types.STATUS_CHANGED",
        INSPECTION_STARTED: "stock_receipt.event.types.INSPECTION_STARTED",
        INSPECTION_SAVED: "stock_receipt.event.types.INSPECTION_SAVED",
        INVENTORY_POSTED: "stock_receipt.event.types.INVENTORY_POSTED",
        SHORTAGE_ACCEPTED: "stock_receipt.event.types.SHORTAGE_ACCEPTED",
        BACKORDER_CREATED: "stock_receipt.event.types.BACKORDER_CREATED",
        CANCELLED: "stock_receipt.event.types.CANCELLED",
        REVERSED: "stock_receipt.event.types.REVERSED",
    }
};
