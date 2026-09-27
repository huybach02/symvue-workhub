<template>
    <div>
        <!-- Giao diện dành cho ADMIN: Hiển thị các Expand Panels ứng với từng chi nhánh -->
        <template v-if="isAdmin">
            <div
                class="mb-4 d-flex align-center justify-space-between flex-wrap ga-2"
            >
                <div class="d-flex align-center ga-2">
                    <v-icon
                        icon="mdi-source-branch"
                        color="primary"
                        size="large"
                    />
                    <span class="text-h6 font-weight-bold text-grey-darken-3">
                        {{
                            $t("dining_table.branch_list_title") ||
                            "Quản lý Bàn ăn theo Chi nhánh"
                        }}
                    </span>
                    <v-chip
                        color="primary"
                        size="small"
                        variant="tonal"
                        class="font-weight-medium"
                    >
                        {{ branchOptions.length }} {{ $t("sidebar.branch") }}
                    </v-chip>
                </div>
            </div>

            <div v-if="branchLoading" class="py-12 d-flex justify-center">
                <v-progress-circular indeterminate color="primary" size="40" />
            </div>

            <v-expansion-panels
                v-else-if="branchOptions.length > 0"
                v-model="expandedPanels"
                multiple
                variant="accordion"
                class="branch-expansion-panels"
            >
                <v-expansion-panel
                    v-for="branch in branchOptions"
                    :key="branch.value"
                    :value="branch.value"
                    class="border rounded-lg mb-3 shadow-sm"
                >
                    <v-expansion-panel-title
                        class="py-3 px-4 bg-grey-lighten-4"
                    >
                        <div
                            class="d-flex align-center justify-space-between flex-grow-1 mr-4"
                        >
                            <div class="d-flex align-center ga-3">
                                <v-avatar
                                    size="36"
                                    color="primary"
                                    variant="tonal"
                                >
                                    <v-icon size="20">
                                        mdi-store-outline
                                    </v-icon>
                                </v-avatar>
                                <div>
                                    <div
                                        class="text-subtitle-1 font-weight-bold text-grey-darken-4"
                                    >
                                        {{ branch.label || branch.name }}
                                    </div>
                                    <div
                                        v-if="branch.code"
                                        class="text-caption text-medium-emphasis"
                                    >
                                        {{
                                            $t("branch.columns.code") || "Mã"
                                        }}:
                                        {{ branch.code }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </v-expansion-panel-title>

                    <v-expansion-panel-text class="pt-4 bg-white">
                        <v-row class="mb-2" align="center">
                            <v-col cols="12" sm="6" md="5">
                                <ExportDiningTableQrPdf
                                    v-if="
                                        permission?.export || permission?.index
                                    "
                                    :path="path"
                                    :branch-id="branch.value"
                                    :branch-name="branch.label"
                                />
                            </v-col>
                            <v-col
                                cols="12"
                                sm="6"
                                md="7"
                                class="text-sm-right"
                            >
                                <CreateEditDiningTable
                                    v-if="permission?.create"
                                    :path="path"
                                    :branch-id="branch.value"
                                    :branch-name="branch.label"
                                    mode="create"
                                    @reload="refreshBranch(branch.value)"
                                />
                            </v-col>
                        </v-row>

                        <DiningTableList
                            :ref="(el) => setTableListRef(branch.value, el)"
                            :path="path"
                            :permission="permission"
                            :branch-id="branch.value"
                            :branch-name="branch.label"
                        />
                    </v-expansion-panel-text>
                </v-expansion-panel>
            </v-expansion-panels>

            <v-card
                v-else
                variant="flat"
                border
                class="pa-8 text-center rounded-lg"
            >
                <v-icon
                    icon="mdi-store-off-outline"
                    size="48"
                    color="grey-lighten-1"
                />
                <div
                    class="text-grey-darken-1 mt-3 text-body-1 font-weight-medium"
                >
                    {{
                        $t("dining_table.no_branches") ||
                        "Chưa có chi nhánh nào trong hệ thống"
                    }}
                </div>
            </v-card>
        </template>

        <!-- Giao diện dành cho USER KHÔNG PHẢI ADMIN: Giữ nguyên giao diện như hiện tại -->
        <template v-else>
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
        </template>
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import DiningTableList from "./DiningTableList.vue";
import CreateEditDiningTable from "./CreateEditDiningTable.vue";
import ExportDiningTableQrPdf from "./ExportDiningTableQrPdf.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions, mapGetters } from "vuex";

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
            expandedPanels: [],
            tableListRefs: {},
        };
    },
    computed: {
        ...mapGetters("auth", ["isAdmin", "currentUser"]),
        ...mapGetters("branch", {
            branchOptions: "options",
            branchLoading: "optionsLoading",
        }),
        permission() {
            return usePermission(this.path);
        },
    },
    async created() {
        if (this.isAdmin) {
            await this.fetchBranchOptions();
            if (this.branchOptions.length > 0) {
                this.expandedPanels = [this.branchOptions[0].value];
            }
        } else {
            await this.getDanhSach();
        }
    },
    methods: {
        ...mapActions("diningTable", ["fetchItems"]),
        ...mapActions("branch", { fetchBranchOptions: "fetchOptions" }),
        setTableListRef(branchId, el) {
            if (el) {
                this.tableListRefs[branchId] = el;
            }
        },
        refreshBranch(branchId) {
            if (this.tableListRefs[branchId]?.reloadList) {
                this.tableListRefs[branchId].reloadList();
            }
        },
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

<style scoped>
.branch-expansion-panels :deep(.v-expansion-panel-title__overlay) {
    transition: opacity 0.2s ease;
}
</style>
