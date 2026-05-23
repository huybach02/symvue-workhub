import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import {
    getDataById,
    getDataSelect,
    getListData,
    getListPhanQuyenMacDinh,
} from "@/services/bases/getData";
import { deleteData } from "@/services/bases/deleteData";
import { postData } from "@/services/bases/postData";
import { putData } from "@/services/bases/updateData";

const state = {
    departments: [],
    totalItems: 0,
    departmentsLoading: false,
    departmentDetailsById: {},
    userOptions: [],
    positionsByDepartment: {},
    positionsLoadingByDepartment: {},
    defaultPermissions: [],
    defaultPermissionsLoading: false,
};

const getters = {
    departments: (state) => state.departments,
    totalItems: (state) => state.totalItems,
    departmentsLoading: (state) => state.departmentsLoading,
    departmentDetailById: (state) => (departmentId) =>
        state.departmentDetailsById[departmentId] ?? null,
    userOptions: (state) => state.userOptions,
    positionsByDepartment: (state) => (departmentId) =>
        state.positionsByDepartment[departmentId] ?? [],
    positionsLoadingByDepartment: (state) => (departmentId) =>
        Boolean(state.positionsLoadingByDepartment[departmentId]),
    defaultPermissions: (state) => state.defaultPermissions,
    defaultPermissionsLoading: (state) => state.defaultPermissionsLoading,
};

const mutations = {
    SET_DEPARTMENTS(state, { departments, totalItems }) {
        state.departments = departments;
        state.totalItems = totalItems;
    },
    SET_DEPARTMENTS_LOADING(state, value) {
        state.departmentsLoading = value;
    },
    SET_DEPARTMENT_DETAIL(state, { departmentId, data }) {
        state.departmentDetailsById = {
            ...state.departmentDetailsById,
            [departmentId]: data,
        };
    },
    SET_USER_OPTIONS(state, userOptions) {
        state.userOptions = userOptions;
    },
    SET_POSITIONS(state, { departmentId, positions }) {
        state.positionsByDepartment = {
            ...state.positionsByDepartment,
            [departmentId]: positions,
        };
    },
    SET_POSITIONS_LOADING(state, { departmentId, value }) {
        state.positionsLoadingByDepartment = {
            ...state.positionsLoadingByDepartment,
            [departmentId]: value,
        };
    },
    SET_DEFAULT_PERMISSIONS(state, permissions) {
        state.defaultPermissions = permissions;
    },
    SET_DEFAULT_PERMISSIONS_LOADING(state, value) {
        state.defaultPermissionsLoading = value;
    },
};

const actions = {
    async fetchDepartments({ commit }, params = {}) {
        commit("SET_DEPARTMENTS_LOADING", true);

        try {
            const response = await getListData(
                API_ROUTES_CONFIG.department,
                params,
            );
            const departments = response?.data ?? [];
            const totalItems = response?.total ?? 0;

            commit("SET_DEPARTMENTS", { departments, totalItems });
            return { departments, totalItems };
        } finally {
            commit("SET_DEPARTMENTS_LOADING", false);
        }
    },
    async fetchDepartmentDetail({ commit, state }, { departmentId, force = false }) {
        if (!departmentId) {
            return null;
        }

        const cachedData = state.departmentDetailsById[departmentId];
        if (!force && cachedData) {
            return cachedData;
        }

        const data = await getDataById(
            API_ROUTES_CONFIG.department,
            departmentId,
        );
        commit("SET_DEPARTMENT_DETAIL", { departmentId, data });

        return data;
    },
    async createDepartment(_, values) {
        return postData(API_ROUTES_CONFIG.department, values);
    },
    async updateDepartment(_, { departmentId, values }) {
        return putData(API_ROUTES_CONFIG.department, departmentId, values);
    },
    async deleteDepartment(_, departmentId) {
        return deleteData(API_ROUTES_CONFIG.department, departmentId);
    },
    async fetchUserOptions({ commit, state }, { force = false } = {}) {
        if (!force && state.userOptions.length) {
            return state.userOptions;
        }

        const userOptions =
            (await getDataSelect(API_ROUTES_CONFIG.users)) ?? [];
        commit("SET_USER_OPTIONS", userOptions);

        return userOptions;
    },
    async fetchDepartmentPositions(
        { commit, state },
        { departmentId, force = false },
    ) {
        if (!departmentId) {
            return [];
        }

        const cachedData = state.positionsByDepartment[departmentId];
        if (!force && cachedData) {
            return cachedData;
        }

        commit("SET_POSITIONS_LOADING", { departmentId, value: true });

        try {
            const positions =
                (await getDataById(
                    API_ROUTES_CONFIG.department,
                    departmentId,
                    "positions",
                )) ?? [];
            commit("SET_POSITIONS", { departmentId, positions });

            return positions;
        } finally {
            commit("SET_POSITIONS_LOADING", { departmentId, value: false });
        }
    },
    async createPosition({ dispatch }, { departmentId, values }) {
        const response = await postData(
            `${API_ROUTES_CONFIG.department}/${departmentId}/positions`,
            values,
        );

        if (response) {
            await Promise.all([
                dispatch("fetchDepartmentDetail", { departmentId, force: true }),
                dispatch("fetchDepartmentPositions", {
                    departmentId,
                    force: true,
                }),
            ]);
        }

        return response;
    },
    async updatePosition({ dispatch }, { departmentId, positionId, values }) {
        const response = await putData(
            `${API_ROUTES_CONFIG.department}/${departmentId}/positions`,
            positionId,
            values,
        );

        if (response) {
            await Promise.all([
                dispatch("fetchDepartmentDetail", { departmentId, force: true }),
                dispatch("fetchDepartmentPositions", {
                    departmentId,
                    force: true,
                }),
            ]);
        }

        return response;
    },
    async deletePosition({ dispatch }, { departmentId, positionId }) {
        const response = await deleteData(
            `${API_ROUTES_CONFIG.department}/${departmentId}/positions`,
            positionId,
        );

        if (response?.success) {
            await Promise.all([
                dispatch("fetchDepartmentDetail", { departmentId, force: true }),
                dispatch("fetchDepartmentPositions", {
                    departmentId,
                    force: true,
                }),
            ]);
        }

        return response;
    },
    async updatePositionPermissions({ commit }, { departmentId, phanQuyen }) {
        const response = await putData(
            `${API_ROUTES_CONFIG.department}/${departmentId}/permissions`,
            null,
            { phanQuyen },
        );

        if (response) {
            commit("SET_DEPARTMENT_DETAIL", {
                departmentId,
                data: response,
            });
        }

        return response;
    },
    async fetchDefaultPermissions({ commit, state }, { force = false } = {}) {
        if (!force && state.defaultPermissions.length) {
            return state.defaultPermissions;
        }

        commit("SET_DEFAULT_PERMISSIONS_LOADING", true);

        try {
            const permissions = (await getListPhanQuyenMacDinh()) ?? [];
            commit("SET_DEFAULT_PERMISSIONS", permissions);

            return permissions;
        } finally {
            commit("SET_DEFAULT_PERMISSIONS_LOADING", false);
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
