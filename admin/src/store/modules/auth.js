const state = {
    user: null,
    isAuthenticated: false,
};

const getters = {
    currentUser: (state) => state.user,
    isAuthenticated: (state) => state.isAuthenticated,
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
