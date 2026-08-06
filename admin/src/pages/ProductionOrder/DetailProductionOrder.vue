<template>
    <v-dialog v-model="dialog" max-width="1600" scrollable persistent>
        <v-card class="position-relative">
            <v-card-title
                class="d-flex align-start justify-space-between py-3 px-4 ga-2 border-b"
            >
                <div
                    class="font-weight-bold text-h6 text-grey-darken-3 text-wrap pr-2"
                    style="word-break: break-word"
                >
                    {{ $t("production_order.detail_title") }}:
                    {{ detailData?.code || item?.code || "--" }}
                </div>
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    class="flex-shrink-0 align-self-start"
                    @click="dialog = false"
                />
            </v-card-title>

            <v-card-text v-if="loadingDetail" class="pa-4">
                <div class="d-flex justify-center align-center py-8">
                    <v-progress-circular indeterminate color="primary" />
                </div>
            </v-card-text>

            <v-card-text v-else-if="detailData" class="pa-4">
                <v-card variant="flat" border class="pa-4 mb-4">
                    <v-row dense>
                        <v-col cols="12" sm="6" md="4" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{ $t("production_order.columns.code") }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-primary font-weight-bold"
                                >
                                    {{ detailData.code || "--" }}
                                </span>
                            </div>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{ $t("production_order.columns.title") }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                >
                                    {{ detailData.title || "--" }}
                                </span>
                            </div>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{ $t("production_order.columns.request") }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                >
                                    {{ requestLabel || "--" }}
                                </span>
                            </div>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{
                                        $t(
                                            "production_order.columns.material_warehouse",
                                        )
                                    }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                >
                                    {{
                                        warehouseLabel(
                                            detailData.materialWarehouse,
                                        ) || "--"
                                    }}
                                </span>
                            </div>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{
                                        $t(
                                            "production_order.columns.finished_goods_warehouse",
                                        )
                                    }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                >
                                    {{
                                        warehouseLabel(
                                            detailData.finishedGoodsWarehouse,
                                        ) || "--"
                                    }}
                                </span>
                            </div>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{ $t("base.created_at") }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                >
                                    {{ detailData.createdAt || "--" }}
                                </span>
                            </div>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{
                                        $t(
                                            "production_order.columns.started_at",
                                        )
                                    }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                >
                                    {{ detailData.startedAt || "--" }}
                                </span>
                            </div>
                        </v-col>
                        <v-col cols="12" sm="6" md="4" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{
                                        $t(
                                            "production_order.columns.completed_at",
                                        )
                                    }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                >
                                    {{ detailData.completedAt || "--" }}
                                </span>
                            </div>
                        </v-col>
                        <v-col v-if="detailData.note" cols="12" class="py-1">
                            <div class="d-flex align-start ga-2">
                                <span
                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                >
                                    {{ $t("base.note") }}:
                                </span>
                                <span
                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                >
                                    {{ detailData.note || "--" }}
                                </span>
                            </div>
                        </v-col>
                    </v-row>
                </v-card>

                <v-card variant="flat" border class="mb-4 overflow-hidden">
                    <v-card-title
                        class="bg-grey-lighten-4 py-3 px-4 d-flex align-center justify-space-between"
                    >
                        <span>{{ $t("base.status") }}</span>
                        <v-chip
                            size="small"
                            variant="tonal"
                            :color="getStatusColor(detailData.status)"
                        >
                            {{ getStatusLabel(detailData.status) }}
                        </v-chip>
                    </v-card-title>
                    <StatusStepper
                        :status="detailData.status"
                        :steps="statusSteps"
                        :disabled="true"
                        :display-only="true"
                    />
                </v-card>

                <v-card variant="flat" border class="overflow-hidden">
                    <v-card-title
                        class="bg-grey-lighten-4 py-3 px-4 d-flex align-center justify-space-between"
                    >
                        <span>
                            {{
                                $t(
                                    "production_order.detail.finished_products",
                                )
                            }}
                        </span>
                        <v-chip size="small" color="primary" variant="tonal">
                            {{ detailData.items?.length || 0 }}
                        </v-chip>
                    </v-card-title>

                    <v-expansion-panels
                        v-if="detailData.items?.length"
                        variant="inset"
                        class=""
                    >
                        <v-expansion-panel
                            v-for="(orderItem, index) in detailData.items"
                            :key="orderItem.id || index"
                        >
                            <v-expansion-panel-title
                                class="py-3 px-4"
                                style="min-height: 56px"
                            >
                                <div
                                    class="d-flex align-center ga-2 flex-grow-1"
                                >
                                    <v-chip size="small" color="primary">
                                        {{ index + 1 }}
                                    </v-chip>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold">
                                            {{ productName(orderItem) }}
                                        </div>
                                        <div
                                            class="text-caption text-medium-emphasis"
                                        >
                                            {{ productCode(orderItem) }}
                                        </div>
                                    </div>
                                </div>
                                <template #actions>
                                    <v-chip
                                        size="small"
                                        variant="tonal"
                                        :color="
                                            getStatusColor(orderItem.status)
                                        "
                                    >
                                        {{ getStatusLabel(orderItem.status) }}
                                    </v-chip>
                                </template>
                            </v-expansion-panel-title>

                            <v-expansion-panel-text class="pa-0">
                                <v-divider />

                                <StatusStepper
                                    :status="orderItem.status"
                                    :steps="statusSteps"
                                    :loading-value="
                                        updatingItemId === orderItem.id
                                            ? targetStatus
                                            : null
                                    "
                                    :disabled="
                                        updatingItemId !== null &&
                                        updatingItemId !== orderItem.id
                                    "
                                    @change="
                                        (status) =>
                                            changeItemStatus(orderItem, status)
                                    "
                                />

                                <v-divider />

                                <div class="pa-4">
                                    <v-row dense class="mb-2">
                                        <v-col cols="12" sm="6" md="3">
                                            <div
                                                class="d-flex align-start ga-2"
                                            >
                                                <span
                                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                                >
                                                    {{
                                                        $t(
                                                            "production_order.detail.planned_quantity",
                                                        )
                                                    }}:
                                                </span>
                                                <span
                                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                                >
                                                    {{
                                                        plannedQuantityLabel(
                                                            orderItem,
                                                        ) || "--"
                                                    }}
                                                </span>
                                            </div>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="3">
                                            <div
                                                class="d-flex align-start ga-2"
                                            >
                                                <span
                                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                                >
                                                    {{
                                                        $t(
                                                            "production_order.detail.base_quantity",
                                                        )
                                                    }}:
                                                </span>
                                                <span
                                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                                >
                                                    {{
                                                        baseQuantityLabel(
                                                            orderItem,
                                                        ) || "--"
                                                    }}
                                                </span>
                                            </div>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="3">
                                            <div
                                                class="d-flex align-start ga-2"
                                            >
                                                <span
                                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                                >
                                                    {{
                                                        $t(
                                                            "production_order.detail.accepted_quantity",
                                                        )
                                                    }}:
                                                </span>
                                                <span
                                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                                >
                                                    {{
                                                        acceptedQuantityLabel(
                                                            orderItem,
                                                        ) || "--"
                                                    }}
                                                </span>
                                            </div>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="3">
                                            <div
                                                class="d-flex align-start ga-2"
                                            >
                                                <span
                                                    class="text-subtitle-2 text-medium-emphasis flex-shrink-0"
                                                >
                                                    {{
                                                        $t(
                                                            "production_order.detail.waste_rate",
                                                        )
                                                    }}:
                                                </span>
                                                <span
                                                    class="text-body-1 font-weight-medium text-wrap text-grey-darken-3"
                                                >
                                                    {{
                                                        wasteRateLabel(
                                                            orderItem,
                                                        ) || "--"
                                                    }}
                                                </span>
                                            </div>
                                        </v-col>
                                    </v-row>

                                    <div
                                        class="text-subtitle-1 font-weight-bold mb-2"
                                    >
                                        {{
                                            $t(
                                                "production_order.detail.materials",
                                            )
                                        }}
                                    </div>

                                    <div
                                        v-if="orderItem.materials?.length"
                                        class="overflow-x-auto"
                                    >
                                        <v-table
                                            density="comfortable"
                                            class="materials-table"
                                        >
                                            <thead>
                                                <tr class="bg-grey-lighten-5">
                                                    <th
                                                        class="text-center"
                                                        style="width: 60px"
                                                    >
                                                        {{ $t("field.stt") }}
                                                    </th>
                                                    <th>
                                                        {{
                                                            $t(
                                                                "production_order.detail.material",
                                                            )
                                                        }}
                                                    </th>
                                                    <th class="text-right">
                                                        {{
                                                            $t(
                                                                "production_order.detail.planned_quantity",
                                                            )
                                                        }}
                                                    </th>
                                                    <th class="text-right">
                                                        {{
                                                            $t(
                                                                "production_order.detail.base_quantity",
                                                            )
                                                        }}
                                                    </th>
                                                    <th>
                                                        {{
                                                            $t(
                                                                "production_order.detail.unit",
                                                            )
                                                        }}
                                                    </th>
                                                    <th>
                                                        {{ $t("base.note") }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="(
                                                        material, materialIndex
                                                    ) in orderItem.materials"
                                                    :key="
                                                        material.id ||
                                                        materialIndex
                                                    "
                                                >
                                                    <td class="text-center">
                                                        {{ materialIndex + 1 }}
                                                    </td>
                                                    <td>
                                                        <div
                                                            class="font-weight-medium"
                                                        >
                                                            {{
                                                                materialName(
                                                                    material,
                                                                )
                                                            }}
                                                        </div>
                                                        <div
                                                            class="text-caption text-medium-emphasis"
                                                        >
                                                            {{
                                                                materialCode(
                                                                    material,
                                                                )
                                                            }}
                                                        </div>
                                                    </td>
                                                    <td class="text-right">
                                                        {{
                                                            formatNumber(
                                                                material.plannedQuantity,
                                                            )
                                                        }}
                                                    </td>
                                                    <td class="text-right">
                                                        {{
                                                            formatNumber(
                                                                material.plannedBaseQuantity,
                                                            )
                                                        }}
                                                    </td>
                                                    <td>
                                                        {{
                                                            unitName(
                                                                material.plannedUnit,
                                                            )
                                                        }}
                                                    </td>
                                                    <td>
                                                        {{
                                                            material.note ||
                                                            "--"
                                                        }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </v-table>
                                    </div>
                                    <div
                                        v-else
                                        class="text-body-2 text-medium-emphasis py-4"
                                    >
                                        {{
                                            $t(
                                                "production_order.detail.no_materials",
                                            )
                                        }}
                                    </div>

                                    <div
                                        v-if="orderItem.note"
                                        class="mt-3 text-body-2"
                                    >
                                        <span class="font-weight-medium">
                                            {{ $t("base.note") }}:
                                        </span>
                                        {{ orderItem.note }}
                                    </div>
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>

                    <v-card-text
                        v-else
                        class="text-center text-medium-emphasis py-8"
                    >
                        {{
                            $t(
                                "production_order.detail.no_finished_products",
                            )
                        }}
                    </v-card-text>
                </v-card>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import { mapActions } from "vuex";
import { functionHelper } from "@/helpers/functionHelper";
import StatusStepper from "@/components/StatusStepper.vue";

export default {
    name: "DetailProductionOrder",
    components: {
        StatusStepper,
    },
    inheritAttrs: false,
    props: {
        item: {
            type: Object,
            default: null,
        },
        modelValue: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:modelValue", "reload"],
    data() {
        return {
            detailData: null,
            loadingDetail: false,
            updatingItemId: null,
            targetStatus: null,
            statusSteps: [
                {
                    value: "CREATED",
                    key: "production_order.status.CREATED",
                },
                {
                    value: "MATERIAL_ISSUED",
                    key: "production_order.status.MATERIAL_ISSUED",
                },
                {
                    value: "STARTED",
                    key: "production_order.status.STARTED",
                },
                {
                    value: "IN_PROGRESS",
                    key: "production_order.status.IN_PROGRESS",
                },
                {
                    value: "INSPECTING",
                    key: "production_order.status.INSPECTING",
                },
                {
                    value: "COMPLETED",
                    key: "production_order.status.COMPLETED",
                },
            ],
        };
    },
    computed: {
        dialog: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
        requestLabel() {
            const request = this.detailData?.request;
            if (!request) {
                return this.detailData?.requestId || "--";
            }
            return [request.code, request.title].filter(Boolean).join(" - ");
        },
    },
    watch: {
        dialog: {
            immediate: true,
            handler(isOpen) {
                if (isOpen && this.item?.id) {
                    this.loadDetail();
                }
            },
        },
        item: {
            immediate: true,
            handler(newItem) {
                if (this.dialog && newItem?.id) {
                    this.loadDetail();
                }
            },
        },
    },
    methods: {
        ...mapActions("productionOrder", [
            "fetchItemDetail",
            "updateItemStatus",
        ]),
        async loadDetail() {
            if (!this.item?.id) {
                return;
            }

            this.loadingDetail = true;
            try {
                this.detailData = await this.fetchItemDetail({
                    id: this.item.id,
                    force: true,
                });
            } finally {
                this.loadingDetail = false;
            }
        },
        async changeItemStatus(orderItem, status) {
            if (!orderItem?.id || this.updatingItemId !== null) {
                return;
            }

            this.updatingItemId = orderItem.id;
            this.targetStatus = status;

            try {
                const result = await this.updateItemStatus({
                    id: orderItem.id,
                    status,
                });

                if (!result) {
                    return;
                }

                this.detailData = await this.fetchItemDetail({
                    id: this.item.id,
                    force: true,
                });
                this.$emit("reload");
            } finally {
                this.updatingItemId = null;
                this.targetStatus = null;
            }
        },
        productName(orderItem) {
            return (
                orderItem.finishedProduct?.name ||
                orderItem.productSnapshot?.name ||
                "--"
            );
        },
        productCode(orderItem) {
            return (
                orderItem.finishedProduct?.code ||
                orderItem.productSnapshot?.code ||
                "--"
            );
        },
        materialName(material) {
            return (
                material.ingredient?.name ||
                material.ingredientSnapshot?.name ||
                "--"
            );
        },
        materialCode(material) {
            return (
                material.ingredient?.code ||
                material.ingredientSnapshot?.code ||
                "--"
            );
        },
        unitName(unit) {
            return unit?.name || "--";
        },
        warehouseLabel(warehouse) {
            if (!warehouse) {
                return "--";
            }
            return [warehouse.code, warehouse.name]
                .filter(Boolean)
                .join(" - ");
        },
        plannedQuantityLabel(orderItem) {
            return [
                this.formatNumber(orderItem.plannedQuantity),
                this.unitName(orderItem.plannedUnit),
            ].join(" ");
        },
        baseQuantityLabel(orderItem) {
            return [
                this.formatNumber(orderItem.plannedBaseQuantity),
                this.unitName(orderItem.baseUnit),
            ].join(" ");
        },
        acceptedQuantityLabel(orderItem) {
            return [
                this.formatNumber(orderItem.acceptedBaseQuantity),
                this.unitName(orderItem.baseUnit),
            ].join(" ");
        },
        wasteRateLabel(orderItem) {
            return [orderItem.expectedWastePercent || 0, "%"].join("");
        },
        formatNumber(value) {
            return functionHelper.formatNumber(value);
        },
        getStatusLabel(status) {
            return (
                this.$t("production_order.status." + status) ||
                status ||
                "--"
            );
        },
        getStatusColor(status) {
            return (
                {
                    CREATED: "grey",
                    MATERIAL_ISSUED: "deep-purple",
                    STARTED: "info",
                    IN_PROGRESS: "warning",
                    INSPECTING: "teal",
                    COMPLETED: "success",
                    CANCELLED: "error",
                }[status] || "primary"
            );
        },
    },
};
</script>

<style scoped>
.border-b {
    border-bottom: 1px solid rgba(0, 0, 0, 0.12);
}

.materials-table {
    min-width: 900px;
}
</style>
