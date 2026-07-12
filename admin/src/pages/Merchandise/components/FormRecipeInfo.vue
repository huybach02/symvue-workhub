<template>
    <div>
        <VeeField
            v-slot="{ field: fieldRecipe, handleChange: onChangeRecipe }"
            name="recipe"
        >
            <div class="mb-4">
                <v-card variant="flat" class="pa-4 border">
                    <h3 class="text-subtitle-1 font-weight-bold mb-4">
                        {{
                            $t("merchandise.recipe.title") ||
                            "Công thức nguyên liệu"
                        }}
                    </h3>

                    <!-- Định lượng thành phẩm đầu ra -->
                    <v-row class="align-center mb-4">
                        <v-col cols="12" md="6">
                            <div class="mb-2 font-weight-medium">
                                {{
                                    $t("merchandise.recipe.output") ||
                                    "Thành phẩm đầu ra"
                                }}
                            </div>
                            <div class="d-flex align-center ga-2">
                                <v-text-field
                                    :model-value="
                                        fieldRecipe.value.outputQuantity
                                    "
                                    type="number"
                                    variant="outlined"
                                    density="compact"
                                    readonly
                                    disabled
                                    hide-details
                                    style="max-width: 100px"
                                    class="bg-grey-lighten-4"
                                />
                                <v-select
                                    v-model="fieldRecipe.value.outputUnitId"
                                    :items="outputUnitOptions"
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    density="compact"
                                    hide-details
                                    :placeholder="
                                        $t('merchandise.recipe.output_unit') ||
                                        'Đơn vị thành phẩm'
                                    "
                                    @update:model-value="
                                        onChangeRecipe(fieldRecipe.value)
                                    "
                                />
                            </div>
                            <div
                                v-if="
                                    showRecipeErrors &&
                                    !fieldRecipe.value.outputUnitId
                                "
                                class="text-caption text-error mt-1"
                            >
                                {{
                                    $t("validation.mixed.required", {
                                        field: $t(
                                            "merchandise.recipe.output_unit",
                                        ),
                                    })
                                }}
                            </div>
                        </v-col>
                    </v-row>

                    <!-- Danh sách nguyên liệu -->
                    <div class="d-flex justify-space-between align-center mb-3">
                        <h4 class="text-subtitle-2 font-weight-bold">
                            {{ $t("merchandise.recipe.title") }}
                        </h4>
                        <v-btn
                            color="primary"
                            prepend-icon="mdi-plus"
                            size="small"
                            @click="
                                addRecipeItem(fieldRecipe.value, onChangeRecipe)
                            "
                        >
                            {{
                                $t("merchandise.recipe.add_ingredient") ||
                                "Thêm nguyên liệu"
                            }}
                        </v-btn>
                    </div>

                    <!-- Bảng các nguyên liệu trong công thức -->
                    <div
                        v-if="
                            fieldRecipe.value.items &&
                            fieldRecipe.value.items.length > 0
                        "
                    >
                        <div
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
                                        <col style="width: 300px" />
                                        <col style="width: 120px" />
                                        <col style="width: 150px" />
                                        <col style="width: 120px" />
                                        <col style="width: 200px" />
                                        <col style="width: 60px" />
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th
                                                class="text-left font-weight-bold px-4 py-2"
                                            >
                                                {{
                                                    $t(
                                                        "merchandise.recipe.ingredient",
                                                    )
                                                }}
                                            </th>
                                            <th
                                                class="text-left font-weight-bold px-4 py-2"
                                            >
                                                {{
                                                    $t(
                                                        "merchandise.recipe.quantity",
                                                    )
                                                }}
                                            </th>
                                            <th
                                                class="text-left font-weight-bold px-4 py-2"
                                            >
                                                {{
                                                    $t(
                                                        "merchandise.recipe.unit",
                                                    )
                                                }}
                                            </th>
                                            <th
                                                class="text-left font-weight-bold px-4 py-2"
                                            >
                                                {{
                                                    $t(
                                                        "merchandise.recipe.waste_rate",
                                                    )
                                                }}
                                            </th>
                                            <th
                                                class="text-left font-weight-bold px-4 py-2"
                                            >
                                                {{
                                                    $t(
                                                        "merchandise.recipe.notes",
                                                    )
                                                }}
                                            </th>
                                            <th
                                                class="text-center px-4 py-2"
                                            ></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(
                                                itemRow, index
                                            ) in fieldRecipe.value.items"
                                            :key="index"
                                            class="border-bottom"
                                        >
                                            <!-- Chọn Nguyên liệu -->
                                            <td class="px-2 py-2">
                                                <v-autocomplete
                                                    v-model="
                                                        itemRow.ingredientId
                                                    "
                                                    :items="ingredients"
                                                    item-title="label"
                                                    item-value="value"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                    clearable
                                                    :loading="
                                                        loadingIngredients &&
                                                        !isIngredientsLoaded
                                                    "
                                                    :readonly="
                                                        loadingIngredients &&
                                                        !isIngredientsLoaded
                                                    "
                                                    :placeholder="
                                                        $t(
                                                            'merchandise.recipe.select_ingredient',
                                                        ) || 'Chọn nguyên liệu'
                                                    "
                                                    @update:model-value="
                                                        (val) =>
                                                            onIngredientSelected(
                                                                val,
                                                                itemRow,
                                                                index,
                                                                fieldRecipe.value,
                                                                onChangeRecipe,
                                                            )
                                                    "
                                                />
                                                <div
                                                    v-if="
                                                        showRecipeErrors &&
                                                        !itemRow.ingredientId
                                                    "
                                                    class="text-caption text-error"
                                                >
                                                    {{
                                                        $t(
                                                            "merchandise.recipe.ingredient_required",
                                                        ) ||
                                                        "Bắt buộc chọn nguyên liệu"
                                                    }}
                                                </div>
                                            </td>

                                            <!-- Nhập Định lượng -->
                                            <td class="px-2 py-2">
                                                <v-text-field
                                                    v-model.number="
                                                        itemRow.quantity
                                                    "
                                                    type="number"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                    @update:model-value="
                                                        onChangeRecipe(
                                                            fieldRecipe.value,
                                                        )
                                                    "
                                                />
                                                <div
                                                    v-if="
                                                        showRecipeErrors &&
                                                        (!itemRow.quantity ||
                                                            +itemRow.quantity <=
                                                                0)
                                                    "
                                                    class="text-caption text-error"
                                                >
                                                    {{
                                                        $t(
                                                            "merchandise.recipe.quantity_positive",
                                                        ) || "Định lượng > 0"
                                                    }}
                                                </div>
                                            </td>

                                            <!-- Chọn Đơn vị của nguyên liệu -->
                                            <td class="px-2 py-2">
                                                <v-select
                                                    v-model="itemRow.unitId"
                                                    :items="
                                                        getIngredientUnitOptions(
                                                            itemRow.ingredientId,
                                                        )
                                                    "
                                                    item-title="label"
                                                    item-value="value"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                    :disabled="
                                                        !itemRow.ingredientId
                                                    "
                                                    :placeholder="
                                                        $t(
                                                            'merchandise.recipe.select_unit',
                                                        ) || 'Chọn đơn vị'
                                                    "
                                                    @update:model-value="
                                                        onChangeRecipe(
                                                            fieldRecipe.value,
                                                        )
                                                    "
                                                />
                                                <div
                                                    v-if="
                                                        showRecipeErrors &&
                                                        !itemRow.unitId
                                                    "
                                                    class="text-caption text-error"
                                                >
                                                    {{
                                                        $t(
                                                            "merchandise.recipe.unit_required",
                                                        ) ||
                                                        "Bắt buộc chọn đơn vị"
                                                    }}
                                                </div>
                                            </td>

                                            <!-- Nhập Hao hụt % -->
                                            <td class="px-2 py-2">
                                                <v-text-field
                                                    v-model.number="
                                                        itemRow.wasteRate
                                                    "
                                                    type="number"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                    @update:model-value="
                                                        onChangeRecipe(
                                                            fieldRecipe.value,
                                                        )
                                                    "
                                                />
                                                <div
                                                    v-if="
                                                        showRecipeErrors &&
                                                        (itemRow.wasteRate ===
                                                            undefined ||
                                                            itemRow.wasteRate <
                                                                0)
                                                    "
                                                    class="text-caption text-error"
                                                >
                                                    {{
                                                        $t(
                                                            "merchandise.recipe.waste_rate_non_negative",
                                                        ) || "Hao hụt >= 0"
                                                    }}
                                                </div>
                                            </td>

                                            <!-- Nhập Ghi chú -->
                                            <td class="px-2 py-2">
                                                <v-text-field
                                                    v-model="itemRow.notes"
                                                    type="text"
                                                    variant="outlined"
                                                    density="compact"
                                                    hide-details
                                                    :placeholder="
                                                        $t(
                                                            'merchandise.recipe.notes_placeholder',
                                                        ) ||
                                                        'Ghi chú nguyên liệu'
                                                    "
                                                    @update:model-value="
                                                        onChangeRecipe(
                                                            fieldRecipe.value,
                                                        )
                                                    "
                                                />
                                            </td>

                                            <!-- Nút Xóa dòng -->
                                            <td class="text-center px-2 py-2">
                                                <v-btn
                                                    icon="mdi-delete"
                                                    color="error"
                                                    variant="text"
                                                    size="small"
                                                    @click="
                                                        removeRecipeItem(
                                                            index,
                                                            fieldRecipe.value,
                                                            onChangeRecipe,
                                                        )
                                                    "
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Trạng thái trống -->
                    <div
                        v-else
                        class="d-flex flex-column align-center justify-center border rounded-lg py-8 px-4 text-grey bg-grey-lighten-5"
                    >
                        <v-icon
                            icon="mdi-food-variant"
                            size="42"
                            class="mb-2 text-grey"
                        />
                        <span>
                            {{
                                $t("merchandise.recipe.empty_recipe_desc") ||
                                "Công thức chưa có nguyên liệu nào. Vui lòng bấm thêm nguyên liệu."
                            }}
                        </span>
                        <div
                            v-if="showRecipeErrors"
                            class="text-caption text-error font-weight-bold mt-2"
                        >
                            {{
                                $t(
                                    "merchandise.recipe.recipe_items_required",
                                ) ||
                                "Công thức sản xuất phải có ít nhất 1 dòng nguyên liệu!"
                            }}
                        </div>
                    </div>

                    <!-- Phần Preview công thức -->
                    <div class="mt-6 border-top pt-4">
                        <div class="d-flex align-center mb-3">
                            <v-icon color="primary" class="mr-2">
                                mdi-eye-outline
                            </v-icon>
                            <span
                                class="font-weight-bold text-primary text-subtitle-2"
                            >
                                {{
                                    $t("merchandise.recipe.preview") ||
                                    "Xem trước công thức chế biến"
                                }}
                            </span>
                        </div>

                        <v-card
                            variant="outlined"
                            class="bg-primary-lighten-5 border-primary-lighten-3 rounded-lg overflow-hidden"
                        >
                            <v-row no-gutters class="align-stretch">
                                <!-- Cột Trái: Thành phẩm đầu ra tối giản -->
                                <v-col
                                    cols="12"
                                    md="3"
                                    class="pa-3 bg-primary text-white d-flex flex-column justify-center align-center text-center"
                                >
                                    <v-icon
                                        size="26"
                                        class="mb-1 text-primary-lighten-4"
                                    >
                                        mdi-food-turkey
                                    </v-icon>
                                    <div
                                        class="text-caption text-primary-lighten-4 uppercase tracking-wider font-weight-bold"
                                        style="
                                            font-size: 11px;
                                            letter-spacing: 0.5px;
                                        "
                                    >
                                        {{
                                            $t("merchandise.recipe.output") ||
                                            "THÀNH PHẨM ĐẦU RA"
                                        }}
                                    </div>
                                    <div
                                        class="text-h6 font-weight-black mt-1 line-height-tight"
                                    >
                                        {{ recipePreviewData.outputQuantity }}
                                        {{ recipePreviewData.outputUnitName }}
                                    </div>
                                    <div
                                        class="text-body-2 font-weight-medium text-wrap text-primary-lighten-4 mt-0.5 px-1"
                                    >
                                        {{ recipePreviewData.productName }}
                                    </div>
                                </v-col>

                                <!-- Cột Phải: Danh sách nguyên liệu tiêu hao tối giản -->
                                <v-col cols="12" md="9" class="pa-3 bg-white">
                                    <div
                                        v-if="
                                            recipePreviewData.items.length === 0
                                        "
                                        class="py-6 text-center text-grey text-caption"
                                    >
                                        {{
                                            $t(
                                                "merchandise.recipe.preview_empty_desc",
                                            ) ||
                                            "Vui lòng nhập đầy đủ nguyên liệu để hiển thị sơ đồ."
                                        }}
                                    </div>

                                    <div v-else class="d-flex flex-column">
                                        <div
                                            v-for="(
                                                ing, idx
                                            ) in recipePreviewData.items"
                                            :key="idx"
                                            class="d-flex align-center justify-space-between border-bottom py-2 px-3 hover-bg-grey-lighten-5 transition-all text-body-1"
                                        >
                                            <div
                                                class="d-flex align-center ga-2 text-wrap"
                                                style="max-width: 75%"
                                            >
                                                <span
                                                    class="text-grey font-weight-bold"
                                                    >{{ idx + 1 }}.
                                                </span>
                                                <div class="text-left">
                                                    <span
                                                        class="font-weight-bold text-grey-darken-3"
                                                    >
                                                        {{ ing.ingredientName }}
                                                    </span>
                                                    <span
                                                        class="text-grey ml-2"
                                                        style="font-size: 12px"
                                                    >
                                                        ({{
                                                            $t(
                                                                "merchandise.recipe.ingredient_code",
                                                            ) || "Mã"
                                                        }}:
                                                        {{
                                                            ing.ingredientCode
                                                        }})
                                                    </span>
                                                    <span
                                                        v-if="ing.notes"
                                                        class="text-primary ml-2 font-italic"
                                                        style="font-size: 12px"
                                                    >
                                                        - {{ ing.notes }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div
                                                class="d-flex align-center ga-2"
                                            >
                                                <v-chip
                                                    v-if="ing.wasteRate > 0"
                                                    color="orange"
                                                    size="small"
                                                    variant="flat"
                                                    class="px-2 py-0"
                                                >
                                                    {{
                                                        $t(
                                                            "merchandise.recipe.waste_rate_label",
                                                        ) || "Hao hụt"
                                                    }}
                                                    {{ ing.wasteRate }}%
                                                </v-chip>
                                                <span
                                                    class="font-weight-black text-primary text-subtitle-1"
                                                >
                                                    {{ ing.quantity }}
                                                    {{ ing.unitName }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </v-col>
                            </v-row>
                        </v-card>
                    </div>
                </v-card>
            </div>
        </VeeField>
    </div>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { mapActions, mapGetters } from "vuex";

export default {
    name: "FormRecipeInfo",
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
            ingredients: [],
            ingredientUnitsCache: {},
            loadingIngredients: false,
            isIngredientsLoaded: false,
            showRecipeErrors: false,
        };
    },
    computed: {
        ...mapGetters("unit", {
            unitOptions: "options",
        }),
        outputUnitOptions() {
            const list = [];
            const baseUnitId = this.formValues.baseUnitId;
            if (!baseUnitId) return [];

            const baseUnitObj = this.unitOptions.find(
                (u) => u.value === baseUnitId,
            );
            if (baseUnitObj) {
                list.push(baseUnitObj);
            }

            if (!this.formValues.isSingleUnit) {
                const conversions = this.formValues.conversions || [];
                conversions.forEach((c) => {
                    if (c.fromUnitId) {
                        const u = this.unitOptions.find(
                            (u) => u.value === c.fromUnitId,
                        );
                        if (
                            u &&
                            !list.some((item) => item.value === c.fromUnitId)
                        ) {
                            list.push(u);
                        }
                    }
                    if (c.toUnitId) {
                        const u = this.unitOptions.find(
                            (u) => u.value === c.toUnitId,
                        );
                        if (
                            u &&
                            !list.some((item) => item.value === c.toUnitId)
                        ) {
                            list.push(u);
                        }
                    }
                });
            }
            return list;
        },
        recipePreviewData() {
            const recipe = this.formValues.recipe;
            const productName = this.formValues.name || "Thành phẩm";
            if (!recipe || !recipe.outputUnitId) {
                return {
                    isValid: false,
                    productName,
                    outputQuantity: 1,
                    outputUnitName: "?",
                    items: [],
                };
            }

            const outputUnit = this.outputUnitOptions.find(
                (u) => u.value === recipe.outputUnitId,
            );
            const outputUnitName = outputUnit ? outputUnit.label : "?";

            const previewItems = [];
            const items = recipe.items || [];
            items.forEach((item) => {
                if (item.ingredientId && item.quantity > 0 && item.unitId) {
                    const ing = this.ingredients.find(
                        (i) => i.value === item.ingredientId,
                    );
                    const ingName = ing ? ing.name : "--";
                    const ingCode = ing ? ing.code : "";
                    const u = this.getIngredientUnitOptions(
                        item.ingredientId,
                    ).find((u) => u.value === item.unitId);
                    const uName = u ? u.label : "?";

                    previewItems.push({
                        ingredientName: ingName,
                        ingredientCode: ingCode,
                        quantity: item.quantity,
                        unitName: uName,
                        wasteRate: item.wasteRate || 0,
                        notes: item.notes || "",
                    });
                }
            });

            return {
                isValid: true,
                productName,
                outputQuantity: recipe.outputQuantity || 1,
                outputUnitName,
                items: previewItems,
            };
        },
    },

    mounted() {
        this.fetchUnitOptions();
        this.loadIngredients();
        // Nhận thông báo lỗi từ parent form khi bấm next/submit
        let parent = this.$parent;
        while (parent) {
            if (parent.$options.name === "FormFinishedProduct") {
                this.$watch(
                    () => parent.showRecipeErrors,
                    (val) => {
                        this.showRecipeErrors = val;
                    },
                    { immediate: true },
                );
                break;
            }
            parent = parent.$parent;
        }
    },
    methods: {
        ...mapActions("merchandise", [
            "fetchIngredientsOptions",
            "fetchItemDetail",
        ]),
        ...mapActions("unit", {
            fetchUnitOptions: "fetchOptions",
        }),

        async loadIngredients() {
            this.loadingIngredients = true;
            try {
                const response = await this.fetchIngredientsOptions();
                const items = Array.isArray(response)
                    ? response
                    : response?.data || [];

                this.ingredients = items.map((item) => ({
                    value: item.id,
                    label: `[${item.code}] ${item.name}`,
                    code: item.code,
                    name: item.name,
                }));

                const cache = {};
                items.forEach((detail) => {
                    cache[detail.id] = this.extractIngredientUnits(detail);
                });
                this.ingredientUnitsCache = cache;
            } catch (e) {
                console.error(e);
            } finally {
                this.loadingIngredients = false;
                this.isIngredientsLoaded = true;
            }
        },

        extractIngredientUnits(detail) {
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

        getIngredientUnitOptions(ingredientId) {
            if (!ingredientId) return [];
            return this.ingredientUnitsCache[ingredientId] || [];
        },

        async onIngredientSelected(
            ingredientId,
            item,
            index,
            recipeValue,
            onChangeRecipe,
        ) {
            if (!ingredientId) {
                item.unitId = null;
                onChangeRecipe(recipeValue);
                return;
            }

            await this.loadIngredientUnits(ingredientId);

            // Tìm base unit làm mặc định
            const cachedUnits = this.ingredientUnitsCache[ingredientId] || [];
            const baseUnit = cachedUnits.find((u) => u.value);
            item.unitId = baseUnit ? baseUnit.value : null;

            onChangeRecipe(recipeValue);
        },

        async loadIngredientUnits(ingredientId) {
            if (this.ingredientUnitsCache[ingredientId]) return;

            try {
                const detail = await this.fetchItemDetail({ id: ingredientId });
                if (detail) {
                    this.ingredientUnitsCache = {
                        ...this.ingredientUnitsCache,
                        [ingredientId]: this.extractIngredientUnits(detail),
                    };
                }
            } catch (e) {
                console.error(e);
            }
        },

        addRecipeItem(recipe, onChange) {
            const list = recipe.items ? [...recipe.items] : [];
            list.push({
                ingredientId: null,
                quantity: 1,
                unitId: null,
                wasteRate: 0,
                notes: "",
                sortOrder: list.length,
            });
            recipe.items = list;
            onChange(recipe);
        },

        removeRecipeItem(index, recipe, onChange) {
            const list = [...recipe.items];
            list.splice(index, 1);

            // Sắp xếp lại sort order
            list.forEach((item, idx) => {
                item.sortOrder = idx;
            });

            recipe.items = list;
            onChange(recipe);
        },
    },
};
</script>

<style scoped>
.bg-primary-lighten-5 {
    background-color: #f5f9ff !important;
}
.border-primary-lighten-3 {
    border-color: #d2e4ff !important;
}
.border-top {
    border-top: 1px solid rgba(0, 0, 0, 0.12) !important;
}
.text-error {
    color: rgb(var(--v-theme-error)) !important;
}
.border-bottom {
    border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
}
.hover-bg-grey-lighten-5:hover {
    background-color: #f9f9f9 !important;
}
.transition-all {
    transition: all 0.2s ease-in-out;
}
</style>
