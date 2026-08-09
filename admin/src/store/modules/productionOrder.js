import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getAllData, getDataById, getListData } from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
import { postData } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";

const state = {
    items: [],
    totalItems: 0,
    loading: false,
    detailsById: {},
    openShortages: [],
};

const getters = {
    items: (state) => state.items,
    totalItems: (state) => state.totalItems,
    loading: (state) => state.loading,
    itemById: (state) => (id) => state.detailsById[id] ?? null,
    openShortages: (state) => state.openShortages,
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
    SET_OPEN_SHORTAGES(state, items) {
        state.openShortages = items;
    },
};

const actions = {
    async fetchItems({ commit }, params = {}) {
        commit("SET_LOADING", true);

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.productionOrder,
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
            API_ROUTES_CONFIG.productionOrder,
            id,
        );
        commit("SET_DETAIL", { id, data });

        return data;
    },
    async updateItemStatus(_, { id, status }) {
        return putData(
            API_ROUTES_CONFIG.productionOrder + "/item/" + id + "/status",
            null,
            { status },
        );
    },
    async fetchOpenShortages({ commit }, merchandiseId) {
        const items = await getAllData(
            API_ROUTES_CONFIG.productionOrder + "/open-shortages",
            { merchandiseId },
        );
        commit("SET_OPEN_SHORTAGES", Array.isArray(items) ? items : []);
        return Array.isArray(items) ? items : [];
    },
    async inspectItem(_, { id, values }) {
        return postData(
            API_ROUTES_CONFIG.productionOrder + "/item/" + id + "/inspect",
            values,
        );
    },
    async createItem(_, values) {
        return postData(API_ROUTES_CONFIG.productionOrder, values);
    },
    async updateItem(_, { id, values }) {
        return putData(API_ROUTES_CONFIG.productionOrder, id, values);
    },
    async deleteItem(_, id) {
        return deleteData(API_ROUTES_CONFIG.productionOrder, id);
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
