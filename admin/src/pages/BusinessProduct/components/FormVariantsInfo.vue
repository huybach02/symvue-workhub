<template>
    <div>
        <VeeField
            v-slot="{ field: fieldVariants, handleChange: onChangeVariants }"
            name="variants"
        >
            <div class="mb-4">
                <div class="d-flex justify-space-between align-center mb-4">
                    <h3 class="text-subtitle-1 font-weight-bold">
                        {{
                            $t("business_product.variants_management") ||
                            "Quản lý phiên bản sản phẩm"
                        }}
                    </h3>
                    <v-btn
                        color="primary"
                        prepend-icon="mdi-plus"
                        @click="
                            addVariant(fieldVariants.value, onChangeVariants)
                        "
                    >
                        {{ $t("base.add_variant") }}
                    </v-btn>
                </div>

                <!-- Danh sách phiên bản -->
                <div
                    v-if="fieldVariants.value && fieldVariants.value.length > 0"
                >
                    <v-expansion-panels
                        v-model="openedPanel"
                        multiple
                        class="mb-4"
                    >
                        <v-expansion-panel
                            v-for="(variant, vIdx) in fieldVariants.value"
                            :key="vIdx"
                            class="border mb-2 rounded-lg"
                        >
                            <v-expansion-panel-title class="py-3">
                                <div
                                    class="d-flex align-center justify-space-between w-100 pr-4"
                                >
                                    <div class="d-flex align-center ga-3">
                                        <v-icon color="primary">
                                            mdi-package-variant-closed
                                        </v-icon>
                                        <span
                                            class="font-weight-bold text-subtitle-1"
                                        >
                                            {{
                                                variant.name || variant.code
                                                    ? `[${variant.code || "--"}] ${variant.name || "--"}`
                                                    : `#${vIdx + 1}`
                                            }}
                                        </span>
                                        <v-chip
                                            v-if="variant.isDefault"
                                            color="primary"
                                            size="x-small"
                                            variant="flat"
                                        >
                                            {{ $t("base.default") }}
                                        </v-chip>
                                    </div>
                                    <v-btn
                                        icon="mdi-delete"
                                        color="error"
                                        variant="text"
                                        size="small"
                                        class="flex-shrink-0"
                                        @click.stop="
                                            removeVariant(
                                                vIdx,
                                                fieldVariants.value,
                                                onChangeVariants,
                                            )
                                        "
                                    />
                                </div>
                            </v-expansion-panel-title>

                            <v-expansion-panel-text class="pt-4 bg-white">
                                <!-- Thông tin cơ bản của Variant -->
                                <v-row>
                                    <v-col cols="12" md="4">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t("field.sku_code") ||
                                                "Mã biến thể / SKU"
                                            }}
                                            <span class="text-red">*</span>
                                        </div>
                                        <v-text-field
                                            v-model="variant.code"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :error="
                                                showErrors &&
                                                variant._isNew &&
                                                !variant.code
                                            "
                                            :placeholder="`${$t('base.enter')} ${$t('field.sku_code') || 'mã biến thể'}`"
                                            @update:model-value="
                                                onChangeVariants(
                                                    fieldVariants.value,
                                                )
                                            "
                                        />
                                        <div
                                            v-if="
                                                showErrors &&
                                                variant._isNew &&
                                                !variant.code
                                            "
                                            class="text-caption text-error mt-1"
                                        >
                                            {{
                                                $t(
                                                    "validation.mixed.required",
                                                    {
                                                        field:
                                                            $t(
                                                                "field.sku_code",
                                                            ) || "Mã biến thể",
                                                    },
                                                )
                                            }}
                                        </div>
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t("field.variant_name") ||
                                                "Tên biến thể"
                                            }}
                                            <span class="text-red">*</span>
                                        </div>
                                        <v-text-field
                                            v-model="variant.name"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :error="
                                                showErrors &&
                                                variant._isNew &&
                                                !variant.name
                                            "
                                            :placeholder="`${$t('base.enter')} ${$t('field.variant_name') || 'tên biến thể'}`"
                                            @update:model-value="
                                                onChangeVariants(
                                                    fieldVariants.value,
                                                )
                                            "
                                        />
                                        <div
                                            v-if="
                                                showErrors &&
                                                variant._isNew &&
                                                !variant.name
                                            "
                                            class="text-caption text-error mt-1"
                                        >
                                            {{
                                                $t(
                                                    "validation.mixed.required",
                                                    {
                                                        field:
                                                            $t(
                                                                "field.variant_name",
                                                            ) || "Tên biến thể",
                                                    },
                                                )
                                            }}
                                        </div>
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t("field.unit") ||
                                                "Đơn vị tính"
                                            }}
                                            <span class="text-red">*</span>
                                        </div>
                                        <v-autocomplete
                                            v-model="variant.unitId"
                                            :items="unitOptions"
                                            item-title="label"
                                            item-value="value"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :error="
                                                showErrors &&
                                                variant._isNew &&
                                                !variant.unitId
                                            "
                                            :placeholder="
                                                $t('field.select_unit') ||
                                                'Chọn đơn vị'
                                            "
                                            @update:model-value="
                                                onChangeVariants(
                                                    fieldVariants.value,
                                                )
                                            "
                                        />
                                        <div
                                            v-if="
                                                showErrors &&
                                                variant._isNew &&
                                                !variant.unitId
                                            "
                                            class="text-caption text-error mt-1"
                                        >
                                            {{
                                                $t(
                                                    "validation.mixed.required",
                                                    {
                                                        field:
                                                            $t("field.unit") ||
                                                            "Đơn vị tính",
                                                    },
                                                )
                                            }}
                                        </div>
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <div class="mb-2 font-weight-medium">
                                            {{ $t("field.barcode") }}
                                        </div>
                                        <v-text-field
                                            v-model="variant.barcode"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :placeholder="`${$t('base.enter')} ${$t('field.barcode') || 'mã vạch'}`"
                                            @update:model-value="
                                                onChangeVariants(
                                                    fieldVariants.value,
                                                )
                                            "
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <div class="mb-2 font-weight-medium">
                                            {{
                                                $t(
                                                    "field.is_default_variant",
                                                ) || "Là phiên bản mặc định"
                                            }}
                                        </div>
                                        <v-switch
                                            v-model="variant.isDefault"
                                            color="primary"
                                            density="compact"
                                            hide-details
                                            @update:model-value="
                                                handleDefaultChange(
                                                    vIdx,
                                                    fieldVariants.value,
                                                    onChangeVariants,
                                                )
                                            "
                                        />
                                    </v-col>

                                    <v-col cols="12" md="4">
                                        <div class="mb-2 font-weight-medium">
                                            {{ $t("field.trang_thai") }}
                                            <span class="text-red">*</span>
                                        </div>
                                        <v-select
                                            v-model="variant.status"
                                            :items="statusOptions"
                                            item-title="text"
                                            item-value="value"
                                            variant="outlined"
                                            density="compact"
                                            hide-details
                                            @update:model-value="
                                                onChangeVariants(
                                                    fieldVariants.value,
                                                )
                                            "
                                        />
                                    </v-col>
                                </v-row>

                                <v-divider class="my-4" />

                                <!-- Thiết lập Công thức sản phẩm cho variant -->
                                <div>
                                    <div
                                        class="d-flex justify-space-between align-center mb-3"
                                    >
                                        <h4
                                            class="text-subtitle-2 font-weight-bold text-grey-darken-3"
                                        >
                                            {{
                                                $t(
                                                    "business_product.variant_recipe",
                                                ) ||
                                                "Thiết lập công thức sản phẩm cho phiên bản"
                                            }}
                                        </h4>
                                        <v-btn
                                            color="primary"
                                            prepend-icon="mdi-plus"
                                            size="small"
                                            variant="outlined"
                                            @click="
                                                addRecipeItem(
                                                    variant,
                                                    fieldVariants.value,
                                                    onChangeVariants,
                                                )
                                            "
                                        >
                                            {{
                                                $t(
                                                    "business_product.add_finished_product",
                                                ) || "Thêm thành phẩm công thức"
                                            }}
                                        </v-btn>
                                    </div>

                                    <!-- Bảng các thành phẩm trong công thức của Variant -->
                                    <div
                                        v-if="
                                            variant.recipe?.items &&
                                            variant.recipe.items.length > 0
                                        "
                                        class="border rounded-lg bg-white overflow-x-auto"
                                    >
                                        <v-table
                                            density="compact"
                                            class="w-100"
                                            style="min-width: 960px"
                                        >
                                            <thead>
                                                <tr>
                                                    <th
                                                        class="text-left font-weight-bold py-2"
                                                        style="width: 28%"
                                                    >
                                                        {{
                                                            $t(
                                                                "business_product.recipe.finished_product",
                                                            ) || "Thành phẩm"
                                                        }}
                                                    </th>
                                                    <th
                                                        class="text-left font-weight-bold py-2"
                                                        style="width: 12%"
                                                    >
                                                        {{
                                                            $t(
                                                                "business_product.recipe.quantity",
                                                            ) || "Số lượng"
                                                        }}
                                                    </th>
                                                    <th
                                                        class="text-left font-weight-bold py-2"
                                                        style="width: 16%"
                                                    >
                                                        {{
                                                            $t(
                                                                "business_product.recipe.unit",
                                                            ) || "Đơn vị tính"
                                                        }}
                                                    </th>
                                                    <th
                                                        class="text-left font-weight-bold py-2"
                                                        style="width: 12%"
                                                    >
                                                        {{
                                                            $t(
                                                                "business_product.recipe.waste_rate",
                                                            ) || "Hao hụt (%)"
                                                        }}
                                                    </th>
                                                    <th
                                                        class="text-left font-weight-bold py-2"
                                                        style="width: 22%"
                                                    >
                                                        {{
                                                            $t(
                                                                "business_product.recipe.notes",
                                                            ) || "Ghi chú"
                                                        }}
                                                    </th>
                                                    <th
                                                        class="text-center py-2"
                                                        style="width: 5%"
                                                    ></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="(
                                                        itemRow, itemIdx
                                                    ) in variant.recipe.items"
                                                    :key="itemIdx"
                                                >
                                                    <!-- Chọn thành phẩm (Merchandise) -->
                                                    <td
                                                        class="py-2"
                                                        style="
                                                            vertical-align: top;
                                                        "
                                                    >
                                                        <v-autocomplete
                                                            v-model="
                                                                itemRow.finishedProductId
                                                            "
                                                            :items="
                                                                merchandises
                                                            "
                                                            item-title="label"
                                                            item-value="value"
                                                            variant="outlined"
                                                            density="compact"
                                                            hide-details
                                                            clearable
                                                            :loading="
                                                                loadingMerchandises
                                                            "
                                                            :error="
                                                                showErrors &&
                                                                itemRow._isNew &&
                                                                !itemRow.finishedProductId
                                                            "
                                                            :placeholder="
                                                                $t(
                                                                    'business_product.recipe.select_finished_product',
                                                                ) ||
                                                                'Chọn thành phẩm'
                                                            "
                                                            @update:model-value="
                                                                (val) =>
                                                                    onMerchandiseSelected(
                                                                        val,
                                                                        itemRow,
                                                                        vIdx,
                                                                        itemIdx,
                                                                        fieldVariants.value,
                                                                        onChangeVariants,
                                                                    )
                                                            "
                                                        />
                                                        <div
                                                            v-if="
                                                                showErrors &&
                                                                itemRow._isNew &&
                                                                !itemRow.finishedProductId
                                                            "
                                                            class="text-caption text-error"
                                                        >
                                                            {{
                                                                $t(
                                                                    "validation.mixed.required",
                                                                    {
                                                                        field:
                                                                            $t(
                                                                                "business_product.recipe.finished_product",
                                                                            ) ||
                                                                            "Thành phẩm",
                                                                    },
                                                                )
                                                            }}
                                                        </div>
                                                    </td>

                                                    <!-- Nhập số lượng -->
                                                    <td
                                                        class="py-2"
                                                        style="
                                                            vertical-align: top;
                                                        "
                                                    >
                                                        <v-text-field
                                                            v-model.number="
                                                                itemRow.quantity
                                                            "
                                                            type="number"
                                                            variant="outlined"
                                                            density="compact"
                                                            hide-details
                                                            :error="
                                                                showErrors &&
                                                                itemRow._isNew &&
                                                                (!itemRow.quantity ||
                                                                    +itemRow.quantity <=
                                                                        0)
                                                            "
                                                            @update:model-value="
                                                                onChangeVariants(
                                                                    fieldVariants.value,
                                                                )
                                                            "
                                                        />
                                                        <div
                                                            v-if="
                                                                showErrors &&
                                                                itemRow._isNew &&
                                                                (!itemRow.quantity ||
                                                                    +itemRow.quantity <=
                                                                        0)
                                                            "
                                                            class="text-caption text-error"
                                                        >
                                                            {{
                                                                $t(
                                                                    "merchandise.recipe.quantity_positive",
                                                                ) ||
                                                                "Định lượng > 0"
                                                            }}
                                                        </div>
                                                    </td>

                                                    <!-- Chọn đơn vị tính của thành phẩm -->
                                                    <td
                                                        class="py-2"
                                                        style="
                                                            vertical-align: top;
                                                        "
                                                    >
                                                        <v-select
                                                            v-model="
                                                                itemRow.unitId
                                                            "
                                                            :items="
                                                                getMerchandiseUnitOptions(
                                                                    itemRow.finishedProductId,
                                                                )
                                                            "
                                                            item-title="label"
                                                            item-value="value"
                                                            variant="outlined"
                                                            density="compact"
                                                            hide-details
                                                            :disabled="
                                                                !itemRow.finishedProductId
                                                            "
                                                            :error="
                                                                showErrors &&
                                                                itemRow._isNew &&
                                                                !itemRow.unitId &&
                                                                !!itemRow.finishedProductId
                                                            "
                                                            :placeholder="
                                                                $t(
                                                                    'business_product.recipe.select_unit',
                                                                ) ||
                                                                'Chọn đơn vị'
                                                            "
                                                            @update:model-value="
                                                                onChangeVariants(
                                                                    fieldVariants.value,
                                                                )
                                                            "
                                                        />
                                                        <div
                                                            v-if="
                                                                showErrors &&
                                                                itemRow._isNew &&
                                                                !itemRow.unitId
                                                            "
                                                            class="text-caption text-error"
                                                        >
                                                            {{
                                                                $t(
                                                                    "validation.mixed.required",
                                                                    {
                                                                        field:
                                                                            $t(
                                                                                "business_product.recipe.unit",
                                                                            ) ||
                                                                            "Đơn vị tính",
                                                                    },
                                                                )
                                                            }}
                                                        </div>
                                                    </td>

                                                    <!-- Hao hụt (%) -->
                                                    <td
                                                        class="py-2"
                                                        style="
                                                            vertical-align: top;
                                                        "
                                                    >
                                                        <v-text-field
                                                            v-model.number="
                                                                itemRow.wasteRate
                                                            "
                                                            type="number"
                                                            variant="outlined"
                                                            density="compact"
                                                            hide-details
                                                            :error="
                                                                showErrors &&
                                                                (itemRow.wasteRate ===
                                                                    undefined ||
                                                                    itemRow.wasteRate ===
                                                                        null ||
                                                                    itemRow.wasteRate <
                                                                        0)
                                                            "
                                                            @update:model-value="
                                                                onChangeVariants(
                                                                    fieldVariants.value,
                                                                )
                                                            "
                                                        />
                                                        <div
                                                            v-if="
                                                                showErrors &&
                                                                (itemRow.wasteRate ===
                                                                    undefined ||
                                                                    itemRow.wasteRate ===
                                                                        null ||
                                                                    itemRow.wasteRate <
                                                                        0)
                                                            "
                                                            class="text-caption text-error"
                                                        >
                                                            {{
                                                                $t(
                                                                    "business_product.recipe.waste_rate_non_negative",
                                                                ) ||
                                                                "Hao hụt >= 0"
                                                            }}
                                                        </div>
                                                    </td>

                                                    <!-- Nhập ghi chú -->
                                                    <td
                                                        class="py-2"
                                                        style="
                                                            vertical-align: top;
                                                        "
                                                    >
                                                        <v-text-field
                                                            v-model="
                                                                itemRow.notes
                                                            "
                                                            variant="outlined"
                                                            density="compact"
                                                            hide-details
                                                            :placeholder="
                                                                $t(
                                                                    'business_product.recipe.notes_placeholder',
                                                                ) ||
                                                                'Ghi chú...'
                                                            "
                                                            @update:model-value="
                                                                onChangeVariants(
                                                                    fieldVariants.value,
                                                                )
                                                            "
                                                        />
                                                    </td>

                                                    <!-- Nút xóa thành phẩm -->
                                                    <td
                                                        class="text-center py-2"
                                                        style="
                                                            vertical-align: top;
                                                        "
                                                    >
                                                        <v-btn
                                                            icon="mdi-delete"
                                                            color="error"
                                                            variant="text"
                                                            size="small"
                                                            @click="
                                                                removeRecipeItem(
                                                                    itemIdx,
                                                                    variant,
                                                                    fieldVariants.value,
                                                                    onChangeVariants,
                                                                )
                                                            "
                                                        />
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </v-table>
                                    </div>

                                    <!-- Empty Recipe State -->
                                    <div
                                        v-else
                                        class="d-flex flex-column align-center justify-center border border-dashed rounded-lg py-6 bg-white text-grey"
                                    >
                                        <v-icon
                                            icon="mdi-food-variant"
                                            size="32"
                                            class="mb-1"
                                        />
                                        <span class="text-body-2">
                                            {{
                                                $t(
                                                    "business_product.recipe.empty_desc",
                                                ) ||
                                                "Chưa có thành phẩm nào trong công thức của phiên bản này."
                                            }}
                                        </span>
                                        <div
                                            v-if="showErrors"
                                            class="text-caption text-error font-weight-bold mt-1"
                                        >
                                            {{
                                                $t(
                                                    "business_product.recipe.items_required",
                                                ) ||
                                                "Phải có ít nhất 1 thành phẩm công thức!"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </v-expansion-panel-text>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </div>

                <!-- Empty Variants State -->
                <div
                    v-else
                    class="d-flex flex-column align-center justify-center border border-dashed rounded-lg py-12 text-grey bg-white"
                >
                    <v-icon
                        icon="mdi-package-variant-closed"
                        size="48"
                        class="mb-2"
                    />
                    <span class="text-body-1 font-weight-medium">
                        {{
                            $t("business_product.empty_variants") ||
                            "Sản phẩm chưa thiết lập phiên bản nào"
                        }}
                    </span>
                    <span class="text-caption mt-1">
                        {{
                            $t("business_product.empty_variants_desc") ||
                            "Vui lòng bấm 'Thêm mới phiên bản' để bắt đầu thiết lập."
                        }}
                    </span>
                    <div
                        v-if="showErrors"
                        class="text-caption text-error font-weight-bold mt-2"
                    >
                        {{
                            $t("business_product.variants_required") ||
                            "Phải có ít nhất 1 phiên bản!"
                        }}
                    </div>
                </div>
            </div>
        </VeeField>
    </div>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { constant } from "@/utils/constants/constant";
import { mapActions, mapGetters } from "vuex";

export default {
    name: "FormVariantsInfo",
    components: {
        VeeField,
    },
    props: {
        item: {
            type: Object,
            default: null,
        },
        formValues: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            openedPanel: [0], // Mở panel đầu tiên mặc định
            merchandises: [],
            merchandiseUnitsCache: {},
            merchandiseOutputUnitCache: {},
            loadingMerchandises: false,
            isMerchandisesLoaded: false,
            showErrors: false,
        };
    },
    computed: {
        ...mapGetters("unit", {
            unitOptions: "options",
        }),
        ...mapGetters("generalSettings", ["currency"]),
        statusOptions() {
            return constant.STATUS.map((item) => ({
                value: item.value,
                text: this.$t(item.key),
            }));
        },
    },
    mounted() {
        this.fetchUnitOptions();
        this.loadMerchandises();
        this.fetchGeneralSettings();
    },
    methods: {
        ...mapActions("unit", {
            fetchUnitOptions: "fetchOptions",
        }),
        ...mapActions("merchandise", {
            fetchAllMerchandises: "fetchAllMerchandiseOptions",
            fetchMerchandiseDetail: "fetchItemDetail",
        }),
        ...mapActions("generalSettings", {
            fetchGeneralSettings: "fetchSettings",
        }),

        async loadMerchandises() {
            this.loadingMerchandises = true;
            const response = await this.fetchAllMerchandises();

            this.merchandises = response.map((item) => ({
                value: item.id,
                label: `[${item.code}] ${item.name}`,
                code: item.code,
                name: item.name,
            }));

            const cache = {};
            const outputUnitCache = {};
            response.forEach((detail) => {
                cache[detail.id] = this.extractMerchandiseUnits(detail);
                if (detail.outputUnitId) {
                    outputUnitCache[detail.id] = detail.outputUnitId;
                }
            });
            this.merchandiseUnitsCache = cache;
            this.merchandiseOutputUnitCache = outputUnitCache;
            this.loadingMerchandises = false;
            this.isMerchandisesLoaded = true;
        },

        extractMerchandiseUnits(detail) {
            if (!detail) return [];

            const units = [];
            const baseUnitObj = this.unitOptions.find(
                (u) => u.value === detail.baseUnitId,
            );
            if (baseUnitObj) {
                units.push(baseUnitObj);
            }

            if (!detail.isSingleUnit && detail.conversions) {
                detail.conversions.forEach((c) => {
                    if (c.fromUnitId) {
                        const u = this.unitOptions.find(
                            (u) => u.value === c.fromUnitId,
                        );
                        if (
                            u &&
                            !units.some((item) => item.value === c.fromUnitId)
                        ) {
                            units.push(u);
                        }
                    }
                    if (c.toUnitId) {
                        const u = this.unitOptions.find(
                            (u) => u.value === c.toUnitId,
                        );
                        if (
                            u &&
                            !units.some((item) => item.value === c.toUnitId)
                        ) {
                            units.push(u);
                        }
                    }
                });
            }
            return units;
        },

        getMerchandiseUnitOptions(finishedProductId) {
            if (!finishedProductId) return [];
            return this.merchandiseUnitsCache[finishedProductId] || [];
        },

        async onMerchandiseSelected(
            merchandiseId,
            item,
            vIdx,
            itemIdx,
            variants,
            onChangeVariants,
        ) {
            if (!merchandiseId) {
                item.unitId = null;
                onChangeVariants(variants);
                return;
            }

            await this.loadMerchandiseUnitsDetail(merchandiseId);

            const cachedUnits = this.merchandiseUnitsCache[merchandiseId] || [];

            // Ưu tiên outputUnitId (đơn vị thành phẩm đầu ra đã thiết lập)
            const outputUnitId = this.merchandiseOutputUnitCache[merchandiseId];
            if (
                outputUnitId &&
                cachedUnits.some((u) => u.value === outputUnitId)
            ) {
                item.unitId = outputUnitId;
            } else {
                const defaultUnit = cachedUnits.find((u) => u.value);
                item.unitId = defaultUnit ? defaultUnit.value : null;
            }

            onChangeVariants(variants);
        },

        async loadMerchandiseUnitsDetail(merchandiseId) {
            if (this.merchandiseUnitsCache[merchandiseId]) return;

            const detail = await this.fetchMerchandiseDetail({
                id: merchandiseId,
            });
            if (detail) {
                this.merchandiseUnitsCache = {
                    ...this.merchandiseUnitsCache,
                    [merchandiseId]: this.extractMerchandiseUnits(detail),
                };
            }
        },

        addVariant(variants, onChangeVariants) {
            const list = variants ? [...variants] : [];
            list.push({
                code: "",
                name: "",
                unitId: null,
                barcode: "",
                isDefault: list.length === 0,
                status: 1,
                recipe: {
                    items: [],
                },
                priceConfig: {
                    price: null,
                    currency: this.currency || "VND",
                    effectiveFrom: null,
                    effectiveTo: null,
                    isCurrent: true,
                    note: "",
                },
                sortOrder: list.length,
                _isNew: true,
            });
            onChangeVariants(list);
            // Mở panel của variant vừa thêm
            this.openedPanel = [list.length - 1];
        },

        removeVariant(index, variants, onChangeVariants) {
            const list = [...variants];
            list.splice(index, 1);
            list.forEach((v, idx) => {
                v.sortOrder = idx;
            });
            const hasDefault = list.some((v) => v.isDefault);
            if (!hasDefault && list.length > 0) {
                list[0].isDefault = true;
            }
            onChangeVariants(list);
        },

        handleDefaultChange(changedIndex, variants, onChangeVariants) {
            variants.forEach((v, idx) => {
                v.isDefault = idx === changedIndex;
            });
            onChangeVariants(variants);
        },

        addRecipeItem(variant, variants, onChangeVariants) {
            if (!variant.recipe) {
                variant.recipe = { items: [] };
            }
            const items = variant.recipe.items ? [...variant.recipe.items] : [];
            items.push({
                finishedProductId: null,
                quantity: 1,
                unitId: null,
                wasteRate: 0,
                notes: "",
                sortOrder: items.length,
                _isNew: true,
            });
            variant.recipe.items = items;
            onChangeVariants(variants);
        },

        removeRecipeItem(itemIndex, variant, variants, onChangeVariants) {
            const items = [...variant.recipe.items];
            items.splice(itemIndex, 1);
            items.forEach((item, idx) => {
                item.sortOrder = idx;
            });
            variant.recipe.items = items;
            onChangeVariants(variants);
        },

        // Validate tab unit trước khi bấm Next
        validate() {
            this.showErrors = true;
            const variants = this.formValues.variants || [];

            // Điều kiện 1: Phải có ít nhất 1 phiên bản
            if (variants.length === 0) {
                return false;
            }

            let isValid = true;
            const errorPanels = [];

            for (let vIdx = 0; vIdx < variants.length; vIdx++) {
                const variant = variants[vIdx];
                let variantHasError = false;

                // Validate variant mới: code, name, unitId
                if (variant._isNew) {
                    if (!variant.code || !variant.name || !variant.unitId) {
                        variantHasError = true;
                    }
                }

                // Điều kiện 2: Mỗi phiên bản phải có ít nhất 1 thành phẩm công thức
                if (
                    !variant.recipe?.items ||
                    variant.recipe.items.length === 0
                ) {
                    variantHasError = true;
                } else {
                    // Validate recipe items
                    for (const item of variant.recipe.items) {
                        if (
                            item.wasteRate === undefined ||
                            item.wasteRate === null ||
                            item.wasteRate < 0
                        ) {
                            variantHasError = true;
                        }
                        if (item._isNew) {
                            if (
                                !item.finishedProductId ||
                                !item.quantity ||
                                +item.quantity <= 0 ||
                                !item.unitId
                            ) {
                                variantHasError = true;
                            }
                        }
                    }
                }

                if (variantHasError) {
                    isValid = false;
                    errorPanels.push(vIdx);
                }
            }

            // Mở các panel có lỗi
            if (errorPanels.length > 0) {
                this.openedPanel = [
                    ...new Set([...this.openedPanel, ...errorPanels]),
                ];
            }

            return isValid;
        },

        // Reset trạng thái lỗi
        resetErrors() {
            this.showErrors = false;
        },
    },
};
</script>

<style scoped>
.w-100 {
    width: 100% !important;
}
.cursor-pointer {
    cursor: pointer !important;
}
.unit-select-error :deep(.v-field__outline) {
    --v-field-border-opacity: 1;
    color: rgb(var(--v-theme-error));
}
.unit-select-error :deep(.v-field__outline__start),
.unit-select-error :deep(.v-field__outline__end),
.unit-select-error :deep(.v-field__outline__notch::before),
.unit-select-error :deep(.v-field__outline__notch::after) {
    border-color: rgb(var(--v-theme-error));
}
</style>
