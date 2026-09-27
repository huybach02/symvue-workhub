import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getDataById, getListData } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";

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
    PREPEND_ITEM(state, item) {
        if (!item || !item.id) return;
        const exists = state.items.some(
            (existing) => Number(existing.id) === Number(item.id),
        );
        if (!exists) {
            state.items = [item, ...state.items];
            state.totalItems = (state.totalItems || 0) + 1;
        }
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

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.saleOrder,
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
            API_ROUTES_CONFIG.saleOrder,
            id,
        );
        commit("SET_DETAIL", { id, data });

        return data;
    },
    async createItem(_, values) {
        return postData(API_ROUTES_CONFIG.saleOrder, values);
    },
    async updateItem(_, { id, values }) {
        return putData(API_ROUTES_CONFIG.saleOrder, id, values);
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
