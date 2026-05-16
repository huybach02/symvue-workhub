<template>
    <div>
        <v-row class="g-2">
            <v-col v-for="item in data" :key="item.id" cols="12" md="3">
                <ParttimeCard
                    :permission="permission"
                    :thoi-gian-lam-viec="item"
                    @open-dialog="openDialog"
                />
            </v-col>
        </v-row>

        <!-- Modal thêm ca làm việc, sẽ xử lý riêng cho từng ParttimeCard -->
        <ParttimeDialog
            :is-open="isOpenDialog"
            :thoi-gian-lam-viec="selectedThoiGianLamViec"
            @close="isOpenDialog = false"
            @update="handleParttimeUpdated"
        />
    </div>
</template>

<script>
import ParttimeCard from "./ParttimeCard.vue";
import ParttimeDialog from "./ParttimeDialog.vue";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        ParttimeCard,
        ParttimeDialog,
    },
    props: {
        permission: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            isOpenDialog: false,
            selectedThoiGianLamViec: null,
        };
    },
    computed: {
        ...mapGetters("workingTime", ["fulltimeList"]),
        data() {
            return this.fulltimeList;
        },
    },
    created() {
        this.fetchThoiGianLamViec();
    },
    methods: {
        ...mapActions("workingTime", ["fetchFulltimeList"]),
        async fetchThoiGianLamViec() {
            await this.fetchFulltimeList();
        },
        openDialog(thoiGianLamViec) {
            this.selectedThoiGianLamViec = thoiGianLamViec;
            this.isOpenDialog = true;
        },
        handleParttimeUpdated() {
            this.isOpenDialog = false;
        },
    },
};
</script>

<style></style>
