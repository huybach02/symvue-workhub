<template>
    <v-card color="primary" variant="outlined" class="mx-auto">
        <v-card-item>
            <div>
                <div class="d-flex justify-space-between">
                    <div class="text-h6 mb-1">{{ thoiGianLamViec.thu }}</div>
                    <v-tooltip text="Thêm ca làm việc" location="top">
                        <template v-slot:activator="{ props }">
                            <v-btn
                                v-if="permission.create"
                                v-bind="props"
                                icon="mdi-plus"
                                size="small"
                                variant="outlined"
                                color="primary"
                                @click="$emit('open-dialog', thoiGianLamViec)"
                            />
                        </template>
                    </v-tooltip>
                </div>
                <v-list lines="one">
                    <v-list-item
                        v-for="(caLamViec, index) in caLamViecList"
                        :key="caLamViec.id"
                        :title="`Ca ${index + 1}: ${caLamViec.gioBatDau} - ${caLamViec.gioKetThuc}`"
                        :subtitle="`${caLamViec.ghiChu}`"
                        prepend-icon="mdi-clock-outline"
                    >
                        <!-- CHỖ NÀY CHƯA XỬ LÝ PHẦN XÓA CA LÀM VIỆC => ĐỂ LÀM SAU
                            LƯU Ý: KIỂM TRA XEM CÓ NHÂN VIÊN NÀO ĐƯỢC GÁN VÀO CA LÀM VIỆC NÀY HAY CHƯA?
                            NẾU CÓ NHÂN VIÊN ĐƯỢC GÁN VÀO CA LÀM VIỆC NÀY THÌ KHÔNG CÓ THỂ XÓA CA LÀM VIỆC NÀY
                        -->

                        <template v-slot:append>
                            <v-btn
                                v-if="permission.delete"
                                icon="mdi-trash-can-outline"
                                size="x-small"
                                variant="outlined"
                                color="error"
                                @click="deleteCaLamViec(caLamViec.id)"
                            />
                        </template>
                    </v-list-item>
                </v-list>
            </div>
        </v-card-item>
    </v-card>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { getAllData } from "@/services/bases/getData";

export default {
    props: {
        thoiGianLamViec: {
            type: Object,
            required: true,
        },
        isRefresh: {
            type: Boolean,
            required: true,
        },
        permission: {
            type: Object,
            default: () => ({}),
        },
    },
    emits: ["open-dialog"],
    data() {
        return {
            caLamViecList: [],
        };
    },
    watch: {
        isRefresh() {
            this.fetchCaLamViecList();
        },
    },
    created() {
        this.fetchCaLamViecList();
    },
    methods: {
        async fetchCaLamViecList() {
            try {
                this.$store.commit("setIsLoading");
                const response = await getAllData(
                    API_ROUTES_CONFIG.thoiGianLamViec,
                    {
                        type: "parttime",
                        thoiGianLamViecId: this.thoiGianLamViec.id,
                    },
                );
                this.caLamViecList = response;
                this.$store.commit("unsetIsLoading");
            } catch (error) {
                console.error(error);
            }
        },
    },
};
</script>

<style></style>
