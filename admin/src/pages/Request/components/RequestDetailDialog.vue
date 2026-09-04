<template>
    <div>
        <v-dialog
            :model-value="modelValue"
            max-width="1500"
            width="calc(100vw - 24px)"
            scrollable
            @update:model-value="$emit('update:modelValue', $event)"
        >
            <v-card class="request-detail-dialog">
                <div class="dialog-header">
                    <div class="d-flex align-start ga-3 flex-grow-1 min-w-0">
                        <v-avatar
                            color="primary"
                            variant="tonal"
                            size="44"
                            class="flex-shrink-0"
                        >
                            <v-icon
                                icon="mdi-file-document-outline"
                                size="26"
                            />
                        </v-avatar>

                        <div class="min-w-0">
                            <div class="text-h6 font-weight-bold dialog-title">
                                {{ item?.title || "" }}
                            </div>

                            <div
                                class="d-flex flex-wrap align-center ga-2 mt-2"
                            >
                                <v-chip
                                    v-if="item"
                                    color="info"
                                    size="small"
                                    variant="flat"
                                >
                                    {{ item?.code || "" }}
                                </v-chip>

                                <v-chip
                                    v-if="item"
                                    :color="getRequestStatusColor(item.status)"
                                    size="small"
                                    variant="flat"
                                >
                                    {{ getRequestStatusLabel(item.status) }}
                                </v-chip>
                            </div>
                        </div>
                    </div>

                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        class="flex-shrink-0"
                        @click="$emit('update:modelValue', false)"
                    />
                </div>

                <v-divider />

                <v-card-text class="pa-6">
                    <div v-if="loading" class="loading-circle mb-4">
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="40"
                        />
                    </div>

                    <template v-if="item">
                        <div class="meta-bar mb-5">
                            <v-chip
                                variant="tonal"
                                color="primary"
                                size="small"
                                class="meta-chip font-weight-medium"
                            >
                                <v-icon start icon="mdi-account-outline" />
                                {{ $t("request.requester") }}:
                                {{ item.requester?.name || "--" }}
                            </v-chip>

                            <v-chip
                                variant="tonal"
                                color="success"
                                size="small"
                                class="meta-chip font-weight-medium"
                            >
                                <v-icon
                                    start
                                    icon="mdi-account-check-outline"
                                />
                                {{ $t("request.approver") }}:
                                {{ item.currentApprover?.name || "--" }}
                            </v-chip>
                        </div>

                        <v-row>
                            <v-col cols="12" md="9">
                                <v-card class="section-card">
                                    <div class="section-title">
                                        <v-icon
                                            icon="mdi-information-outline"
                                            size="20"
                                        />
                                        <span>{{
                                            $t("request.detail_info")
                                        }}</span>
                                    </div>

                                    <v-divider />

                                    <component
                                        :is="activeDetailComponent"
                                        v-if="activeDetailComponent"
                                        :item="item"
                                    />

                                    <v-alert
                                        v-else
                                        type="warning"
                                        variant="tonal"
                                        class="ma-4"
                                    >
                                        {{ $t("request.unsupported_type") }}
                                    </v-alert>
                                </v-card>

                                <v-card
                                    v-if="
                                        item?.permissions?.canApprove ||
                                        item?.permissions?.canReject
                                    "
                                    class="section-card mt-4"
                                >
                                    <div class="section-title">
                                        <v-icon
                                            icon="mdi-check-decagram-outline"
                                            size="20"
                                        />
                                        <span>{{
                                            $t("request.approval_action")
                                        }}</span>
                                    </div>

                                    <v-divider />

                                    <v-card-text>
                                        <div class="mb-2">
                                            {{ $t("request.approval_note") }}
                                            <span class="text-error">*</span>
                                        </div>
                                        <v-textarea
                                            v-model="decisionComment"
                                            variant="outlined"
                                            rows="3"
                                            hide-details
                                        />

                                        <div
                                            class="d-flex justify-end ga-2 mt-4"
                                        >
                                            <v-btn
                                                v-if="permission?.reject"
                                                color="error"
                                                prepend-icon="mdi-close-circle-outline"
                                                :loading="isRejecting"
                                                @click="openRejectDialog"
                                            >
                                                {{
                                                    $t("request.reject_button")
                                                }}
                                            </v-btn>

                                            <v-btn
                                                v-if="permission?.approve"
                                                color="success"
                                                prepend-icon="mdi-check-circle-outline"
                                                :loading="isApproving"
                                                @click="openApproveDialog"
                                            >
                                                {{
                                                    $t("request.approve_button")
                                                }}
                                            </v-btn>
                                        </div>
                                    </v-card-text>
                                </v-card>
                            </v-col>

                            <v-col cols="12" md="3">
                                <v-card
                                    class="section-card timeline-card"
                                >
                                    <div class="section-title">
                                        <v-icon icon="mdi-history" size="20" />
                                        <span>{{
                                            $t("request.timeline")
                                        }}</span>
                                    </div>

                                    <v-divider />

                                    <v-card-text>
                                        <v-timeline
                                            v-if="timeline.length"
                                            density="compact"
                                            side="end"
                                            align="start"
                                        >
                                            <v-timeline-item
                                                v-for="event in timeline"
                                                :key="event.id"
                                                :dot-color="
                                                    getEventColor(
                                                        event.eventType,
                                                    )
                                                "
                                                fill-dot
                                                size="small"
                                            >
                                                <div class="timeline-item">
                                                    <div
                                                        class="font-weight-bold"
                                                    >
                                                        {{
                                                            getEventTitle(
                                                                event.eventType,
                                                            )
                                                        }}
                                                    </div>

                                                    <div
                                                        class="text-caption text-medium-emphasis mt-1"
                                                    >
                                                        {{
                                                            event.actor?.name ||
                                                            "Hệ thống"
                                                        }}
                                                        -
                                                        {{
                                                            event.createdAt ||
                                                            ""
                                                        }}
                                                    </div>

                                                    <div
                                                        v-if="event.comment"
                                                        class="text-body-2 mt-2"
                                                    >
                                                        {{ event.comment }}
                                                    </div>
                                                </div>
                                            </v-timeline-item>
                                        </v-timeline>

                                        <div v-else class="empty-state">
                                            <v-icon
                                                icon="mdi-clock-outline"
                                                size="40"
                                            />
                                            <div class="mt-2">
                                                {{ $t("base.no_data") }}
                                            </div>
                                        </div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </template>
                </v-card-text>

                <v-divider />

                <div class="d-flex justify-end ga-2 my-4 me-4">
                    <v-btn
                        v-if="showCreateReceiptButton"
                        color="success"
                        prepend-icon="mdi-plus-box-outline"
                        :loading="isCreatingReceipt"
                        @click="handleCreateStockReceipt"
                    >
                        {{ $t("request.stock_in.create_receipt_button") || "Tạo phiếu nhập kho" }}
                    </v-btn>

                    <v-btn
                        v-if="showCreateProductionOrderButton"
                        color="success"
                        prepend-icon="mdi-factory"
                        :loading="isCreatingOrder"
                        @click="handleCreateProductionOrder"
                    >
                        {{ $t("request.production.create_order_button") || "Tạo lệnh sản xuất" }}
                    </v-btn>

                    <v-btn
                        v-if="showExecuteStockTransferButton"
                        color="success"
                        prepend-icon="mdi-swap-horizontal-bold"
                        :loading="isExecutingTransfer"
                        @click="handleExecuteStockTransfer"
                    >
                        {{ $t("request.stock_transfer.execute_button") }}
                    </v-btn>

                    <v-btn
                        v-if="item?.permissions?.canEdit && permission?.edit"
                        color="warning"
                        prepend-icon="mdi-pencil-outline"
                        @click="$emit('edit', item)"
                    >
                        {{ $t("button.update") }}
                    </v-btn>

                    <v-btn
                        v-if="
                            item?.permissions?.canCancel && permission?.cancel
                        "
                        color="error"
                        prepend-icon="mdi-cancel"
                        :loading="isCancelling"
                        @click="openCancelDialog"
                    >
                        {{ $t("request.cancel_button") }}
                    </v-btn>

                    <v-btn
                        v-if="
                            item?.permissions?.canDelete && permission?.delete
                        "
                        color="error"
                        prepend-icon="mdi-trash-can-outline"
                        :loading="isDeleting"
                        @click="openDeleteDialog"
                    >
                        {{ $t("button.delete") }}
                    </v-btn>
                </div>
            </v-card>
        </v-dialog>
        <ConfirmDialog
            v-model="showConfirmApprove"
            :title="$t('request.approve_confirm_title')"
            :message="$t('request.approve_confirm_message')"
            :confirm-text="$t('request.approve_button')"
            confirm-color="success"
            :loading="isApproving"
            @confirm="handleApprove"
            @cancel="showConfirmApprove = false"
        />
        <ConfirmDialog
            v-model="showConfirmReject"
            :title="$t('request.reject_confirm_title')"
            :message="$t('request.reject_confirm_message')"
            :confirm-text="$t('request.reject_button')"
            :loading="isRejecting"
            @confirm="handleReject"
            @cancel="showConfirmReject = false"
        />
        <ConfirmDialog
            v-model="showConfirmCancel"
            :title="$t('request.cancel_confirm_title')"
            :message="$t('request.cancel_confirm_message')"
            :confirm-text="$t('request.cancel_button')"
            :loading="isCancelling"
            @confirm="handleCancelRequest"
            @cancel="showConfirmCancel = false"
        />
        <ConfirmDialog
            v-model="showConfirmDelete"
            :title="$t('request.delete_confirm_title')"
            :message="$t('request.delete_confirm_message')"
            :confirm-text="$t('button.delete')"
            :loading="isDeleting"
            @confirm="handleDeleteRequest"
            @cancel="showConfirmDelete = false"
        />
    </div>
