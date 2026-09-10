<template>
    <div>
        <v-row>
            <v-col cols="12" md="5">
                <ExportDiningTableQrPdf
                    v-if="permission?.export || permission?.index"
                    :path="path"
                />
            </v-col>
            <v-col cols="12" md="7">
                <CreateEditDiningTable
                    v-if="permission?.create"
                    :path="path"
                    mode="create"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <DiningTableList
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
import DiningTableList from "./DiningTableList.vue";
import CreateEditDiningTable from "./CreateEditDiningTable.vue";
import ExportDiningTableQrPdf from "./ExportDiningTableQrPdf.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "DiningTable",
    components: {
        DiningTableList,
        CreateEditDiningTable,
        ExportDiningTableQrPdf,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.diningTable,
            lastParams: null,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("diningTable", ["fetchItems"]),
        async getDanhSach(params) {
            if (
                params &&
                typeof params === "object" &&
                !(params instanceof Event)
            ) {
                this.lastParams = params;
            }
            await this.fetchItems(this.lastParams || params);
        },
    },
};
</script>

<style></style>
