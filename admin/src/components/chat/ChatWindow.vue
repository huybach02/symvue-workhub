<template>
    <div class="d-flex flex-column" style="height: 100%">
        <div class="chat-header px-4 pt-3 pb-2 flex-shrink-0">
            <div class="d-flex align-center ga-3">
                <v-btn
                    v-if="mobileMode"
                    icon
                    variant="text"
                    size="small"
                    @click="$emit('back')"
                >
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <div class="avatar-wrap flex-shrink-0">
                    <v-avatar size="38">
                        <v-img
                            v-if="conversation.avatar"
                            :src="conversation.avatar"
                        />
                        <span
                            v-else
                            class="text-caption font-weight-bold text-white"
                        >
                            <v-avatar v-if="conversation.avatar" size="42">
                                <v-img
                                    :src="conversation.avatar"
                                    :alt="conversation.nameUser"
                                    cover
                                />
                            </v-avatar>
                            <v-avatar v-else color="grey-lighten-2" size="50">
                                <v-icon
                                    icon="mdi-account"
                                    color="grey-darken-1"
                                />
                            </v-avatar>
                        </span>
                    </v-avatar>
                    <span
                        class="online-dot"
                        :class="conversation.online ? 'online' : 'offline'"
                    />
                </div>

                <div class="flex-grow-1">
                    <div class="text-body-2 font-weight-bold">
                        {{ conversation.nameUser }}
                    </div>
                    <div
                        class="text-caption text-grey d-flex align-center ga-1"
                    >
                        <v-icon
                            size="10"
                            :color="conversation.online ? 'success' : 'grey'"
                        >
                            mdi-circle
                        </v-icon>
                        {{ conversation.online ? "Đang hoạt động" : "Offline" }}
                    </div>
                </div>

                <div class="d-flex align-center ga-1">
                    <v-btn icon variant="text" size="small">
                        <v-icon size="18">mdi-dots-vertical</v-icon>
                        <v-tooltip activator="parent" location="top">
                            Thêm
                        </v-tooltip>
                    </v-btn>
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        @click="$emit('close')"
                    >
                        <v-icon size="20">mdi-close</v-icon>
                        <v-tooltip activator="parent" location="top">
                            Đóng
                        </v-tooltip>
                    </v-btn>
                </div>
            </div>
        </div>

        <v-divider />

        <v-progress-linear
            :active="isLoadingMessages"
            indeterminate
            color="primary"
            height="2"
        />

        <div class="message-wrapper flex-grow-1 position-relative">
            <div ref="messageArea" class="message-area" @scroll="handleScroll">
                <div style="flex: 1" />
                <div
                    v-for="(msg, index) in normalizedMessages"
                    :key="msg.code || index"
                    class="message-row"
                    :class="msg.isMine ? 'justify-end' : 'justify-start'"
                >
                    <v-avatar
                        v-if="!msg.isMine && showAvatar(index)"
                        size="28"
                        class="flex-shrink-0 align-self-end mb-1"
                    >
                        <span class="text-white" style="font-size: 10px">
                            <v-avatar v-if="conversation.avatar" size="42">
                                <v-img
                                    :src="conversation.avatar"
                                    :alt="conversation.nameUser"
                                    cover
                                />
                            </v-avatar>
                            <v-avatar v-else color="grey-lighten-2" size="50">
                                <v-icon
                                    icon="mdi-account"
                                    color="grey-darken-1"
                                />
                            </v-avatar>
                        </span>
                    </v-avatar>
                    <div
                        v-else-if="!msg.isMine"
                        style="width: 28px; flex-shrink: 0"
                    />

                    <div
                        class="message-bubble"
                        :class="msg.isMine ? 'my-bubble' : 'their-bubble'"
                    >
                        <template v-if="msg.type === 'image'">
                            <v-img
                                :src="msg.content"
                                width="200"
                                rounded="lg"
                                class="mb-1"
                            />
                        </template>

                        <template v-else-if="msg.type === 'file'">
                            <div class="file-bubble d-flex align-center ga-2">
                                <v-icon color="primary" size="24">
                                    mdi-file-document-outline
                                </v-icon>
                                <div>
                                    <div
                                        class="text-caption font-weight-medium"
                                    >
                                        {{ msg.fileName }}
                                    </div>
                                    <div class="text-caption text-grey">
                                        {{ msg.fileSize }}
                                    </div>
                                </div>
                                <v-btn
                                    icon
                                    variant="text"
                                    size="x-small"
                                    class="ms-auto"
                                >
                                    <v-icon size="16">mdi-download</v-icon>
                                </v-btn>
                            </div>
                        </template>

                        <template v-else>
                            <span class="text-body-2">{{ msg.content }}</span>
                        </template>

                        <div class="message-meta d-flex align-center ga-1 mt-1">
                            <span
                                class="text-caption"
                                style="font-size: 10px; opacity: 0.7"
                            >
                                {{ msg.time }}
                            </span>
                            <v-icon
                                v-if="msg.isMine"
                                size="12"
                                :color="
                                    msg.isSeen
                                        ? 'white'
                                        : 'rgba(255,255,255,0.5)'
                                "
                            >
                                {{ msg.isSeen ? "mdi-check-all" : "mdi-check" }}
                            </v-icon>
                        </div>
                    </div>
                </div>

                <div v-if="isTyping" class="message-row justify-start">
                    <v-avatar
                        size="28"
                        class="flex-shrink-0 align-self-end mb-1"
                    >
                        <span class="text-white" style="font-size: 10px">
                            <v-avatar v-if="conversation.avatar" size="42">
                                <v-img
                                    :src="conversation.avatar"
                                    :alt="conversation.nameUser"
                                    cover
                                />
                            </v-avatar>
                            <v-avatar v-else color="grey-lighten-2" size="50">
                                <v-icon
                                    icon="mdi-account"
                                    color="grey-darken-1"
                                />
                            </v-avatar>
                        </span>
                    </v-avatar>
                    <div class="message-bubble their-bubble typing-bubble">
                        <div class="typing-dots">
                            <span />
                            <span />
                            <span />
                        </div>
                    </div>
                </div>
            </div>

            <v-btn
                v-show="showScrollBtn"
                icon
                size="small"
                color="warning"
                elevation="4"
                class="scroll-to-bottom-btn"
                @click="scrollToBottom(true)"
            >
                <v-icon size="18">mdi-chevron-double-down</v-icon>
            </v-btn>
        </div>

        <v-divider />

        <div class="chat-input-area px-4 py-2 flex-shrink-0">
            <div class="d-flex align-center ga-1 mb-2">
                <v-btn icon variant="text" size="x-small" color="grey">
                    <v-icon size="18">mdi-emoticon-outline</v-icon>
                    <v-tooltip activator="parent" location="top">
                        Emoji
                    </v-tooltip>
                </v-btn>
                <v-btn icon variant="text" size="x-small" color="grey">
                    <v-icon size="18">mdi-paperclip</v-icon>
                    <v-tooltip activator="parent" location="top">
                        Đính kèm file
                    </v-tooltip>
                </v-btn>
                <v-btn icon variant="text" size="x-small" color="grey">
                    <v-icon size="18">mdi-image-outline</v-icon>
                    <v-tooltip activator="parent" location="top">
                        Gửi ảnh
                    </v-tooltip>
                </v-btn>
            </div>

            <div class="d-flex align-end ga-2">
                <v-textarea
                    v-model="newMessage"
                    variant="outlined"
                    density="compact"
                    placeholder="Nhập tin nhắn..."
                    rows="1"
                    auto-grow
                    max-rows="4"
                    hide-details
                    rounded="lg"
                    class="flex-grow-1"
                    @keydown.enter.exact.prevent="handleSend"
                    @keydown.enter.shift.exact="newMessage += '\n'"
                />
                <v-btn
                    :disabled="!newMessage.trim()"
                    color="primary"
                    icon
                    size="small"
                    @click="handleSend"
                >
                    <v-icon size="18">mdi-send</v-icon>
                </v-btn>
            </div>
            <div class="text-caption text-grey mt-1" style="font-size: 10px">
                Nhấn Enter để gửi · Shift+Enter xuống dòng
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "ChatWindow",

    props: {
        conversation: {
            type: Object,
            required: true,
        },
        messages: {
            type: Array,
            default: () => [],
        },
        isTyping: {
            type: Boolean,
            default: false,
        },
        mobileMode: {
            type: Boolean,
            default: false,
        },
        currentUserId: {
            type: [Number, String],
            default: null,
        },
        isLoadingMessages: {
            type: Boolean,
            default: false,
        },
    },

    emits: ["close", "send", "back"],

    data() {
        return {
            newMessage: "",
            showScrollBtn: false,
        };
    },

    computed: {
        normalizedMessages() {
            return (this.messages ?? []).map((msg) => ({
                ...msg,
                isMine:
                    msg.isMine !== undefined
                        ? msg.isMine
                        : Number(msg.senderId) === Number(this.currentUserId),
            }));
        },
    },

    watch: {
        messages() {
            this.$nextTick(() => {
                if (!this.showScrollBtn) {
                    this.scrollToBottom();
                }
            });
        },
        normalizedMessages() {
            this.$nextTick(() => {
                if (!this.showScrollBtn) {
                    this.scrollToBottom();
                }
            });
        },
        isTyping() {
            this.$nextTick(() => {
                if (!this.showScrollBtn) {
                    this.scrollToBottom();
                }
            });
        },
        conversation() {
            this.newMessage = "";
            this.showScrollBtn = false;
            this.$nextTick(() => this.scrollToBottom());
        },
    },

    mounted() {
        this.$nextTick(() => this.scrollToBottom());
    },

    beforeUnmount() {
        const el = this.$refs.messageArea;
        if (el) el.removeEventListener("scroll", this.handleScroll);
    },

    methods: {
        showAvatar(index) {
            if (index === 0) return true;
            return (
                this.normalizedMessages[index - 1].isMine !==
                this.normalizedMessages[index].isMine
            );
        },

        scrollToBottom(smooth = true) {
            const el = this.$refs.messageArea;
            if (!el) return;
            el.scrollTo({
                top: el.scrollHeight,
                behavior: smooth ? "smooth" : "instant",
            });
        },

        handleScroll() {
            const el = this.$refs.messageArea;
            if (!el) return;
            this.showScrollBtn =
                el.scrollHeight - el.scrollTop - el.clientHeight > 80;
        },

        handleSend() {
            const content = this.newMessage.trim();
            if (!content) return;
            this.$emit("send", content);
            this.newMessage = "";
            this.showScrollBtn = false;
            this.$nextTick(() => this.scrollToBottom());
        },
    },
};
</script>