</template>

<script>
import dayjs from "dayjs";
import { mapActions, mapGetters } from "vuex";
import { functionHelper } from "@/helpers/functionHelper";
import { constant } from "@/utils/constants/constant";
import { getRequestTypeComponentConfig } from "./request-types/requestTypeComponentRegistry";
import { toast } from "@/main";
import ConfirmDialog from "@/components/ConfirmDialog.vue";

export default {
    name: "RequestDetailDialog",
    components: {
        ConfirmDialog,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        item: {
            type: Object,
            default: null,
        },
        timeline: {
            type: Array,
            default: () => [],
        },
        loading: {
            type: Boolean,
            default: false,
        },
        permission: {
            type: Object,
            default: null,
        },
    },
    emits: ["update:modelValue", "refresh", "edit"],
    data() {
        return {
            decisionComment: "",
            showConfirmApprove: false,
            isApproving: false,
            showConfirmReject: false,
            isRejecting: false,
            showConfirmCancel: false,
            isCancelling: false,
            showConfirmDelete: false,
            isDeleting: false,
            isCreatingReceipt: false,
            isCreatingOrder: false,
            isExecutingTransfer: false,
        };
    },
    computed: {
        ...mapGetters("auth", ["currentUser"]),
        activeDetailComponent() {
            return (
                getRequestTypeComponentConfig(this.item?.type)
                    ?.detailComponent ?? null
            );
        },
        showCreateReceiptButton() {
            return (
                this.item?.type === "stock:stock-in" &&
                this.item?.status === "approved" &&
                this.currentUser?.id === this.item?.requester?.id &&
                !this.item?.targetRefId
            );
        },
        showCreateProductionOrderButton() {
            return (
                this.item?.type === "stock:production" &&
                this.item?.status === "approved" &&
                this.currentUser?.id === this.item?.requester?.id &&
                !this.item?.targetRefId
            );
        },
        showExecuteStockTransferButton() {
            return (
                this.item?.type === "stock:stock-transfer" &&
                this.item?.status === "approved" &&
                this.currentUser?.id === this.item?.requester?.id &&
                !this.item?.targetRefId
            );
        },
    },
    watch: {
        item: {
            immediate: true,
            handler() {
                this.decisionComment = "";
            },
        },
    },
    methods: {
        ...mapActions("request", [
            "approveRequest",
            "rejectRequest",
            "cancelRequest",
            "deleteRequest",
        ]),
        ...mapActions("stockReceipt", {
            createStockReceipt: "createItem",
        }),
        ...mapActions("productionOrder", {
            createProductionOrder: "createItem",
        }),
        ...mapActions("stockTransfer", {
            executeStockTransfer: "execute",
        }),
        async handleCreateStockReceipt() {
            this.isCreatingReceipt = true;
            await this.createStockReceipt({
                requestId: this.item.id,
            });
            this.$emit("refresh");

            this.isCreatingReceipt = false;
        },
        async handleCreateProductionOrder() {
            this.isCreatingOrder = true;
            await this.createProductionOrder({
                requestId: this.item.id,
            });
            this.$emit("refresh");

            this.isCreatingOrder = false;
        },
        async handleExecuteStockTransfer() {
            this.isExecutingTransfer = true;
            try {
                await this.executeStockTransfer(this.item.id);
                this.$emit("refresh");
            } finally {
                this.isExecutingTransfer = false;
            }
        },
        getRequestStatusColor(status) {
            return (
                constant.REQUEST_STATUS.find((item) => item.value === status)
                    ?.color || "primary"
            );
        },
        getRequestStatusLabel(status) {
            const requestStatus = constant.REQUEST_STATUS.find(
                (item) => item.value === status,
            );

            return requestStatus ? this.$t(requestStatus.key) : status || "--";
        },
        getEventTitle(eventType) {
            return functionHelper.getRequestEventTitle(eventType);
        },
        getEventColor(eventType) {
            return functionHelper.getRequestEventColor(eventType);
        },
        openApproveDialog() {
            this.showConfirmApprove = true;
        },
        openRejectDialog() {
            if (!this.decisionComment.trim()) {
                toast.error(this.$t("request.reject_comment_required"));
                return;
            }

            this.showConfirmReject = true;
        },
        async handleApprove() {
            this.isApproving = true;

            try {
                await this.approveRequest({
                    requestId: this.item.id,
                    values: {
                        comment: this.decisionComment || null,
                    },
                });
                this.decisionComment = "";
                this.showConfirmApprove = false;
                this.$emit("refresh");
            } finally {
                this.isApproving = false;
            }
        },
        async handleReject() {
            if (!this.decisionComment.trim()) {
                toast.error(this.$t("request.reject_comment_required"));
                return;
            }

            this.isRejecting = true;

            try {
                await this.rejectRequest({
                    requestId: this.item.id,
                    values: {
                        comment: this.decisionComment,
                    },
                });
                this.decisionComment = "";
                this.showConfirmReject = false;
                this.$emit("refresh");
            } finally {
                this.isRejecting = false;
            }
        },
        openCancelDialog() {
            this.showConfirmCancel = true;
        },
        openDeleteDialog() {
            this.showConfirmDelete = true;
        },
        async handleCancelRequest() {
            this.isCancelling = true;

            try {
                await this.cancelRequest(this.item.id);
                this.showConfirmCancel = false;
                this.$emit("refresh");
            } finally {
                this.isCancelling = false;
            }
        },
        async handleDeleteRequest() {
            this.isDeleting = true;

            try {
                await this.deleteRequest(this.item.id);
                this.showConfirmDelete = false;
                this.$emit("update:modelValue", false);
                this.$emit("refresh");
            } finally {
                this.isDeleting = false;
            }
        },
    },
};
</script>

