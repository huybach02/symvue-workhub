<template>
    <VeeField
        v-slot="{ field: fieldItems, handleChange: onChangeItems }"
        name="items"
    >
        <div>
            <div class="d-flex justify-space-between align-center mb-4">
                <div class="text-subtitle-1 font-weight-bold">
                    {{ $t("request.production.items_title") }}
                </div>
                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="addItem(fieldItems.value, onChangeItems)"
                >
                    {{ $t("request.production.add_item") }}
                </v-btn>
            </div>

            <div
                v-if="!fieldItems.value || fieldItems.value.length === 0"
                class="text-center text-medium-emphasis py-8 border rounded-lg"
            >
                {{ $t("request.production.empty_items") }}
            </div>

            <v-expansion-panels
                v-else
                v-model="openedPanels"
                multiple
                variant="accordion"
                class="border rounded"
            >
                <v-expansion-panel
                    v-for="(item, itemIndex) in fieldItems.value"
                    :key="itemIndex"
                >
                    <v-expansion-panel-title>
                        <div
                            class="d-flex align-center justify-space-between w-100 pr-2"
                        >
                            <span class="font-weight-bold text-subtitle-1">
                                {{
                                    getItemTitle(
                                        item.finishedProductId,
                                        itemIndex,
                                    )
                                }}
                            </span>
                            <v-btn
                                icon="mdi-delete"
                                color="error"
                                variant="text"
                                size="small"
                                @click.stop="
                                    removeItem(
                                        fieldItems.value,
                                        onChangeItems,
                                        itemIndex,
                                    )
                                "
                            />
                        </div>
                    </v-expansion-panel-title>

                    <v-expansion-panel-text class="pt-4 bg-white">
                        <v-row class="mb-2">
                            <v-col cols="12" md="6">
                                <div class="mb-2">
                                    {{ $t("merchandise.finished_product") }}
                                    <span class="text-error">*</span>
                                </div>
                                <v-autocomplete
                                    v-model="item.finishedProductId"
                                    :items="finishedProductOptions"
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    :loading="loadingFinishedProducts"
                                    :placeholder="
                                        $t(
                                            'request.production.select_finished_product',
                                        )
                                    "
                                    @update:model-value="
                                        (value) =>
                                            onFinishedProductSelected(
                                                value,
                                                item,
                                                fieldItems.value,
                                                onChangeItems,
                                            )
                                    "
                                />
                            </v-col>
                        </v-row>

                        <template v-if="item.finishedProductId">
                            <v-row class="mb-2">
                                <v-col cols="12" md="4">
                                    <div class="mb-2">
                                        {{ $t("field.quantity") }}
                                        <span class="text-error">*</span>
                                    </div>
                                    <v-text-field
                                        :model-value="item.quantity"
                                        type="number"
                                        min="0"
                                        step="any"
                                        variant="outlined"
                                        density="compact"
                                        hide-details
                                        @update:model-value="
                                            (value) =>
                                                onQuantityChanged(
                                                    value,
                                                    item,
                                                    fieldItems.value,
                                                    onChangeItems,
                                                )
                                        "
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <div class="mb-2">
                                        {{
                                            $t("merchandise.recipe.output_unit")
                                        }}
                                        <span class="text-error">*</span>
                                    </div>
                                    <v-text-field
                                        :model-value="item.outputUnitName"
                                        variant="outlined"
                                        density="compact"
                                        readonly
                                        hide-details
                                        :placeholder="
                                            $t(
                                                'request.production.output_unit_auto',
                                            )
                                        "
                                    />
                                </v-col>
                                <v-col cols="12" md="4">
                                    <div class="mb-2">
                                        {{
                                            $t("merchandise.recipe.waste_rate")
                                        }}
                                    </div>
                                    <v-text-field
                                        :model-value="item.expectedWastePercent"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="any"
                                        variant="outlined"
                                        density="compact"
                                        suffix="%"
                                        hide-details
                                        @update:model-value="
                                            (value) =>
                                                onWasteRateChanged(
                                                    value,
                                                    item,
                                                    fieldItems.value,
                                                    onChangeItems,
                                                )
                                        "
                                    />
                                </v-col>
                            </v-row>

                            <v-alert
                                v-if="item.openShortages?.length"
                                type="warning"
                                variant="tonal"
                                class="mb-4"
                            >
                                <div class="font-weight-bold mb-2">
                                    {{ $t("request.production.open_shortages") }}
                                </div>
                                <div
                                    v-for="shortage in item.openShortages"
                                    :key="shortage.productionOrderItemId"
                                    class="border rounded pa-3 mb-2 bg-white"
                                >
                                    <v-checkbox
                                        :model-value="isShortageSelected(item, shortage)"
                                        density="compact"
                                        hide-details
                                        :label="shortage.productionOrderCode"
                                        @update:model-value="toggleShortage(item, shortage, $event, fieldItems.value, onChangeItems)"
                                    />
                                    <div class="text-body-2 ml-8">
                                        {{ $t("request.production.received_progress") }}: {{ quantityWithUnit({ baseUnitName: shortage.baseUnitName || item.baseUnitName || item.outputUnitName }, shortage.acceptedBaseQuantity) }} / {{ quantityWithUnit({ baseUnitName: shortage.baseUnitName || item.baseUnitName || item.outputUnitName }, shortage.plannedBaseQuantity) }}
                                        · {{ $t("request.production.minimum_target") }}: {{ quantityWithUnit({ baseUnitName: shortage.baseUnitName || item.baseUnitName || item.outputUnitName }, shortage.minimumAcceptableBaseQuantity) }}
                                    </div>
                                    <v-radio-group
                                        v-if="isShortageSelected(item, shortage)"
                                        :model-value="selectedShortageMode(item, shortage)"
                                        inline
                                        hide-details
                                        class="ml-8 mt-1"
                                        @update:model-value="setShortageMode(item, shortage, $event, fieldItems.value, onChangeItems)"
                                    >
                                        <v-radio :label="$t('request.production.supplement_minimum')" value="MINIMUM" />
                                        <v-radio :label="$t('request.production.supplement_full')" value="FULL" />
                                    </v-radio-group>
                                </div>
                                <div class="mt-3 text-body-2">
                                    {{ $t("request.production.new_requirement") }} {{ quantityWithUnit({ baseUnitName: item.outputUnitName }, item.quantity) }} · {{ $t("request.production.supplement_quantity") }} {{ quantityWithUnit({ baseUnitName: supplementUnitName(item) }, selectedSupplementBase(item)) }} · <strong>{{ $t("request.production.production_target") }} {{ quantityWithUnit({ baseUnitName: item.outputUnitName }, productionTarget(item)) }}</strong>
                                </div>
                            </v-alert>

                            <div
                                class="d-flex align-center ga-2 text-subtitle-2 font-weight-bold mb-3 mt-2"
                            >
                                <v-icon
                                    icon="mdi-flask-outline"
                                    size="18"
                                    color="primary"
                                />
                                {{ $t("request.production.materials_title") }}
                            </div>

                            <div
                                v-if="
                                    !item.materials ||
                                    item.materials.length === 0
                                "
                                class="text-center text-medium-emphasis py-6 border rounded-lg"
                            >
                                {{ $t("request.production.empty_materials") }}
                            </div>

                            <div
                                v-else
                                class="v-table v-table--density-compact border rounded-lg overflow-x-auto"
                            >
                                <div class="v-table__wrapper">
                                    <table
                                        style="
                                            table-layout: fixed;
                                            width: 100%;
                                            border-collapse: collapse;
                                        "
                                    >
                                        <colgroup>
                                            <col style="width: 56px" />
                                            <col style="width: 260px" />
                                            <col style="width: 100px" />
                                            <col style="width: 120px" />
                                            <col style="width: 120px" />
                                            <col style="width: 200px" />
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th
                                                    class="text-left font-weight-bold px-3 py-2"
                                                >
                                                    {{ $t("field.stt") }}
                                                </th>
                                                <th
                                                    class="text-left font-weight-bold px-3 py-2"
                                                >
                                                    {{
                                                        $t(
                                                            "merchandise.ingredient",
                                                        )
                                                    }}
                                                </th>
                                                <th
                                                    class="text-right font-weight-bold px-3 py-2"
                                                >
                                                    {{ $t("field.quantity") }}
                                                </th>
                                                <th
                                                    class="text-left font-weight-bold px-3 py-2"
                                                >
                                                    {{ $t("field.unit") }}
                                                </th>
                                                <th
                                                    class="text-left font-weight-bold px-3 py-2"
                                                >
                                                    {{
                                                        $t(
                                                            "merchandise.recipe.waste_rate",
                                                        )
                                                    }}
                                                </th>
                                                <th
                                                    class="text-left font-weight-bold px-3 py-2"
                                                >
                                                    {{ $t("field.ghi_chu") }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(
                                                    material, materialIndex
                                                ) in item.materials"
                                                :key="materialIndex"
                                                class="border-bottom"
                                            >
                                                <td class="px-3 py-2">
                                                    {{ materialIndex + 1 }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 font-weight-medium"
                                                >
                                                    {{
                                                        material.ingredientName
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-right"
                                                >
                                                    {{
                                                        formatQuantity(
                                                            material.quantity,
                                                        )
                                                    }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{
                                                        material.unitName ||
                                                        "--"
                                                    }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    {{
                                                        formatQuantity(
                                                            material.wasteRate,
                                                        )
                                                    }}
                                                    %
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-body-2 text-medium-emphasis"
                                                >
                                                    {{ material.note || "--" }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </v-expansion-panel-text>
                </v-expansion-panel>
            </v-expansion-panels>
        </div>
    </VeeField>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { mapActions, mapGetters } from "vuex";

export default {
    name: "RequestProductionFormFields",
    components: {
        VeeField,
    },
    data() {
        return {
            openedPanels: [],
            finishedProductOptions: [],
            loadingFinishedProducts: false,
        };
    },
    computed: {
        ...mapGetters("merchandise", ["itemById"]),
    },
    mounted() {
        this.loadFinishedProducts();
    },
    methods: {
        ...mapActions("merchandise", [
            "fetchProductionMerchandiseOptions",
            "fetchItemDetail",
        ]),
        ...mapActions("productionOrder", ["fetchOpenShortages"]),

        async loadFinishedProducts() {
            this.loadingFinishedProducts = true;
            const items = await this.fetchProductionMerchandiseOptions();
            const list = Array.isArray(items) ? items : [];

            this.finishedProductOptions = list.map((item) => ({
                value: item.id,
                label: `[${item.code}] ${item.name}`,
            }));
            this.loadingFinishedProducts = false;
        },

        createEmptyItem() {
            return {
                lineId: crypto.randomUUID(),
                finishedProductId: null,
                finishedProductName: "",
                quantity: null,
                outputUnitId: null,
                outputUnitName: "",
                expectedWastePercent: null,
                materials: [],
                supplementSelections: [],
                openShortages: [],
            };
        },

        addItem(items, onChange) {
            const list = Array.isArray(items) ? [...items] : [];
            list.push(this.createEmptyItem());
            onChange(list);
            this.openedPanels = [...this.openedPanels, list.length - 1];
        },

        removeItem(items, onChange, index) {
            const list = [...(items || [])];
            list.splice(index, 1);
            onChange(list);
            this.openedPanels = this.openedPanels
                .filter((panelIndex) => panelIndex !== index)
                .map((panelIndex) =>
                    panelIndex > index ? panelIndex - 1 : panelIndex,
                );
        },

        getItemTitle(finishedProductId, index) {
            if (!finishedProductId) {
                return this.$t("request.production.item_untitled", {
                    index: index + 1,
                });
            }

            const option = this.finishedProductOptions.find(
                (item) => item.value === finishedProductId,
            );

            return (
                option?.label ||
                this.$t("request.production.item_untitled", {
                    index: index + 1,
                })
            );
        },

        async onFinishedProductSelected(
            finishedProductId,
            item,
            items,
            onChange,
        ) {
            if (!finishedProductId) {
                item.finishedProductId = null;
                item.finishedProductName = "";
                item.outputUnitId = null;
                item.outputUnitName = "";
                item.materials = [];
                item.openShortages = [];
                item.supplementSelections = [];
                onChange(items);
                return;
            }

            item.finishedProductId = finishedProductId;
            const option = this.finishedProductOptions.find(
                (opt) => opt.value === finishedProductId,
            );
            item.finishedProductName = option?.label ?? "";

            await this.ensureMerchandiseDetail(finishedProductId);
            const detail = this.itemById(finishedProductId);
            const recipe = detail?.recipe;

            item.outputUnitId = recipe?.outputUnitId ?? null;
            item.outputUnitName = recipe?.outputUnit?.name ?? "";
            item.baseUnitName = detail?.baseUnit?.name ?? item.outputUnitName;
            item.outputFactorToBase = Number(recipe?.outputFactorToBaseSnapshot) || 1;
            item.supplementSelections = [];
            item.openShortages = await this.fetchOpenShortages(finishedProductId);

            await this.rebuildMaterials(item, items, onChange);
        },

        onQuantityChanged(value, item, items, onChange) {
            item.quantity =
                value === "" || value === null || value === undefined
                    ? null
                    : Number(value);
            this.rebuildMaterials(item, items, onChange);
        },

        onWasteRateChanged(value, item, items, onChange) {
            item.expectedWastePercent =
                value === "" || value === null || value === undefined
                    ? null
                    : Number(value);
            onChange(items);
        },

        isShortageSelected(item, shortage) {
            return (item.supplementSelections || []).some(
                (selection) => selection.productionOrderItemId === shortage.productionOrderItemId,
            );
        },
        selectedShortageMode(item, shortage) {
            return (item.supplementSelections || []).find(
                (selection) => selection.productionOrderItemId === shortage.productionOrderItemId,
            )?.mode || "MINIMUM";
        },
        toggleShortage(item, shortage, selected, items, onChange) {
            const selections = [...(item.supplementSelections || [])];
            const index = selections.findIndex(
                (selection) => selection.productionOrderItemId === shortage.productionOrderItemId,
            );
            if (selected && index < 0) {
                selections.push({ productionOrderItemId: shortage.productionOrderItemId, mode: "MINIMUM" });
            } else if (!selected && index >= 0) {
                selections.splice(index, 1);
            }
            item.supplementSelections = selections;
            return this.rebuildMaterials(item, items, onChange);
        },
        setShortageMode(item, shortage, mode, items, onChange) {
            item.supplementSelections = (item.supplementSelections || []).map(
                (selection) => selection.productionOrderItemId === shortage.productionOrderItemId
                    ? { ...selection, mode }
                    : selection,
            );
            return this.rebuildMaterials(item, items, onChange);
        },
        selectedSupplementBase(item) {
            return (item.supplementSelections || []).reduce((total, selection) => {
                const shortage = (item.openShortages || []).find(
                    (candidate) => candidate.productionOrderItemId === selection.productionOrderItemId,
                );
                return total + Number(
                    selection.mode === "FULL"
                        ? shortage?.fullRemainingBaseQuantity || 0
                        : shortage?.minimumRemainingBaseQuantity || 0,
                );
            }, 0);
        },
        productionTarget(item) {
            return Number(item.quantity || 0) + this.selectedSupplementBase(item) / (Number(item.outputFactorToBase) || 1);
        },
        supplementUnitName(item) {
            return item.openShortages?.[0]?.baseUnitName || item.baseUnitName || item.outputUnitName || "";
        },

        async ensureMerchandiseDetail(merchandiseId) {
            if (!merchandiseId) {
                return;
            }
            if (this.itemById(merchandiseId)) {
                return;
            }
            await this.fetchItemDetail({ id: merchandiseId });
        },

        async rebuildMaterials(item, items, onChange) {
            if (!item.finishedProductId) {
                item.materials = [];
                onChange(items);
                return;
            }

            await this.ensureMerchandiseDetail(item.finishedProductId);
            const detail = this.itemById(item.finishedProductId);
            const recipe = detail?.recipe;

            if (
                !recipe ||
                !Array.isArray(recipe.items) ||
                recipe.items.length === 0
            ) {
                item.materials = [];
                onChange(items);
                return;
            }

            const scale = this.getScale(recipe, this.productionTarget(item));
            item.materials = recipe.items.map((recipeItem) => ({
                ingredientId: recipeItem.ingredientId ?? null,
                ingredientName: this.getIngredientLabel(recipeItem),
                quantity: this.computeMaterialQuantity(recipeItem, scale),
                unitId: recipeItem.unitId ?? null,
                unitName: recipeItem.unit?.name ?? "",
                wasteRate: Number(recipeItem.wasteRate) || 0,
                note: recipeItem.notes ?? "",
            }));

            onChange(items);
        },

        getScale(recipe, quantity) {
            const outputQuantity = Number(recipe.outputQuantity) || 1;
            const qty = Number(quantity);
            // Chưa nhập số lượng thành phẩm -> hiển thị định lượng gốc trong công thức
            if (!qty || qty <= 0) {
                return 1;
            }
            return qty / outputQuantity;
        },

        computeMaterialQuantity(recipeItem, scale) {
            const base = Number(recipeItem.quantity) || 0;
            const wasteRate = Number(recipeItem.wasteRate) || 0;
            const quantity = base * scale * (1 + wasteRate / 100);
            return Math.round(quantity * 10000) / 10000;
        },

        getIngredientLabel(recipeItem) {
            const ingredient = recipeItem.ingredient;
            if (ingredient?.code || ingredient?.name) {
                return `[${ingredient.code}] ${ingredient.name}`;
            }
            return String(recipeItem.ingredientId ?? "");
        },

        formatQuantity(value) {
            if (value === null || value === undefined || value === "") {
                return "--";
            }
            return Number(value).toLocaleString("en-US", {
                maximumFractionDigits: 4,
            });
        },
        quantityWithUnit(item, value) {
            return [this.formatQuantity(value), item?.baseUnitName]
                .filter(Boolean)
                .join(" ");
        },
    },
};
</script>
