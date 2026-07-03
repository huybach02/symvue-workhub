<template>
    <div>
        <VeeField
            v-slot="{
                field: fieldIsSingleUnit,
                handleChange: onChangeIsSingleUnit,
            }"
            name="isSingleUnit"
        >
            <VeeField
                v-slot="{
                    field: fieldConversions,
                    handleChange: onChangeConversions,
                }"
                name="conversions"
            >
                <VeeField
                    v-slot="{
                        field: fieldBaseUnit,
                        handleChange: onChangeBaseUnit,
                    }"
                    name="baseUnitId"
                >
                    <!-- Checkbox: Chỉ có 1 đơn vị tính -->
                    <v-card
                        :variant="
                            fieldIsSingleUnit.value ? 'tonal' : 'outlined'
                        "
                        :color="
                            fieldIsSingleUnit.value
                                ? 'primary'
                                : 'grey-lighten-1'
                        "
                        class="mb-4 pa-4"
                    >
                        <div class="d-flex align-center justify-space-between">
                            <div class="d-flex align-center">
                                <v-icon size="28" class="mr-3">
                                    {{
                                        fieldIsSingleUnit.value
                                            ? "mdi-cube-outline"
                                            : "mdi-layers-outline"
                                    }}
                                </v-icon>
                                <div>
                                    <div class="font-weight-bold">
                                        {{
                                            $t("merchandise.is_single_unit") ||
                                            "Chỉ có 1 đơn vị tính"
                                        }}
                                    </div>
                                    <div
                                        class="text-caption"
                                        :class="
                                            fieldIsSingleUnit.value
                                                ? 'text-primary'
                                                : 'text-grey'
                                        "
                                    >
                                        {{
                                            fieldIsSingleUnit.value
                                                ? $t(
                                                      "merchandise.is_single_unit_desc_active",
                                                  )
                                                : $t(
                                                      "merchandise.is_single_unit_desc_inactive",
                                                  )
                                        }}
                                    </div>
                                </div>
                            </div>
                            <v-switch
                                :model-value="fieldIsSingleUnit.value"
                                color="primary"
                                inset
                                density="compact"
                                hide-details
                                :disabled="
                                    !!(
                                        fieldConversions.value &&
                                        fieldConversions.value.length > 0
                                    )
                                "
                                @update:model-value="
                                    (val) => {
                                        onChangeIsSingleUnit(val);
                                        if (val) {
                                            onChangeConversions([]);
                                        }
                                    }
                                "
                            />
                        </div>
                    </v-card>

                    <v-card
                        v-if="!fieldIsSingleUnit.value"
                        variant="flat"
                        class="pa-4 border mb-4"
                    >
                        <div class="d-flex justify-between align-center mb-4">
                            <h3 class="text-subtitle-1 font-weight-bold">
                                {{
                                    $t("merchandise.conversions_config") ||
                                    "Cấu hình đơn vị quy đổi"
                                }}
                            </h3>
                            <v-btn
                                color="primary"
                                prepend-icon="mdi-plus"
                                size="small"
                                @click="
                                    addConversion(
                                        fieldConversions.value || [],
                                        onChangeConversions,
                                    )
                                "
                            >
                                {{ $t("button.create") || "Thêm mới" }}
                            </v-btn>
                        </div>

                        <v-row
                            v-if="
                                !fieldConversions.value ||
                                fieldConversions.value.length === 0
                            "
                            class="mb-4"
                        >
                            <v-col cols="12" class="text-center text-grey py-6">
                                {{
                                    $t("merchandise.no_conversions") ||
                                    "Chưa cấu hình đơn vị quy đổi nào."
                                }}
                            </v-col>
                        </v-row>

                        <div v-else>
                            <v-card
                                v-for="(conv, index) in fieldConversions.value"
                                :key="index"
                                class="mb-4 pa-4 position-relative border"
                                variant="outlined"
                            >
                                <v-btn
                                    icon="mdi-close"
                                    variant="text"
                                    size="small"
                                    color="error"
                                    class="position-absolute"
                                    style="top: 8px; right: 8px; z-index: 10"
                                    @click="
                                        removeConversion(
                                            index,
                                            fieldConversions.value,
                                            onChangeConversions,
                                            fieldBaseUnit.value,
                                            onChangeBaseUnit,
                                        )
                                    "
                                />

                                <v-row class="align-start pr-8 pt-2">
                                    <v-col cols="12" sm="3">
                                        <div class="mb-2">
                                            {{ $t("field.quantity") }}
                                        </div>
                                        <v-text-field
                                            v-model.number="conv.fromValue"
                                            type="number"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :error-messages="
                                                showErrors &&
                                                (!conv.fromValue ||
                                                    conv.fromValue <= 0)
                                                    ? $t(
                                                          'validation.mixed.required',
                                                          {
                                                              field: $t(
                                                                  'field.quantity',
                                                              ),
                                                          },
                                                      )
                                                    : ''
                                            "
                                            @update:model-value="
                                                updateConversions(
                                                    fieldConversions.value,
                                                    onChangeConversions,
                                                )
                                            "
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="3">
                                        <div class="mb-2">
                                            {{ $t("field.unit") }}
                                        </div>
                                        <v-autocomplete
                                            v-model="conv.fromUnitId"
                                            :items="
                                                filteredFromUnits(
                                                    index,
                                                    fieldConversions.value,
                                                )
                                            "
                                            item-title="label"
                                            item-value="value"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :error-messages="
                                                showErrors && !conv.fromUnitId
                                                    ? $t(
                                                          'validation.mixed.required',
                                                          {
                                                              field: $t(
                                                                  'field.unit',
                                                              ),
                                                          },
                                                      )
                                                    : ''
                                            "
                                            @update:model-value="
                                                onUnitChanged(
                                                    fieldConversions.value,
                                                    onChangeConversions,
                                                    fieldBaseUnit.value,
                                                    onChangeBaseUnit,
                                                )
                                            "
                                        />
                                    </v-col>
                                    <v-col
                                        cols="12"
                                        sm="1"
                                        class="text-center text-h6 font-weight-bold pt-6 pb-0"
                                    >
                                        =
                                    </v-col>
                                    <v-col cols="12" sm="2">
                                        <div class="mb-2">
                                            {{ $t("field.quantity") }}
                                        </div>
                                        <v-text-field
                                            v-model.number="conv.toValue"
                                            type="number"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :error-messages="
                                                showErrors &&
                                                (!conv.toValue ||
                                                    conv.toValue <= 0)
                                                    ? $t(
                                                          'validation.mixed.required',
                                                          {
                                                              field: $t(
                                                                  'field.quantity',
                                                              ),
                                                          },
                                                      )
                                                    : ''
                                            "
                                            @update:model-value="
                                                updateConversions(
                                                    fieldConversions.value,
                                                    onChangeConversions,
                                                )
                                            "
                                        />
                                    </v-col>
                                    <v-col cols="12" sm="3">
                                        <div class="mb-2">
                                            {{ $t("field.unit") }}
                                        </div>
                                        <v-autocomplete
                                            v-model="conv.toUnitId"
                                            :items="
                                                filteredToUnits(
                                                    index,
                                                    fieldConversions.value,
                                                )
                                            "
                                            item-title="label"
                                            item-value="value"
                                            variant="outlined"
                                            density="compact"
                                            hide-details="auto"
                                            :error-messages="
                                                showErrors && !conv.toUnitId
                                                    ? $t(
                                                          'validation.mixed.required',
                                                          {
                                                              field: $t(
                                                                  'field.unit',
                                                              ),
                                                          },
                                                      )
                                                    : ''
                                            "
                                            @update:model-value="
                                                onUnitChanged(
                                                    fieldConversions.value,
                                                    onChangeConversions,
                                                    fieldBaseUnit.value,
                                                    onChangeBaseUnit,
                                                )
                                            "
                                        />
                                    </v-col>
                                </v-row>
                            </v-card>
                        </div>
                    </v-card>

                    <!-- Chọn Đơn vị tính cơ sở -->
                    <v-card variant="flat" class="pa-4 border">
                        <v-row>
                            <v-col cols="12" md="6">
                                <div class="mb-2">
                                    {{ $t("field.base_unit") }}
                                </div>
                                <v-select
                                    :model-value="fieldBaseUnit.value"
                                    :items="
                                        fieldIsSingleUnit.value
                                            ? units
                                            : configuredUnits(
                                                  fieldConversions.value,
                                              )
                                    "
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    :error-messages="
                                        showErrors && !fieldBaseUnit.value
                                            ? $t('validation.mixed.required', {
                                                  field: $t('field.base_unit'),
                                              })
                                            : ''
                                    "
                                    :placeholder="
                                        $t('field.select_base_unit') ||
                                        'Chọn đơn vị cơ sở'
                                    "
                                    @update:model-value="onChangeBaseUnit"
                                />
                            </v-col>
                        </v-row>
                    </v-card>

                    <!-- Phần Preview quy đổi -->
                    <v-card variant="flat" class="mt-4 pa-4 border">
                        <div class="d-flex align-center mb-3">
                            <v-icon color="primary" class="mr-2">
                                mdi-eye-outline
                            </v-icon>
                            <span class="font-weight-bold text-primary">
                                {{
                                    $t("merchandise.conversion_preview") ||
                                    "Xem trước quy đổi"
                                }}
                            </span>
                        </div>

                        <v-row v-if="fieldIsSingleUnit.value">
                            <v-col cols="12">
                                <div class="text-caption text-grey">
                                    {{
                                        $t(
                                            "merchandise.single_unit_preview_desc",
                                        ) ||
                                        "Nguyên liệu cấu hình chỉ sử dụng 1 đơn vị tính."
                                    }}
                                </div>
                                <div
                                    class="font-weight-bold text-subtitle-1 mt-2 text-primary"
                                >
                                    1
                                    {{
                                        getUnitName(fieldBaseUnit.value) || "?"
                                    }}
                                    = 1
                                    {{
                                        getUnitName(fieldBaseUnit.value) || "?"
                                    }}
                                    ({{ $t("field.base_unit") }})
                                </div>
                            </v-col>
                        </v-row>

                        <v-row v-else>
                            <!-- 1. Cấu hình đơn vị quy đổi -->
                            <v-col cols="12" md="5">
                                <div
                                    class="text-subtitle-2 font-weight-bold mb-2"
                                >
                                    1.
                                    {{
                                        $t(
                                            "merchandise.configured_conversions",
                                        ) || "Cấu hình quy đổi"
                                    }}
                                </div>
                                <v-list
                                    density="compact"
                                    bg-color="transparent"
                                    class="pa-0"
                                >
                                    <v-list-item
                                        v-for="(c, idx) in (
                                            fieldConversions.value || []
                                        ).filter(
                                            (c) => c.fromUnitId && c.toUnitId,
                                        )"
                                        :key="idx"
                                        class="px-0 py-1"
                                    >
                                        <div class="d-flex align-center">
                                            <v-icon
                                                size="small"
                                                class="mr-2 text-grey"
                                            >
                                                mdi-arrow-right-bold-circle-outline
                                            </v-icon>
                                            <span>
                                                <strong>{{
                                                    c.fromValue
                                                }}</strong>
                                                {{ getUnitName(c.fromUnitId) }}
                                                =
                                                <strong>{{ c.toValue }}</strong>
                                                {{ getUnitName(c.toUnitId) }}
                                            </span>
                                        </div>
                                    </v-list-item>
                                    <div
                                        v-if="
                                            !(
                                                fieldConversions.value &&
                                                fieldConversions.value.some(
                                                    (c) =>
                                                        c.fromUnitId &&
                                                        c.toUnitId,
                                                )
                                            )
                                        "
                                        class="text-caption text-grey"
                                    >
                                        {{
                                            $t(
                                                "merchandise.no_conversions_configured",
                                            ) || "Chưa cấu hình đơn vị quy đổi."
                                        }}
                                    </div>
                                </v-list>
                            </v-col>

                            <v-col
                                cols="12"
                                md="1"
                                class="d-none d-md-flex justify-center"
                            >
                                <v-divider vertical />
                            </v-col>

                            <!-- 2. Quy đổi về đơn vị cơ sở -->
                            <v-col cols="12" md="6">
                                <div
                                    class="text-subtitle-2 font-weight-bold mb-2"
                                >
                                    2.
                                    {{
                                        $t("merchandise.computed_base_rates") ||
                                        "Quy đổi về đơn vị cơ sở"
                                    }}
                                    <span v-if="fieldBaseUnit.value">
                                        ({{ getUnitName(fieldBaseUnit.value) }})
                                    </span>
                                </div>
                                <v-list
                                    density="compact"
                                    bg-color="transparent"
                                    class="pa-0"
                                >
                                    <v-list-item
                                        v-for="item in previewCalculatedFactors(
                                            fieldConversions.value,
                                            fieldBaseUnit.value,
                                        )"
                                        :key="item.id"
                                        class="px-0 py-1"
                                    >
                                        <div class="d-flex align-center">
                                            <v-icon
                                                size="small"
                                                class="mr-2"
                                                :color="
                                                    item.isBase
                                                        ? 'primary'
                                                        : item.factor ===
                                                            undefined
                                                          ? 'error'
                                                          : 'success'
                                                "
                                            >
                                                {{
                                                    item.isBase
                                                        ? "mdi-star"
                                                        : item.factor ===
                                                            undefined
                                                          ? "mdi-alert-circle"
                                                          : "mdi-circle-medium"
                                                }}
                                            </v-icon>
                                            <span>
                                                1 {{ item.name }} =
                                                <strong
                                                    :class="
                                                        item.factor ===
                                                        undefined
                                                            ? 'text-error'
                                                            : ''
                                                    "
                                                >
                                                    {{
                                                        item.factor !==
                                                        undefined
                                                            ? formatFactor(
                                                                  item.factor,
                                                              )
                                                            : $t(
                                                                  "merchandise.unlinked",
                                                              ) ||
                                                              "Chưa liên kết"
                                                    }}
                                                </strong>
                                                {{
                                                    getUnitName(
                                                        fieldBaseUnit.value,
                                                    )
                                                }}
                                                <span
                                                    v-if="item.isBase"
                                                    class="text-caption text-primary font-weight-bold ml-1"
                                                >
                                                    ({{
                                                        $t("field.base_unit")
                                                    }})
                                                </span>
                                            </span>
                                        </div>
                                    </v-list-item>
                                    <div
                                        v-if="!fieldBaseUnit.value"
                                        class="text-caption text-grey"
                                    >
                                        {{
                                            $t(
                                                "merchandise.select_base_unit_to_preview",
                                            ) ||
                                            "Vui lòng chọn đơn vị cơ sở để xem."
                                        }}
                                    </div>
                                </v-list>
                            </v-col>
                        </v-row>
                    </v-card>
                </VeeField>
            </VeeField>
        </VeeField>
    </div>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { mapActions, mapGetters } from "vuex";
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "FormUnitInfo",
    components: {
        VeeField,
    },
    props: {
        item: {
            type: Object,
            default: null,
        },
        showErrors: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        ...mapGetters("unit", { units: "options" }),
    },
    mounted() {
        this.fetchOptions();
    },
    methods: {
        ...mapActions("unit", ["fetchOptions"]),

        addConversion(conversions, onChange) {
            const list = [...(conversions || [])];
            list.push({
                fromValue: 1,
                fromUnitId: null,
                toValue: 1,
                toUnitId: null,
                sortOrder: list.length,
            });
            onChange(list);
        },

        removeConversion(
            index,
            conversions,
            onChange,
            baseUnitId,
            onChangeBaseUnit,
        ) {
            const list = [...(conversions || [])];
            list.splice(index, 1);

            // Cập nhật lại sortOrder
            list.forEach((item, idx) => {
                item.sortOrder = idx;
            });

            onChange(list);
            this.$nextTick(() => {
                onChangeBaseUnit(null);
            });
        },

        updateConversions(conversions, onChange) {
            onChange([...(conversions || [])]);
        },

        onUnitChanged(conversions, onChange, baseUnitId, onChangeBaseUnit) {
            onChange([...(conversions || [])]);
            this.$nextTick(() => {
                onChangeBaseUnit(null);
            });
        },

        configuredUnits(conversions) {
            if (!conversions || !Array.isArray(conversions)) return [];
            const ids = new Set();
            conversions.forEach((c) => {
                if (c.fromUnitId) ids.add(c.fromUnitId);
                if (c.toUnitId) ids.add(c.toUnitId);
            });
            return this.units.filter((unit) => ids.has(unit.value));
        },

        filteredFromUnits(index, conversions) {
            const currentCard = conversions[index];
            const otherConversions = conversions.filter((_, idx) => idx !== index);

            // 1. Loại trừ các fromUnitId của các dòng khác (tránh lặp nguồn)
            const excludedIds = otherConversions
                .map((c) => c.fromUnitId)
                .filter((id) => id !== null && id !== undefined)
                .map((id) => String(id));

            // 2. Loại trừ tất cả các đơn vị đã liên thông với toUnitId của chính nó thông qua các dòng khác
            if (currentCard.toUnitId) {
                const connected = functionHelper.getConnectedUnits(currentCard.toUnitId, otherConversions);
                connected.forEach((id) => {
                    if (!excludedIds.includes(id)) {
                        excludedIds.push(id);
                    }
                });
            }

            return this.units.filter(
                (unit) => !excludedIds.includes(String(unit.value)),
            );
        },

        filteredToUnits(index, conversions) {
            const currentCard = conversions[index];
            const otherConversions = conversions.filter((_, idx) => idx !== index);

            // 1. Loại trừ các toUnitId của các dòng khác (tránh lặp đích)
            const excludedIds = otherConversions
                .map((c) => c.toUnitId)
                .filter((id) => id !== null && id !== undefined)
                .map((id) => String(id));

            // 2. Loại trừ tất cả các đơn vị đã liên thông với fromUnitId của chính nó thông qua các dòng khác
            if (currentCard.fromUnitId) {
                const connected = functionHelper.getConnectedUnits(currentCard.fromUnitId, otherConversions);
                connected.forEach((id) => {
                    if (!excludedIds.includes(id)) {
                        excludedIds.push(id);
                    }
                });
            }

            return this.units.filter(
                (unit) => !excludedIds.includes(String(unit.value)),
            );
        },

        getUnitName(unitId) {
            if (!unitId) return "";
            const unit = this.units.find((u) => u.value === unitId);
            return unit ? unit.label : "";
        },

        formatFactor(val) {
            if (val === undefined || isNaN(val)) return "";
            return parseFloat(Number(val).toFixed(4)).toString();
        },

        previewCalculatedFactors(conversions, baseUnitId) {
            return functionHelper.previewCalculatedFactors(
                conversions,
                baseUnitId,
                this.units,
            );
        },
    },
};
</script>

<style scoped>
.d-flex.justify-between {
    justify-content: space-between;
}
</style>