<style scoped>
.request-detail-dialog {
    overflow: hidden;
}

.dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.meta-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.section-card {
    border: 1px solid rgba(var(--v-border-color), 0.16);
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 16px;
    font-weight: 700;
}

.timeline-card {
    min-height: 360px;
}

.timeline-item {
    padding-bottom: 8px;
}

.empty-state {
    min-height: 260px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: rgba(var(--v-theme-on-surface), 0.55);
}

.dialog-actions {
    padding: 16px 24px;
    background: #fafafa;
}
.min-w-0 {
    min-width: 0;
}

.loading-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 36px;
    color: rgba(var(--v-theme-on-surface), 0.85);
}

.dialog-title {
    line-height: 1.35;
}

.meta-chip {
    max-width: 100%;
}

.meta-chip :deep(.v-chip__content) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 600px) {
    .dialog-header {
        padding: 18px 16px;
    }

    .dialog-title {
        font-size: 18px !important;
    }

    :deep(.v-card-text.pa-6) {
        padding: 16px !important;
    }

    .meta-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .meta-chip {
        width: 100%;
    }

    .section-title {
        padding: 12px 14px;
    }

    .dialog-actions {
        padding: 12px 16px;
        flex-wrap: wrap;
    }

    .dialog-actions .v-btn {
        flex: 1 1 auto;
    }
}
</style>
