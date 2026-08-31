import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import {
    getDataById,
    getDataSelect,
    getListData,
} from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
import { postData } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";

const state = {
    items: [],
    totalItems: 0,
    loading: false,
    detailsById: {},
    options: [],
    optionsLoading: false,
};

const getters = {
    items: (state) => state.items,
    totalItems: (state) => state.totalItems,
    loading: (state) => state.loading,
    itemById: (state) => (id) => state.detailsById[id] ?? null,
    options: (state) => state.options,
    optionsLoading: (state) => state.optionsLoading,
};

const mutations = {
    SET_ITEMS(state, { items, totalItems }) {
        state.items = items;
        state.totalItems = totalItems;
    },
    SET_LOADING(state, value) {
        state.loading = value;
    },
    SET_DETAIL(state, { id, data }) {
        state.detailsById = {
            ...state.detailsById,
            [id]: data,
        };
    },
    SET_OPTIONS(state, options) {
        state.options = options;
    },
    SET_OPTIONS_LOADING(state, value) {
        state.optionsLoading = value;
    },
};

const actions = {
    async fetchItems({ commit }, params = {}) {
        commit("SET_LOADING", true);

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.branch,
                params,
            );
            const items = response?.data ?? [];
            const totalItems = response?.total ?? 0;

            commit("SET_ITEMS", { items, totalItems });
            return { items, totalItems };
        } finally {
            commit("SET_LOADING", false);
        }
    },
    async fetchItemDetail({ commit, state }, { id, force = false }) {
        if (!id) {
            return null;
        }

        const cachedData = state.detailsById[id];
        if (!force && cachedData) {
            return cachedData;
        }

        const data = await getDataById(
            API_ROUTES_CONFIG.branch,
            id,
        );
        commit("SET_DETAIL", { id, data });

        return data;
    },
    async fetchOptions({ commit, state }, { force = false } = {}) {
        if (!force && state.options.length) {
            return state.options;
        }

        commit("SET_OPTIONS_LOADING", true);

        try {
            const options = (await getDataSelect(API_ROUTES_CONFIG.branch)) ?? [];
            commit("SET_OPTIONS", options);

            return options;
        } finally {
            commit("SET_OPTIONS_LOADING", false);
        }
    },
    async createItem({ dispatch }, values) {
        const response = await postData(API_ROUTES_CONFIG.branch, values);

        if (response) {
            await dispatch("fetchOptions", { force: true });
        }

        return response;
    },
    async updateItem({ dispatch }, { id, values }) {
        const response = await putData(API_ROUTES_CONFIG.branch, id, values);

        if (response) {
            await dispatch("fetchOptions", { force: true });
        }

        return response;
    },
    async deleteItem({ dispatch }, id) {
        const response = await deleteData(API_ROUTES_CONFIG.branch, id);

        if (response?.success) {
            await dispatch("fetchOptions", { force: true });
        }

        return response;
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
