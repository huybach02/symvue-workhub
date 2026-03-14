<template>
    <div class="d-flex flex-column" style="height: 100%">
        <div class="list-header px-4 pt-3 pb-2 flex-shrink-0">
            <div class="d-flex align-center justify-space-between">
                <span class="text-subtitle-1 font-weight-bold">
                    <v-icon size="20" class="me-1">mdi-chat-outline</v-icon>
                    Tin nhắn
                </span>
                <v-btn
                    v-if="mobileMode"
                    icon
                    variant="text"
                    size="small"
                    @click="$emit('close')"
                >
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </div>

            <div class="d-flex align-center ga-2 mt-2 mb-1">
                <v-chip size="x-small" variant="tonal" color="primary">
                    <v-icon start size="12">mdi-chat-outline</v-icon>
                    Tổng: {{ conversations.length }}
                </v-chip>
                <v-chip size="x-small" variant="tonal" color="error">
                    <v-icon start size="12">mdi-message-badge</v-icon>
                    Chưa đọc: {{ totalUnread }}
                </v-chip>
            </div>

            <v-text-field
                v-model="searchQuery"
                density="compact"
                variant="outlined"
                placeholder="Tìm kiếm hội thoại..."
                prepend-inner-icon="mdi-magnify"
                hide-details
                class="mt-2"
                rounded="lg"
                :loading="isSearchingUsers"
            />
        </div>

        <v-divider />

        <div class="flex-grow-1 overflow-y-auto">
            <template v-if="filteredConversations.length > 0">
                <div v-if="searchQuery.trim()" class="section-label px-4 py-2">
                    <v-icon size="14" class="me-1" color="grey-darken-1">
                        mdi-chat-outline
                    </v-icon>
                    <span
                        class="text-caption text-grey-darken-1 font-weight-medium"
                        >Hội thoại
                    </span>
                </div>

                <v-list class="pa-0">
                    <TransitionGroup name="chat-item" tag="div">
                        <div
                            v-for="(conv, index) in filteredConversations"
                            :key="'conv-' + conv.id"
                        >
                            <v-list-item
                                :class="{
                                    'unread-conv': conv.unread > 0,
                                    'active-conv':
                                        conv.id === activeConversationId,
                                }"
                                class="conv-item px-3 py-3"
                                style="cursor: pointer"
                                @click="$emit('select', conv)"
                            >
                                <div class="d-flex align-center ga-3">
                                    <div class="avatar-wrap flex-shrink-0">
                                        <v-avatar size="42" :color="conv.color">
                                            <v-img
                                                v-if="conv.avatar"
                                                :src="conv.avatar"
                                                :alt="conv.nameUser"
                                            />
                                            <span
                                                v-else
                                                class="text-body-2 font-weight-bold text-white"
                                            >
                                                <v-avatar
                                                    v-if="conv.avatar"
                                                    size="42"
                                                >
                                                    <v-img
                                                        :src="conv.avatar"
                                                        :alt="conv.nameUser"
                                                        cover
                                                    />
                                                </v-avatar>
                                                <v-avatar
                                                    v-else
                                                    color="grey-lighten-2"
                                                    size="50"
                                                >
                                                    <v-icon
                                                        icon="mdi-account"
                                                        color="grey-darken-1"
                                                    />
                                                </v-avatar>
                                            </span>
                                        </v-avatar>
                                        <span
                                            v-if="conv.type === 'private'"
                                            class="online-dot"
                                            :class="
                                                isUserOnline(conv.receiverId)
                                                    ? 'online'
                                                    : 'offline'
                                            "
                                        />
                                    </div>

                                    <div class="flex-grow-1 min-width-0">
                                        <div
                                            class="d-flex align-center justify-space-between"
                                        >
                                            <span
                                                class="text-body-2 font-weight-medium text-truncate"
                                                :class="{
                                                    'font-weight-bold':
                                                        unreadByConversation(
                                                            conv.id,
                                                        ) > 0,
                                                }"
                                            >
                                                {{
                                                    conv.type === "department"
                                                        ? "[" +
                                                          $t("chat.group") +
                                                          "] " +
                                                          conv.name
                                                        : conv.nameUser
                                                }}
                                            </span>
                                            <span
                                                class="text-caption text-grey flex-shrink-0 ms-2"
                                            >
                                                {{
                                                    formatMessageTime(conv.time)
                                                }}
                                            </span>
                                        </div>
                                        <div
                                            class="d-flex align-center justify-space-between mt-1"
                                        >
                                            <span
                                                class="text-caption text-medium-emphasis text-truncate"
                                                :class="{
                                                    'text-high-emphasis font-weight-medium':
                                                        unreadByConversation(
                                                            conv.id,
                                                        ) > 0,
                                                }"
                                                style="max-width: 180px"
                                            >
                                                {{ conv.lastMessage }}
                                            </span>
                                            <v-badge
                                                v-if="
                                                    unreadByConversation(
                                                        conv.id,
                                                    ) > 0
                                                "
                                                :content="
                                                    unreadByConversation(
                                                        conv.id,
                                                    )
                                                "
                                                color="error"
                                                inline
                                                class="flex-shrink-0 ms-1"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </v-list-item>
                            <v-divider
                                v-if="index < filteredConversations.length - 1"
                            />
                        </div>
                    </TransitionGroup>
                </v-list>
            </template>

            <div
                v-if="filteredConversations.length === 0 && !searchQuery.trim()"
                class="d-flex flex-column align-center justify-center pa-8 text-grey"
            >
                <v-icon size="40" color="grey-lighten-1">
                    mdi-chat-sleep-outline
                </v-icon>
                <span class="text-body-2 mt-3">Không có hội thoại nào</span>
            </div>

            <template v-if="searchQuery.trim()">
                <v-divider
                    v-if="filteredConversations.length > 0"
                    class="my-1"
                />

                <div class="section-label px-4 py-2">
                    <v-icon size="14" class="me-1" color="grey-darken-1">
                        mdi-account-plus-outline
                    </v-icon>
                    <span
                        class="text-caption text-grey-darken-1 font-weight-medium"
                        >Nhắn tin mới
                    </span>
                    >
                </div>

                <div v-if="isSearchingUsers" class="d-flex justify-center pa-4">
                    <v-progress-circular
                        indeterminate
                        size="24"
                        width="2"
                        color="primary"
                    />
                </div>

                <v-list v-else-if="availableUsers.length > 0" class="pa-0">
                    <div
                        v-for="(user, index) in availableUsers"
                        :key="'user-' + user.id"
                    >
                        <v-list-item
                            class="conv-item px-3 py-3"
                            style="cursor: pointer"
                            @click="handleStartConversation(user)"
                        >
                            <div class="d-flex align-center ga-3">
                                <div class="avatar-wrap flex-shrink-0">
                                    <v-avatar size="42" color="grey-lighten-2">
                                        <v-img
                                            v-if="user.avatar"
                                            :src="user.avatar"
                                            :alt="user.name"
                                        />
                                        <v-icon
                                            v-else
                                            icon="mdi-account"
                                            color="grey-darken-1"
                                        />
                                    </v-avatar>
                                </div>

                                <div class="flex-grow-1 min-width-0">
                                    <div
                                        class="d-flex align-center justify-space-between"
                                    >
                                        <span
                                            class="text-body-2 font-weight-medium text-truncate"
                                        >
                                            {{ user.name }}
                                        </span>
                                        <v-chip
                                            size="x-small"
                                            variant="tonal"
                                            color="primary"
                                            class="flex-shrink-0 ms-2"
                                        >
                                            <v-icon start size="10">
                                                mdi-plus
                                            </v-icon>
                                            Chat
                                        </v-chip>
                                    </div>
                                    <span
                                        v-if="user.email"
                                        class="text-caption text-medium-emphasis text-truncate d-block"
                                    >
                                        {{ user.email }}
                                    </span>
                                </div>
                            </div>
                        </v-list-item>
                        <v-divider v-if="index < availableUsers.length - 1" />
                    </div>
                </v-list>

                <div
                    v-else
                    class="d-flex flex-column align-center pa-6 text-grey"
                >
                    <v-icon size="32" color="grey-lighten-1">
                        mdi-account-search-outline
                    </v-icon>
                    <span class="text-caption mt-2 text-center">
                        Không tìm thấy người dùng nào
                    </span>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getAllData } from "@/services/bases/getData";
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "ChatConversationList",

    props: {
        conversations: {
            type: Array,
            default: () => [],
        },
        activeConversationId: {
            type: Number,
            default: null,
        },
        mobileMode: {
            type: Boolean,
            default: false,
        },
    },

    emits: ["select", "close", "start-conversation"],

    data() {
        return {
            searchQuery: "",
            availableUsers: [],
            isSearchingUsers: false,
            searchDebounceTimer: null,
        };
    },

    computed: {
        ...mapGetters("chat", ["isUserOnline", "unreadByConversation"]),

        totalUnread() {
            return this.conversations.reduce((sum, c) => sum + c.unread, 0);
        },

        filteredConversations() {
            if (!this.searchQuery.trim()) return this.conversations;
            const query = this.searchQuery.toLowerCase();
            return this.conversations.filter(
                (c) =>
                    (c.nameUser ?? c.name ?? "")
                        .toLowerCase()
                        .includes(query) ||
                    (c.lastMessage ?? "").toLowerCase().includes(query),
            );
        },
    },

    watch: {
        searchQuery(newVal) {
            if (this.searchDebounceTimer) {
                clearTimeout(this.searchDebounceTimer);
            }

            const trimmed = (newVal ?? "").trim();

            if (!trimmed) {
                this.availableUsers = [];
                this.isSearchingUsers = false;
                return;
            }

            this.isSearchingUsers = true;
            this.searchDebounceTimer = setTimeout(() => {
                this.fetchAvailableUsers(trimmed);
            }, 400);
        },
    },

    methods: {
        formatMessageTime(dateString) {
            return functionHelper.formatMessageTime(dateString);
        },

        async fetchAvailableUsers(keyword) {
            const data = await getAllData(
                API_ROUTES_CONFIG.conversation + "/search",
                {
                    keyword,
                },
            );
            this.availableUsers = data ?? [];
            this.isSearchingUsers = false;
        },

        async handleStartConversation(user) {
            this.$emit("start-conversation", user);
        },

        removeAvailableUser(userId) {
            this.availableUsers = this.availableUsers.filter(
                (u) => u.id !== userId,
            );
        },
    },
};
</script>

<style scoped>
.list-header {
    background-color: #fafafa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.active-conv {
    background-color: rgba(var(--v-theme-primary), 0.1) !important;
    border-left: 3px solid rgb(var(--v-theme-primary));
}

.unread-conv {
    background-color: rgba(var(--v-theme-primary), 0.05);
    border-left: 3px solid rgb(var(--v-theme-primary));
}

.conv-item {
    transition: background-color 0.2s;
}

.conv-item:hover {
    background-color: rgba(0, 0, 0, 0.04);
}

.avatar-wrap {
    position: relative;
}

.online-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid white;
}

.online-dot.online {
    background-color: #4caf50;
}

.online-dot.offline {
    background-color: #bdbdbd;
}

.min-width-0 {
    min-width: 0;
}

.section-label {
    background-color: rgba(0, 0, 0, 0.03);
    display: flex;
    align-items: center;
}

.chat-item-enter-active {
    animation: slide-in 0.25s ease;
}

.chat-item-leave-active {
    transition: all 0.2s ease;
}

.chat-item-leave-to {
    opacity: 0;
    transform: translateX(-12px);
}

@keyframes slide-in {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
