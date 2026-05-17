import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getAllData } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";

const state = {
    fulltimeList: [],
    fulltimeById: {},
    parttimeShiftsByWorkingTime: {},
    fulltimeLoading: false,
    parttimeLoadingByWorkingTime: {},
    saving: false,
};

const getters = {
    fulltimeList: (state) => state.fulltimeList,
    fulltimeById: (state) => (id) => state.fulltimeById[id] ?? null,
    parttimeShiftsByWorkingTime: (state) => (workingTimeId) =>
        state.parttimeShiftsByWorkingTime[workingTimeId] ?? [],
    fulltimeLoading: (state) => state.fulltimeLoading,
    parttimeLoadingByWorkingTime: (state) => (workingTimeId) =>
        Boolean(state.parttimeLoadingByWorkingTime[workingTimeId]),
    saving: (state) => state.saving,
};

const mutations = {
    SET_FULLTIME_LIST(state, data) {
        state.fulltimeList = data;
    },
    SET_FULLTIME_DETAIL(state, { id, data }) {
        state.fulltimeById = {
            ...state.fulltimeById,
            [id]: data,
        };
    },
    SET_PARTTIME_SHIFTS(state, { workingTimeId, data }) {
        state.parttimeShiftsByWorkingTime = {
            ...state.parttimeShiftsByWorkingTime,
            [workingTimeId]: data,
        };
    },
    SET_FULLTIME_LOADING(state, value) {
        state.fulltimeLoading = value;
    },
    SET_PARTTIME_LOADING(state, { workingTimeId, value }) {
        state.parttimeLoadingByWorkingTime = {
            ...state.parttimeLoadingByWorkingTime,
            [workingTimeId]: value,
        };
    },
    SET_SAVING(state, value) {
        state.saving = value;
    },
};

const actions = {
    async fetchFulltimeList({ commit }) {
        commit("SET_FULLTIME_LOADING", true);

        try {
            const data =
                (await getAllData(API_ROUTES_CONFIG.thoiGianLamViec, {
                    type: "fulltime",
                })) ?? [];
            commit("SET_FULLTIME_LIST", data);

            return data;
        } finally {
            commit("SET_FULLTIME_LOADING", false);
        }
    },
    async fetchFulltimeDetail({ commit, state }, { id, force = false }) {
        if (!id) {
            return null;
        }

        const cachedData = state.fulltimeById[id];
        if (!force && cachedData) {
            return cachedData;
        }

        const data = await getAllData(API_ROUTES_CONFIG.thoiGianLamViec, {
            type: "fulltime",
            id,
        });
        commit("SET_FULLTIME_DETAIL", { id, data });

        return data;
    },
    async updateFulltime({ commit, dispatch }, { id, values }) {
        commit("SET_SAVING", true);

        try {
            const response = await putData(
                `${API_ROUTES_CONFIG.thoiGianLamViec}?type=fulltime&id=${id}`,
                null,
                values,
            );

            if (response) {
                commit("SET_FULLTIME_DETAIL", { id, data: response });
                await dispatch("fetchFulltimeList");
            }

            return response;
        } finally {
            commit("SET_SAVING", false);
        }
    },
    async fetchParttimeShifts(
        { commit, state },
        { workingTimeId, force = false },
    ) {
        if (!workingTimeId) {
            return [];
        }

        const cachedData = state.parttimeShiftsByWorkingTime[workingTimeId];
        if (!force && cachedData) {
            return cachedData;
        }

        commit("SET_PARTTIME_LOADING", { workingTimeId, value: true });

        try {
            const data =
                (await getAllData(API_ROUTES_CONFIG.thoiGianLamViec, {
                    type: "parttime",
                    thoiGianLamViecId: workingTimeId,
                })) ?? [];
            commit("SET_PARTTIME_SHIFTS", { workingTimeId, data });

            return data;
        } finally {
            commit("SET_PARTTIME_LOADING", { workingTimeId, value: false });
        }
    },
    async createParttimeShift({ commit, dispatch }, values) {
        commit("SET_SAVING", true);

        try {
            const response = await postData(
                API_ROUTES_CONFIG.thoiGianLamViec,
                values,
            );

            if (response) {
                await dispatch("fetchParttimeShifts", {
                    workingTimeId: values.thoiGianLamViecId,
                    force: true,
                });
            }

            return response;
        } finally {
            commit("SET_SAVING", false);
        }
    },
    async updateParttimeShift({ commit, dispatch }, { id, values }) {
        commit("SET_SAVING", true);

        try {
            const response = await putData(
                `${API_ROUTES_CONFIG.thoiGianLamViec}/ca-lam-viec?id=${id}`,
                null,
                values,
            );

            if (response) {
                await dispatch("fetchParttimeShifts", {
                    workingTimeId: values.thoiGianLamViecId,
                    force: true,
                });
            }

            return response;
        } finally {
            commit("SET_SAVING", false);
        }
    },
    async updateParttimeShiftStatus(
        { commit, dispatch },
        { id, workingTimeId, status },
    ) {
        commit("SET_SAVING", true);

        try {
            const response = await putData(
                `${API_ROUTES_CONFIG.thoiGianLamViec}/ca-lam-viec/status?id=${id}`,
                null,
                { status },
            );

            if (response) {
                await dispatch("fetchParttimeShifts", {
                    workingTimeId,
                    force: true,
                });
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
