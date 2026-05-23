<template>
    <div>
        <v-row>
            <v-col cols="12">
                <ImportHistoryList
                    :path="path"
                    @reload="getDanhSach"
                />
            </v-col>
        </v-row>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import ImportHistoryList from "./ImportHistoryList.vue";
import { mapActions } from "vuex";

export default {
    name: "ImportHistory",
    components: {
        ImportHistoryList,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.importHistory,
        };
    },
    created() {
        this.getDanhSach();
    },
    methods: {
        ...mapActions("importHistory", ["fetchImportHistory"]),
        async getDanhSach(params) {
            await this.fetchImportHistory(params);
        },
    },
};
</script>

<style></style>
