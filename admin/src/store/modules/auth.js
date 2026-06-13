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
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
