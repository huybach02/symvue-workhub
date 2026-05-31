<template>
    <div class="notification-wrapper">
        <PopupNotification ref="popup" :drawer-open="drawer" />

        <div :class="{ 'bell-pulse-wrapper': unreadCount > 0 }">
            <v-btn
                icon
                variant="text"
                class="notification-btn"
                @click="openDrawer"
            >
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
                        :class="{ 'bell-shake': unreadCount > 0 }"
                    >
                        mdi-bell-outline
                    </v-icon>
                </v-badge>
            </v-btn>
        </div>

        <Teleport to="body">
            <v-navigation-drawer
                v-model="drawer"
                location="right"
                temporary
                :scrim="false"
                width="450"
                style="z-index: 3100; top: 0; height: 100vh"
            >
                <div class="drawer-header ps-4 pt-3 pb-2">
                    <div class="d-flex align-center justify-space-between">
                        <span class="text-subtitle-1 font-weight-bold">
                            <v-icon size="20">mdi-bell-outline</v-icon>
                            {{ $t("thong-bao.title") }}
                        </span>
                        <div class="d-flex align-center ga-2">
                            <v-btn
                                v-if="unreadCount > 0"
                                variant="text"
                                size="small"
                                color="primary"
                                @click="markAllRead"
                            >
                                {{ $t("thong-bao.read_all") }}
                            </v-btn>
                            <v-btn
                                icon
                                variant="text"
                                size="small"
                                @click="drawer = false"
                            >
                                <v-icon>mdi-close</v-icon>
                            </v-btn>
                        </div>
                    </div>

                    <div class="d-flex align-center ga-2 mt-2 mb-1">
                        <v-chip size="x-small" variant="tonal" color="primary">
                            <v-icon start size="12">mdi-bell-outline</v-icon>
                            {{ $t("thong-bao.total") }}:
                            {{ notifications.length }}
                        </v-chip>
                        <v-chip size="x-small" variant="tonal" color="error">
                            <v-icon start size="12">mdi-bell-ring</v-icon>
                            {{ $t("thong-bao.unread") }}: {{ unreadCount }}
                        </v-chip>
                        <v-chip size="x-small" variant="tonal" color="success">
                            <v-icon start size="12">
                                mdi-check-circle-outline
                            </v-icon>
                            {{ $t("thong-bao.read") }}: {{ readCount }}
                        </v-chip>
                    </div>
                </div>

                <v-divider />

                <v-list class="pa-0">
                    <TransitionGroup name="notif-item" tag="div">
                        <div
                            v-for="(item, index) in notifications"
                            :key="item.id"
                        >
                            <v-list-item
                                :class="{ 'unread-item': !item.seen }"
                                class="notification-item px-4 py-3"
                                style="cursor: pointer"
                            >
                                <p
                                    class="text-caption text-grey font-weight-regular mb-1 text-right"
                                    style="margin: 0 0 4px 0"
                                >
                                    {{ functionHelper.timeAgo(item.createdAt) }}
                                </p>

                                <div class="d-flex align-center ga-5">
                                    <v-avatar
                                        size="36"
                                        :color="item.color"
                                        class="flex-shrink-0"
                                    >
                                        <v-icon size="18" color="white">
                                            {{ item.icon }}
                                        </v-icon>
                                    </v-avatar>

                                    <div class="flex-grow-1">
                                        <div
                                            class="text-body-2 font-weight-medium"
                                            style="
                                                white-space: normal;
                                                word-break: break-word;
                                            "
                                        >
                                            <b>{{ item.title }}</b>
                                        </div>
                                        <div
                                            class="text-caption text-medium-emphasis mt-1 text-justify"
                                        >
                                            {{ item.body }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="!item.seen"
                                    class="d-flex justify-end mt-2"
                                >
                                    <v-btn
                                        icon
                                        variant="tonal"
                                        size="small"
                                        color="success"
                                        @click="markAsRead(item.code)"
                                    >
                                        <v-icon size="16">
                                            mdi-checkbox-marked-circle-outline
                                        </v-icon>
                                    </v-btn>
                                </div>
                            </v-list-item>
                            <v-divider
                                v-if="index < notifications.length - 1"
                            />
                        </div>
                    </TransitionGroup>

                    <div
                        v-if="notifications.length === 0"
                        class="d-flex flex-column align-center justify-center pa-8 text-grey"
                    >
                        <v-icon size="48" color="grey-lighten-1">
                            mdi-bell-off-outline
                        </v-icon>
                        <span class="text-body-2 mt-3">
                            {{ $t("thong-bao.no_notification") }}
                        </span>
                    </div>
                </v-list>
            </v-navigation-drawer>
        </Teleport>
    </div>
</template>

<script>
import axiosInstance from "@/configs/axios";
import { mapGetters } from "vuex";
import PopupNotification from "./PopupNotification.vue";
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "NotificationRealtime",
    components: {
        PopupNotification,
    },
    data() {
        return {
            drawer: false,
            functionHelper,
        };
    },
    computed: {
        ...mapGetters("auth", ["currentUser"]),
        notifications() {
            return this.$store.state.mercure.notifications;
        },
        unreadCount() {
            return this.notifications.filter((n) => !n.seen).length;
        },
        readCount() {
            return this.notifications.filter((n) => n.seen).length;
        },
        badgeLabel() {
            return this.unreadCount > 99 ? "99+" : String(this.unreadCount);
        },
    },
    mounted() {},
    methods: {
        openDrawer() {
            this.drawer = true;
        },

        async markAsRead(code) {
            this.$store.commit("mercure/MARK_AS_READ", code);

            try {
                await axiosInstance.get(
                    `/mercure/notification-list/${this.currentUser?.id}/read/${code}`,
                );
            } catch (error) {
                console.error(
                    "Không thể đánh dấu một thông báo đã đọc:",
                    error,
                );
            }
        },

        async markAllRead() {
            const updated = this.notifications.map((n) => ({
                ...n,
                seen: true,
            }));
            this.$store.commit("mercure/SET_NOTIFICATIONS", updated);

            try {
                await axiosInstance.get(
                    `/mercure/notification-list/${this.currentUser?.id}/read-all`,
                );
            } catch (error) {
                console.error(
                    "Không thể đánh dấu tất cả thông báo đã đọc:",
                    error,
                );
            }
        },
    },
};
</script>

