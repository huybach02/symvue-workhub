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

            <!-- Danh sách nhà cung cấp -->
            <v-col cols="12" class="mt-4">
                <div class="text-subtitle-1 font-weight-bold mb-4 d-flex align-center ga-2">
                    <v-icon icon="mdi-truck-outline" color="primary" />
                    {{ $t("request.stock_in.providers_title") }}
                </div>

                <div
                    v-if="!providers.length"
                    class="text-medium-emphasis py-6 text-center border rounded-lg"
                >
                    {{ $t("request.stock_in.empty_items") || "Không có nhà cung cấp" }}
                </div>

                <v-card
                    v-for="(providerGroup, providerIndex) in providers"
                    :key="providerIndex"
                    variant="flat"
                    border
                    class="mb-6 overflow-hidden"
                >
                    <v-card-title class="bg-grey-lighten-4 py-3 px-4 d-flex align-center justify-space-between flex-wrap ga-2">
                        <div class="d-flex align-center ga-2">
                            <v-chip size="small" color="primary" variant="flat">
                                {{ providerIndex + 1 }}
                            </v-chip>
                            <span class="text-subtitle-1 font-weight-bold text-grey-darken-3">
                                {{
                                    providerGroup.providerName ||
                                    providerGroup.providerId ||
                                    "--"
                                }}
                            </span>
                        </div>
                        <div class="text-subtitle-2 font-weight-bold text-primary">
                            {{ $t("field.total_amount") }}: {{ getProviderTotal(providerGroup) }}
                        </div>
                    </v-card-title>

                    <v-divider />

                    <v-table density="comfortable">
                        <thead>
                            <tr class="bg-grey-lighten-5">
                                <th class="text-center font-weight-bold py-3" style="width: 60px">
                                    {{ $t("field.stt") }}
                                </th>
                                <th class="text-left font-weight-bold py-3">
                                    {{ $t("field.stock_in_merchandise") }}
                                </th>
                                <th class="text-right font-weight-bold py-3" style="width: 110px">
                                    {{ $t("field.quantity") }}
                                </th>
                                <th class="text-center font-weight-bold py-3" style="width: 110px">
                                    {{ $t("field.unit") }}
                                </th>
                                <th class="text-right font-weight-bold py-3" style="width: 160px">
                                    {{ $t("field.import_price") }}
                                </th>
                                <th class="text-right font-weight-bold py-3" style="width: 180px">
                                    {{ $t("field.total_amount") }}
                                </th>
                                <th class="text-left font-weight-bold py-3" style="width: 200px">
                                    {{ $t("field.ghi_chu") }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, itemIndex) in providerGroup.items || []"
                                :key="itemIndex"
                            >
                                <td class="text-center py-2 text-grey-darken-1">
                                    {{ itemIndex + 1 }}
                                </td>
                                <td class="py-2 font-weight-medium text-grey-darken-4">
                                    {{ row.merchandiseName || row.merchandiseId || "--" }}
                                </td>
                                <td class="text-right py-2 font-weight-bold text-grey-darken-3">
                                    {{ formatNumber(row.quantity) }}
                                </td>
                                <td class="text-center py-2">
                                    <v-chip size="small" variant="tonal" color="secondary">
                                        {{ row.unitName || row.unitId || "--" }}
                                    </v-chip>
                                </td>
                                <td class="text-right py-2 text-grey-darken-3">
                                    {{ formatNumber(row.price) }} <span class="text-caption text-medium-emphasis">{{ row.currency || "VND" }}</span>
                                </td>
                                <td class="text-right py-2 font-weight-bold text-primary">
                                    {{ formatNumber((row.quantity || 0) * (row.price || 0)) }} <span class="text-caption">{{ row.currency || "VND" }}</span>
                                </td>
                                <td class="py-2 text-body-2 text-medium-emphasis">
                                    {{ row.note || "--" }}
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>

                <!-- Tổng cộng của tất cả nhà cung cấp -->
                <v-card variant="flat" border class="pa-4 bg-grey-lighten-5 mt-4" v-if="providers.length">
                    <div class="d-flex justify-space-between align-center flex-wrap ga-2">
                        <div class="text-subtitle-1 font-weight-bold text-grey-darken-3 d-flex align-center ga-2">
                            <v-icon icon="mdi-calculator" color="primary" />
                            <span>{{ $t("field.grand_total") }}</span>
                        </div>
                        <div class="d-flex ga-4 flex-wrap">
                            <div
                                v-for="total in getGrandTotals()"
                                :key="total.currency"
                                class="text-h6 font-weight-black text-primary"
                            >
                                {{ total.amount }} {{ total.currency }}
                            </div>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>
    </v-card-text>
</template>

<script>
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "RequestStockInDetailFields",
    props: {
        item: {
            type: Object,
            default: null,
        },
    },
    computed: {
        providers() {
            return Array.isArray(this.item?.payload?.providers)
                ? this.item.payload.providers
                : [];
        },
    },
    methods: {
        formatNumber(value) {
            if (value == null) {
                return "--";
            }
            return functionHelper.formatNumber(Number(value) || 0);
        },
        getProviderTotal(providerGroup) {
            let total = 0;
            let currency = "VND";
            if (Array.isArray(providerGroup.items)) {
                providerGroup.items.forEach((item) => {
                    total += (Number(item.quantity) || 0) * (Number(item.price) || 0);
                    if (item.currency) {
                        currency = item.currency;
                    }
                });
            }
            return this.formatNumber(total) + " " + currency;
        },
        getGrandTotals() {
            const totals = {};
            this.providers.forEach((providerGroup) => {
                if (Array.isArray(providerGroup.items)) {
                    providerGroup.items.forEach((item) => {
                        const currency = item.currency || "VND";
                        const amount = (Number(item.quantity) || 0) * (Number(item.price) || 0);
                        totals[currency] = (totals[currency] || 0) + amount;
                    });
                }
            });
            return Object.entries(totals).map(([currency, amount]) => ({
                currency,
                amount: this.formatNumber(amount),
            }));
        },
    },
};
</script>
