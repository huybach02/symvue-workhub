<template>
    <v-card-text class="pa-4">
        <v-row dense>
            <!-- Thông tin chung -->
            <v-col cols="12" md="6">
                <v-sheet
                    rounded="lg"
                    border
                    color="grey-lighten-5"
                    class="pa-4 h-100"
                >
                    <div class="d-flex align-center ga-2 mb-2">
                        <v-icon
                            icon="mdi-account-outline"
                            size="18"
                            color="primary"
                        />
                        <div class="text-caption text-medium-emphasis">
                            {{ $t("field.requester_name") }}
                        </div>
                    </div>
                    <div class="text-body-1 font-weight-bold">
                        {{ item?.requester?.name || "--" }}
                    </div>
                </v-sheet>
            </v-col>

            <v-col cols="12" md="6">
                <v-sheet
                    rounded="lg"
                    border
                    color="grey-lighten-5"
                    class="pa-4 h-100"
                >
                    <div class="d-flex align-center ga-2 mb-2">
                        <v-icon
                            icon="mdi-calendar-plus-outline"
                            size="18"
                            color="primary"
                        />
                        <div class="text-caption text-medium-emphasis">
                            {{ $t("field.request_created_at") }}
                        </div>
                    </div>
                    <div class="text-body-1 font-weight-bold">
                        {{ item?.createdAt || "--" }}
                    </div>
                </v-sheet>
            </v-col>

            <!-- Danh sách thành phẩm sản xuất -->
            <v-col cols="12" class="mt-4">
                <div
                    class="text-subtitle-1 font-weight-bold mb-4 d-flex align-center ga-2"
                >
                    <v-icon icon="mdi-factory" color="primary" />
                    {{ $t("request.production.items_title") }}
                </div>

                <div
                    v-if="!items.length"
                    class="text-medium-emphasis py-6 text-center border rounded-lg"
                >
                    {{ $t("request.production.empty_items") }}
                </div>

                <v-card
                    v-for="(productionItem, itemIndex) in items"
                    :key="itemIndex"
                    variant="flat"
                    border
                    class="mb-6 overflow-hidden"
                >
                    <v-card-title
                        class="bg-grey-lighten-4 py-3 px-4 d-flex align-center justify-space-between flex-wrap ga-2"
                    >
                        <div class="d-flex align-center ga-2">
                            <v-chip size="small" color="primary" variant="flat">
                                {{ itemIndex + 1 }}
                            </v-chip>
                            <span
                                class="text-subtitle-1 font-weight-bold text-grey-darken-3"
                            >
                                {{
                                    productionItem.finishedProductName ||
                                    productionItem.finishedProductId ||
                                    "--"
                                }}
                            </span>
                        </div>
                        <div class="d-flex align-center ga-3 flex-wrap">
                            <div
                                class="text-subtitle-2 font-weight-bold text-primary"
                            >
                                {{
                                    $t("field.quantity")
                                }}:
                                {{ formatNumber(productionItem.quantity) }}
                                {{ productionItem.outputUnitName || "" }}
                            </div>
                            <v-chip
                                v-if="
                                    productionItem.expectedWastePercent != null
                                "
                                size="small"
                                variant="tonal"
                                color="warning"
                            >
                                {{
                                    $t("merchandise.recipe.waste_rate")
                                }}:
                                {{
                                    formatNumber(
                                        productionItem.expectedWastePercent,
                                    )
                                }}
                                %
                            </v-chip>
                        </div>
                    </v-card-title>

                    <v-divider />

                    <v-table density="comfortable">
                        <thead>
                            <tr class="bg-grey-lighten-5">
                                <th
                                    class="text-center font-weight-bold py-3"
                                    style="width: 60px"
                                >
                                    {{ $t("field.stt") }}
                                </th>
                                <th
                                    class="text-left font-weight-bold py-3"
                                >
                                    {{ $t("merchandise.ingredient") }}
                                </th>
                                <th
                                    class="text-right font-weight-bold py-3"
                                    style="width: 130px"
                                >
                                    {{ $t("field.quantity") }}
                                </th>
                                <th
                                    class="text-center font-weight-bold py-3"
                                    style="width: 110px"
                                >
                                    {{ $t("field.unit") }}
                                </th>
                                <th
                                    class="text-center font-weight-bold py-3"
                                    style="width: 120px"
                                >
                                    {{
                                        $t("merchandise.recipe.waste_rate")
                                    }}
                                </th>
                                <th
                                    class="text-left font-weight-bold py-3"
                                    style="width: 200px"
                                >
                                    {{ $t("field.ghi_chu") }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(
                                    material, materialIndex
                                ) in productionItem.materials || []"
                                :key="materialIndex"
                            >
                                <td class="text-center py-2 text-grey-darken-1">
                                    {{ materialIndex + 1 }}
                                </td>
                                <td
                                    class="py-2 font-weight-medium text-grey-darken-4"
                                >
                                    {{
                                        material.ingredientName ||
                                        material.ingredientId ||
                                        "--"
                                    }}
                                </td>
                                <td
                                    class="text-right py-2 font-weight-bold text-grey-darken-3"
                                >
                                    {{ formatNumber(material.quantity) }}
                                </td>
                                <td class="text-center py-2">
                                    <v-chip
                                        size="small"
                                        variant="tonal"
                                        color="secondary"
                                    >
                                        {{
                                            material.unitName ||
                                            material.unitId ||
                                            "--"
                                        }}
                                    </v-chip>
                                </td>
                                <td
                                    class="text-center py-2 text-grey-darken-3"
                                >
                                    {{ formatNumber(material.wasteRate) }} %
                                </td>
                                <td
                                    class="py-2 text-body-2 text-medium-emphasis"
                                >
                                    {{ material.note || "--" }}
                                </td>
                            </tr>
                        </tbody>
                    </v-table>

                    <div
                        v-if="
                            !productionItem.materials ||
                            productionItem.materials.length === 0
                        "
                        class="text-medium-emphasis py-4 text-center"
                    >
                        {{ $t("request.production.empty_materials") }}
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-card-text>
</template>

<script>
export default {
    name: "RequestProductionDetailFields",
    props: {
        item: {
            type: Object,
            default: null,
        },
    },
    computed: {
        items() {
            return Array.isArray(this.item?.payload?.items)
                ? this.item.payload.items
                : [];
        },
    },
    methods: {
        formatNumber(value) {
            if (value === null || value === undefined || value === "") {
                return "--";
            }
            return Number(value).toLocaleString("en-US", {
                maximumFractionDigits: 4,
            });
        },
    },
};
</script>
