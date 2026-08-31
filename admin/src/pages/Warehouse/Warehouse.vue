<template>
    <div>
        <v-row>
            <v-col cols="12" md="5">
                <div class="d-flex ga-2">
                    <ExportDataExcel v-if="permission?.export" :path="path" />
                    <ImportDataExcel
                        v-if="permission?.import"
                        :path="path"
                        :note="``"
                        @reload="getDanhSach"
                    />
                </div>
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <WarehouseList
                    v-if="permission?.index"
                    :path="path"
                    :permission="permission"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import WarehouseList from "./WarehouseList.vue";
import ExportDataExcel from "@/components/ExportDataExcel.vue";
import ImportDataExcel from "@/components/ImportDataExcel.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "Warehouse",
    components: {
        WarehouseList,
        ExportDataExcel,
        ImportDataExcel,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.warehouse,
            lastParams: null,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("warehouse", ["fetchItems"]),
        async getDanhSach(params) {
            if (params && typeof params === "object" && !(params instanceof Event)) {
                this.lastParams = params;
            }
            await this.fetchItems(this.lastParams || params);
        },
    },
};
</script>

<style></style>
