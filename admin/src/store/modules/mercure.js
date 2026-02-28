const state = {
    notifications: [],
    popupNotification: null,
};

const getters = {
    notifications: (state) => state.notifications,
    popupNotification: (state) => state.popupNotification,
};

const mutations = {
    SET_NOTIFICATIONS(state, notifications) {
        state.notifications = notifications;
    },
    ADD_NOTIFICATION(state, notification) {
        state.notifications.unshift(notification);
    },
    MARK_AS_READ(state, code) {
        const item = state.notifications.find((n) => n.code === code);
        if (item) item.seen = true;
    },
    SET_POPUP_NOTIFICATION(state, notification) {
        state.popupNotification = notification;
    },
    REMOVE_POPUP_NOTIFICATION(state) {
        state.popupNotification = null;
    },
};

const actions = {
    async setNotifications({ commit }, notifications) {
        commit("SET_NOTIFICATIONS", notifications);
    },
    async setPopupNotification({ commit }, notification) {
        commit("SET_POPUP_NOTIFICATION", notification);
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
