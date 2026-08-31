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
    departmentOptionsByBranch: {},
    departmentOptionsLoadingByBranch: {},
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
    departmentOptionsByBranch: (state) => (branchId) =>
        state.departmentOptionsByBranch[branchId] ?? [],
    departmentOptionsLoadingByBranch: (state) => (branchId) =>
        Boolean(state.departmentOptionsLoadingByBranch[branchId]),
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
    SET_DEPARTMENT_OPTIONS(state, { branchId, departments }) {
        state.departmentOptionsByBranch = {
            ...state.departmentOptionsByBranch,
            [branchId]: departments,
        };
    },
    SET_DEPARTMENT_OPTIONS_LOADING(state, { branchId, value }) {
        state.departmentOptionsLoadingByBranch = {
            ...state.departmentOptionsLoadingByBranch,
            [branchId]: value,
        };
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
            const response = await getListData(API_ROUTES_CONFIG.users, params);
            const users = response?.data ?? [];
            const totalItems = response?.total ?? 0;

            commit("SET_USERS", { users, totalItems });
            return { users, totalItems };
        } finally {
            commit("SET_USERS_LOADING", false);
        }
    },
    async deleteUser(_, userId) {
        return deleteData(API_ROUTES_CONFIG.users, userId);
    },
    async fetchUserDetail({ commit, state }, { userId, force = false }) {
        if (!userId) {
            return null;
        }

        const cachedData = state.userDetailsById[userId];
        if (!force && cachedData) {
            return cachedData;
        }

        const data = await getDataById(API_ROUTES_CONFIG.users, userId);
        commit("SET_USER_DETAIL", { userId, data });

        return data;
    },
    async createUser(_, values) {
        return postData(API_ROUTES_CONFIG.users, values);
    },
    async updateUser(_, { userId, values }) {
        return putData(API_ROUTES_CONFIG.users, userId, values);
    },
    async fetchProvince({ commit, state }, { force = false } = {}) {
        if (!force && Object.keys(state.provinceData ?? {}).length) {
            return state.provinceData;
        }

        const data =
            (await getAllData(API_ROUTES_CONFIG.users + "/provinces")) ?? {};
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
            (await getAllData(API_ROUTES_CONFIG.users + "/wards/" + provinceId)) ??
            {};
        commit("SET_WARD_DATA", { provinceId, data });

        return data;
    },
    async fetchEmployeeCode({ commit }) {
        const employeeCode =
            (await getAllData(API_ROUTES_CONFIG.users + "/employee-code")) ??
            "";
        commit("SET_EMPLOYEE_CODE", employeeCode);

        return employeeCode;
    },
    async fetchDepartmentOptions(
        { commit, state },
        { branchId, force = false },
    ) {
        if (!branchId) {
            return [];
        }

        const cachedData = state.departmentOptionsByBranch[branchId];
        if (!force && cachedData) {
            return cachedData;
        }

        commit("SET_DEPARTMENT_OPTIONS_LOADING", { branchId, value: true });

        try {
            const departments =
                (await getDataSelect(API_ROUTES_CONFIG.department, {
                    branchId,
                })) ?? [];
            commit("SET_DEPARTMENT_OPTIONS", { branchId, departments });

            return departments;
        } finally {
            commit("SET_DEPARTMENT_OPTIONS_LOADING", {
                branchId,
                value: false,
            });
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
                    API_ROUTES_CONFIG.department,
                    departmentId,
                    "positions",
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
                API_ROUTES_CONFIG.users,
                userId,
                "job-positions",
            )) ?? null;
        commit("SET_USER_POSITION", { userId, data });

        return data;
    },
    async updateUserPosition({ commit }, { userId, values }) {
        const response = await putData(
            `${API_ROUTES_CONFIG.users}/${userId}/job-positions`,
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
                API_ROUTES_CONFIG.users,
                userId,
                "job-positions/list",
            )) ?? [];
        commit("SET_USER_POSITION_LIST", { userId, positions });

        return positions;
    },
    async createTemporaryPosition(_, { userId, values }) {
        return postData(
            `${API_ROUTES_CONFIG.users}/${userId}/temporary-positions`,
            {
                ...values,
            },
        );
    },
    async deleteTemporaryPosition(_, temporaryPositionId) {
        return deleteData(
            `${API_ROUTES_CONFIG.users}/temporary-positions`,
            temporaryPositionId,
        );
    },
    async uploadContracts({ commit, state }, { userId, formData }) {
        const response = await postDataWithFile(
            `${API_ROUTES_CONFIG.users}/${userId}/contracts`,
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
            (await getDataById(
                API_ROUTES_CONFIG.users,
                userId,
                "permissions",
            )) ??
            [];
        commit("SET_USER_PERMISSIONS", { userId, permissions });

        return permissions;
    },
    async updateUserPermissions({ commit }, { userId, permissions }) {
        const response = await putData(
            `${API_ROUTES_CONFIG.users}/${userId}/permissions`,
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
            API_ROUTES_CONFIG.users,
            userId,
            "custom-permissions",
        );
        commit("SET_USER_CUSTOM_PERMISSION", { userId, customPermission });

        return customPermission;
    },
    async restoreDefaultPermissions({ dispatch }, userId) {
        await getDataById(
            API_ROUTES_CONFIG.users,
            userId,
            "restore-default-permissions",
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
