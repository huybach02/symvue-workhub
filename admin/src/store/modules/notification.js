import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getDataById, getDataSelect, getListData } from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
import { postData } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";

const state = {
    notifications: [],
    totalItems: 0,
    loading: false,
    notificationDetailsById: {},
    departmentOptions: [],
    departmentOptionsLoading: false,
    activeUserOptions: [],
    activeUserOptionsLoading: false,
};

const getters = {
    notifications: (state) => state.notifications,
    totalItems: (state) => state.totalItems,
    loading: (state) => state.loading,
    notificationDetailById: (state) => (notificationId) =>
        state.notificationDetailsById[notificationId] ?? null,
    departmentOptions: (state) => state.departmentOptions,
    departmentOptionsLoading: (state) => state.departmentOptionsLoading,
    activeUserOptions: (state) => state.activeUserOptions,
    activeUserOptionsLoading: (state) => state.activeUserOptionsLoading,
};

const mutations = {
    SET_NOTIFICATIONS(state, { notifications, totalItems }) {
        state.notifications = notifications;
        state.totalItems = totalItems;
    },
    SET_LOADING(state, value) {
        state.loading = value;
    },
    SET_NOTIFICATION_DETAIL(state, { notificationId, data }) {
        state.notificationDetailsById = {
            ...state.notificationDetailsById,
            [notificationId]: data,
        };
    },
    SET_DEPARTMENT_OPTIONS(state, departmentOptions) {
        state.departmentOptions = departmentOptions;
    },
    SET_DEPARTMENT_OPTIONS_LOADING(state, value) {
        state.departmentOptionsLoading = value;
    },
    SET_ACTIVE_USER_OPTIONS(state, activeUserOptions) {
        state.activeUserOptions = activeUserOptions;
    },
    SET_ACTIVE_USER_OPTIONS_LOADING(state, value) {
        state.activeUserOptionsLoading = value;
    },
};

const actions = {
    async fetchNotifications({ commit }, params = {}) {
        commit("SET_LOADING", true);

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.notifications,
                params,
            );
            const notifications = response?.data ?? [];
            const totalItems = response?.total ?? 0;

            commit("SET_NOTIFICATIONS", { notifications, totalItems });
            return { notifications, totalItems };
        } finally {
            commit("SET_LOADING", false);
        }
    },
    async fetchNotificationDetail({ commit, state }, { notificationId, force = false }) {
        if (!notificationId) {
            return null;
        }

        const cachedData = state.notificationDetailsById[notificationId];
        if (!force && cachedData) {
            return cachedData;
        }

        const data = await getDataById(
            API_ROUTES_CONFIG.notifications,
            notificationId,
        );
        commit("SET_NOTIFICATION_DETAIL", { notificationId, data });

        return data;
    },
    async createNotification(_, values) {
        return postData(API_ROUTES_CONFIG.notifications, values);
    },
    async updateNotification(_, { notificationId, values }) {
        return putData(
            API_ROUTES_CONFIG.notifications,
            notificationId,
            values,
        );
    },
    async deleteNotification(_, notificationId) {
        return deleteData(API_ROUTES_CONFIG.notifications, notificationId);
    },
    async fetchDepartmentOptions({ commit, state }, { force = false } = {}) {
        if (!force && state.departmentOptions.length) {
            return state.departmentOptions;
        }

        commit("SET_DEPARTMENT_OPTIONS_LOADING", true);

        try {
            const departmentOptions =
                (await getDataSelect(API_ROUTES_CONFIG.department)) ?? [];
            commit("SET_DEPARTMENT_OPTIONS", departmentOptions);

            return departmentOptions;
        } finally {
            commit("SET_DEPARTMENT_OPTIONS_LOADING", false);
        }
    },
    async fetchActiveUserOptions({ commit, state }, { force = false } = {}) {
        if (!force && state.activeUserOptions.length) {
            return state.activeUserOptions;
        }

        commit("SET_ACTIVE_USER_OPTIONS_LOADING", true);

        try {
            const activeUserOptions =
                (await getDataSelect(API_ROUTES_CONFIG.users, {
                    f: [
                        {
                            field: "status",
                            operator: "equal",
                            value: 1,
                        },
                    ],
                })) ?? [];
            commit("SET_ACTIVE_USER_OPTIONS", activeUserOptions);

            return activeUserOptions;
        } finally {
            commit("SET_ACTIVE_USER_OPTIONS_LOADING", false);
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
