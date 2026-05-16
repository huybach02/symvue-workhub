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
                            v-if="permission.show"
                            color="warning"
                            variant="outlined"
                            icon="mdi-pencil"
                            size="small"
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
import FulltimeDialog from "./FulltimeDialog.vue";
import { mapActions, mapGetters } from "vuex";

export default {
    components: {
        FulltimeDialog,
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
            itemEdit: null,
        };
    },
    computed: {
        ...mapGetters("workingTime", ["fulltimeList"]),
        data() {
            return this.fulltimeList;
        },
    },
    created() {
        this.fetchData();
    },
    methods: {
        ...mapActions("workingTime", ["fetchFulltimeList"]),
        async fetchData() {
            await this.fetchFulltimeList();
        },
        handleEdit(item) {
            this.itemEdit = item;
            this.isOpenDialog = true;
        },
    },
};
</script>

<style scoped></style>
