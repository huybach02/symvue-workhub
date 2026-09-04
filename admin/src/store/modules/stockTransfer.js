import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getAllData } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";

const state = {
    context: {
        sourceWarehouse: null,
        destinationWarehouses: [],
        balances: [],
    },
    contextLoading: false,
};

const getters = {
    context: (state) => state.context,
    contextLoading: (state) => state.contextLoading,
};

const mutations = {
    SET_CONTEXT(state, context) {
        state.context = {
            sourceWarehouse: context?.sourceWarehouse ?? null,
            destinationWarehouses: context?.destinationWarehouses ?? [],
            balances: context?.balances ?? [],
        };
    },
    SET_CONTEXT_LOADING(state, value) {
        state.contextLoading = value;
    },
};

const actions = {
    async fetchContext({ commit }) {
        commit("SET_CONTEXT_LOADING", true);
        try {
            const context = await getAllData(
                API_ROUTES_CONFIG.stockTransfer + "/context",
            );
            commit("SET_CONTEXT", context);
            return context;
        } finally {
            commit("SET_CONTEXT_LOADING", false);
        }
    },
    async execute(_, requestId) {
        return postData(API_ROUTES_CONFIG.stockTransfer, { requestId });
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
