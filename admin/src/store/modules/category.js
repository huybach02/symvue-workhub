import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getDataById, getListData } from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
import { postData } from "@/services/bases/postData";
import { patchData, putData } from "@/services/bases/updateData";

const state = {
    items: [],
    totalItems: 0,
    loading: false,
    detailsById: {},
};

const getters = {
    items: (state) => state.items,
    totalItems: (state) => state.totalItems,
    loading: (state) => state.loading,
    itemById: (state) => (id) => state.detailsById[id] ?? null,
};

const mutations = {
    SET_ITEMS(state, { items, totalItems }) {
        state.items = items;
        state.totalItems = totalItems;
    },
    CLEAR_ITEMS(state) {
        state.items = [];
        state.totalItems = 0;
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
};

const actions = {
    async fetchItems({ commit }, params = {}) {
        commit("SET_LOADING", true);
        commit("CLEAR_ITEMS");

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.category,
                params,
            );
            const items = Array.isArray(response)
                ? response
                : (response?.data ?? []);
            const totalItems = response?.total ?? items.length;

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
            API_ROUTES_CONFIG.category,
            id,
        );
        commit("SET_DETAIL", { id, data });

        return data;
    },
    async createItem(_, values) {
        return postData(API_ROUTES_CONFIG.category, values);
    },
    async updateItem(_, { id, values }) {
        return putData(API_ROUTES_CONFIG.category, id, values);
    },
    async deleteItem(_, id) {
        return deleteData(API_ROUTES_CONFIG.category, id);
    },
    async moveItem({ dispatch }, { id, payload, params = {} }) {
        await patchData(API_ROUTES_CONFIG.category + "/move", id, payload);
        await dispatch("fetchItems", params);
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
