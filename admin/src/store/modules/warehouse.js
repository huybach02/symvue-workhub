import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getDataById, getDataSelect, getListData } from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
import { postData } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";

const state = {
    items: [],
    totalItems: 0,
    loading: false,
    detailsById: {},
    inventoryBalancesByWarehouseId: {},
    inventoryMovementsByWarehouseId: {},
    inventoryBalanceLoading: false,
    inventoryMovementLoading: false,
    options: [],
};

const getters = {
    items: (state) => state.items,
    totalItems: (state) => state.totalItems,
    loading: (state) => state.loading,
    itemById: (state) => (id) => state.detailsById[id] ?? null,
    inventoryBalancesByWarehouseId: (state) => (id) =>
        state.inventoryBalancesByWarehouseId[id] ?? {
            items: [],
            totalItems: 0,
        },
    inventoryMovementsByWarehouseId: (state) => (id) =>
        state.inventoryMovementsByWarehouseId[id] ?? {
            items: [],
            totalItems: 0,
        },
    inventoryBalanceLoading: (state) => state.inventoryBalanceLoading,
    inventoryMovementLoading: (state) => state.inventoryMovementLoading,
    options: (state) => state.options,
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
    SET_INVENTORY_BALANCES(state, { id, items, totalItems }) {
        state.inventoryBalancesByWarehouseId = {
            ...state.inventoryBalancesByWarehouseId,
            [id]: { items, totalItems },
        };
    },
    SET_INVENTORY_MOVEMENTS(state, { id, items, totalItems }) {
        state.inventoryMovementsByWarehouseId = {
            ...state.inventoryMovementsByWarehouseId,
            [id]: { items, totalItems },
        };
    },
    SET_INVENTORY_BALANCE_LOADING(state, value) {
        state.inventoryBalanceLoading = value;
    },
    SET_INVENTORY_MOVEMENT_LOADING(state, value) {
        state.inventoryMovementLoading = value;
    },
    SET_OPTIONS(state, value) {
        state.options = value;
    },
};

const actions = {
    async fetchItems({ commit }, params = {}) {
        commit("SET_LOADING", true);

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.warehouse,
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
            API_ROUTES_CONFIG.warehouse,
            id,
        );
        commit("SET_DETAIL", { id, data });

        return data;
    },
    async fetchInventoryBalances({ commit }, { id, params = {} }) {
        if (!id) {
            return { items: [], totalItems: 0 };
        }

        commit("SET_INVENTORY_BALANCE_LOADING", true);

        try {
            const response = await getListData(
                `${API_ROUTES_CONFIG.warehouse}/${id}/inventory-balances`,
                params,
            );
            const items = response?.data ?? [];
            const totalItems = response?.total ?? 0;

            commit("SET_INVENTORY_BALANCES", { id, items, totalItems });

            return { items, totalItems };
        } finally {
            commit("SET_INVENTORY_BALANCE_LOADING", false);
        }
    },
    async fetchInventoryMovements({ commit }, { id, params = {} }) {
        if (!id) {
            return { items: [], totalItems: 0 };
        }

        commit("SET_INVENTORY_MOVEMENT_LOADING", true);

        try {
            const response = await getListData(
                `${API_ROUTES_CONFIG.warehouse}/${id}/inventory-movements`,
                params,
            );
            const items = response?.data ?? [];
            const totalItems = response?.total ?? 0;

            commit("SET_INVENTORY_MOVEMENTS", { id, items, totalItems });

            return { items, totalItems };
        } finally {
            commit("SET_INVENTORY_MOVEMENT_LOADING", false);
        }
    },
    async createItem(_, values) {
        return postData(API_ROUTES_CONFIG.warehouse, values);
    },
    async updateItem(_, { id, values }) {
        return putData(API_ROUTES_CONFIG.warehouse, id, values);
    },
    async deleteItem(_, id) {
        return deleteData(API_ROUTES_CONFIG.warehouse, id);
    },
    async fetchOptions({ commit }) {
        const options = await getDataSelect(API_ROUTES_CONFIG.warehouse);
        commit("SET_OPTIONS", options || []);
        return options || [];
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
