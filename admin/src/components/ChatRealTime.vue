<template>
    <div class="chat-wrapper">
        <div :class="{ 'chat-pulse-wrapper': unreadCount > 0 }">
            <v-btn icon variant="text" class="chat-btn" @click="dialog = true">
                <v-badge
                    :content="badgeLabel"
                    :model-value="unreadCount > 0"
                    color="error"
                    location="top end"
                    :offset-x="-2"
                    :offset-y="-2"
                >
                    <v-icon
                        size="26"
                        :class="{ 'chat-bounce': unreadCount > 0 }"
                    >
                        mdi-chat-outline
                    </v-icon>
                </v-badge>
            </v-btn>
        </div>

        <v-dialog
            v-if="!$vuetify.display.mobile"
            v-model="dialog"
            max-width="1000"
            :height="dialogHeight"
            persistent
        >
            <v-card
                class="chat-dialog-card d-flex flex-row"
                style="overflow: hidden; height: 100%"
            >
                <div class="chat-sidebar">
                    <ChatConversationList
                        ref="convList"
                        :conversations="conversations"
                        :active-conversation-id="
                            activeConversation ? activeConversation.id : null
                        "
                        @select="openConversation"
                        @start-conversation="handleStartConversation"
                    />
                </div>

                <div class="chat-main flex-grow-1 d-flex flex-column">
                    <ChatWindow
                        v-if="activeConversation"
                        :conversation="activeConversation"
                        :messages="activeMessages"
                        :is-typing="isTyping"
                        :current-user-id="currentUserId"
                        :is-loading-messages="isLoadingMessages"
                        @close="dialog = false"
                        @send="sendMessage"
                    />

                    <div
                        v-else
                        class="d-flex flex-column align-center justify-center flex-grow-1 text-grey position-relative"
                    >
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            style="position: absolute; top: 8px; right: 8px"
                            @click="dialog = false"
                        >
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <v-icon size="72" color="grey-lighten-2">
                            mdi-chat-outline
                        </v-icon>
                        <div
                            class="text-h6 font-weight-medium mt-4 text-grey-lighten-1"
                        >
                            Chọn một hội thoại
                        </div>
                        <div class="text-body-2 text-grey-lighten-1 mt-1">
                            để bắt đầu trò chuyện
                        </div>
                    </div>
                </div>
            </v-card>
        </v-dialog>

        <v-dialog
            v-else
            v-model="dialog"
            fullscreen
            transition="dialog-bottom-transition"
        >
            <v-card class="d-flex flex-column" style="height: 100%">
                <ChatConversationList
                    v-if="mobileScreen === 0"
                    ref="convList"
                    :conversations="conversations"
                    :active-conversation-id="
                        activeConversation ? activeConversation.id : null
                    "
                    :mobile-mode="true"
                    @select="openConversationMobile"
                    @start-conversation="handleStartConversation"
                    @close="dialog = false"
                />

                <ChatWindow
                    v-else-if="mobileScreen === 1 && activeConversation"
                    :conversation="activeConversation"
                    :messages="activeMessages"
                    :is-typing="isTyping"
                    :current-user-id="currentUserId"
                    :mobile-mode="true"
                    @back="mobileScreen = 0"
                    @close="dialog = false"
                    @send="sendMessage"
                />
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import { getAllData } from "@/services/bases/getData";
import { postData } from "@/services/bases/postData";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import ChatConversationList from "./chat/ChatConversationList.vue";
import ChatWindow from "./chat/ChatWindow.vue";
import presenceService from "@/services/presenceService";

