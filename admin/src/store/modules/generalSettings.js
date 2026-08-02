import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getAllData } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";

const defaultValues = {
    soLanDangNhapSai: 0,
    thoiGianTamKhoaTaiKhoan: 0,
    xacThuc2YeuTo: false,
    thoiGianHetHanMaOtp: 0,
    thoiHanXacThucLaiThietBi: 0,
    kiemTraThoiGianLamViec: false,
    soThietBiDangNhapToiDa: 0,
    checkInGraceMinutes: 0,
    lateLimitMinutes: 0,
    checkInEarliestMinutes: 0,
    checkOutGraceMinutes: 0,
    checkOutLatestMinutes: 0,
    latitude: 0,
    longitude: 0,
    radiusMeters: 0,
    addressDisplay: "",
    ipAddress: "",
    qrTtlSeconds: 0,
    photoRetentionDays: 0,
    maxDevicesPerEmployee: 0,
    sameDeviceMaxEmployees: 0,
    remindMissingCheckIn: false,
    remindMissingCheckOut: false,
    checkInReminderMinutesBefore: 0,
    checkOutReminderMinutesBefore: 0,
    currency: "VND",
    receiveFromProviderWarehouseId: "",
    productionMaterialWarehouseId: "",
    productionFinishedGoodsWarehouseId: "",
};

const configMapping = {
    SO_LAN_DANG_NHAP_SAI_TOI_DA: "soLanDangNhapSai",
    THOI_GIAN_KHOA_TAI_KHOAN: "thoiGianTamKhoaTaiKhoan",
    XAC_THUC_2_YEU_TO: "xacThuc2YeuTo",
    THOI_GIAN_HET_HAN_OTP: "thoiGianHetHanMaOtp",
    THOI_HAN_XAC_THUC_LAI_THIET_BI: "thoiHanXacThucLaiThietBi",
    CHECK_THOI_GIAN_LAM_VIEC: "kiemTraThoiGianLamViec",
    SO_THIET_BI_DANG_NHAP_TOI_DA: "soThietBiDangNhapToiDa",
    CHECK_IN_GRACE_MINUTES: "checkInGraceMinutes",
    LATE_LIMIT_MINUTES: "lateLimitMinutes",
    CHECK_IN_EARLIEST_MINUTES: "checkInEarliestMinutes",
    CHECK_OUT_GRACE_MINUTES: "checkOutGraceMinutes",
    CHECK_OUT_LATEST_MINUTES: "checkOutLatestMinutes",
    LATITUDE: "latitude",
    LONGITUDE: "longitude",
    RADIUS_METERS: "radiusMeters",
    ADDRESS_DISPLAY: "addressDisplay",
    IP_ADDRESS: "ipAddress",
    QR_TTL_SECONDS: "qrTtlSeconds",
    PHOTO_RETENTION_DAYS: "photoRetentionDays",
    MAX_DEVICES_PER_EMPLOYEE: "maxDevicesPerEmployee",
    SAME_DEVICE_MAX_EMPLOYEES: "sameDeviceMaxEmployees",
    REMIND_MISSING_CHECK_IN: "remindMissingCheckIn",
    REMIND_MISSING_CHECK_OUT: "remindMissingCheckOut",
    CHECK_IN_REMINDER_MINUTES_BEFORE: "checkInReminderMinutesBefore",
    CHECK_OUT_REMINDER_MINUTES_BEFORE: "checkOutReminderMinutesBefore",
    CURRENCY: "currency",
    RECEIVE_FROM_PROVIDER_WAREHOUSE_ID: "receiveFromProviderWarehouseId",
    PRODUCTION_MATERIAL_WAREHOUSE_ID: "productionMaterialWarehouseId",
    PRODUCTION_FINISHED_GOODS_WAREHOUSE_ID:
        "productionFinishedGoodsWarehouseId",
};

const booleanFields = [
    "xacThuc2YeuTo",
    "kiemTraThoiGianLamViec",
    "remindMissingCheckIn",
    "remindMissingCheckOut",
];

const stringFields = [
    "addressDisplay",
    "ipAddress",
    "currency",
    "receiveFromProviderWarehouseId",
    "productionMaterialWarehouseId",
    "productionFinishedGoodsWarehouseId",
];

const decimalFields = ["latitude", "longitude"];

const mapConfigToValues = (settings = []) => {
    const values = { ...defaultValues };

    settings.forEach((item) => {
        const key = configMapping[item.tenCauHinh];

        if (!key) {
            return;
        }

        if (booleanFields.includes(key)) {
            values[key] = item.giaTri === "1";
        } else if (stringFields.includes(key)) {
            values[key] = item.giaTri ?? "";
        } else if (decimalFields.includes(key)) {
            values[key] = parseFloat(item.giaTri);
        } else {
            values[key] = parseInt(item.giaTri);
        }
    });

    return values;
};

const state = {
    settings: [],
    initialValues: { ...defaultValues },
    dataLoaded: false,
    loading: false,
    saving: false,
};

const getters = {
    settings: (state) => state.settings,
    initialValues: (state) => state.initialValues,
    dataLoaded: (state) => state.dataLoaded,
    loading: (state) => state.loading,
    saving: (state) => state.saving,
    currency: (state) => state.initialValues.currency || "VND",
};

const mutations = {
    SET_SETTINGS(state, settings) {
        state.settings = settings;
        state.initialValues = mapConfigToValues(settings);
        state.dataLoaded = true;
    },
    SET_INITIAL_VALUES(state, values) {
        state.initialValues = {
            ...defaultValues,
            ...values,
        };
    },
    SET_LOADING(state, value) {
        state.loading = value;
    },
    SET_SAVING(state, value) {
        state.saving = value;
    },
};

const actions = {
    async fetchSettings({ commit }) {
        commit("SET_LOADING", true);

        try {
            const settings =
                (await getAllData(API_ROUTES_CONFIG.generalSettings)) ?? [];
            commit("SET_SETTINGS", settings);

            return settings;
        } finally {
            commit("SET_LOADING", false);
        }
    },
    async updateSettings({ commit }, values) {
        commit("SET_SAVING", true);

        try {
            const response = await postData(
                API_ROUTES_CONFIG.generalSettings,
                values,
            );

            if (response) {
                commit("SET_INITIAL_VALUES", values);
            }

            return response;
        } finally {
            commit("SET_SAVING", false);
        }
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
