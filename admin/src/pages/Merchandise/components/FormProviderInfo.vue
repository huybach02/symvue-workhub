<template>
    <VeeField
        v-slot="{ field: fieldProviders, handleChange: onChangeProviders }"
        name="providers"
    >
        <div class="mb-4 d-flex justify-space-between align-center">
            <h3 class="text-h6 font-weight-bold">
                {{ $t("merchandise.provider_config") || "Cấu hình Nhà cung cấp" }}
            </h3>
            <v-btn
                color="primary"
                prepend-icon="mdi-plus"
                @click="addProvider(fieldProviders.value, onChangeProviders)"
            >
                {{ $t("button.create") }}
            </v-btn>
        </div>

        <!-- Danh sách nhà cung cấp dưới dạng Expansion Panels -->
        <div v-if="fieldProviders.value && fieldProviders.value.length > 0">
            <v-expansion-panels variant="accordion" class="border rounded">
                <v-expansion-panel
                    v-for="(prov, index) in fieldProviders.value"
                    :key="index"
                >
                    <!-- Tiêu đề Expansion Panel -->
                    <v-expansion-panel-title>
                        <div class="d-flex align-center justify-space-between w-100 pr-4">
                            <span class="font-weight-bold text-subtitle-1">
                                {{ getProviderTitle(prov.providerId, index) }}
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
                                        index,
                                    )
                                "
                            />
                        </div>
                    </v-expansion-panel-title>

                    <!-- Nội dung Expansion Panel -->
                    <v-expansion-panel-text class="pt-4 bg-white">
                        <v-row>
                            <!-- Chọn Nhà cung cấp -->
                            <v-col cols="12" md="6">
                                <div class="mb-2">
                                    {{ $t("field.provider") || "Nhà cung cấp" }}
                                </div>
                                <v-autocomplete
                                    v-model="prov.providerId"
                                    :items="filteredProviders(index, fieldProviders.value)"
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    :placeholder="
                                        $t('field.select_provider') ||
                                        'Chọn nhà cung cấp'
                                    "
                                    @update:model-value="
                                        onProviderSelected(
                                            fieldProviders.value,
                                            onChangeProviders,
                                            index,
                                        )
                                    "
                                />
                            </v-col>

                            <!-- Chọn Đơn vị mua hàng mặc định -->
                            <v-col cols="12" md="6" v-if="prov.providerId">
                                <div class="mb-2">
                                    {{
                                        $t("field.default_purchase_unit") ||
                                        "Đơn vị mua hàng mặc định"
                                    }}
                                </div>
                                <v-select
                                    v-model="prov.defaultPurchaseUnitId"
                                    :items="getConfiguredUnits()"
                                    item-title="label"
                                    item-value="value"
                                    variant="outlined"
                                    density="compact"
                                    clearable
                                    :placeholder="
                                        $t('field.select_purchase_unit') ||
                                        'Chọn đơn vị mua hàng mặc định'
                                    "
                                    @update:model-value="
                                        onChangeProviders(fieldProviders.value)
                                    "
                                />
                            </v-col>
                        </v-row>

                        <!-- Tỉ lệ quy đổi đơn vị (Custom conversions) -->
                        <div
                            v-if="
                                prov.providerId &&
                                prov.conversions &&
                                prov.conversions.length > 0
                            "
                            class="mt-4"
                        >
                            <h4 class="text-subtitle-1 font-weight-bold mb-2">
                                {{
                                    $t("merchandise.conversions_config") ||
                                    "Tỉ lệ quy đổi đơn vị"
                                }}
                            </h4>
                            <v-card variant="flat" class="pa-4 border mb-4">
                                <div class="d-flex flex-column ga-2">
                                    <v-row
                                        v-for="(conv, cIndex) in prov.conversions"
                                        :key="cIndex"
                                        class="align-center pr-8 pt-2"
                                    >
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
                                                    onChangeProviders(
                                                        fieldProviders.value,
                                                    )
                                                "
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="3">
                                            <div class="mb-2">
                                                {{ $t("field.unit") }}
                                            </div>
                                            <v-select
                                                v-model="conv.fromUnitId"
                                                :items="unitOptions"
                                                item-title="label"
                                                item-value="value"
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                readonly
                                                :menu-icon="null"
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
                                                    onChangeProviders(
                                                        fieldProviders.value,
                                                    )
                                                "
                                            />
                                        </v-col>
                                        <v-col cols="12" sm="3">
                                            <div class="mb-2">
                                                {{ $t("field.unit") }}
                                            </div>
                                            <v-select
                                                v-model="conv.toUnitId"
                                                :items="unitOptions"
                                                item-title="label"
                                                item-value="value"
                                                variant="outlined"
                                                density="compact"
                                                hide-details
                                                readonly
                                                :menu-icon="null"
                                            />
                                        </v-col>
                                    </v-row>
                                </div>
                            </v-card>
                        </div>

                        <!-- Thiết lập Giá mặc định theo từng đơn vị -->
                        <div
                            v-if="
                                prov.providerId && prov.prices && prov.prices.length > 0
                            "
                            class="mt-6"
                        >
                            <h4 class="text-subtitle-1 font-weight-bold mb-4">
                                {{
                                    $t("merchandise.default_prices_config") ||
                                    "Thiết lập Giá mặc định theo từng đơn vị"
                                }}
                            </h4>
                            <div class="v-table v-table--density-compact border rounded-lg mt-2 overflow-x-auto">
                                <div class="v-table__wrapper">
                                    <table style="table-layout: fixed; width: 100%; border-collapse: collapse;">
                                        <colgroup>
                                            <col style="width: 100px;" />
                                            <col style="width: 140px;" />
                                            <col style="width: 90px;" />
                                            <col style="width: 140px;" />
                                            <col style="width: 140px;" />
                                            <col style="width: 180px;" />
                                            <col style="width: 180px;" />
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th class="text-left font-weight-bold px-4 py-3 border-bottom">
                                                    {{ $t("merchandise.prices.unit") || "Đơn vị" }}
                                                </th>
                                                <th class="text-left font-weight-bold px-4 py-3 border-bottom">
                                                    {{ $t("merchandise.prices.original_price") || "Giá gốc" }} ({{ currency }})
                                                </th>
                                                <th class="text-left font-weight-bold px-4 py-3 border-bottom">
                                                    {{ $t("merchandise.prices.discount_rate") || "% Giảm" }}
                                                </th>
                                                <th class="text-left font-weight-bold px-4 py-3 border-bottom">
                                                    {{ $t("merchandise.prices.discount_amount") || "Tiền giảm" }} ({{ currency }})
                                                </th>
                                                <th class="text-left font-weight-bold px-4 py-3 border-bottom">
                                                    {{ $t("merchandise.prices.price_after_discount") || "Giá sau giảm" }} ({{ currency }})
                                                </th>
                                                <th class="text-left font-weight-bold px-4 py-3 border-bottom">
                                                    {{ $t("merchandise.prices.effective_from") || "Hiệu lực từ" }}
                                                </th>
                                                <th class="text-left font-weight-bold px-4 py-3 border-bottom">
                                                    {{ $t("merchandise.prices.effective_to") || "Hiệu lực đến" }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(priceItem, pIndex) in getSortedPrices(prov.prices)"
                                                :key="pIndex"
                                                class="border-bottom"
                                            >
                                                <!-- Cột 1: Đơn vị tính -->
                                                <td class="font-weight-bold text-capitalize px-4 py-2">
                                                    {{ getPriceLabel(priceItem.unitId) }}
                                                </td>

                                                <!-- Cột 2: Giá gốc -->
                                                <td class="px-2 py-2">
                                                    <v-text-field
                                                        v-model.number="priceItem.price"
                                                        type="number"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        @update:model-value="
                                                            calculatePriceAfterDiscount(
                                                                priceItem,
                                                                fieldProviders.value,
                                                                onChangeProviders,
                                                            )
                                                        "
                                                    />
                                                </td>

                                                <!-- Cột 3: % Giảm -->
                                                <td class="px-2 py-2">
                                                    <v-text-field
                                                        v-model.number="priceItem.discountRate"
                                                        type="number"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        @update:model-value="
                                                            calculatePriceAfterDiscount(
                                                                priceItem,
                                                                fieldProviders.value,
                                                                onChangeProviders,
                                                            )
                                                        "
                                                    />
                                                </td>

                                                <!-- Cột 4: Tiền giảm -->
                                                <td class="px-2 py-2">
                                                    <v-text-field
                                                        v-model.number="priceItem.discountAmount"
                                                        type="number"
                                                        variant="outlined"
                                                        density="compact"
                                                        hide-details
                                                        @update:model-value="
                                                            calculatePriceAfterDiscount(
                                                                priceItem,
                                                                fieldProviders.value,
                                                                onChangeProviders,
                                                            )
                                                        "
                                                    />
                                                </td>

                                                <!-- Cột 5: Giá sau giảm (Readonly) -->
                                                <td class="px-2 py-2">
                                                    <v-text-field
                                                        v-model="priceItem.priceAfterDiscount"
                                                        type="number"
                                                        variant="outlined"
                                                        density="compact"
                                                        readonly
                                                        disabled
                                                        hide-details
                                                        class="bg-grey-lighten-4"
                                                    />
                                                </td>

                                                <!-- Cột 6: Hiệu lực từ -->
                                                <td class="px-2 py-2">
                                                    <DatePicker
                                                        v-model="priceItem.effectiveFrom"
                                                        density="compact"
                                                        variant="outlined"
                                                        :clearable="true"
                                                        hide-details
                                                        @update:model-value="
                                                            onChangeProviders(fieldProviders.value)
                                                        "
                                                    />
                                                </td>

                                                <!-- Cột 7: Hiệu lực đến -->
                                                <td class="px-2 py-2">
                                                    <DatePicker
                                                        v-model="priceItem.effectiveTo"
                                                        density="compact"
                                                        variant="outlined"
                                                        :clearable="true"
                                                        hide-details
                                                        @update:model-value="
                                                            onChangeProviders(fieldProviders.value)
                                                        "
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </v-expansion-panel-text>
                </v-expansion-panel>
            </v-expansion-panels>
        </div>

        <div
            v-else
            class="d-flex flex-column align-center justify-center border rounded-lg py-12 px-4 text-grey bg-grey-lighten-5"
        >
            <v-icon icon="mdi-truck-outline" size="48" class="mb-2" />
            <span>
                {{
                    $t("merchandise.no_providers") ||
                    "Chưa có cấu hình nhà cung cấp nào."
                }}
            </span>
        </div>
    </VeeField>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { mapActions, mapGetters } from "vuex";
import DatePicker from "@/components/DatePicker.vue";

export default {
    name: "FormProviderInfo",
    components: {
        VeeField,
        DatePicker,
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
    computed: {
        ...mapGetters("provider", {
            providerOptions: "options",
        }),
        ...mapGetters("unit", {
            unitOptions: "options",
        }),
        ...mapGetters("generalSettings", ["currency"]),
    },
    watch: {
        "formValues.conversions": {
            handler() {
                this.syncAllProvidersData();
            },
            deep: true,
        },
        "formValues.baseUnitId": {
            handler() {
                this.syncAllProvidersData();
            },
        },
    },
    mounted() {
        this.fetchProviderOptions();
        this.fetchUnitOptions();
        this.fetchGeneralSettings();
    },
    methods: {
        ...mapActions("provider", {
            fetchProviderOptions: "fetchOptions",
        }),
        ...mapActions("unit", {
            fetchUnitOptions: "fetchOptions",
        }),
        ...mapActions("generalSettings", {
            fetchGeneralSettings: "fetchSettings",
        }),

        addProvider(providers, onChange) {
            const list = providers ? [...providers] : [];
            list.push({
                providerId: null,
                unitConfigMode: "custom",
                defaultPurchaseUnitId: null,
                conversions: [],
                prices: [],
            });
            onChange(list);
        },

        removeProvider(providers, onChange, index) {
            const list = [...providers];
            list.splice(index, 1);
            onChange(list);
        },

        filteredProviders(index, providers) {
            const selectedProviderIds = providers
                .filter((p, i) => i !== index && p.providerId)
                .map((p) => p.providerId);
            return this.providerOptions.filter(
                (opt) => !selectedProviderIds.includes(opt.value),
            );
        },

        getProviderTitle(providerId, index) {
            if (!providerId) {
                return this.$t("merchandise.provider_title_unselected", {
                    index: index + 1,
                });
            }
            const provider = this.providerOptions.find(
                (p) => p.value === providerId,
            );
            return provider
                ? provider.label
                : this.$t("merchandise.provider_title_default", {
                      index: index + 1,
                  });
        },

        getConfiguredUnits() {
            const units = [];
            if (this.formValues.baseUnitId) {
                const baseUnitObj = this.unitOptions.find(
                    (u) => u.value === this.formValues.baseUnitId,
                );
                if (baseUnitObj) {
                    units.push(baseUnitObj);
                }
            }
            const conversions = this.formValues.conversions || [];
            conversions.forEach((c) => {
                if (c.fromUnitId) {
                    const fromUnitObj = this.unitOptions.find(
                        (u) => u.value === c.fromUnitId,
                    );
                    if (
                        fromUnitObj &&
                        !units.some((u) => u.value === c.fromUnitId)
                    ) {
                        units.push(fromUnitObj);
                    }
                }
                if (c.toUnitId) {
                    const toUnitObj = this.unitOptions.find(
                        (u) => u.value === c.toUnitId,
                    );
                    if (
                        toUnitObj &&
                        !units.some((u) => u.value === c.toUnitId)
                    ) {
                        units.push(toUnitObj);
                    }
                }
            });
            return units;
        },

        getSortedConfiguredUnits() {
            const units = this.getConfiguredUnits();
            const conversions = this.formValues.conversions || [];

            const adj = {};
            conversions.forEach((c) => {
                const fromId = c.fromUnitId;
                const toId = c.toUnitId;
                const fromVal = parseFloat(c.fromValue) || 1.0;
                const toVal = parseFloat(c.toValue) || 1.0;
                if (fromId && toId && fromVal > 0 && toVal > 0) {
                    const ratio = toVal / fromVal;
                    if (!adj[fromId]) adj[fromId] = [];
                    if (!adj[toId]) adj[toId] = [];
                    adj[fromId].push({
                        node: toId,
                        ratio: ratio,
                        direction: "forward",
                    });
                    adj[toId].push({
                        node: fromId,
                        ratio: ratio,
                        direction: "backward",
                    });
                }
            });

            const baseUnitId = this.formValues.baseUnitId;
            const factors = { [baseUnitId]: 1.0 };
            const queue = [baseUnitId];
            const visited = { [baseUnitId]: true };

            while (queue.length > 0) {
                const u = queue.shift();
                const uFactor = factors[u];
                const edges = adj[u] || [];
                edges.forEach((edge) => {
                    const v = edge.node;
                    if (!visited[v]) {
                        visited[v] = true;
                        if (edge.direction === "forward") {
                            factors[v] = uFactor / edge.ratio;
                        } else {
                            factors[v] = edge.ratio * uFactor;
                        }
                        queue.push(v);
                    }
                });
            }

            return units.sort((a, b) => {
                const factorA = factors[a.value] || 1.0;
                const factorB = factors[b.value] || 1.0;
                return factorB - factorA;
            });
        },

        getSortedPrices(prices) {
            if (!prices) return [];
            const sortedUnits = this.getSortedConfiguredUnits();
            const sorted = [];
            sortedUnits.forEach((unit) => {
                const priceItem = prices.find((p) => p.unitId === unit.value);
                if (priceItem) {
                    const price = parseFloat(priceItem.price) || 0;
                    const rate = parseFloat(priceItem.discountRate) || 0;
                    const amount = parseFloat(priceItem.discountAmount) || 0;
                    const discountFromRate = (price * rate) / 100;
                    const finalPrice = Math.max(0, price - discountFromRate - amount);
                    priceItem.priceAfterDiscount = finalPrice.toFixed(2);
                    sorted.push(priceItem);
                }
            });
            return sorted;
        },

        calculatePriceAfterDiscount(priceItem, providers, onChange) {
            const price = parseFloat(priceItem.price) || 0;
            const rate = parseFloat(priceItem.discountRate) || 0;
            const amount = parseFloat(priceItem.discountAmount) || 0;
            const discountFromRate = (price * rate) / 100;
            const finalPrice = Math.max(0, price - discountFromRate - amount);
            priceItem.priceAfterDiscount = finalPrice.toFixed(2);
            onChange(providers);
        },

        getPriceLabel(unitId) {
            const unit = this.unitOptions.find((u) => u.value === unitId);
            const name = unit ? unit.label : "";
            return `${name}`;
        },

        onProviderSelected(providers, onChange, index) {
            const providerItem = providers[index];
            if (!providerItem.providerId) {
                providerItem.conversions = [];
                providerItem.prices = [];
                providerItem.defaultPurchaseUnitId = null;
                onChange(providers);
                return;
            }

            const baseConversions = this.formValues.conversions || [];
            providerItem.conversions = baseConversions.map((bc) => ({
                fromUnitId: bc.fromUnitId,
                fromValue: bc.fromValue,
                toUnitId: bc.toUnitId,
                toValue: bc.toValue,
            }));

            const configuredUnits = this.getConfiguredUnits();
            providerItem.prices = configuredUnits.map((unit) => {
                const oldPrice = (providerItem.prices || []).find(
                    (p) => p.unitId === unit.value,
                );
                return {
                    unitId: unit.value,
                    price: oldPrice ? oldPrice.price : null,
                    discountRate: oldPrice ? oldPrice.discountRate : "0.00",
                    discountAmount: oldPrice ? oldPrice.discountAmount : "0.00",
                    priceAfterDiscount: oldPrice ? oldPrice.priceAfterDiscount : null,
                    effectiveFrom: oldPrice ? oldPrice.effectiveFrom : null,
                    effectiveTo: oldPrice ? oldPrice.effectiveTo : null,
                };
            });

            providerItem.defaultPurchaseUnitId =
                providerItem.defaultPurchaseUnitId ||
                this.formValues.baseUnitId;

            onChange(providers);
        },

        syncAllProvidersData() {
            const formRef = this.getParentForm();
            if (!formRef) {
                return;
            }

            const providers = formRef.values.providers || [];
            if (providers.length === 0) {
                return;
            }

            const updatedProviders = providers.map((prov) => {
                if (!prov.providerId) {
                    return prov;
                }

                const baseConversions = this.formValues.conversions || [];
                const updatedConversions = baseConversions.map((bc) => {
                    const match = (prov.conversions || []).find(
                        (oldC) =>
                            oldC.fromUnitId === bc.fromUnitId &&
                            oldC.toUnitId === bc.toUnitId,
                    );
                    return {
                        fromUnitId: bc.fromUnitId,
                        fromValue: match ? match.fromValue : bc.fromValue,
                        toUnitId: bc.toUnitId,
                        toValue: match ? match.toValue : bc.toValue,
                    };
                });

                const configuredUnits = this.getConfiguredUnits();
                const updatedPrices = configuredUnits.map((unit) => {
                    const match = (prov.prices || []).find(
                        (p) => p.unitId === unit.value,
                    );
                    return {
                        unitId: unit.value,
                        price: match ? match.price : null,
                        discountRate: match ? match.discountRate : "0.00",
                        discountAmount: match ? match.discountAmount : "0.00",
                        priceAfterDiscount: match ? match.priceAfterDiscount : null,
                        effectiveFrom: match ? match.effectiveFrom : null,
                        effectiveTo: match ? match.effectiveTo : null,
                    };
                });

                let defaultPurchaseUnitId = prov.defaultPurchaseUnitId;
                if (
                    !configuredUnits.some(
                        (u) => u.value === defaultPurchaseUnitId,
                    )
                ) {
                    defaultPurchaseUnitId = this.formValues.baseUnitId;
                }

                return {
                    ...prov,
                    conversions: updatedConversions,
                    prices: updatedPrices,
                    defaultPurchaseUnitId,
                };
            });

            formRef.setFieldValue("providers", updatedProviders);
        },

        getParentForm() {
            let parent = this.$parent;
            while (parent) {
                if (parent.$options.name === "FormIngredient") {
                    return parent.$refs.formRef;
                }
                parent = parent.$parent;
            }
            return null;
        },
    },
};
</script>

<style scoped></style>
