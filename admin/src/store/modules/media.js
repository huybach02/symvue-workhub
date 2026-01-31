const state = {
    selectedMedia: [],
};

const getters = {
    selectedMedia: (state) => state.selectedMedia,
};

const mutations = {
    SET_SELECTED_MEDIA(state, mediaList) {
        state.selectedMedia = mediaList;
    },
    ADD_SELECTED_MEDIA(state, media) {
        state.selectedMedia.push(media);
    },
    REMOVE_SELECTED_MEDIA(state, media) {
        state.selectedMedia = state.selectedMedia.filter(
            (item) => item.id !== media.id,
        );
    },
    CLEAR_SELECTED_MEDIA(state) {
        state.selectedMedia = [];
    },
};

const actions = {
    async setSelectedMedia({ commit }, mediaList) {
        commit("SET_SELECTED_MEDIA", mediaList);
    },
    async addSelectedMedia({ commit }, media) {
        commit("ADD_SELECTED_MEDIA", media);
    },
    async removeSelectedMedia({ commit }, media) {
        commit("REMOVE_SELECTED_MEDIA", media);
    },
    async clearSelectedMedia({ commit }) {
        commit("CLEAR_SELECTED_MEDIA");
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
