import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import {
    getAllData,
    getDataById,
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
    childrenByParentId: {},
    loadingParentIds: [],
};

const getters = {
    items: (state) => state.items,
    totalItems: (state) => state.totalItems,
    loading: (state) => state.loading,
    itemById: (state) => (id) => state.detailsById[id] ?? null,
    childrenByParentId: (state) => state.childrenByParentId,
    childrenOf: (state) => (parentId) =>
        state.childrenByParentId[parentId] ?? [],
    isChildLoading: (state) => (parentId) =>
        state.loadingParentIds.includes(parentId),
};

const mutations = {
    SET_ITEMS(state, { items, totalItems }) {
        state.items = items;
        state.totalItems = totalItems;
        state.childrenByParentId = {};
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
    SET_CHILDREN(state, { parentId, children }) {
        state.childrenByParentId = {
            ...state.childrenByParentId,
            [parentId]: children,
        };
    },
    ADD_LOADING_PARENT(state, parentId) {
        if (!state.loadingParentIds.includes(parentId)) {
            state.loadingParentIds.push(parentId);
        }
    },
    REMOVE_LOADING_PARENT(state, parentId) {
        state.loadingParentIds = state.loadingParentIds.filter(
            (id) => id !== parentId,
        );
    },
};

const actions = {
    async fetchItems({ commit }, params = {}) {
        commit("SET_LOADING", true);

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.stockReceipt,
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
    async fetchChildren({ commit }, parentId) {
        if (!parentId) {
            return [];
        }

        commit("ADD_LOADING_PARENT", parentId);
        try {
            const children = await getAllData(
                `${API_ROUTES_CONFIG.stockReceipt}/${parentId}/children`,
            );

            commit("SET_CHILDREN", {
                parentId,
                children: children ?? [],
            });

            return children ?? [];
        } finally {
            commit("REMOVE_LOADING_PARENT", parentId);
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
            API_ROUTES_CONFIG.stockReceipt,
            id,
        );
        commit("SET_DETAIL", { id, data });

        return data;
    },
    async createItem(_, values) {
        return postData(API_ROUTES_CONFIG.stockReceipt, values);
    },
    async updateItem(_, { id, values }) {
        return putData(API_ROUTES_CONFIG.stockReceipt, id, values);
    },
    async deleteItem(_, id) {
        return deleteData(API_ROUTES_CONFIG.stockReceipt, id);
    },
    async updateProviderStatus(_, { id, status }) {
        return putData(`${API_ROUTES_CONFIG.stockReceipt}/provider/${id}/status`, null, { status });
    },
    async inspectProviderAction(_, { id, values }) {
        return postData(`${API_ROUTES_CONFIG.stockReceipt}/inspecting`, {
            ...values,
            providerId: id,
        });
    },
    async fetchInspectionOptions(_, providerId) {
        if (!providerId) {
            return [];
        }

        return (
            (await getAllData(
                `${API_ROUTES_CONFIG.stockReceipt}/provider/${providerId}/inspection-options`,
            )) || []
        );
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