export default {
    name: "ChatRealTime",

    components: {
        ChatConversationList,
        ChatWindow,
    },

    data() {
        return {
            dialog: false,
            isTyping: false,
            isLoadingMessages: false,
            activeConversation: null,
            mobileScreen: 0,
            conversations: [],
        };
    },

    computed: {
        ...mapGetters("auth", ["currentUser"]),
        ...mapGetters("chat", ["messagesByConversation", "totalUnread"]),

        currentUserId() {
            return this.currentUser?.id ?? null;
        },

        unreadCount() {
            return this.totalUnread;
        },

        badgeLabel() {
            return this.unreadCount > 99 ? "99+" : String(this.unreadCount);
        },

        activeMessages() {
            if (!this.activeConversation) return [];
            return this.messagesByConversation(this.activeConversation.id);
        },

        dialogHeight() {
            return Math.min(window.innerHeight * 0.82, 680);
        },
    },

    watch: {
        dialog(val) {
            if (val) {
                this.loadConversations();
            } else {
                this.mobileScreen = 0;
                this.activeConversation = null;
                this.isTyping = false;
                this.$store.commit("chat/SET_ACTIVE_CONVERSATION", null);
            }
        },
    },

    mounted() {
        this.loadConversations();
    },

    methods: {
        async loadConversations() {
            const data = await getAllData(API_ROUTES_CONFIG.conversation);
            this.conversations = (data ?? []).map((conv) => ({
                ...conv,
                unread: conv.unread ?? 0,
                lastMessage: conv.lastMessage ?? "",
                time: conv.time ?? null,
            }));

            // Khởi tạo unread map trong Vuex từ dữ liệu API
            const unreadMap = {};
            this.conversations.forEach((conv) => {
                if (conv.id) unreadMap[conv.id] = conv.unread ?? 0;
            });
            this.$store.commit("chat/SET_UNREAD_MAP", unreadMap);

            // Load trạng thái online ban đầu cho tất cả conversations và lưu vào Vuex
            const userIds = this.conversations
                .map((c) => c.receiverId)
                .filter(Boolean);

            if (userIds.length > 0) {
                const statuses = await presenceService.fetchStatuses(userIds);
                if (statuses && Object.keys(statuses).length > 0) {
                    this.$store.commit("chat/SET_ONLINE_STATUSES", statuses);
                }
            }
        },

        async openConversation(conv) {
            this.activeConversation = conv;
            this.isLoadingMessages = true;
            this.$store.commit("chat/SET_ACTIVE_CONVERSATION", conv.id);
            this.$store.commit("chat/RESET_UNREAD", conv.id);
            postData(`${API_ROUTES_CONFIG.conversation}/${conv.id}/read`);
            await this.loadMessageOfConversation(conv.id);
            this.isLoadingMessages = false;
        },

        openConversationMobile(conv) {
            this.openConversation(conv);
            this.mobileScreen = 1;
        },

        async handleStartConversation(user) {
            const result = await postData(API_ROUTES_CONFIG.conversation, {
                type: "private",
                userId: user.id,
            });
            if (result) {
                const existing = this.conversations.find(
                    (c) => c.id === result.id,
                );
                if (existing) {
                    this.openConversation(existing);
                } else {
                    const newConv = {
                        ...result,
                        unread: 0,
                        online: false,
                        lastMessage: "",
                        time: null,
                    };
                    this.conversations.unshift(newConv);
                    this.openConversation(newConv);
                }
                this.$refs.convList?.removeAvailableUser(user.id);
            }
        },

        async sendMessage(content) {
            if (!content || !this.activeConversation) return;

            await postData(API_ROUTES_CONFIG.message, {
                conversationId: this.activeConversation.id,
                receiverId: this.activeConversation.receiverId,
                content: content,
            });

            const conv = this.conversations.find(
                (c) => c.id === this.activeConversation.id,
            );
            if (conv) {
                conv.lastMessage = content;
                conv.time = new Date().toLocaleTimeString("vi-VN", {
                    hour: "2-digit",
                    minute: "2-digit",
                });
            }
        },

        async loadMessageOfConversation(conversationId) {
            const data = await getAllData(
                `${API_ROUTES_CONFIG.message}/conversation/${conversationId}`,
            );
            this.$store.commit("chat/SET_MESSAGES", {
                conversationId,
                messages: data ?? [],
            });
        },
    },
};
</script>

<style scoped>
.chat-wrapper {
    display: inline-flex;
    align-items: center;
}

.chat-bounce {
    animation: chat-bounce 2s ease infinite;
    transform-origin: bottom center;
}

@keyframes chat-bounce {
    0%,
    100% {
        transform: translateY(0);
    }
    20% {
        transform: translateY(-4px);
    }
    40% {
        transform: translateY(0);
    }
    60% {
        transform: translateY(-2px);
    }
    80% {
        transform: translateY(0);
    }
}

.chat-pulse-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.chat-pulse-wrapper::before,
.chat-pulse-wrapper::after {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: transparent;
    border: 2px solid rgb(var(--v-theme-error));
    opacity: 0;
    pointer-events: none;
    animation: pulse-ring 2.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
}

.chat-pulse-wrapper::after {
    animation-delay: 1s;
}

@keyframes pulse-ring {
    0% {
        transform: scale(0.6);
        opacity: 0.8;
    }
    80% {
        transform: scale(1.35);
        opacity: 0;
    }
    100% {
        transform: scale(1.35);
        opacity: 0;
    }
}

/* ===== Desktop dialog ===== */
.chat-dialog-card {
    border-radius: 16px !important;
}

.chat-sidebar {
    width: 320px;
    min-width: 320px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.chat-main {
    min-width: 0;
    overflow: hidden;
}
</style>
