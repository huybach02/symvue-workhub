// Vuex module quản lý tin nhắn chat theo conversationId
const state = {
    // Map { conversationId: [message, ...] }
    messagesMap: {},
    // ID conversation đang active (để biết push vào đâu)
    activeConversationId: null,
};

const getters = {
    // Lấy messages của 1 conversation cụ thể
    messagesByConversation: (state) => (conversationId) => {
        return state.messagesMap[conversationId] ?? [];
    },
    activeConversationId: (state) => state.activeConversationId,
};

const mutations = {
    // Set conversation đang active
    SET_ACTIVE_CONVERSATION(state, conversationId) {
        state.activeConversationId = conversationId;
    },

    // Khởi tạo danh sách tin nhắn cho 1 conversation
    SET_MESSAGES(state, { conversationId, messages }) {
        state.messagesMap = {
            ...state.messagesMap,
            [conversationId]: messages,
        };
    },

    // Thêm 1 tin nhắn mới vào conversation
    PUSH_MESSAGE(state, { conversationId, message }) {
        const existing = state.messagesMap[conversationId] ?? [];
        state.messagesMap = {
            ...state.messagesMap,
            [conversationId]: [...existing, message],
        };
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
