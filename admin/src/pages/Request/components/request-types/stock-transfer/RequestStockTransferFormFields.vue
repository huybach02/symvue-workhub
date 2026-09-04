<template>
    <div>
        <v-progress-linear
            v-if="contextLoading"
            indeterminate
            color="primary"
            class="mb-4"
        />

        <v-row>
            <v-col cols="12" md="6">
                <div class="mb-2 font-weight-medium">
                    {{ $t("request.stock_transfer.source_warehouse") }}
                </div>
                <v-sheet border rounded="lg" class="pa-3 bg-grey-lighten-5">
                    <div class="font-weight-bold">
                        {{ warehouseLabel(context.sourceWarehouse) }}
                    </div>
                    <div class="text-caption text-medium-emphasis">
                        {{ context.sourceWarehouse?.branch?.name || "--" }}
                    </div>
                </v-sheet>
            </v-col>

            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ value, errorMessage, handleChange }"
                    name="destinationWarehouseId"
                >
                    <div class="mb-2 font-weight-medium">
                        {{ $t("request.stock_transfer.destination_warehouse") }}
                        <span class="text-error">*</span>
                    </div>
                    <v-autocomplete
                        :model-value="value"
                        :items="destinationOptions"
                        item-title="label"
                        item-value="value"
                        variant="outlined"
                        density="compact"
                        :error-messages="errorMessage"
                        :loading="contextLoading"
                        @update:model-value="handleChange"
                    />
                </VeeField>
            </v-col>
        </v-row>

        <VeeField
            v-slot="{ value: items, errorMessage, handleChange }"
            name="items"
        >
            <div class="d-flex justify-space-between align-center mt-4 mb-3">
                <div class="text-subtitle-1 font-weight-bold">
                    {{ $t("request.stock_transfer.items_title") }}
                </div>
                <v-btn
                    color="primary"
                    size="small"
                    prepend-icon="mdi-plus"
                    @click="addItem(items, handleChange)"
                >
                    {{ $t("request.stock_transfer.add_item") }}
                </v-btn>
            </div>

            <v-alert
                v-if="errorMessage"
                type="error"
                variant="tonal"
                density="compact"
                class="mb-3"
            >
                {{ errorMessage }}
            </v-alert>

            <div
                v-if="!items?.length"
                class="text-center text-medium-emphasis py-8 border rounded-lg"
            >
                {{ $t("request.stock_transfer.empty_items") }}
            </div>

            <v-table v-else density="compact" class="border rounded-lg">
                <thead>
                    <tr>
                        <th style="width: 56px">#</th>
                        <th>{{ $t("request.stock_transfer.lot") }}</th>
                        <th style="width: 180px">{{ $t("field.quantity") }}</th>
                        <th style="width: 120px">{{ $t("field.unit") }}</th>
                        <th style="width: 64px" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in items" :key="row.lineId">
                        <td>{{ index + 1 }}</td>
                        <td class="py-2">
                            <v-autocomplete
                                :model-value="row.sourceBalanceId"
                                :items="balanceOptionsFor(items, index)"
                                item-title="label"
                                item-value="value"
                                variant="outlined"
                                density="compact"
                                hide-details
                                @update:model-value="
                                    updateItem(
                                        items,
                                        handleChange,
                                        index,
                                        'sourceBalanceId',
                                        $event,
                                    )
                                "
                            />
                        </td>
                        <td class="py-2">
                            <v-text-field
                                :model-value="row.quantity"
                                type="number"
                                min="0.000001"
                                step="0.000001"
                                variant="outlined"
                                density="compact"
                                hide-details
                                @update:model-value="
                                    updateItem(
                                        items,
                                        handleChange,
                                        index,
                                        'quantity',
                                        $event,
                                    )
                                "
                            />
                            <div
                                v-if="selectedBalance(row.sourceBalanceId)"
                                class="text-caption text-medium-emphasis mt-1"
                            >
                                {{ $t("request.stock_transfer.available") }}:
                                {{
                                    formatQuantity(
                                        selectedBalance(row.sourceBalanceId)
                                            ?.availableQuantity,
                                    )
                                }}
                            </div>
                        </td>
                        <td>
                            {{
                                selectedBalance(row.sourceBalanceId)?.baseUnit
                                    ?.name || "--"
                            }}
                        </td>
                        <td>
                            <v-btn
                                icon="mdi-delete"
                                color="error"
                                variant="text"
                                size="small"
                                @click="removeItem(items, handleChange, index)"
                            />
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </VeeField>

        <v-row class="mt-4">
            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ value, errorMessage, handleChange }"
                    name="reason"
                >
                    <div class="mb-2 font-weight-medium">
                        {{ $t("request.stock_transfer.reason") }}
                        <span class="text-error">*</span>
                    </div>
                    <v-textarea
                        :model-value="value"
                        rows="3"
                        variant="outlined"
                        :error-messages="errorMessage"
                        @update:model-value="handleChange"
                    />
                </VeeField>
            </v-col>
            <v-col cols="12" md="6">
                <VeeField
                    v-slot="{ value, errorMessage, handleChange }"
                    name="note"
                >
                    <div class="mb-2 font-weight-medium">
                        {{ $t("field.ghi_chu") }}
                    </div>
                    <v-textarea
                        :model-value="value"
                        rows="3"
                        variant="outlined"
                        :error-messages="errorMessage"
                        @update:model-value="handleChange"
                    />
                </VeeField>
            </v-col>
        </v-row>
    </div>
