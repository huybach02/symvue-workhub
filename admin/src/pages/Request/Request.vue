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
                            @click="reloadApprovalTable"
                        >
                        </v-btn>
                    </div>

                    <v-divider />

                    <v-card-text>
                        <RequestTable
                            ref="approvalTable"
                            :key="approvalTab"
                            :items="activeApprovalItems"
                            :total-items="activeApprovalTotal"
                            :loading="activeApprovalLoading"
                            :request-types="requestTypes"
                            :show-requester="true"
                            :show-type="true"
                            :show-status-filter="false"
                            :isApproval="true"
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
                        <div class="text-h6">
                            {{ $t("request.management_title") }}
                        </div>
                    </v-card-title>

                    <v-divider />

                    <v-card-text>
                        <RequestTypeCards
                            :items="visibleRequestTypes"
                            @select="handleSelectType"
                        />
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <RequestDrawer
            ref="requestDrawer"
            :open="drawer"
            :selected-type="selectedType"
            :mine-items="mineItems"
            :mine-total="totalMineItems"
            :loading="mineLoading"
            :permission="permission"
            :request-types="requestTypes"
            @close="handleCloseDrawer"
            @create="openCreateDialog"
            @refresh="fetchMineRequests"
            @show-detail="openDetailDialog"
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
            :permission="permission"
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
            "refreshUUID",
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
        visibleRequestTypes() {
            return this.requestTypes.filter((item) => {
                const permission = usePermission(
                    API_ROUTES_CONFIG.requests,
                    `requests:${item.code}`,
                );

                return permission?.index;
            });
        },
        permission() {
            if (!this.selectedType?.code && !this.requestDetail?.type) {
                return usePermission(API_ROUTES_CONFIG.requests);
            }

            return usePermission(
                API_ROUTES_CONFIG.requests,
                `requests:${this.selectedType?.code || this.requestDetail?.type || ""}`,
            );
        },
    },
    async created() {
        await this.fetchRequestTypes();
    },
    watch: {
        "$route.query.requestId": {
            immediate: true,
            handler(requestId) {
                if (!requestId) {
                    return;
                }

                this.openDetailDialog(requestId);
            },
        },
        detailDialog(value) {
            if (value) {
                return;
            }

            this.clearDetailDialogQuery();
        },
        refreshUUID(value) {
            if (value) {
                this.reloadApprovalTable();
            }
        },
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
        async fetchPendingApprovalRequests(query = {}) {
            await this.fetchRequests({
                view: "approval",
                ...query,
            });
        },
        async fetchApprovedRequests(query = {}) {
            await this.fetchRequests({
                view: "approval",
                status: "approved",
                ...query,
            });
        },
        async fetchActiveApprovalTab(query = {}) {
            if (this.approvalTab === "approved") {
                await this.fetchApprovedRequests(query);
                return;
            }

            await this.fetchPendingApprovalRequests(query);
        },
        async fetchMineRequests(query = {}) {
            if (!this.selectedType?.code) {
                return;
            }

            await this.fetchRequests({
                type: this.selectedType.code,
                view: "mine",
                ...query,
            });
        },
        handleSelectType(item) {
            this.selectedType = item;
            this.drawer = true;
        },
        async handleRefreshAll() {
            await Promise.all([
                this.reloadApprovalTable(),
                this.reloadMineTable(),
            ]);

            if (this.requestDetail?.id) {
                await this.openDetailDialog(this.requestDetail.id);
            }
        },
        reloadApprovalTable() {
            const query = this.$refs.approvalTable?.getCurrentQuery?.() ?? {};

            return this.fetchActiveApprovalTab(query);
        },
        reloadMineTable() {
            const query = this.$refs.requestDrawer?.getCurrentQuery?.() ?? {};

            return this.fetchMineRequests(query);
        },
        handleCloseDrawer() {
            this.drawer = false;
            this.selectedType = null;
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
        clearDetailDialogQuery() {
            if (!this.$route.query.requestId) {
                return;
            }

            const query = { ...this.$route.query };
            delete query.requestId;

            this.$router.replace({
                query,
            });
        },
        async handleSaved() {
            this.formDialog = false;
            await this.handleRefreshAll();
        },
    },
};
</script>
