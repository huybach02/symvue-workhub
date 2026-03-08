import axiosInstance from "@/configs/axios";

let heartbeatInterval = null;

const sendOnline = async () => {
    try {
        await axiosInstance.post("/presence/online");
    } catch (error) {
        console.warn(
            "[Presence] Không thể gửi online heartbeat:",
            error?.message,
        );
    }
};

const sendOffline = async () => {
    try {
        await axiosInstance.post("/presence/offline");
    } catch (error) {
        console.warn("[Presence] Không thể gửi offline:", error?.message);
    }
};

const startPresence = () => {
    console.log("[Presence] Bắt đầu gửi online heartbeat");
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
    sendOnline();
    heartbeatInterval = setInterval(() => {
        sendOnline();
    }, 60 * 1000);
};

const stopPresence = async () => {
    if (heartbeatInterval) {
        clearInterval(heartbeatInterval);
        heartbeatInterval = null;
    }
    await sendOffline();
};

const fetchStatuses = async (userIds) => {
    if (!userIds || userIds.length === 0) return {};

    try {
        // Tạo query string: userIds[]=1&userIds[]=2
        const params = new URLSearchParams();
        userIds.forEach((id) => params.append("userIds[]", id));

        const res = await axiosInstance.get(
            `/presence/status?${params.toString()}`,
        );
        return res?.data ?? {};
    } catch (error) {
        console.warn("[Presence] Không thể lấy trạng thái:", error?.message);
        return {};
    }
};

export default {
    startPresence,
    stopPresence,
    fetchStatuses,
};
