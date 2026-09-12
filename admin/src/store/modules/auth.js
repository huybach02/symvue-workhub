import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { putData } from "@/services/bases/updateData";
import { postData } from "@/services/bases/postData";

const state = {
    user: null,
    isAuthenticated: false,
    dataLogin: {
        email: "",
        password: "",
    },
};

const getters = {
    currentUser: (state) => state.user,
    isAuthenticated: (state) => state.isAuthenticated,
    dataLogin: (state) => state.dataLogin,
    isAdmin: (state) => state.user?.roles?.includes("ROLE_ADMIN"),
};

const mutations = {
    SET_USER(state, user) {
        state.user = user;
    },
    SET_IS_AUTHENTICATED(state, isAuthenticated) {
        state.isAuthenticated = isAuthenticated;
    },
    CLEAR_AUTH_DATA(state) {
        state.user = null;
        state.isAuthenticated = false;
    },
    SET_DATA_LOGIN(state, data) {
        state.dataLogin = data;
    },
    CLEAR_DATA_LOGIN(state) {
        state.dataLogin = {
            email: "",
            password: "",
        };
    },
};

const actions = {
    // Action đăng xuất
    async logout({ commit }) {
        commit("CLEAR_AUTH_DATA");
    },
    async updateProfile({ commit, state }, { data, callback }) {
        commit("setIsLoading", null, { root: true });
        const resData = await putData(
            API_ROUTES_CONFIG.profile,
            null,
            data,
            callback,
        );
        if (resData) {
            commit("SET_USER", { ...state.user, ...resData });
        }
        commit("unsetIsLoading", null, { root: true });
        return resData;
    },
    async changePasswordProfile({ commit }, { data, callback }) {
        commit("setIsLoading", null, { root: true });
        const resData = await postData(
            API_ROUTES_CONFIG.changePasswordProfile,
            data,
            callback,
        );
        commit("unsetIsLoading", null, { root: true });
        return resData;
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
