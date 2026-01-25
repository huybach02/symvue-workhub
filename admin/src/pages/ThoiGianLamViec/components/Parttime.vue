<template>
    <div>
        <v-row class="g-2">
            <v-col v-for="item in data" :key="item.id" cols="12" md="3">
                <ParttimeCard
                    :thoi-gian-lam-viec="item"
                    :is-refresh="isRefresh"
                    @open-dialog="openDialog"
                />
            </v-col>
        </v-row>

        <!-- Modal thêm ca làm việc, sẽ xử lý riêng cho từng ParttimeCard -->
        <ParttimeDialog
            :is-open="isOpenDialog"
            :thoi-gian-lam-viec="selectedThoiGianLamViec"
            @close="isOpenDialog = false"
            @update="isRefresh = !isRefresh"
        />
    </div>
</template>

<script>
import { thoiGianLamViecService } from "@/services/thoiGianLamViecService";
import ParttimeCard from "./ParttimeCard.vue";
import ParttimeDialog from "./ParttimeDialog.vue";

export default {
    components: {
        ParttimeCard,
        ParttimeDialog,
    },
    data() {
        return {
            data: [],
            isOpenDialog: false,
            selectedThoiGianLamViec: null,
            isRefresh: false,
        };
    },
    created() {
        this.fetchThoiGianLamViec();
    },
    methods: {
        async fetchThoiGianLamViec() {
            try {
                this.$store.commit("setIsLoading");
                const response = await thoiGianLamViecService.findAll();
                this.$store.commit("unsetIsLoading");
                this.data = response;
            } catch (error) {
                console.error(error);
            }
        },
        openDialog(thoiGianLamViec) {
            this.selectedThoiGianLamViec = thoiGianLamViec;
            this.isOpenDialog = true;
        },
    },
};
</script>

<style></style>
