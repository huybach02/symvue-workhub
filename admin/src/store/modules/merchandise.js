import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getDataById, getListData, getDataSelect } from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
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
                API_ROUTES_CONFIG.merchandise,
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

        const data = await getDataById(API_ROUTES_CONFIG.merchandise, id);
        commit("SET_DETAIL", { id, data });

        return data;
    },
    async createItem(_, values) {
        return postData(API_ROUTES_CONFIG.merchandise, values);
    },
    async updateItem(_, { id, values }) {
        return putData(API_ROUTES_CONFIG.merchandise, id, values);
    },
    async deleteItem(_, id) {
        return deleteData(API_ROUTES_CONFIG.merchandise, id);
    },
    async fetchIngredientsOptions() {
        return getDataSelect(API_ROUTES_CONFIG.merchandise, {
            f: [
                {
                    field: "type",
                    operator: "equal",
                    value: "ingredient",
                },
            ],
        });
    },
    async fetchAllMerchandiseOptions() {
        return getDataSelect(API_ROUTES_CONFIG.merchandise);
    },
    /**
     * Merchandise đủ điều kiện nhập kho:
     * - type = ingredient
     * - hoặc type = finished_product + finishedProductSource = supplier
     */
    async fetchStockInMerchandiseOptions() {
        const [ingredients, finishedProducts] = await Promise.all([
            getDataSelect(API_ROUTES_CONFIG.merchandise, {
                f: [
                    {
                        field: "type",
                        operator: "equal",
                        value: "ingredient",
                    },
                ],
            }),
            getDataSelect(API_ROUTES_CONFIG.merchandise, {
                f: [
                    {
                        field: "type",
                        operator: "equal",
                        value: "finished_product",
                    },
                    {
                        field: "finishedProductSource",
                        operator: "equal",
                        value: "supplier",
                    },
                ],
            }),
        ]);

        const mergeById = new Map();

        [...(ingredients || []), ...(finishedProducts || [])].forEach((item) => {
            if (item?.id != null) {
                mergeById.set(item.id, item);
            }
        });

        return Array.from(mergeById.values());
    },
    /**
     * Thành phẩm sản xuất nội bộ:
     * - type = finished_product + finishedProductSource = production
     */
    async fetchProductionMerchandiseOptions() {
        return getDataSelect(API_ROUTES_CONFIG.merchandise, {
            f: [
                {
                    field: "type",
                    operator: "equal",
                    value: "finished_product",
                },
                {
                    field: "finishedProductSource",
                    operator: "equal",
                    value: "production",
                },
            ],
        });
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
