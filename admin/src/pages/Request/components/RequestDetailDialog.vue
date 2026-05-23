<template>
    <v-dialog
        :model-value="modelValue"
        max-width="1120"
        width="calc(100vw - 24px)"
        scrollable
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <v-card class="request-detail-dialog" rounded="xl">
            <div class="dialog-header">
                <div class="d-flex align-start ga-3 flex-grow-1 min-w-0">
                    <v-avatar
                        color="primary"
                        variant="tonal"
                        size="44"
                        class="flex-shrink-0"
                    >
                        <v-icon icon="mdi-file-document-outline" size="26" />
                    </v-avatar>

                    <div class="min-w-0">
                        <div class="text-h6 font-weight-bold dialog-title">
                            {{ item?.title || "--" }}
                        </div>

                        <div class="d-flex flex-wrap align-center ga-2 mt-2">
                            <v-chip
                                v-if="item"
                                color="info"
                                size="small"
                                variant="flat"
                            >
                                {{ item?.code || "--" }}
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
                <v-progress-linear
                    v-if="loading"
                    indeterminate
                    color="primary"
                    class="mb-4"
                    rounded
                />

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
                            <v-icon start icon="mdi-account-check-outline" />
                            {{ $t("request.approver") }}:
                            {{ item.currentApprover?.name || "--" }}
                        </v-chip>
                    </div>

                    <v-row>
                        <v-col cols="12" md="7">
                            <v-card class="section-card" rounded="lg">
                                <div class="section-title">
                                    <v-icon
                                        icon="mdi-information-outline"
                                        size="20"
                                    />
                                    <span>{{ $t("request.detail_info") }}</span>
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
                                    item.permissions?.canApprove ||
                                    item.permissions?.canReject
                                "
                                class="section-card mt-4"
                                rounded="lg"
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
                                    <v-textarea
                                        v-model="decisionComment"
                                        :label="$t('request.approval_note')"
                                        variant="outlined"
                                        rows="3"
                                        hide-details
                                    />

                                    <div class="d-flex justify-end ga-2 mt-4">
                                        <v-btn
                                            color="error"
                                            variant="tonal"
                                            prepend-icon="mdi-close-circle-outline"
                                            @click="handleReject"
                                        >
                                            {{ $t("request.reject_button") }}
                                        </v-btn>

                                        <v-btn
                                            color="success"
                                            prepend-icon="mdi-check-circle-outline"
                                            @click="handleApprove"
                                        >
                                            {{ $t("request.approve_button") }}
                                        </v-btn>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>

                        <v-col cols="12" md="5">
                            <v-card
                                class="section-card timeline-card"
                                rounded="lg"
                            >
                                <div class="section-title">
                                    <v-icon icon="mdi-history" size="20" />
                                    <span>{{ $t("request.timeline") }}</span>
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
                                                getEventColor(event.eventType)
                                            "
                                            fill-dot
                                            size="small"
                                        >
                                            <div class="timeline-item">
                                                <div class="font-weight-bold">
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
                                                    ·
                                                    {{
                                                        formatMessageTime(
                                                            event.createdAt,
                                                        )
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

            <v-card-actions v-if="item" class="dialog-actions">
                <v-spacer />

                <v-btn
                    v-if="item.permissions?.canEdit"
                    color="warning"
                    variant="tonal"
                    prepend-icon="mdi-pencil-outline"
                    @click="$emit('edit', item)"
                >
                    {{ $t("button.update") }}
                </v-btn>

                <v-btn
                    v-if="item.permissions?.canCancel"
                    color="error"
                    variant="tonal"
                    prepend-icon="mdi-cancel"
                    @click="handleCancelRequest"
                >
                    {{ $t("request.cancel_button") }}
                </v-btn>

                <v-btn
                    v-if="item.permissions?.canDelete"
                    color="error"
                    variant="text"
                    prepend-icon="mdi-trash-can-outline"
                    @click="handleDeleteRequest"
                >
                    {{ $t("button.delete") }}
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import { mapActions } from "vuex";
import { functionHelper } from "@/helpers/functionHelper";
import { getRequestTypeComponentConfig } from "./request-types/requestTypeComponentRegistry";

export default {
    name: "RequestDetailDialog",
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
    },
    emits: ["update:modelValue", "refresh", "edit"],
    data() {
        return {
            decisionComment: "",
        };
    },
    computed: {
        activeDetailComponent() {
            return (
                getRequestTypeComponentConfig(this.item?.type)
                    ?.detailComponent ?? null
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
        getRequestStatusColor(status) {
            return functionHelper.getRequestStatusColor(status);
        },
        getRequestStatusLabel(status) {
            return functionHelper.getRequestStatusLabel(status);
        },
        getEventTitle(eventType) {
            return functionHelper.getRequestEventTitle(eventType);
        },
        getEventColor(eventType) {
            return functionHelper.getRequestEventColor(eventType);
        },
        formatMessageTime(value) {
            return functionHelper.formatMessageTime(value);
        },
        async handleApprove() {
            await this.approveRequest({
                requestId: this.item.id,
                values: {
                    comment: this.decisionComment || null,
                },
            });
            this.decisionComment = "";
            this.$emit("refresh");
        },
        async handleReject() {
            if (!this.decisionComment.trim()) {
                return;
            }

            await this.rejectRequest({
                requestId: this.item.id,
                values: {
                    comment: this.decisionComment,
                },
            });
            this.decisionComment = "";
            this.$emit("refresh");
        },
        async handleCancelRequest() {
            await this.cancelRequest(this.item.id);
            this.$emit("refresh");
        },
        async handleDeleteRequest() {
            await this.deleteRequest(this.item.id);
            this.$emit("update:modelValue", false);
            this.$emit("refresh");
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
