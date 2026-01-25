<template>
    <div>
        <v-table striped="even">
            <thead class="header-custom">
                <tr>
                    <th class="text-left">
                        {{ $t("thoi_gian_lam_viec.action") }}
                    </th>
                    <th class="text-left">
                        {{ $t("thoi_gian_lam_viec.thu") }}
                    </th>
                    <th class="text-left">
                        {{ $t("thoi_gian_lam_viec.gio_bat_dau") }}
                    </th>
                    <th class="text-left">
                        {{ $t("thoi_gian_lam_viec.gio_ket_thuc") }}
                    </th>
                    <th class="text-left">
                        {{ $t("thoi_gian_lam_viec.ghi_chu") }}
                    </th>
                    <th class="text-left">
                        {{ $t("thoi_gian_lam_viec.thoi_gian_cap_nhat") }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in data" :key="item.id">
                    <td>
                        <v-btn
                            color="primary"
                            variant="tonal"
                            icon="mdi-pencil"
                            size="x-small"
                            @click="handleEdit(item)"
                        />
                    </td>
                    <td>{{ item.thu }}</td>
                    <td>{{ item.gioBatDau }}</td>
                    <td>{{ item.gioKetThuc }}</td>
                    <td>{{ item.ghiChu || "--" }}</td>
                    <td>{{ item.updatedAt }}</td>
                </tr>
            </tbody>
        </v-table>
        <FulltimeDialog
            :is-open="isOpenDialog"
            :item-edit="itemEdit"
            @close="isOpenDialog = false"
            @update="fetchData"
        />
    </div>
</template>

<script>
import { thoiGianLamViecService } from "@/services/thoiGianLamViecService";
import FulltimeDialog from "./FulltimeDialog.vue";

export default {
    components: {
        FulltimeDialog,
    },
    data() {
        return {
            data: [],
            isOpenDialog: false,
            itemEdit: null,
        };
    },
    created() {
        this.fetchData();
    },
    methods: {
        async fetchData() {
            try {
                this.$store.commit("setIsLoading");
                const response = await thoiGianLamViecService.findAll();
                this.$store.commit("unsetIsLoading");
                this.data = response;
            } catch (error) {
                console.error(error);
            }
        },
        handleEdit(item) {
            this.itemEdit = item;
            this.isOpenDialog = true;
        },
    },
};
</script>

<style scoped></style>
