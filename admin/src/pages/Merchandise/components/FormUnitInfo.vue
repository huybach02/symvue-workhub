<template>
    <div>
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
                <v-card variant="flat" class="pa-4 border mb-4">
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

                            <v-row class="align-center pr-8 pt-2">
                                <v-col cols="12" sm="3">
                                    <div class="mb-2">
                                        {{ $t("field.quantity") }}
                                    </div>
                                    <v-text-field
                                        v-model.number="conv.fromValue"
                                        type="number"
                                        variant="outlined"
                                        density="compact"
                                        hide-details
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
                                        hide-details
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
                                        hide-details
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
                                        hide-details
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
                                :items="configuredUnits(fieldConversions.value)"
                                item-title="label"
                                item-value="value"
                                variant="outlined"
                                density="compact"
                                clearable
                                :placeholder="
                                    $t('field.select_base_unit') ||
                                    'Chọn đơn vị cơ sở'
                                "
                                @update:model-value="onChangeBaseUnit"
                            />
                        </v-col>
                    </v-row>
                </v-card>
            </VeeField>
        </VeeField>
    </div>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { mapActions, mapGetters } from "vuex";

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
            this.syncBaseUnit(list, baseUnitId, onChangeBaseUnit);
        },

        updateConversions(conversions, onChange) {
            onChange([...(conversions || [])]);
        },

        onUnitChanged(conversions, onChange, baseUnitId, onChangeBaseUnit) {
            onChange([...(conversions || [])]);
            this.syncBaseUnit(conversions, baseUnitId, onChangeBaseUnit);
        },

        // Đồng bộ đơn vị tính cơ sở khi danh sách cấu hình thay đổi
        syncBaseUnit(conversions, baseUnitId, onChangeBaseUnit) {
            if (!baseUnitId) return;
            const validUnits = this.configuredUnits(conversions);
            const exists = validUnits.some((u) => u.value === baseUnitId);
            if (!exists) {
                // Nếu base unit hiện tại không còn nằm trong list đã cấu hình, reset về null
                onChangeBaseUnit(null);
            }
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
            const excludedIds = conversions
                .filter((_, idx) => idx !== index)
                .map((c) => c.fromUnitId)
                .filter((id) => id !== null && id !== undefined);

            if (currentCard.toUnitId) {
                excludedIds.push(currentCard.toUnitId);
            }

            return this.units.filter(
                (unit) => !excludedIds.includes(unit.value),
            );
        },

        filteredToUnits(index, conversions) {
            const currentCard = conversions[index];
            const excludedIds = conversions
                .filter((_, idx) => idx !== index)
                .map((c) => c.toUnitId)
                .filter((id) => id !== null && id !== undefined);

            if (currentCard.fromUnitId) {
                excludedIds.push(currentCard.fromUnitId);
            }

            return this.units.filter(
                (unit) => !excludedIds.includes(unit.value),
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
