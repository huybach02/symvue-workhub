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
};

const configMapping = {
    SO_LAN_DANG_NHAP_SAI_TOI_DA: "soLanDangNhapSai",
    THOI_GIAN_KHOA_TAI_KHOAN: "thoiGianTamKhoaTaiKhoan",
    XAC_THUC_2_YEU_TO: "xacThuc2YeuTo",
    THOI_GIAN_HET_HAN_OTP: "thoiGianHetHanMaOtp",
    THOI_HAN_XAC_THUC_LAI_THIET_BI: "thoiHanXacThucLaiThietBi",
    CHECK_THOI_GIAN_LAM_VIEC: "kiemTraThoiGianLamViec",
    SO_THIET_BI_DANG_NHAP_TOI_DA: "soThietBiDangNhapToiDa",
};

const booleanFields = ["xacThuc2YeuTo", "kiemTraThoiGianLamViec"];

const mapConfigToValues = (settings = []) => {
    const values = { ...defaultValues };

    settings.forEach((item) => {
        const key = configMapping[item.tenCauHinh];

        if (!key) {
            return;
        }

        values[key] = booleanFields.includes(key)
            ? item.giaTri === "1"
            : parseInt(item.giaTri);
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
                (await getAllData(API_ROUTES_CONFIG.cauHinhChung)) ?? [];
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
                API_ROUTES_CONFIG.cauHinhChung,
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
