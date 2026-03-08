const state = {
    // Map { conversationId: [message, ...] }
    messagesMap: {},
    // ID conversation đang active
    activeConversationId: null,
    // Map trạng thái online: { userId: true/false }
    onlineUsers: {},
    // Map số tin chưa đọc: { conversationId: count }
    unreadMap: {},
};

const getters = {
    messagesByConversation: (state) => (conversationId) => {
        return state.messagesMap[conversationId] ?? [];
    },
    activeConversationId: (state) => state.activeConversationId,
    isUserOnline: (state) => (userId) => !!state.onlineUsers[userId],
    totalUnread: (state) =>
        Object.values(state.unreadMap).reduce((sum, n) => sum + n, 0),
    unreadByConversation: (state) => (conversationId) =>
        state.unreadMap[conversationId] ?? 0,
};

const mutations = {
    SET_ACTIVE_CONVERSATION(state, conversationId) {
        state.activeConversationId = conversationId;
    },

    SET_MESSAGES(state, { conversationId, messages }) {
        state.messagesMap = {
            ...state.messagesMap,
            [conversationId]: messages,
        };
    },

    PUSH_MESSAGE(state, { conversationId, message }) {
        const existing = state.messagesMap[conversationId] ?? [];
        state.messagesMap = {
            ...state.messagesMap,
            [conversationId]: [...existing, message],
        };
    },

    SET_ONLINE_STATUSES(state, statuses) {
        state.onlineUsers = { ...state.onlineUsers, ...statuses };
    },

    SET_USER_ONLINE(state, { userId, online }) {
        state.onlineUsers = { ...state.onlineUsers, [userId]: online };
    },

    // Khởi tạo unread map từ API (load ban đầu)
    SET_UNREAD_MAP(state, map) {
        state.unreadMap = { ...map };
    },

    // Tăng unread khi nhận Mercure event (không phải conversation đang xem)
    INCREMENT_UNREAD(state, conversationId) {
        const current = state.unreadMap[conversationId] ?? 0;
        state.unreadMap = { ...state.unreadMap, [conversationId]: current + 1 };
    },

    // Reset về 0 khi mở conversation
    RESET_UNREAD(state, conversationId) {
        state.unreadMap = { ...state.unreadMap, [conversationId]: 0 };
    },
};

const actions = {
    setActiveConversation({ commit }, conversationId) {
        commit("SET_ACTIVE_CONVERSATION", conversationId);
    },
    setMessages({ commit }, payload) {
        commit("SET_MESSAGES", payload);
    },
    pushMessage({ commit }, payload) {
        commit("PUSH_MESSAGE", payload);
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
};
