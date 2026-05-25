<template>
    <div class="request-page">
        <v-row>
            <v-col cols="12" md="9">
                <v-card border>
                    <div
                        class="d-flex align-center justify-space-between px-4 py-3"
                    >
                        <v-tabs
                            v-model="approvalTab"
                            color="primary"
                            grow
                            class="flex-grow-1"
                        >
                            <v-tab value="pending">
                                {{ $t("request.pending_approval") }}
                            </v-tab>
                            <v-tab value="approved">
                                {{ $t("request.approved_requests") }}
                            </v-tab>
                        </v-tabs>

                        <v-btn
                            color="primary"
                            variant="text"
                            prepend-icon="mdi-refresh"
                            class="ml-4"
                            @click="fetchActiveApprovalTab"
                        >
                            {{ $t("button.update") }}
                        </v-btn>
                    </div>

                    <v-divider />

                    <v-card-text>
                        <RequestTable
                            :key="approvalTab"
                            :items="activeApprovalItems"
                            :total-items="activeApprovalTotal"
                            :loading="activeApprovalLoading"
                            :query="activeApprovalQuery"
                            :request-types="requestTypes"
                            :show-requester="true"
                            :show-type="true"
                            @update:query="handleApprovalQueryChange"
                            @reload="fetchActiveApprovalTab"
                            @show-detail="openDetailDialog"
                        />
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="3">
                <v-card border>
                    <v-card-title
                        class="bg-primary text-white d-flex align-center py-4"
                        style="min-height: 84px"
                    >
                        <div class="text-h6">Quản lý đề xuất</div>
                    </v-card-title>

                    <v-divider />

                    <v-card-text>
                        <RequestTypeCards
                            :items="requestTypes"
                            @select="handleSelectType"
                        />
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <RequestDrawer
            :open="drawer"
            :selected-type="selectedType"
            :mine-items="mineItems"
            :mine-total="totalMineItems"
            :loading="mineLoading"
            :permission="permission"
            :query="mineQuery"
            :request-types="requestTypes"
            @close="drawer = false"
            @create="openCreateDialog"
            @refresh="fetchMineRequests"
            @show-detail="openDetailDialog"
            @update:query="handleMineQueryChange"
        />

        <RequestFormDialog
            v-model="formDialog"
            :selected-type="selectedType"
            :mode="formMode"
            :item="editingItem"
            @saved="handleSaved"
        />

        <RequestDetailDialog
            v-model="detailDialog"
            :item="requestDetail"
            :timeline="requestTimeline"
            :loading="detailLoading || timelineLoading"
            @refresh="handleRefreshAll"
            @edit="openEditDialog"
        />
    </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { usePermission } from "@/hooks/usePermission";
import RequestTypeCards from "./components/RequestTypeCards.vue";
import RequestDrawer from "./components/RequestDrawer.vue";
import RequestFormDialog from "./components/RequestFormDialog.vue";
import RequestDetailDialog from "./components/RequestDetailDialog.vue";
import RequestTable from "./components/RequestTable.vue";

export default {
    name: "RequestPage",
    components: {
        RequestTypeCards,
        RequestDrawer,
        RequestFormDialog,
        RequestDetailDialog,
        RequestTable,
    },
    data() {
        return {
            drawer: false,
            formDialog: false,
            detailDialog: false,
            selectedType: null,
            formMode: "create",
            editingItem: null,
            approvalTab: "pending",
            pendingApprovalQuery: {
                page: 1,
                limit: 10,
                sort_column: null,
                sort_direction: null,
                f: [],
            },
            approvedQuery: {
                page: 1,
                limit: 10,
                sort_column: null,
                sort_direction: null,
                f: [],
            },
            mineQuery: {
                page: 1,
                limit: 10,
                sort_column: null,
                sort_direction: null,
                f: [],
            },
        };
    },
    computed: {
        ...mapGetters("request", [
            "requestTypes",
            "mineItems",
            "approvalItems",
            "approvedItems",
            "totalMineItems",
            "totalApprovalItems",
            "totalApprovedItems",
            "requestDetail",
            "requestTimeline",
            "mineLoading",
            "approvalLoading",
            "approvedLoading",
            "detailLoading",
            "timelineLoading",
        ]),
        activeApprovalItems() {
            return this.approvalTab === "approved"
                ? this.approvedItems
                : this.approvalItems;
        },
        activeApprovalTotal() {
            return this.approvalTab === "approved"
                ? this.totalApprovedItems
                : this.totalApprovalItems;
        },
        activeApprovalLoading() {
            return this.approvalTab === "approved"
                ? this.approvedLoading
                : this.approvalLoading;
        },
        activeApprovalQuery() {
            return this.approvalTab === "approved"
                ? this.approvedQuery
                : this.pendingApprovalQuery;
        },
        permission() {
            return usePermission(API_ROUTES_CONFIG.requests);
        },
    },
    watch: {
        approvalTab() {
            this.fetchActiveApprovalTab();
        },
    },
    async created() {
        await Promise.all([
            this.fetchRequestTypes(),
            this.fetchPendingApprovalRequests(),
            this.fetchApprovedRequests(),
        ]);
    },
    methods: {
        ...mapActions("request", [
            "fetchRequestTypes",
            "fetchRequests",
            "fetchRequestDetail",
            "fetchRequestTimeline",
            "clearRequestDetail",
            "clearRequestTimeline",
        ]),
        async fetchPendingApprovalRequests() {
            await this.fetchRequests({
                view: "approval",
                ...this.pendingApprovalQuery,
            });
        },
        async fetchApprovedRequests() {
            await this.fetchRequests({
                view: "approval",
                status: "approved",
                ...this.approvedQuery,
            });
        },
        async fetchActiveApprovalTab() {
            if (this.approvalTab === "approved") {
                await this.fetchApprovedRequests();
                return;
            }

            await this.fetchPendingApprovalRequests();
        },
        async fetchMineRequests() {
            if (!this.selectedType?.code) {
                return;
            }

            await this.fetchRequests({
                type: this.selectedType.code,
                view: "mine",
                ...this.mineQuery,
            });
        },
        async handleSelectType(item) {
            this.selectedType = item;
            this.mineQuery = {
                ...this.mineQuery,
                page: 1,
                f: [],
            };
            this.drawer = true;
            await this.fetchMineRequests();
        },
        async handleRefreshAll() {
            await Promise.all([
                this.fetchPendingApprovalRequests(),
                this.fetchApprovedRequests(),
                this.fetchMineRequests(),
            ]);

            if (this.requestDetail?.id) {
                await this.openDetailDialog(this.requestDetail.id);
            }
        },
        handleApprovalQueryChange(query) {
            if (this.approvalTab === "approved") {
                this.approvedQuery = { ...query };
                return;
            }

            this.pendingApprovalQuery = { ...query };
        },
        handleMineQueryChange(query) {
            this.mineQuery = { ...query };
        },
        openCreateDialog() {
            this.formMode = "create";
            this.editingItem = null;
            this.formDialog = true;
        },
        openEditDialog(item) {
            this.formMode = "edit";
            this.editingItem = item;
            this.detailDialog = false;
            this.formDialog = true;
        },
        openDetailDialog(requestId) {
            this.detailDialog = true;

            this.clearRequestDetail();
            this.clearRequestTimeline();

            return Promise.all([
                this.fetchRequestDetail(requestId),
                this.fetchRequestTimeline(requestId),
            ]);
        },
        async handleSaved() {
            this.formDialog = false;
            await this.handleRefreshAll();
        },
    },
};
</script>
