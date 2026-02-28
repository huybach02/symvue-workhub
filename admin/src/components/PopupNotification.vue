<template>
    <Teleport to="body">
        <div class="popup-notification-stack">
            <TransitionGroup name="toast" tag="div">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="toast-item"
                    @mouseenter="pauseTimer(toast)"
                    @mouseleave="resumeTimer(toast)"
                >
                    <div class="toast-body">
                        <v-avatar
                            size="36"
                            :color="toast.color"
                            class="toast-avatar"
                        >
                            <v-icon size="18" color="white">
                                {{ toast.icon }}
                            </v-icon>
                        </v-avatar>

                        <div class="toast-content">
                            <div class="toast-title">{{ toast.title }}</div>
                            <div class="toast-message">{{ toast.body }}</div>
                            <div class="toast-time">{{ toast.time }}</div>
                        </div>

                        <v-btn
                            icon
                            variant="text"
                            size="x-small"
                            class="toast-close"
                            @click="dismiss(toast.id)"
                        >
                            <v-icon size="14">mdi-close</v-icon>
                        </v-btn>
                    </div>

                    <div class="toast-progress">
                        <div
                            class="toast-progress-bar"
                            :style="{
                                width: toast.progress + '%',
                                backgroundColor: getProgressColor(toast.color),
                                animationDuration: toast.duration + 'ms',
                                animationPlayState: toast.paused
                                    ? 'paused'
                                    : 'running',
                            }"
                        ></div>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script>
export default {
    name: "PopupNotification",
    props: {
        drawerOpen: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            toasts: [],
            nextId: 1,
        };
    },

    computed: {
        popupNotification() {
            return this.$store.state.mercure.popupNotification;
        },
    },

    watch: {
        popupNotification(newVal) {
            if (newVal && !this.drawerOpen) {
                this.push(newVal);
            }
        },
    },

    methods: {
        push(notification) {
            const duration = notification.duration || 5000;
            const id = this.nextId++;
            const toast = {
                id,
                title: notification.title || "Thông báo",
                body: notification.body || "",
                time: notification.time || "Vừa xong",
                icon: notification.icon || "mdi-bell-outline",
                color: notification.color || "primary",
                duration,
                progress: 100,
                paused: false,
                startedAt: Date.now(),
                elapsed: 0,
                timer: null,
                rafId: null,
            };

            this.toasts.push(toast);
            const reactiveToast = this.toasts[this.toasts.length - 1];
            this._startProgress(reactiveToast);
        },
        _startProgress(toast) {
            const step = () => {
                if (toast.paused) return;
                const now = Date.now();
                const elapsed = toast.elapsed + (now - toast.startedAt);
                toast.progress = Math.max(
                    0,
                    100 - (elapsed / toast.duration) * 100,
                );

                if (elapsed >= toast.duration) {
                    this.dismiss(toast.id);
                } else {
                    toast.rafId = requestAnimationFrame(step);
                }
            };
            toast.startedAt = Date.now();
            toast.rafId = requestAnimationFrame(step);
        },

        pauseTimer(toast) {
            toast.paused = true;
            toast.elapsed += Date.now() - toast.startedAt;
            if (toast.rafId) cancelAnimationFrame(toast.rafId);
        },

        resumeTimer(toast) {
            toast.paused = false;
            toast.startedAt = Date.now();
            this._startProgress(toast);
        },

        dismiss(id) {
            const index = this.toasts.findIndex((t) => t.id === id);
            if (index !== -1) {
                const toast = this.toasts[index];
                if (toast.rafId) cancelAnimationFrame(toast.rafId);
                this.toasts.splice(index, 1);
            }
            // Sau khi tất cả toast biến mất, reset lại giá trị trong store
            if (this.toasts.length === 0) {
                this.$store.commit("mercure/REMOVE_POPUP_NOTIFICATION");
            }
        },

        getProgressColor(color) {
            const map = {
                primary: "rgb(var(--v-theme-primary))",
                success: "rgb(var(--v-theme-success))",
                warning: "rgb(var(--v-theme-warning))",
                error: "rgb(var(--v-theme-error))",
                info: "rgb(var(--v-theme-info))",
            };
            return map[color] || "rgb(var(--v-theme-primary))";
        },
    },
};
</script>

<style scoped>
.popup-notification-stack {
    position: fixed;
    top: 70px;
    right: 16px;
    z-index: 2200;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
    max-width: calc(100vw - 32px);
}

.toast-item {
    pointer-events: all;
    width: 450px;
    max-width: 100%;
    background: #ffffff;
    border-radius: 10px;
    box-shadow:
        0 4px 16px rgba(0, 0, 0, 0.12),
        0 1px 4px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.07);
    cursor: default;
    transition: box-shadow 0.2s;
}

.toast-item:hover {
    box-shadow:
        0 8px 24px rgba(0, 0, 0, 0.15),
        0 2px 8px rgba(0, 0, 0, 0.1);
}

.toast-body {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 12px 10px 14px;
}

.toast-avatar {
    flex-shrink: 0;
    margin-top: 2px;
}

.toast-content {
    flex: 1;
    min-width: 0;
}

.toast-title {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.toast-message {
    font-size: 12px;
    color: #555;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.toast-time {
    font-size: 11px;
    color: #aaa;
    margin-top: 4px;
}

.toast-close {
    flex-shrink: 0;
    margin-top: -2px;
    opacity: 0.5;
    transition: opacity 0.2s;
}

.toast-close:hover {
    opacity: 1;
}

.toast-progress {
    height: 3px;
    background: rgba(0, 0, 0, 0.06);
}

.toast-progress-bar {
    height: 100%;
    transition: width 0.1s linear;
    border-radius: 0 2px 2px 0;
}

.toast-enter-active {
    transition: all 0.35s cubic-bezier(0.21, 1.02, 0.73, 1);
}

.toast-leave-active {
    transition: all 0.25s ease-in;
}

.toast-enter-from {
    transform: translateX(110%);
    opacity: 0;
}

.toast-leave-to {
    transform: translateX(110%);
    opacity: 0;
}

.toast-move {
    transition: transform 0.3s ease;
}

@media (max-width: 600px) {
    .popup-notification-stack {
        left: 8px;
        right: 8px;
        top: 65px;
        max-width: 100%;
    }

    .toast-item {
        width: 100%;
    }

    .toast-enter-from {
        transform: translateY(-20px);
        opacity: 0;
    }

    .toast-leave-to {
        transform: translateY(-20px);
        opacity: 0;
    }
}
</style>
