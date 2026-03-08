<template>
    <NotAuthenticatedMiddleware>
        <PermissionMiddleware>
            <v-card>
                <v-layout>
                    <SidebarMobile v-if="isMobile" />
                    <SidebarPC v-else />
                    <v-main style="min-height: 100vh; overflow-y: auto">
                        <v-progress-linear
                            v-if="$store.state.isLoading"
                            color="primary"
                            indeterminate
                            height="5"
                        />
                        <v-card
                            class="ma-2 pa-4 main-content-card"
                            elevation="3"
                        >
                            <router-view />
                        </v-card>
                    </v-main>
                </v-layout>
            </v-card>
        </PermissionMiddleware>
    </NotAuthenticatedMiddleware>
</template>

<script>
import { mapGetters } from "vuex";
import NotAuthenticatedMiddleware from "@/middlewares/NotAuthenticatedMiddleware.vue";
import SidebarPC from "./SidebarPC.vue";
import SidebarMobile from "./SidebarMobile.vue";
import PermissionMiddleware from "@/middlewares/PermissionMiddleware.vue";
import { EventSourcePolyfill } from "event-source-polyfill";
import { topicMercure } from "@/configs/topicMercure";
import axiosInstance from "@/configs/axios";
import presenceService from "@/services/presenceService";

export default {
    name: "MainLayout",
    components: {
        SidebarPC,
        SidebarMobile,
        NotAuthenticatedMiddleware,
        PermissionMiddleware,
    },
    data() {
        return {
            eventSource: null,
            connectionStatus: "connecting",
        };
    },
    computed: {
        ...mapGetters("auth", ["currentUser"]),
        isMobile() {
            return this.$vuetify.display.mobile;
        },
    },
    watch: {
        currentUser(newVal) {
            if (newVal && newVal.id) {
                if (this.eventSource) {
                    this.eventSource.close();
                    this.eventSource = null;
                }
                this.connectMercure();
                this.danhSachThongBao();
            }
        },
    },
    beforeUnmount() {
        if (this.eventSource) {
            this.eventSource.close();
        }
        presenceService.stopPresence();
    },
    methods: {
        async connectMercure() {
            try {
                const mercureToken = localStorage.getItem("mercure_token");

                const url = new URL(import.meta.env.VITE_MERCURE_URL);
                const userId = this.currentUser?.id;
                const departmentId = this.currentUser?.departmentId;
                for (const topic of topicMercure) {
                    const resolvedTopic = topic
                        .replace(":userId", userId)
                        .replace(":departmentId", departmentId);
                    url.searchParams.append("topic", resolvedTopic);
                }

                this.eventSource = new EventSourcePolyfill(url, {
                    headers: {
                        Authorization: "Bearer " + mercureToken,
                    },
                });

                this.eventSource.onopen = () => {
                    this.connectionStatus = "connected";
                    console.log("[Mercure] Kết nối thành công!");

                    presenceService.startPresence();
                };

                this.eventSource.onmessage = (event) => {
                    const data = JSON.parse(event.data);
                    switch (data.type) {
                        case "presence":
                            this.$store.commit("chat/SET_USER_ONLINE", {
                                userId: data.userId,
                                online: data.online,
                            });
                            break;
                        case "message":
                            this.$store.commit("chat/PUSH_MESSAGE", {
                                conversationId: data.conversationId,
                                message: data,
                            });
                            // Chỉ xử lý khi conversation đó không đang được mở
                            if (
                                this.$store.state.chat.activeConversationId !==
                                data.conversationId
                            ) {
                                // Tăng unread count
                                this.$store.commit(
                                    "chat/INCREMENT_UNREAD",
                                    data.conversationId,
                                );
                                // Hiện popup notification
                                this.$store.commit(
                                    "mercure/SET_POPUP_NOTIFICATION",
                                    {
                                        title:
                                            "[Tin nhắn mới] Từ: " +
                                            (data.senderName || ""),
                                        body: data.content || "",
                                        time: data.time || "Vừa xong",
                                        icon: "mdi-chat-outline",
                                        color: "primary",
                                        duration: 5000,
                                    },
                                );
                            }
                            break;
                        default:
                            this.$store.commit(
                                "mercure/ADD_NOTIFICATION",
                                data,
                            );
                            this.$store.commit(
                                "mercure/SET_POPUP_NOTIFICATION",
                                {
                                    ...data,
                                    title: "[Thông báo mới] " + data.title,
                                },
                            );
                            break;
                    }
                };

                this.eventSource.onerror = (err) => {
                    console.error("[Mercure] Lỗi kết nối:", err);
                    this.connectionStatus = "error";
                };
            } catch (error) {
                console.error("Không thể kết nối Mercure:", error);
                this.connectionStatus = "error";
            }
        },

        async danhSachThongBao() {
            try {
                const res = await axiosInstance.get(
                    `/mercure/danh-sach-thong-bao/${this.currentUser?.id}`,
                );
                this.$store.commit("mercure/SET_NOTIFICATIONS", res.data);
            } catch (error) {
                console.error("Không thể lấy danh sách thông báo:", error);
            }
        },
    },
};
</script>

<style>
.main-content-card {
    min-height: calc(100vh - 100px);
}
</style>
