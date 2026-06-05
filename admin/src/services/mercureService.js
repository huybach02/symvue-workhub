import store from "@/store";
import axiosInstance from "@/configs/axios";
import presenceService from "@/services/presenceService";
import { topicMercure } from "@/configs/topicMercure";
import { EventSourcePolyfill } from "event-source-polyfill";

const DEFAULT_READ_RETRY_DELAYS = [0, 300, 1000];

const resolveMercureTopics = (currentUser) => {
    const url = new URL(import.meta.env.VITE_MERCURE_URL);
    const userId = currentUser?.id;
    const departmentId = currentUser?.departmentId;

    for (const topic of topicMercure) {
        const resolvedTopic = topic
            .replace(":userId", userId)
            .replace(":departmentId", departmentId);
        url.searchParams.append("topic", resolvedTopic);
    }

    return url;
};

const parseMercureEventData = (event) => {
    if (!event?.data) {
        return null;
    }

    try {
        return JSON.parse(event.data);
    } catch (error) {
        console.error("[Mercure] Payload không hợp lệ:", error);
        return null;
    }
};

export const createMercureConnection = ({
    appStore = store,
    getCurrentUser = () => null,
    onMessage = null,
    onOpen = null,
    onError = null,
    onStatusChange = null,
} = {}) => {
    let eventSource = null;
    let connectionStatus = "connecting";
    let pendingReadTimers = {};
    let readRequestInFlight = {};

    const updateStatus = (status) => {
        connectionStatus = status;
        if (typeof onStatusChange === "function") {
            onStatusChange(status);
        }
    };

    const clearReadTimers = (conversationId) => {
        const timers = pendingReadTimers[conversationId] ?? [];
        timers.forEach((timerId) => window.clearTimeout(timerId));
        delete pendingReadTimers[conversationId];
        delete readRequestInFlight[conversationId];
    };

    const clearAllReadTimers = () => {
        Object.keys(pendingReadTimers).forEach((conversationId) => {
            clearReadTimers(conversationId);
        });
    };

    const scheduleMarkConversationRead = (conversationId) => {
        if (!conversationId) return;
        if ((pendingReadTimers[conversationId] ?? []).length > 0) return;

        pendingReadTimers[conversationId] = DEFAULT_READ_RETRY_DELAYS.map(
            (delay) =>
                window.setTimeout(async () => {
                    if (readRequestInFlight[conversationId]) {
                        return;
                    }

                    readRequestInFlight[conversationId] = true;

                    try {
                        const response = await axiosInstance.post(
                            `/conversation/${conversationId}/read`,
                        );
                        const payload = response?.data ?? response;
                        const responseData = payload?.data ?? payload;
                        const markedCount = Number(
                            responseData?.markedCount ?? 0,
                        );

                        if (markedCount > 0) {
                            clearReadTimers(conversationId);
                            return;
                        }
                    } finally {
                        if (pendingReadTimers[conversationId]) {
                            readRequestInFlight[conversationId] = false;
                        }
                    }
                }, delay),
        );
    };

    const handleMessage = (data) => {
        const currentUser = getCurrentUser();

        switch (data.type) {
            case "presence":
                appStore.commit("chat/SET_USER_ONLINE", {
                    userId: data.userId,
                    online: data.online,
                });
                break;
            case "message":
                window.dispatchEvent(
                    new CustomEvent("chat:message", {
                        detail: data,
                    }),
                );
                appStore.commit("chat/PUSH_MESSAGE", {
                    conversationId: data.conversationId,
                    message: data,
                });
                if (
                    appStore.state.chat.activeConversationId ===
                        data.conversationId &&
                    Number(data.senderId) !== Number(currentUser?.id)
                ) {
                    scheduleMarkConversationRead(data.conversationId);
                }
                if (
                    appStore.state.chat.activeConversationId !==
                    data.conversationId
                ) {
                    appStore.commit("chat/INCREMENT_UNREAD", data.conversationId);
                    appStore.commit("mercure/SET_POPUP_NOTIFICATION", {
                        title: "[Tin nhắn mới] Từ: " + (data.senderName || ""),
                        body: data.content || "",
                        time: data.time || "Vừa xong",
                        icon: "mdi-chat-outline",
                        color: "primary",
                        duration: 5000,
                    });
                }
                break;
            case "message_seen":
                clearReadTimers(data.conversationId);
                appStore.commit("chat/MARK_MESSAGES_SEEN", {
                    conversationId: data.conversationId,
                    messageCodes: data.messageCodes,
                    seenAt: data.seenAt,
                });
                break;
            case "request_refresh":
                appStore.commit(
                    "request/SET_REFRESH_UUID",
                    `${data.requestId || "all"}-${data.timestamp || Date.now()}`,
                );
                break;
            default:
                appStore.commit("mercure/ADD_NOTIFICATION", data);
                appStore.commit("mercure/SET_POPUP_NOTIFICATION", {
                    ...data,
                    title: "[Thông báo mới] " + data.title,
                });
                break;
        }

        if (typeof onMessage === "function") {
            onMessage(data);
        }
    };

    const disconnect = async ({ stopPresence = true } = {}) => {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }

        clearAllReadTimers();
        updateStatus("disconnected");

        if (stopPresence) {
            await presenceService.stopPresence();
        }
    };

    const connect = () => {
        try {
            const currentUser = getCurrentUser();
            const mercureToken = localStorage.getItem("mercure_token");

            if (!currentUser?.id || !mercureToken) {
                updateStatus("error");
                return null;
            }

            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }

            clearAllReadTimers();
            updateStatus("connecting");

            eventSource = new EventSourcePolyfill(
                resolveMercureTopics(currentUser),
                {
                    headers: {
                        Authorization: "Bearer " + mercureToken,
                    },
                },
            );

            eventSource.onopen = () => {
                updateStatus("connected");
                console.log("[Mercure] Kết nối thành công!");
                presenceService.startPresence();

                if (typeof onOpen === "function") {
                    onOpen();
                }
            };

            eventSource.onmessage = (event) => {
                const data = parseMercureEventData(event);
                if (!data) return;
                handleMessage(data);
            };

            eventSource.onerror = (error) => {
                console.error("[Mercure] Lỗi kết nối:", error);
                updateStatus("error");

                if (typeof onError === "function") {
                    onError(error);
                }
            };

            return eventSource;
        } catch (error) {
            console.error("Không thể kết nối Mercure:", error);
            updateStatus("error");

            if (typeof onError === "function") {
                onError(error);
            }

            return null;
        }
    };

    return {
        connect,
        disconnect,
        clearReadTimers,
        clearAllReadTimers,
        scheduleMarkConversationRead,
        getConnectionStatus: () => connectionStatus,
        getEventSource: () => eventSource,
    };
};

export default {
    createMercureConnection,
};