<style scoped>
.chat-header {
    background-color: #fafafa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
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

.message-wrapper {
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.message-area {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background-color: #f5f7fa;
    -webkit-overflow-scrolling: touch;
}

.scroll-to-bottom-btn {
    position: absolute;
    bottom: 12px;
    right: 12px;
    opacity: 0.9;
    transition:
        opacity 0.2s,
        transform 0.2s;
}

.scroll-to-bottom-btn:hover {
    opacity: 1;
    transform: translateY(-2px);
}

.message-row {
    display: flex;
    align-items: flex-end;
    gap: 6px;
}

.message-bubble {
    max-width: 72%;
    padding: 9px 13px;
    border-radius: 18px;
    word-break: break-word;
}

.my-bubble {
    background-color: rgb(var(--v-theme-primary));
    color: white;
    border-bottom-right-radius: 4px;
}

.their-bubble {
    background-color: #ffffff;
    color: rgba(0, 0, 0, 0.87);
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.message-meta {
    justify-content: flex-end;
}

.file-bubble {
    background-color: rgba(0, 0, 0, 0.04);
    border-radius: 8px;
    padding: 8px;
    min-width: 180px;
}

.typing-bubble {
    padding: 10px 14px;
}

.typing-dots {
    display: flex;
    align-items: center;
    gap: 4px;
}

.typing-dots span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background-color: #bdbdbd;
    animation: typing 1.2s infinite ease-in-out;
}

.typing-dots span:nth-child(2) {
    animation-delay: 0.2s;
}
.typing-dots span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%,
    60%,
    100% {
        transform: translateY(0);
        opacity: 0.4;
    }
    30% {
        transform: translateY(-6px);
        opacity: 1;
    }
}

.chat-input-area {
    background-color: #fff;
}
</style>
