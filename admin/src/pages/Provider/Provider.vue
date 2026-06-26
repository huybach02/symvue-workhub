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
                <CreateEditProvider
                    v-if="permission?.create"
                    :path="path"
                    mode="create"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
        <v-row>
            <v-col cols="12">
                <ProviderList
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
import ProviderList from "./ProviderList.vue";
import CreateEditProvider from "./CreateEditProvider.vue";
import ExportDataExcel from "@/components/ExportDataExcel.vue";
import ImportDataExcel from "@/components/ImportDataExcel.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "Provider",
    components: {
        ProviderList,
        CreateEditProvider,
        ExportDataExcel,
        ImportDataExcel,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.provider,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("provider", ["fetchItems"]),
        async getDanhSach(params) {
            await this.fetchItems(params);
        },
    },
};
</script>

<style></style>
