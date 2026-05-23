import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getAllData, getDataById, getListData } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";
import { putData, patchData } from "@/services/bases/updateData";
import { deleteData } from "@/services/bases/deleteData";

const state = {
    requestTypes: [],
    mineItems: [],
    approvalItems: [],
    approvedItems: [],
    totalMineItems: 0,
    totalApprovalItems: 0,
    totalApprovedItems: 0,
    requestDetail: null,
    requestTimeline: [],
    loading: false,
    mineLoading: false,
    approvalLoading: false,
    approvedLoading: false,
    detailLoading: false,
    timelineLoading: false,
};

const getters = {
    requestTypes: (state) => state.requestTypes,
    mineItems: (state) => state.mineItems,
    approvalItems: (state) => state.approvalItems,
    approvedItems: (state) => state.approvedItems,
    totalMineItems: (state) => state.totalMineItems,
    totalApprovalItems: (state) => state.totalApprovalItems,
    totalApprovedItems: (state) => state.totalApprovedItems,
    requestDetail: (state) => state.requestDetail,
    requestTimeline: (state) => state.requestTimeline,
    loading: (state) => state.loading,
    mineLoading: (state) => state.mineLoading,
    approvalLoading: (state) => state.approvalLoading,
    approvedLoading: (state) => state.approvedLoading,
    detailLoading: (state) => state.detailLoading,
    timelineLoading: (state) => state.timelineLoading,
};

const mutations = {
    SET_REQUEST_TYPES(state, items) {
        state.requestTypes = items;
    },
    SET_MINE_ITEMS(state, { items, total }) {
        state.mineItems = items;
        state.totalMineItems = total;
    },
    SET_APPROVAL_ITEMS(state, { items, total }) {
        state.approvalItems = items;
        state.totalApprovalItems = total;
    },
    SET_APPROVED_ITEMS(state, { items, total }) {
        state.approvedItems = items;
        state.totalApprovedItems = total;
    },
    SET_REQUEST_DETAIL(state, item) {
        state.requestDetail = item;
    },
    SET_REQUEST_TIMELINE(state, items) {
        state.requestTimeline = items;
    },
    SET_LOADING(state, value) {
        state.loading = value;
    },
    SET_MINE_LOADING(state, value) {
        state.mineLoading = value;
    },
    SET_APPROVAL_LOADING(state, value) {
        state.approvalLoading = value;
    },
    SET_APPROVED_LOADING(state, value) {
        state.approvedLoading = value;
    },
    SET_DETAIL_LOADING(state, value) {
        state.detailLoading = value;
    },
    SET_TIMELINE_LOADING(state, value) {
        state.timelineLoading = value;
    },
};

const actions = {
    async fetchRequestTypes({ commit }) {
        const items = (await getAllData(API_ROUTES_CONFIG.requestTypes)) ?? [];
        commit("SET_REQUEST_TYPES", items);
        return items;
    },
    async fetchRequests({ commit }, params = {}) {
        const isApprovalView = params.view === "approval";
        const isApprovedTab = isApprovalView && params.status === "approved";

        commit("SET_LOADING", true);
        commit(
            isApprovalView
                ? (isApprovedTab ? "SET_APPROVED_LOADING" : "SET_APPROVAL_LOADING")
                : "SET_MINE_LOADING",
            true,
        );

        try {
            const response = await getListData(API_ROUTES_CONFIG.requests, params);
            const items = response?.data ?? [];
            const total = response?.total ?? 0;

            if (isApprovedTab) {
                commit("SET_APPROVED_ITEMS", { items, total });
            } else if (isApprovalView) {
                commit("SET_APPROVAL_ITEMS", { items, total });
            } else {
                commit("SET_MINE_ITEMS", { items, total });
            }

            return { items, total };
        } finally {
            commit("SET_LOADING", false);
            commit(
                isApprovalView
                    ? (isApprovedTab ? "SET_APPROVED_LOADING" : "SET_APPROVAL_LOADING")
                    : "SET_MINE_LOADING",
                false,
            );
        }
    },
    async fetchRequestDetail({ commit }, requestId) {
        commit("SET_DETAIL_LOADING", true);

        try {
            const item = await getDataById(API_ROUTES_CONFIG.requests, requestId);
            commit("SET_REQUEST_DETAIL", item);
            return item;
        } finally {
            commit("SET_DETAIL_LOADING", false);
        }
    },
    async fetchRequestTimeline({ commit }, requestId) {
        commit("SET_TIMELINE_LOADING", true);

        try {
            const items =
                (await getDataById(API_ROUTES_CONFIG.requests, requestId, "timeline")) ?? [];
            commit("SET_REQUEST_TIMELINE", items);
            return items;
        } finally {
            commit("SET_TIMELINE_LOADING", false);
        }
    },
    async createRequest(_, values) {
        return postData(API_ROUTES_CONFIG.requests, values);
    },
    async updateRequest(_, { requestId, values }) {
        return putData(API_ROUTES_CONFIG.requests, requestId, values);
    },
    async approveRequest(_, { requestId, values }) {
        return patchData(API_ROUTES_CONFIG.requests, `${requestId}/approve`, values);
    },
    async rejectRequest(_, { requestId, values }) {
        return patchData(API_ROUTES_CONFIG.requests, `${requestId}/reject`, values);
    },
    async cancelRequest(_, requestId) {
        return patchData(API_ROUTES_CONFIG.requests, `${requestId}/cancel`, {});
    },
    async deleteRequest(_, requestId) {
        return deleteData(API_ROUTES_CONFIG.requests, requestId);
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