</template>

<script>
import { Field as VeeField } from "vee-validate";
import { mapGetters } from "vuex";

export default {
    name: "RequestStockTransferFormFields",
    components: {
        VeeField,
    },
    computed: {
        ...mapGetters("stockTransfer", ["context", "contextLoading"]),
        destinationOptions() {
            return (this.context.destinationWarehouses || []).map(
                (warehouse) => ({
                    value: warehouse.id,
                    label: this.warehouseLabel(warehouse),
                }),
            );
        },
        balanceOptions() {
            return (this.context.balances || []).map((balance) => ({
                value: balance.id,
                label: [
                    `[${balance.merchandise?.code || "--"}]`,
                    balance.merchandise?.name || "--",
                    `| ${balance.lot?.internalCode || "--"}`,
                    `| ${this.formatQuantity(balance.availableQuantity)} ${balance.baseUnit?.name || ""}`,
                ].join(" "),
            }));
        },
    },
    methods: {
        addItem(items, handleChange) {
            handleChange([
                ...(items || []),
                {
                    lineId: crypto.randomUUID(),
                    sourceBalanceId: null,
                    quantity: null,
                },
            ]);
        },
        removeItem(items, handleChange, index) {
            handleChange((items || []).filter((_, itemIndex) => itemIndex !== index));
        },
        updateItem(items, handleChange, index, field, value) {
            handleChange(
                (items || []).map((item, itemIndex) =>
                    itemIndex === index ? { ...item, [field]: value } : item,
                ),
            );
        },
        balanceOptionsFor(items, currentIndex) {
            const selectedIds = new Set(
                (items || [])
                    .filter((_, index) => index !== currentIndex)
                    .map((item) => item.sourceBalanceId)
                    .filter(Boolean),
            );

            return this.balanceOptions.filter(
                (option) => !selectedIds.has(option.value),
            );
        },
        selectedBalance(balanceId) {
            return (this.context.balances || []).find(
                (balance) => balance.id === balanceId,
            );
        },
        warehouseLabel(warehouse) {
            if (!warehouse) {
                return "--";
            }

            return `[${warehouse.code || "--"}] ${warehouse.name || "--"}`;
        },
        formatQuantity(value) {
            return Number(value || 0).toLocaleString("vi-VN", {
                maximumFractionDigits: 6,
            });
        },
    },
};
</script>
