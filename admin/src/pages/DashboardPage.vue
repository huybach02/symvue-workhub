<template>
    <v-container class="py-8">
        <v-btn
            color="primary"
            :loading="isSending"
            prepend-icon="mdi-bell-ring"
            @click="sendTestNotification"
        >
            Gửi thông báo test
        </v-btn>
    </v-container>
</template>

<script>
import axiosInstance from "@/configs/axios";

export default {
    data() {
        return {
            isSending: false,
        };
    },
    methods: {
        async sendTestNotification() {
            this.isSending = true;
            try {
                const res = await axiosInstance.get("/mercure/test");
                console.log("[Mercure] API publish response:", res);
            } catch (error) {
                console.error("[Mercure] Lỗi khi gửi thông báo:", error);
            } finally {
                this.isSending = false;
            }
        },
    },
};
</script>

<style scoped>
.status-row {
    gap: 4px;
}
</style>