<style scoped>
.notification-wrapper {
    display: inline-flex;
    align-items: center;
    position: relative;
}

.bell-shake {
    transform-origin: top center;
    animation: bell-ring 3s ease infinite;
}

@keyframes bell-ring {
    0% {
        transform: rotate(0deg);
    }
    5% {
        transform: rotate(18deg);
    }
    10% {
        transform: rotate(-16deg);
    }
    15% {
        transform: rotate(14deg);
    }
    20% {
        transform: rotate(-12deg);
    }
    25% {
        transform: rotate(8deg);
    }
    30% {
        transform: rotate(-4deg);
    }
    35% {
        transform: rotate(2deg);
    }
    40% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(0deg);
    }
}

.notification-btn {
    position: relative;
}

/* Vòng tròn pulse lan ra khi có thông báo chưa đọc */
.bell-pulse-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.bell-pulse-wrapper::before,
.bell-pulse-wrapper::after {
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

.bell-pulse-wrapper::after {
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

.drawer-header {
    background-color: #fafafa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
}

.unread-item {
    background-color: rgba(var(--v-theme-primary), 0.05);
    border-left: 3px solid rgb(var(--v-theme-primary));
}

.notification-item {
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: rgba(0, 0, 0, 0.03);
}
.notif-item-enter-active {
    animation: highlight-new 1.2s ease forwards;
}

.notif-item-leave-active {
    transition: all 0.25s ease;
}

.notif-item-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

@keyframes highlight-new {
    0% {
        opacity: 0;
        transform: translateY(-16px) scaleY(0.95);
        background-color: rgba(var(--v-theme-primary), 0.35);
        box-shadow: inset 4px 0 0 rgb(var(--v-theme-primary));
    }
    20% {
        opacity: 1;
        transform: translateY(0) scaleY(1);
        background-color: rgba(var(--v-theme-primary), 0.35);
        box-shadow: inset 4px 0 0 rgb(var(--v-theme-primary));
    }
    50% {
        background-color: rgba(var(--v-theme-primary), 0.12);
        box-shadow: inset 4px 0 0 rgba(var(--v-theme-primary), 0.4);
    }
    70% {
        background-color: rgba(var(--v-theme-primary), 0.25);
        box-shadow: inset 4px 0 0 rgb(var(--v-theme-primary));
    }
    100% {
        opacity: 1;
        transform: translateY(0) scaleY(1);
        background-color: transparent;
        box-shadow: inset 3px 0 0 rgba(var(--v-theme-primary), 0);
    }
}
</style>
