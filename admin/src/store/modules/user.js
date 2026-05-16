import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import {
    getAllData,
    getDataById,
    getDataSelect,
    getListData,
} from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
import { postData, postDataWithFile } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";

const state = {
    users: [],
    totalItems: 0,
    usersLoading: false,
    userDetailsById: {},
    provinceData: {},
    wardDataByProvince: {},
    employeeCode: "",
    departmentOptions: [],
    departmentOptionsLoading: false,
    positionOptionsByDepartment: {},
    positionOptionsLoadingByDepartment: {},
    userPositionByUserId: {},
    userPositionListByUserId: {},
    permissionsByUserId: {},
    customPermissionByUserId: {},
};

const getters = {
    users: (state) => state.users,
    totalItems: (state) => state.totalItems,
    usersLoading: (state) => state.usersLoading,
    userDetailById: (state) => (userId) => state.userDetailsById[userId] ?? null,
    provinceData: (state) => state.provinceData,
    wardDataByProvince: (state) => (provinceId) =>
        state.wardDataByProvince[provinceId] ?? {},
    employeeCode: (state) => state.employeeCode,
    departmentOptions: (state) => state.departmentOptions,
    departmentOptionsLoading: (state) => state.departmentOptionsLoading,
    positionOptionsByDepartment: (state) => (departmentId) =>
        state.positionOptionsByDepartment[departmentId] ?? [],
    positionOptionsLoadingByDepartment: (state) => (departmentId) =>
        Boolean(state.positionOptionsLoadingByDepartment[departmentId]),
    userPositionByUserId: (state) => (userId) =>
        state.userPositionByUserId[userId] ?? null,
    userPositionListByUserId: (state) => (userId) =>
        state.userPositionListByUserId[userId] ?? [],
    permissionsByUserId: (state) => (userId) =>
        state.permissionsByUserId[userId] ?? [],
    customPermissionByUserId: (state) => (userId) =>
        state.customPermissionByUserId[userId] ?? null,
};

const mutations = {
    SET_USERS(state, { users, totalItems }) {
        state.users = users;
        state.totalItems = totalItems;
    },
    SET_USERS_LOADING(state, value) {
        state.usersLoading = value;
    },
    SET_USER_DETAIL(state, { userId, data }) {
        state.userDetailsById = {
            ...state.userDetailsById,
            [userId]: data,
        };
    },
    SET_PROVINCE_DATA(state, data) {
        state.provinceData = data;
    },
    SET_WARD_DATA(state, { provinceId, data }) {
        state.wardDataByProvince = {
            ...state.wardDataByProvince,
            [provinceId]: data,
        };
    },
    SET_EMPLOYEE_CODE(state, employeeCode) {
        state.employeeCode = employeeCode;
    },
    SET_DEPARTMENT_OPTIONS(state, departments) {
        state.departmentOptions = departments;
    },
    SET_DEPARTMENT_OPTIONS_LOADING(state, value) {
        state.departmentOptionsLoading = value;
    },
    SET_POSITION_OPTIONS_LOADING(state, { departmentId, value }) {
        state.positionOptionsLoadingByDepartment = {
            ...state.positionOptionsLoadingByDepartment,
            [departmentId]: value,
        };
    },
    SET_POSITION_OPTIONS(state, { departmentId, positions }) {
        state.positionOptionsByDepartment = {
            ...state.positionOptionsByDepartment,
            [departmentId]: positions,
        };
    },
    SET_USER_POSITION(state, { userId, data }) {
        state.userPositionByUserId = {
            ...state.userPositionByUserId,
            [userId]: data,
        };
    },
    SET_USER_POSITION_LIST(state, { userId, positions }) {
        state.userPositionListByUserId = {
            ...state.userPositionListByUserId,
            [userId]: positions,
        };
    },
    SET_USER_PERMISSIONS(state, { userId, permissions }) {
        state.permissionsByUserId = {
            ...state.permissionsByUserId,
            [userId]: permissions,
        };
    },
    SET_USER_CUSTOM_PERMISSION(state, { userId, customPermission }) {
        state.customPermissionByUserId = {
            ...state.customPermissionByUserId,
            [userId]: customPermission,
        };
    },
};

