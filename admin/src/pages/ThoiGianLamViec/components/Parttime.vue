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
import ParttimeCard from "./ParttimeCard.vue";
import ParttimeDialog from "./ParttimeDialog.vue";
import { getAllData } from "@/services/bases/getData";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";

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
                const response = await getAllData(
                    API_ROUTES_CONFIG.thoiGianLamViec.fulltime,
                );
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
