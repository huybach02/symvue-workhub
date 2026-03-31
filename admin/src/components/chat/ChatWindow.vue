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
                        v-if="conversation.type === 'private'"
                        class="online-dot"
                        :class="
                            isUserOnline(conversation.receiverId)
                                ? 'online'
                                : 'offline'
                        "
                    />
                </div>

                <div class="flex-grow-1">
                    <div class="text-body-2 font-weight-bold">
                        {{ conversationTitle }}
                    </div>
                    <div
                        class="text-caption text-grey d-flex align-center ga-1"
                    >
                        <v-icon
                            size="14"
                            :color="
                                conversation.type === 'department'
                                    ? 'primary'
                                    : isUserOnline(conversation.receiverId)
                                      ? 'success'
                                      : 'grey'
                            "
                        >
                            {{
                                conversation.type === "department"
                                    ? "mdi-account-group"
                                    : "mdi-circle"
                            }}
                        </v-icon>
                        {{ memberLabel }}
                    </div>
                </div>

                <div class="d-flex align-center ga-1">
                    <!-- <v-btn icon variant="text" size="small">
                        <v-icon size="18">mdi-dots-vertical</v-icon>
                        <v-tooltip activator="parent" location="top">
                            Thêm
                        </v-tooltip>
                    </v-btn> -->
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        @click="$emit('close')"
                    >
                        <v-icon size="20">mdi-close</v-icon>
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
                        <template v-if="msg.images?.length > 0">
                            <div class="d-flex flex-wrap ga-2 mb-2">
                                <v-img
                                    v-for="(image, index) in msg.images"
                                    :key="index"
                                    :src="resolveMediaUrl(image)"
                                    width="100"
                                    height="100"
                                    rounded="lg"
                                    class="mb-1 message-image-preview"
                                    cover
                                    @click="
                                        openImagePreview(resolveMediaUrl(image))
                                    "
                                />
                            </div>
                        </template>

                        <template v-if="msg.files?.length > 0">
                            <div
                                v-for="(file, fIndex) in msg.files"
                                :key="fIndex"
                                class="file-bubble d-flex align-center ga-2 mb-1"
                            >
                                <v-icon
                                    :color="msg.isMine ? 'white' : 'primary'"
                                    size="24"
                                >
                                    mdi-file-document-outline
                                </v-icon>
                                <div class="flex-grow-1 text-truncate">
                                    <div
                                        class="text-caption font-weight-medium text-truncate"
                                        style="max-width: 160px"
                                    >
                                        {{ file.name }}
                                    </div>
                                    <div class="text-caption text-grey">
                                        {{ formatFileSize(file.size) }}
                                    </div>
                                </div>
                                <v-btn
                                    icon
                                    variant="text"
                                    size="x-small"
                                    class="ms-auto"
                                    @click="downloadFile(file)"
                                >
                                    <v-icon size="16">mdi-download</v-icon>
                                </v-btn>
                            </div>
                        </template>

                        <span class="text-body-2">{{ msg.content }}</span>

                        <div class="message-meta d-flex align-center ga-1 mt-2">
                            <p
                                class="text-caption"
                                style="font-size: 10px; opacity: 0.7"
                            >
                                {{ formatTime(msg.time) }}
                            </p>
                            <v-icon
                                v-if="
                                    msg.isMine &&
                                    conversation.type === 'private'
                                "
                                size="12"
                                color="rgba(255, 255, 255, 0.5)"
                            >
                                {{
                                    msg.isSeen
                                        ? "mdi-check-circle-outline"
                                        : "mdi-check"
                                }}
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
                <v-menu
                    :close-on-content-click="false"
                    location="top"
                    offset="10"
                >
                    <template #activator="{ props }">
                        <v-btn
                            icon
                            variant="text"
                            size="x-small"
                            color="grey"
                            v-bind="props"
                        >
                            <v-icon size="18">mdi-emoticon-outline</v-icon>
                            <v-tooltip activator="parent" location="top">
                                Emoji
                            </v-tooltip>
                        </v-btn>
                    </template>
                    <Picker
                        :data="emojiIndex"
                        set="twitter"
                        @select="onSelectEmoji"
                    />
                </v-menu>
                <v-btn
                    icon
                    variant="text"
                    size="x-small"
                    color="grey"
                    @click="triggerFileSelect"
                >
                    <v-icon size="18">mdi-paperclip</v-icon>
                    <v-tooltip activator="parent" location="top">
                        {{ $t("chat.attach_file") }}
                    </v-tooltip>
                </v-btn>
                <v-btn
                    icon
                    variant="text"
                    size="x-small"
                    color="grey"
                    @click="triggerImageSelect"
                >
                    <v-icon size="18">mdi-image-outline</v-icon>
                    <v-tooltip activator="parent" location="top">
                        {{ $t("chat.send_image") }}
                    </v-tooltip>
                </v-btn>
                <input
                    ref="imageInput"
                    type="file"
                    accept="image/*"
                    multiple
                    style="display: none"
                    @change="handleImageSelected"
                />
                <input
                    ref="fileInput"
                    type="file"
                    multiple
                    style="display: none"
                    @change="handleFileSelected"
                />
            </div>

            <div
                v-if="selectedImages.length > 0"
                class="d-flex align-center ga-2 mb-2 px-2 pb-2 flex-wrap"
            >
                <div
                    v-for="(img, index) in selectedImages"
                    :key="index"
                    class="position-relative"
                    style="width: fit-content"
                >
                    <img
                        :src="img.preview"
                        style="
                            max-width: 80px;
                            max-height: 80px;
                            border-radius: 8px;
                            border: 1px solid #e0e0e0;
                            object-fit: contain;
                        "
                    />
                    <v-btn
                        icon
                        size="x-small"
                        color="error"
                        variant="flat"
                        class="position-absolute"
                        style="
                            top: -8px;
                            right: -8px;
                            z-index: 1;
                            min-width: 20px;
                            width: 20px;
                            height: 20px;
                        "
                        @click="removeSelectedImage(index)"
                    >
                        <v-icon size="12">mdi-close</v-icon>
                    </v-btn>
                </div>
            </div>

            <!-- Preview danh sách file đã chọn -->
            <div
                v-if="selectedFiles.length > 0"
                class="d-flex flex-column ga-1 mb-2 px-2"
            >
                <div
                    v-for="(f, index) in selectedFiles"
                    :key="index"
                    class="file-preview-item d-flex align-center ga-2"
                >
                    <v-icon color="primary" size="20"
                        >mdi-file-document-outline</v-icon
                    >
                    <div class="flex-grow-1 text-truncate">
                        <div
                            class="text-caption font-weight-medium text-truncate"
                            style="max-width: 180px"
                        >
                            {{ f.name }}
                        </div>
                        <div class="text-caption text-grey">
                            {{ formatFileSize(f.size) }}
                        </div>
                    </div>
                    <v-btn
                        icon
                        size="x-small"
                        variant="text"
                        color="error"
                        @click="removeSelectedFile(index)"
                    >
                        <v-icon size="14">mdi-close</v-icon>
                    </v-btn>
                </div>
            </div>

            <div class="d-flex align-end ga-2">
                <v-textarea
                    ref="chatInput"
                    v-model="newMessage"
                    variant="outlined"
                    density="compact"
                    :placeholder="$t('chat.enter_message')"
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
                    :disabled="
                        !newMessage.trim() &&
                        selectedImages.length === 0 &&
                        selectedFiles.length === 0
                    "
                    color="primary"
                    icon
                    size="small"
                    @click="handleSend"
                >
                    <v-icon size="18">mdi-send</v-icon>
                </v-btn>
            </div>
            <div class="text-caption text-grey mt-1" style="font-size: 10px">
                {{ $t("chat.enter_to_send_shift_to_wrap") }}
            </div>
        </div>
        <ImagePreviewDialog
            v-model="imagePreviewDialog"
            :image-url="imagePreviewUrl"
        />
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import data from "emoji-mart-vue-fast/data/all.json";
import "emoji-mart-vue-fast/css/emoji-mart.css";
import { Picker, EmojiIndex } from "emoji-mart-vue-fast/src";
import { functionHelper } from "@/helpers/functionHelper";
import { constant } from "@/utils/constants/constant";
import axiosInstance from "@/configs/axios";
import { toast } from "@/main";
import ImagePreviewDialog from "../ImagePreviewDialog.vue";

