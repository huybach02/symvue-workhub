<template>
    <VeeField
        v-slot="{ field: fieldProviders, handleChange: onChangeProviders }"
        name="providers"
    >
        <div>
            <div class="d-flex justify-space-between align-center mb-4">
                <div class="text-subtitle-1 font-weight-bold">
                    {{ $t("request.stock_in.providers_title") }}
                </div>
                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    @click="
                        addProvider(fieldProviders.value, onChangeProviders)
                    "
                >
                    {{ $t("request.stock_in.add_provider") }}
                </v-btn>
            </div>

            <div
                v-if="
                    !fieldProviders.value || fieldProviders.value.length === 0
                "
                class="text-center text-medium-emphasis py-8 border rounded-lg"
            >
                {{ $t("request.stock_in.empty_providers") }}
            </div>

            <v-expansion-panels
                v-else
                v-model="openedPanels"
                multiple
                variant="accordion"
                class="border rounded"
            >
                <v-expansion-panel
                    v-for="(
                        providerGroup, providerIndex
                    ) in fieldProviders.value"
                    :key="providerIndex"
                >
                    <v-expansion-panel-title>
                        <div
                            class="d-flex align-center justify-space-between w-100 pr-2"
                        >
                            <span class="font-weight-bold text-subtitle-1">
                                {{
                                    getProviderTitle(
                                        providerGroup.providerId,
                                        providerIndex,
                                    )
                                }}
                            </span>
                            <v-btn
                                icon="mdi-delete"
                                color="error"
                                variant="text"
                                size="small"
                                @click.stop="
                                    removeProvider(
                                        fieldProviders.value,
                                        onChangeProviders,
                                        providerIndex,
                                    )
                                "
                            />
                        </div>
                    </v-expansion-panel-title>

                    <v-expansion-panel-text class="pt-4 bg-white">
                        <v-row class="mb-2">
                            <v-col cols="12" md="6">
                                <div class="mb-2">
                                    {{ $t("field.provider") }}
                                    <span class="text-error">*</span>
                                </div>
                                <v-autocomplete
                                    v-model="providerGroup.providerId"
                                    :items="
                                        filteredProviderOptions(
                                            providerIndex,
                                            fieldProviders.value,
                                        )
                                    "
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    :loading="loadingProviders"
                                    :placeholder="$t('field.select_provider')"
                                    @update:model-value="
                                        (value) =>
                                            onProviderSelected(
                                                value,
                                                providerGroup,
                                                fieldProviders.value,
                                                onChangeProviders,
                                            )
                                    "
                                />
                            </v-col>
                        </v-row>

                        <template v-if="providerGroup.providerId">
                            <div
                                class="d-flex justify-space-between align-center mb-3"
                            >
                                <div class="text-subtitle-2 font-weight-bold">
                                    {{ $t("request.stock_in.items_title") }}
                                </div>
                                <v-btn
                                    color="primary"
                                    prepend-icon="mdi-plus"
                                    size="small"
                                    @click="
                                        addItem(
                                            fieldProviders.value,
                                            onChangeProviders,
                                            providerIndex,
                                        )
                                    "
                                >
                                    {{ $t("request.stock_in.add_item") }}
                                </v-btn>
                            </div>

                            <div
                                v-if="
                                    !providerGroup.items ||
                                    providerGroup.items.length === 0
                                "
                                class="text-center text-medium-emphasis py-6 border rounded-lg"
                            >
                                {{ $t("request.stock_in.empty_items") }}
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
                                            <col style="width: 120px" />
                                            <col style="width: 160px" />
                                            <col style="width: 160px" />
                                            <col style="width: 160px" />
                                            <col style="width: 180px" />
                                            <col style="width: 56px" />
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
                                                            "field.stock_in_merchandise",
                                                        )
                                                    }}
                                                </th>
                                                <th
                                                    class="text-left font-weight-bold px-3 py-2"
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
                                                        $t("field.import_price")
                                                    }}
                                                </th>
                                                <th
                                                    class="text-left font-weight-bold px-3 py-2"
                                                >
                                                    {{
                                                        $t("field.total_amount")
                                                    }}
                                                </th>
                                                <th
                                                    class="text-left font-weight-bold px-3 py-2"
                                                >
                                                    {{ $t("field.ghi_chu") }}
                                                </th>
                                                <th class="px-2 py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(
                                                    itemRow, itemIndex
                                                ) in providerGroup.items"
                                                :key="itemIndex"
                                                class="border-bottom"
                                            >
                                                <td class="px-3 py-2">
                                                    {{ itemIndex + 1 }}
                                                </td>
                                                <td class="px-2 py-2">
                                                    <v-autocomplete
                                                        v-model="
                                                            itemRow.merchandiseId
                                                        "
                                                        :items="
                                                            getMerchandiseOptionsForProvider(
                                                                providerGroup.providerId,
                                                            )
                                                        "
                                                        item-title="label"
                                                        item-value="value"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        clearable
                                                        :loading="
                                                            loadingMerchandise
                                                        "
                                                        :placeholder="
                                                            $t(
                                                                'request.stock_in.select_merchandise',
                                                            )
                                                        "
                                                        @update:model-value="
                                                            (value) =>
                                                                onMerchandiseSelected(
                                                                    value,
                                                                    itemRow,
                                                                    providerGroup.providerId,
                                                                    fieldProviders.value,
                                                                    onChangeProviders,
                                                                )
                                                        "
                                                    />
                                                </td>
                                                <td class="px-2 py-2">
                                                    <v-text-field
                                                        v-model.number="
                                                            itemRow.quantity
                                                        "
                                                        type="number"
                                                        min="0"
                                                        step="any"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        @update:model-value="
                                                            onChangeProviders(
                                                                fieldProviders.value,
                                                            )
                                                        "
                                                    />
                                                </td>
                                                <td class="px-2 py-2">
                                                    <v-autocomplete
                                                        v-model="itemRow.unitId"
                                                        :items="
                                                            getUnitOptions(
                                                                itemRow.merchandiseId,
                                                                providerGroup.providerId,
                                                            )
                                                        "
                                                        item-title="label"
                                                        item-value="value"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        clearable
                                                        :disabled="
                                                            !itemRow.merchandiseId
                                                        "
                                                        :placeholder="
                                                            $t(
                                                                'request.stock_in.select_unit',
                                                            )
                                                        "
                                                        @update:model-value="
                                                            (value) =>
                                                                onUnitSelected(
                                                                    value,
                                                                    itemRow,
                                                                    providerGroup.providerId,
                                                                    fieldProviders.value,
                                                                    onChangeProviders,
                                                                )
                                                        "
                                                    />
                                                </td>
                                                <td class="px-2 py-2">
                                                    <v-text-field
                                                        v-bind="
                                                            bindFormattedNumberModel(
                                                                {
                                                                    fieldName: `price_${providerIndex}_${itemIndex}`,
                                                                    value: itemRow.price,
                                                                    onChange: (
                                                                        val,
                                                                    ) => {
                                                                        itemRow.price =
                                                                            val;
                                                                        onChangeProviders(
                                                                            fieldProviders.value,
                                                                        );
                                                                    },
                                                                },
                                                            )
                                                        "
                                                        :suffix="
                                                            getItemCurrency(
                                                                itemRow,
                                                                providerGroup.providerId,
                                                            )
                                                        "
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                    />
                                                </td>
                                                <td class="px-2 py-2">
                                                    <v-text-field
                                                        :model-value="
                                                            calculateTotalRow(
                                                                itemRow,
                                                            )
                                                        "
                                                        :suffix="
                                                            getItemCurrency(
                                                                itemRow,
                                                                providerGroup.providerId,
                                                            )
                                                        "
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        readonly
                                                    />
                                                </td>
                                                <td class="px-2 py-2">
                                                    <v-text-field
                                                        v-model="itemRow.note"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        @update:model-value="
                                                            onChangeProviders(
                                                                fieldProviders.value,
                                                            )
                                                        "
                                                    />
                                                </td>
                                                <td
                                                    class="px-2 py-2 text-center"
                                                >
                                                    <v-btn
                                                        icon="mdi-delete"
                                                        color="error"
                                                        variant="text"
                                                        size="small"
                                                        @click="
                                                            removeItem(
                                                                fieldProviders.value,
                                                                onChangeProviders,
                                                                providerIndex,
                                                                itemIndex,
                                                            )
                                                        "
                                                    />
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
import { useFormatInputNumber } from "@/hooks/useFormatInputNumber";
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "RequestStockInFormFields",
    components: {
        VeeField,
    },
    data() {
        const { bindFormattedNumberModel } = useFormatInputNumber();
        return {
            bindFormattedNumberModel,
            openedPanels: [],
            providerOptions: [],
            merchandiseOptions: [],
            merchandiseUnitsCache: {},
            loadingProviders: false,
            loadingMerchandise: false,
        };
    },
    computed: {
        ...mapGetters("merchandise", ["itemById"]),
    },
    mounted() {
        this.loadProviders();
        this.loadMerchandise();
    },
    methods: {
        ...mapActions("provider", {
            fetchProviderOptions: "fetchOptions",
        }),
        ...mapActions("merchandise", [
            "fetchStockInMerchandiseOptions",
            "fetchItemDetail",
        ]),

        async loadProviders() {
            this.loadingProviders = true;
            const options = await this.fetchProviderOptions();
            this.providerOptions = Array.isArray(options) ? options : [];
            this.loadingProviders = false;
        },

        async loadMerchandise() {
            this.loadingMerchandise = true;
            try {
                const items = await this.fetchStockInMerchandiseOptions();
                const list = Array.isArray(items) ? items : [];

                this.merchandiseOptions = list.map((item) => ({
                    value: item.id,
                    label: `[${item.code}] ${item.name}`,
                    code: item.code,
                    name: item.name,
                    providerIds: item.providerIds || [],
                }));

                const cache = { ...this.merchandiseUnitsCache };
                list.forEach((item) => {
                    const providerUnitsMap = {};
                    if (item.unitByProviders) {
                        Object.keys(item.unitByProviders).forEach((pId) => {
                            providerUnitsMap[pId] = this.normalizeUnits(
                                item.unitByProviders[pId],
                            );
                        });
                    }
                    providerUnitsMap.general = this.normalizeUnits(item.units);
                    cache[item.id] = providerUnitsMap;
                });
                this.merchandiseUnitsCache = cache;
            } finally {
                this.loadingMerchandise = false;
            }
        },

        normalizeUnits(units) {
            if (!Array.isArray(units)) {
                return [];
            }

            const seen = new Set();
            const result = [];

            units.forEach((unit) => {
                const value = unit?.value ?? unit?.unitId ?? unit?.id;
                if (value == null || seen.has(value)) {
                    return;
                }

                seen.add(value);
                result.push({
                    value,
                    label:
                        unit?.label ||
                        unit?.name ||
                        unit?.unit?.name ||
                        String(value),
                    isBase: Boolean(unit?.isBase),
                });
            });

            return result;
        },

        getUnitOptions(merchandiseId, providerId) {
            if (!merchandiseId) {
                return [];
            }

            const cache = this.merchandiseUnitsCache[merchandiseId];
            if (!cache) {
                return [];
            }

            if (providerId && cache[providerId]) {
                return cache[providerId];
            }

            return cache.general || [];
        },

        filteredProviderOptions(currentIndex, providers) {
            const selectedIds = (providers || [])
                .map((item, index) =>
                    index === currentIndex ? null : item.providerId,
                )
                .filter((id) => id != null);

            return this.providerOptions.filter(
                (option) => !selectedIds.includes(option.value),
            );
        },

        getMerchandiseOptionsForProvider(providerId) {
            if (!providerId) {
                return [];
            }
            return this.merchandiseOptions.filter((option) =>
                option.providerIds.includes(Number(providerId)),
            );
        },

        getProviderTitle(providerId, index) {
            if (!providerId) {
                return this.$t("request.stock_in.provider_untitled", {
                    index: index + 1,
                });
            }

            const provider = this.providerOptions.find(
                (item) => item.value === providerId,
            );

            return (
                provider?.label ||
                this.$t("request.stock_in.provider_untitled", {
                    index: index + 1,
                })
            );
        },

        createEmptyProvider() {
            return {
                providerId: null,
                items: [],
            };
        },

        createEmptyItem() {
            return {
                lineId: crypto.randomUUID(),
                merchandiseId: null,
                quantity: null,
                unitId: null,
                price: null,
                currency: "VND",
                note: "",
            };
        },

        addProvider(providers, onChange) {
            const list = Array.isArray(providers) ? [...providers] : [];
            list.push(this.createEmptyProvider());
            onChange(list);
            this.openedPanels = [...this.openedPanels, list.length - 1];
        },

        removeProvider(providers, onChange, index) {
            const list = [...(providers || [])];
            list.splice(index, 1);
            onChange(list);
            this.openedPanels = this.openedPanels
                .filter((panelIndex) => panelIndex !== index)
                .map((panelIndex) =>
                    panelIndex > index ? panelIndex - 1 : panelIndex,
                );
        },

        addItem(providers, onChange, providerIndex) {
            const list = [...(providers || [])];
            const providerGroup = { ...list[providerIndex] };
            const items = [...(providerGroup.items || [])];
            items.push(this.createEmptyItem());
            providerGroup.items = items;
            list[providerIndex] = providerGroup;
            onChange(list);
        },

        removeItem(providers, onChange, providerIndex, itemIndex) {
            const list = [...(providers || [])];
            const providerGroup = { ...list[providerIndex] };
            const items = [...(providerGroup.items || [])];
            items.splice(itemIndex, 1);
            providerGroup.items = items;
            list[providerIndex] = providerGroup;
            onChange(list);
        },

        async onMerchandiseSelected(
            merchandiseId,
            itemRow,
            providerId,
            providers,
            onChange,
        ) {
            if (!merchandiseId) {
                itemRow.unitId = null;
                itemRow.price = null;
                onChange(providers);
                return;
            }

            await this.ensureMerchandiseUnits(merchandiseId);
            await this.ensureMerchandiseDetail(merchandiseId);

            const units = this.getUnitOptions(merchandiseId, providerId);
            const baseUnit = units.find((unit) => unit.isBase) || units[0];
            itemRow.unitId = baseUnit?.value ?? null;
            this.updateItemPrice(itemRow, providerId, providers, onChange);
        },

        async onUnitSelected(unitId, itemRow, providerId, providers, onChange) {
            itemRow.unitId = unitId;
            if (itemRow.merchandiseId) {
                await this.ensureMerchandiseDetail(itemRow.merchandiseId);
            }
            this.updateItemPrice(itemRow, providerId, providers, onChange);
        },

        async onProviderSelected(
            providerId,
            providerGroup,
            providers,
            onChange,
        ) {
            providerGroup.providerId = providerId;
            providerGroup.items = []; // Xóa toàn bộ các dòng hàng hóa khi thay đổi nhà cung cấp
            onChange(providers);
        },

        async ensureMerchandiseDetail(merchandiseId) {
            if (!merchandiseId) {
                return;
            }
            if (this.itemById(merchandiseId)) {
                return;
            }
            try {
                await this.fetchItemDetail({ id: merchandiseId });
            } catch (error) {
                console.error(error);
            }
        },

        getDefaultPrice(merchandiseId, providerId, unitId) {
            if (!merchandiseId || !providerId || !unitId) {
                return null;
            }
            const detail = this.itemById(merchandiseId);
            console.log(detail);
            if (!detail || !Array.isArray(detail.providers)) {
                return null;
            }
            const mProvider = detail.providers.find(
                (p) => Number(p.providerId) === Number(providerId),
            );
            if (!mProvider || !Array.isArray(mProvider.prices)) {
                return null;
            }
            const priceObj = mProvider.prices.find(
                (p) => Number(p.unitId) === Number(unitId),
            );
            if (!priceObj) {
                return null;
            }
            const val =
                priceObj.priceAfterDiscount !== null &&
                priceObj.priceAfterDiscount !== undefined
                    ? priceObj.priceAfterDiscount
                    : priceObj.price;
            return val !== null && val !== undefined ? Number(val) : null;
        },

        updateItemPrice(itemRow, providerId, providers, onChange) {
            const price = this.getDefaultPrice(
                itemRow.merchandiseId,
                providerId,
                itemRow.unitId,
            );
            if (price !== null) {
                itemRow.price = price;
            } else {
                itemRow.price = null;
            }
            itemRow.currency = this.getItemCurrency(itemRow, providerId);
            onChange(providers);
        },

        calculateTotalRow(itemRow) {
            const quantity = Number(itemRow.quantity) || 0;
            const price = Number(itemRow.price) || 0;
            const total = quantity * price;
            return functionHelper.formatNumber(total);
        },

        getItemCurrency(itemRow, providerId) {
            const merchandiseId = itemRow.merchandiseId;
            const unitId = itemRow.unitId;
            if (!merchandiseId || !providerId || !unitId) {
                return "VND";
            }
            const detail = this.itemById(merchandiseId);
            if (!detail || !Array.isArray(detail.providers)) {
                return "VND";
            }
            const mProvider = detail.providers.find(
                (p) => Number(p.providerId) === Number(providerId),
            );
            if (!mProvider || !Array.isArray(mProvider.prices)) {
                return "VND";
            }
            const priceObj = mProvider.prices.find(
                (p) => Number(p.unitId) === Number(unitId),
            );
            return priceObj?.currency || "VND";
        },

        async ensureMerchandiseUnits(merchandiseId) {
            if (this.merchandiseUnitsCache[merchandiseId]) {
                return;
            }

            try {
                const detail = await this.fetchItemDetail({
                    id: merchandiseId,
                });
                if (!detail) {
                    return;
                }

                const providerUnitsMap = {};
                if (detail.providers) {
                    detail.providers.forEach((prov) => {
                        providerUnitsMap[prov.providerId] = this.normalizeUnits(
                            prov.units,
                        );
                    });
                }

                let generalUnits = this.normalizeUnits(detail.units);
                if (!generalUnits.length) {
                    generalUnits = this.extractUnitsFromDetail(detail);
                }
                providerUnitsMap.general = generalUnits;

                this.merchandiseUnitsCache = {
                    ...this.merchandiseUnitsCache,
                    [merchandiseId]: providerUnitsMap,
                };
            } catch (error) {
                console.error(error);
            }
        },

        extractUnitsFromDetail(detail) {
            const units = [];
            const seen = new Set();

            const pushUnit = (unitId, label, isBase = false) => {
                if (unitId == null || seen.has(unitId)) {
                    return;
                }
                seen.add(unitId);
                units.push({
                    value: unitId,
                    label: label || detail?.baseUnit?.name || String(unitId),
                    isBase,
                });
            };

            if (detail.baseUnitId) {
                pushUnit(detail.baseUnitId, detail.baseUnit?.name, true);
            }

            (detail.conversions || []).forEach((conversion) => {
                pushUnit(
                    conversion.fromUnitId,
                    conversion.fromUnit?.name,
                    false,
                );
                pushUnit(conversion.toUnitId, conversion.toUnit?.name, false);
            });

            return units;
        },
    },
};
</script>
