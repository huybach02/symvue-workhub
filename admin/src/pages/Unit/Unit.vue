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
            <v-col cols="12" md="7">
                <CreateEditUnit
                    v-if="permission?.create"
                    :path="path"
                    mode="create"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <UnitList
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
import UnitList from "./UnitList.vue";
import CreateEditUnit from "./CreateEditUnit.vue";
import ExportDataExcel from "@/components/ExportDataExcel.vue";
import ImportDataExcel from "@/components/ImportDataExcel.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "Unit",
    components: {
        UnitList,
        CreateEditUnit,
        ExportDataExcel,
        ImportDataExcel,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.unit,
            lastParams: null,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("unit", ["fetchItems"]),
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
