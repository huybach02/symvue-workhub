import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getListData } from "@/services/bases/getData";

const state = {
    items: [],
    totalItems: 0,
    loading: false,
};

const getters = {
    items: (state) => state.items,
    totalItems: (state) => state.totalItems,
    loading: (state) => state.loading,
};

const mutations = {
    SET_ITEMS(state, { items, totalItems }) {
        state.items = items;
        state.totalItems = totalItems;
    },
    SET_LOADING(state, value) {
        state.loading = value;
    },
};

const actions = {
    async fetchImportHistory({ commit }, params = {}) {
        commit("SET_LOADING", true);

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.importHistory,
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
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