const actions = {
    async fetchUsers({ commit }, params = {}) {
        commit("SET_USERS_LOADING", true);

        try {
            const response = await getListData(API_ROUTES_CONFIG.user, params);
            const users = response?.data ?? [];
            const totalItems = response?.total ?? 0;

            commit("SET_USERS", { users, totalItems });
            return { users, totalItems };
        } finally {
            commit("SET_USERS_LOADING", false);
        }
    },
    async deleteUser(_, userId) {
        return deleteData(API_ROUTES_CONFIG.user, userId);
    },
    async fetchUserDetail({ commit, state }, { userId, force = false }) {
        if (!userId) {
            return null;
        }

        const cachedData = state.userDetailsById[userId];
        if (!force && cachedData) {
            return cachedData;
        }

        const data = await getDataById(API_ROUTES_CONFIG.user, userId);
        commit("SET_USER_DETAIL", { userId, data });

        return data;
    },
    async createUser(_, values) {
        return postData(API_ROUTES_CONFIG.user, values);
    },
    async updateUser(_, { userId, values }) {
        return putData(API_ROUTES_CONFIG.user, userId, values);
    },
    async fetchProvince({ commit, state }, { force = false } = {}) {
        if (!force && Object.keys(state.provinceData ?? {}).length) {
            return state.provinceData;
        }

        const data =
            (await getAllData(API_ROUTES_CONFIG.user + "/province")) ?? {};
        commit("SET_PROVINCE_DATA", data);

        return data;
    },
    async fetchWard({ commit, state }, { provinceId, force = false }) {
        if (!provinceId) {
            return {};
        }

        const cachedData = state.wardDataByProvince[provinceId];
        if (!force && cachedData) {
            return cachedData;
        }

        const data =
            (await getAllData(API_ROUTES_CONFIG.user + "/ward/" + provinceId)) ??
            {};
        commit("SET_WARD_DATA", { provinceId, data });

        return data;
    },
    async fetchEmployeeCode({ commit }) {
        const employeeCode =
            (await getAllData(API_ROUTES_CONFIG.user + "/get-ma-nhan-vien")) ??
            "";
        commit("SET_EMPLOYEE_CODE", employeeCode);

        return employeeCode;
    },
    async fetchDepartmentOptions({ commit, state }, { force = false } = {}) {
        if (!force && state.departmentOptions.length) {
            return state.departmentOptions;
        }

        commit("SET_DEPARTMENT_OPTIONS_LOADING", true);

        try {
            const departments =
                (await getDataSelect(API_ROUTES_CONFIG.boPhan)) ?? [];
            commit("SET_DEPARTMENT_OPTIONS", departments);

            return departments;
        } finally {
            commit("SET_DEPARTMENT_OPTIONS_LOADING", false);
        }
    },
    async fetchDepartmentPositions(
        { commit, state },
        { departmentId, force = false },
    ) {
        if (!departmentId) {
            return [];
        }

        const cachedData = state.positionOptionsByDepartment[departmentId];
        if (!force && cachedData) {
            return cachedData;
        }

        commit("SET_POSITION_OPTIONS_LOADING", { departmentId, value: true });

        try {
            const positions =
                (await getDataById(
                    API_ROUTES_CONFIG.boPhan,
                    departmentId,
                    "chuc-vu",
                )) ?? [];
            commit("SET_POSITION_OPTIONS", { departmentId, positions });

            return positions;
        } finally {
            commit("SET_POSITION_OPTIONS_LOADING", {
                departmentId,
                value: false,
            });
        }
    },
    async fetchUserPosition({ commit }, userId) {
        if (!userId) {
            return null;
        }

        const data =
            (await getDataById(
                API_ROUTES_CONFIG.user,
                userId,
                "vi-tri-cong-viec",
            )) ?? null;
        commit("SET_USER_POSITION", { userId, data });

        return data;
    },
    async updateUserPosition({ commit }, { userId, values }) {
        const response = await putData(
            `${API_ROUTES_CONFIG.user}/${userId}/vi-tri-cong-viec`,
            null,
            values,
        );

        if (response) {
            commit("SET_USER_POSITION", { userId, data: response });
        }

        return response;
    },
    async fetchUserPositionList({ commit }, userId) {
        if (!userId) {
            return [];
        }

        const positions =
            (await getDataById(
                API_ROUTES_CONFIG.user,
                userId,
                "vi-tri-cong-viec/danh-sach",
            )) ?? [];
        commit("SET_USER_POSITION_LIST", { userId, positions });

        return positions;
    },
    async createTemporaryPosition(_, { userId, values }) {
        return postData(
            `${API_ROUTES_CONFIG.user}/${userId}/vi-tri-cong-viec/temp`,
            {
                ...values,
            },
        );
    },
    async deleteTemporaryPosition(_, temporaryPositionId) {
        return deleteData(
            `${API_ROUTES_CONFIG.user}/vi-tri-cong-viec/temp`,
            temporaryPositionId,
        );
    },
    async uploadContracts({ commit, state }, { userId, formData }) {
        const response = await postDataWithFile(
            `${API_ROUTES_CONFIG.user}/${userId}/hop-dong`,
            formData,
        );

        if (response) {
            commit("SET_USER_POSITION", {
                userId,
                data: {
                    ...(state.userPositionByUserId[userId] ?? {}),
                    contracts: response.contracts ?? [],
                },
            });
        }

        return response;
    },
    async fetchUserPermissions({ commit }, userId) {
        if (!userId) {
            commit("SET_USER_PERMISSIONS", { userId, permissions: [] });
            return [];
        }

        const permissions =
            (await getDataById(API_ROUTES_CONFIG.user, userId, "permission")) ??
            [];
        commit("SET_USER_PERMISSIONS", { userId, permissions });

        return permissions;
    },
    async updateUserPermissions({ commit }, { userId, permissions }) {
        const response = await putData(
            `${API_ROUTES_CONFIG.user}/${userId}/permission`,
            null,
            { permissions },
        );

        if (response) {
            commit("SET_USER_PERMISSIONS", {
                userId,
                permissions: response ?? [],
            });
        }

        return response;
    },
    async fetchUserCustomPermission({ commit }, userId) {
        if (!userId) {
            return null;
        }

        const customPermission = await getDataById(
            API_ROUTES_CONFIG.user,
            userId,
            "has-custom-permission",
        );
        commit("SET_USER_CUSTOM_PERMISSION", { userId, customPermission });

        return customPermission;
    },
    async restoreDefaultPermissions({ dispatch }, userId) {
        await getDataById(
            API_ROUTES_CONFIG.user,
            userId,
            "restore-default-permission",
        );

        await Promise.all([
            dispatch("fetchUserPermissions", userId),
            dispatch("fetchUserCustomPermission", userId),
        ]);
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
