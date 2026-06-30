<template>
    <div>
        <v-tabs v-model="tab" color="primary" class="mb-4">
            <v-tab value="ingredient">
                {{ $t("merchandise.ingredient") }}
            </v-tab>
            <v-tab value="finished_product">
                {{ $t("merchandise.finished_product") }}
            </v-tab>
        </v-tabs>

        <v-window v-model="tab" :touch="false">
            <v-window-item value="ingredient">
                <div v-if="tab === 'ingredient'">
                    <v-row>
                        <v-col cols="12" md="5">
                            <div class="d-flex ga-2">
                                <ExportDataExcel
                                    v-if="permission?.export"
                                    :path="path"
                                />
                                <ImportDataExcel
                                    v-if="permission?.import"
                                    :path="path"
                                    :note="``"
                                    @reload="getDanhSach"
                                />
                            </div>
                        </v-col>
                        <v-col cols="12" md="7">
                            <CreateEditMerchandise
                                v-if="permission?.create"
                                :path="path"
                                :type="tab"
                                mode="create"
                                @reload="getDanhSach"
                            />
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12">
                            <MerchandiseList
                                v-if="permission?.index"
                                :path="path"
                                :type="tab"
                                :permission="permission"
                                @reload="getDanhSach"
                            />
                        </v-col>
                    </v-row>
                </div>
            </v-window-item>

            <v-window-item value="finished_product">
                <div v-if="tab === 'finished_product'">
                    <v-row>
                        <v-col cols="12" md="5">
                            <div class="d-flex ga-2">
                                <ExportDataExcel
                                    v-if="permission?.export"
                                    :path="path"
                                />
                                <ImportDataExcel
                                    v-if="permission?.import"
                                    :path="path"
                                    :note="``"
                                    @reload="getDanhSach"
                                />
                            </div>
                        </v-col>
                        <v-col cols="12" md="7">
                            <CreateEditMerchandise
                                v-if="permission?.create"
                                :path="path"
                                :type="tab"
                                mode="create"
                                @reload="getDanhSach"
                            />
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12">
                            <MerchandiseList
                                v-if="permission?.index"
                                :path="path"
                                :type="tab"
                                :permission="permission"
                                @reload="getDanhSach"
                            />
                        </v-col>
                    </v-row>
                </div>
            </v-window-item>
        </v-window>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import MerchandiseList from "./MerchandiseList.vue";
import CreateEditMerchandise from "./CreateEditMerchandise.vue";
import ExportDataExcel from "@/components/ExportDataExcel.vue";
import ImportDataExcel from "@/components/ImportDataExcel.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions } from "vuex";

export default {
    name: "Merchandise",
    components: {
        MerchandiseList,
        CreateEditMerchandise,
        ExportDataExcel,
        ImportDataExcel,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.merchandise,
            tab: "ingredient",
            lastParams: null,
        };
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
    },
    methods: {
        ...mapActions("merchandise", ["fetchItems"]),
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

