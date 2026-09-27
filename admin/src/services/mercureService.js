import store from "@/store";
import axiosInstance from "@/configs/axios";
import presenceService from "@/services/presenceService";
import { topicMercure } from "@/configs/topicMercure";
import { EventSourcePolyfill } from "event-source-polyfill";
import i18n from "@/plugins/i18n";

const t = (key, params) => i18n.global.t(key, params);

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
        console.error(`[Mercure] ${t("mercure.invalid_payload")}`, error);
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
            case "attendance":
                window.dispatchEvent(
                    new CustomEvent("attendance:updated", {
                        detail: data,
                    }),
                );
                break;
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
                        title: t("mercure.chat.new_message_title", {
                            sender: data.senderName || "",
                        }),
                        body: data.content || "",
                        time: data.time || t("mercure.just_now"),
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
            case "sale_order_created": {
                const saleOrder = data.saleOrder;
                if (!saleOrder) break;

                const isAdmin = Boolean(
                    currentUser?.roles?.includes("ROLE_ADMIN"),
                );
                const orderBranchId = Number(
                    saleOrder.branchId ?? saleOrder.branch?.id ?? 0,
                );
                const assignedBranchIds = Array.isArray(
                    currentUser?.assignedBranchIds,
                )
                    ? currentUser.assignedBranchIds.map(Number)
                    : [];

                // Chỉ admin hoặc nhân sự thuộc đúng chi nhánh của đơn hàng mới nhận được dữ liệu realtime
                const isAllowedBranch =
                    isAdmin ||
                    (orderBranchId > 0 &&
                        assignedBranchIds.includes(orderBranchId));

                if (!isAllowedBranch) {
                    break;
                }

                window.dispatchEvent(
                    new CustomEvent("sale_order:created", {
                        detail: data,
                    }),
                );
                appStore.commit("saleOrder/PREPEND_ITEM", saleOrder);

                const tableInfo = saleOrder?.diningTable?.tableNumber
                    ? t("sell_product.table_label", {
                          number: saleOrder.diningTable.tableNumber,
                      })
                    : t("sale_order.table_takeaway");
                const branchName = saleOrder?.branch?.name
                    ? t("mercure.sale_order.branch_suffix", {
                          branch: saleOrder.branch.name,
                      })
                    : "";
                const currencySymbol = t("sale_order.currency_symbol") || "đ";
                const totalFormatted = saleOrder?.totalAmount
                    ? `${Number(saleOrder.totalAmount).toLocaleString("vi-VN")} ${currencySymbol}`
                    : `0 ${currencySymbol}`;

                const notification = {
                    code: data.code || `sale_order_${saleOrder.id || Date.now()}`,
                    title: t("mercure.sale_order.created_title", {
                        code: saleOrder.code || "",
                    }),
                    body: t("mercure.sale_order.created_body", {
                        table: tableInfo,
                        branch: branchName,
                        total: totalFormatted,
                    }),
                    time: t("mercure.just_now"),
                    icon: "mdi-receipt-text-outline",
                    color: "success",
                    seen: false,
                    link: "/sale-order",
                    createdAt: data.timestamp || new Date().toISOString(),
                    duration: 5000,
                };

                appStore.commit("mercure/ADD_NOTIFICATION", notification);
                appStore.commit("mercure/SET_POPUP_NOTIFICATION", notification);
                break;
            }
            case "sale_order_status_updated": {
                const saleOrder = data.saleOrder;
                if (!saleOrder) break;

                const isAdmin = Boolean(
                    currentUser?.roles?.includes("ROLE_ADMIN"),
                );
                const orderBranchId = Number(
                    saleOrder.branchId ?? saleOrder.branch?.id ?? 0,
                );
                const assignedBranchIds = Array.isArray(
                    currentUser?.assignedBranchIds,
                )
                    ? currentUser.assignedBranchIds.map(Number)
                    : [];

                // Chỉ admin hoặc nhân sự thuộc đúng chi nhánh của đơn hàng mới nhận được dữ liệu realtime
                const isAllowedBranch =
                    isAdmin ||
                    (orderBranchId > 0 &&
                        assignedBranchIds.includes(orderBranchId));

                if (!isAllowedBranch) {
                    break;
                }

                window.dispatchEvent(
                    new CustomEvent("sale_order:status_updated", {
                        detail: data,
                    }),
                );
                appStore.commit("saleOrder/UPDATE_ITEM", saleOrder);

                // Chỉ gửi thông báo khi đơn hàng được thanh toán và hoàn tất (COMPLETED)
                if (saleOrder.status === "COMPLETED") {
                    const currencySymbol =
                        t("sale_order.currency_symbol") || "đ";
                    const totalFormatted = saleOrder?.totalAmount
                        ? `${Number(saleOrder.totalAmount).toLocaleString("vi-VN")} ${currencySymbol}`
                        : `0 ${currencySymbol}`;

                    const notification = {
                        code:
                            data.code ||
                            `so_status_${saleOrder.id}_${Date.now()}`,
                        title: t("mercure.sale_order.completed_title", {
                            code: saleOrder.code || "",
                        }),
                        body: t("mercure.sale_order.completed_body", {
                            code: saleOrder.code || "",
                            total: totalFormatted,
                        }),
                        time: t("mercure.just_now"),
                        icon: "mdi-check-circle-outline",
                        color: "success",
                        seen: false,
                        link: "/sale-order",
                        createdAt: data.timestamp || new Date().toISOString(),
                        duration: 5000,
                    };

                    appStore.commit("mercure/ADD_NOTIFICATION", notification);
                    appStore.commit("mercure/SET_POPUP_NOTIFICATION", notification);
                }
                break;
            }
            case "dining_table_updated": {
                const diningTable = data.diningTable;
                if (!diningTable) break;

                const isAdmin = Boolean(
                    currentUser?.roles?.includes("ROLE_ADMIN"),
                );
                const tableBranchId = Number(
                    diningTable.branchId ?? diningTable.branch?.id ?? 0,
                );
                const assignedBranchIds = Array.isArray(
                    currentUser?.assignedBranchIds,
                )
                    ? currentUser.assignedBranchIds.map(Number)
                    : [];

                // Chỉ admin hoặc nhân sự thuộc đúng chi nhánh của bàn ăn mới nhận được dữ liệu realtime
                const isAllowedBranch =
                    isAdmin ||
                    tableBranchId === 0 ||
                    assignedBranchIds.includes(tableBranchId);

                if (!isAllowedBranch) {
                    break;
                }

                window.dispatchEvent(
                    new CustomEvent("dining_table:updated", {
                        detail: data,
                    }),
                );
                appStore.commit("diningTable/UPDATE_ITEM", diningTable);
                break;
            }
            default:
                appStore.commit("mercure/ADD_NOTIFICATION", data);
                appStore.commit("mercure/SET_POPUP_NOTIFICATION", {
                    ...data,
                    title: t("mercure.notification.new_title", {
                        title: data.title || "",
                    }),
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
                console.log(`[Mercure] ${t("mercure.connected")}`);
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
                console.error(`[Mercure] ${t("mercure.connection_error")}`, error);
                updateStatus("error");

                if (typeof onError === "function") {
                    onError(error);
                }
            };

            return eventSource;
        } catch (error) {
            console.error(t("mercure.connect_failed"), error);
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