let emojiIndex = new EmojiIndex(data);

export default {
    name: "ChatWindow",
    components: {
        Picker,
        ImagePreviewDialog,
    },
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
            emojiIndex: emojiIndex,
            emojisOutput: "",
            selectedImages: [], // Mảng chứa objects { file, preview }
            selectedFiles: [], // Mảng chứa File objects đã chọn
            imagePreviewDialog: false,
            imagePreviewUrl: "",
        };
    },

    computed: {
        ...mapGetters("chat", ["isUserOnline"]),

        normalizedMessages() {
            return (this.messages ?? []).map((msg) => ({
                ...msg,
                isMine:
                    msg.isMine !== undefined
                        ? msg.isMine
                        : Number(msg.senderId) === Number(this.currentUserId),
            }));
        },
        conversationTitle() {
            return this.conversation.type === "department"
                ? "[" + this.$t("chat.group") + "] " + this.conversation.name
                : this.conversation?.nameUser || this.conversation?.name || "";
        },
        memberLabel() {
            if (this.conversation?.type === "department") {
                const count = Number(this.conversation?.memberCount ?? 0);
                return `${count} thành viên`;
            }

            return this.isUserOnline(this.conversation?.receiverId)
                ? "Đang hoạt động"
                : "Offline";
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
        imagePreviewDialog(isOpen) {
            if (!isOpen) {
                this.imagePreviewUrl = "";
            }
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
        formatTime(timeString) {
            return functionHelper.formatMessageTime(timeString);
        },

        getApiOrigin() {
            const apiBaseUrl = import.meta.env.VITE_API_BASE_URL;

            if (!apiBaseUrl) {
                return window.location.origin;
            }

            try {
                return new URL(apiBaseUrl, window.location.origin).origin;
            } catch (error) {
                return window.location.origin;
            }
        },

        resolveMediaUrl(url) {
            if (!url) return "";

            const apiOrigin = this.getApiOrigin();

            if (url.startsWith("/")) {
                return `${apiOrigin}${url}`;
            }

            try {
                const parsedUrl = new URL(url, window.location.origin);

                if (parsedUrl.pathname.startsWith("/uploads/")) {
                    return `${apiOrigin}${parsedUrl.pathname}${parsedUrl.search}${parsedUrl.hash}`;
                }

                return parsedUrl.toString();
            } catch (error) {
                return url;
            }
        },

        async downloadFile(file) {
            const fileUrl = this.resolveMediaUrl(file?.url);

            if (!fileUrl) {
                toast("Khong tim thay duong dan tep", "error");
                return;
            }

            try {
                const response = await axiosInstance.get(fileUrl, {
                    responseType: "blob",
                    timeout: 30000,
                });

                const blobUrl = window.URL.createObjectURL(response.data);
                const link = document.createElement("a");
                link.href = blobUrl;
                link.download = file?.name || "download";
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(blobUrl);
            } catch (error) {
                window.open(fileUrl, "_blank", "noopener,noreferrer");
            }
        },

        openImagePreview(imageUrl) {
            if (!imageUrl) return;

            this.imagePreviewUrl = imageUrl;
            this.imagePreviewDialog = true;
        },

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
            if (
                !content &&
                this.selectedImages.length === 0 &&
                this.selectedFiles.length === 0
            )
                return;

            const imageFiles = this.selectedImages.map((img) => img.file);
            const fileFiles = [...this.selectedFiles];
            this.$emit("send", content, imageFiles, fileFiles);

            this.newMessage = "";
            this.removeAllSelectedImages();
            this.selectedFiles = [];
            this.showScrollBtn = false;
            this.$nextTick(() => this.scrollToBottom());
        },

        triggerImageSelect() {
            if (this.$refs.imageInput) {
                this.$refs.imageInput.value = "";
                this.$refs.imageInput.click();
            }
        },

        handleImageSelected(event) {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            const maxImages = constant.MAX_IMAGE_UPLOAD;
            const validImages = Array.from(files).filter((file) =>
                file.type.startsWith("image/"),
            );
            const imagesToAdd = validImages.slice(0, maxImages);

            imagesToAdd.forEach((file) => {
                this.selectedImages.push({
                    file: file,
                    preview: URL.createObjectURL(file),
                });
            });

            setTimeout(() => {
                this.scrollToBottom();
            }, 100);

            event.target.value = null;
        },

        removeSelectedImage(index) {
            const img = this.selectedImages[index];
            if (img && img.preview) {
                URL.revokeObjectURL(img.preview);
            }
            this.selectedImages.splice(index, 1);
        },

        removeAllSelectedImages() {
            this.selectedImages.forEach((img) => {
                if (img.preview) {
                    URL.revokeObjectURL(img.preview);
                }
            });
            this.selectedImages = [];
        },

        triggerFileSelect() {
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = "";
                this.$refs.fileInput.click();
            }
        },

        handleFileSelected(event) {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            const maxFiles = constant.MAX_FILE_UPLOAD;
            const validFiles = Array.from(files).filter(
                (file) => !file.type.startsWith("image/"),
            );
            const filesToAdd = validFiles.slice(0, maxFiles);

            filesToAdd.forEach((file) => {
                this.selectedFiles.push(file);
            });

            setTimeout(() => {
                this.scrollToBottom();
            }, 100);

            event.target.value = null;
        },

        removeSelectedFile(index) {
            this.selectedFiles.splice(index, 1);
        },

        formatFileSize(bytes) {
            if (bytes === 0) return "0 B";
            const k = 1024;
            const sizes = ["B", "KB", "MB", "GB"];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return (
                parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i]
            );
        },

        onSelectEmoji(emoji) {
            const emojiChar = emoji.native;
            const textarea = this.$refs.chatInput.$el.querySelector("textarea");

            if (textarea) {
                const startPos = textarea.selectionStart;
                const endPos = textarea.selectionEnd;

                this.newMessage =
                    this.newMessage.substring(0, startPos) +
                    emojiChar +
                    this.newMessage.substring(endPos);
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(
                        startPos + emojiChar.length,
                        startPos + emojiChar.length,
                    );
                });
            } else {
                this.newMessage += emojiChar;
            }
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

.message-image-preview {
    cursor: zoom-in;
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

.file-preview-item {
    background-color: #f5f7fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 6px 10px;
}
</style>
