<template>
    <v-card-text class="pa-4">
        <v-row dense>
            <v-col cols="12" md="6">
                <v-sheet border rounded="lg" class="pa-4 h-100 bg-grey-lighten-5">
                    <div class="text-caption text-medium-emphasis">
                        {{ $t("request.stock_transfer.source_warehouse") }}
                    </div>
                    <div class="font-weight-bold mt-1">
                        {{ warehouseLabel(payload.sourceWarehouse) }}
                    </div>
                    <div class="text-caption">
                        {{ payload.sourceWarehouse?.branch?.name || "--" }}
                    </div>
                </v-sheet>
            </v-col>
            <v-col cols="12" md="6">
                <v-sheet border rounded="lg" class="pa-4 h-100 bg-grey-lighten-5">
                    <div class="text-caption text-medium-emphasis">
                        {{ $t("request.stock_transfer.destination_warehouse") }}
                    </div>
                    <div class="font-weight-bold mt-1">
                        {{ warehouseLabel(payload.destinationWarehouse) }}
                    </div>
                    <div class="text-caption">
                        {{ payload.destinationWarehouse?.branch?.name || "--" }}
                    </div>
                </v-sheet>
            </v-col>
        </v-row>

        <div class="text-subtitle-1 font-weight-bold mt-5 mb-3">
            {{ $t("request.stock_transfer.items_title") }}
        </div>
        <v-table density="compact" class="border rounded-lg">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ $t("field.stock_in_merchandise") }}</th>
                    <th>{{ $t("request.stock_transfer.lot") }}</th>
                    <th class="text-right">{{ $t("field.quantity") }}</th>
                    <th>{{ $t("field.unit") }}</th>
                    <th>{{ $t("request.stock_transfer.expiry_date") }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in payload.items || []" :key="row.lineId">
                    <td>{{ index + 1 }}</td>
                    <td>
                        [{{ row.merchandise?.code || "--" }}]
                        {{ row.merchandise?.name || "--" }}
                    </td>
                    <td>{{ row.lot?.internalCode || "--" }}</td>
                    <td class="text-right font-weight-bold">
                        {{ formatQuantity(row.quantity) }}
                    </td>
                    <td>{{ row.baseUnit?.name || "--" }}</td>
                    <td>{{ row.lot?.expiryDate || "--" }}</td>
                </tr>
            </tbody>
        </v-table>

        <v-row class="mt-4">
            <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis">
                    {{ $t("request.stock_transfer.reason") }}
                </div>
                <div class="mt-1">{{ payload.reason || "--" }}</div>
            </v-col>
            <v-col cols="12" md="6">
                <div class="text-caption text-medium-emphasis">
                    {{ $t("field.ghi_chu") }}
                </div>
                <div class="mt-1">{{ payload.note || "--" }}</div>
            </v-col>
        </v-row>
    </v-card-text>
</template>

<script>
export default {
    name: "RequestStockTransferDetailFields",
    props: {
        item: {
            type: Object,
            default: null,
        },
    },
    computed: {
        payload() {
            return this.item?.payload ?? {};
        },
    },
    methods: {
        warehouseLabel(warehouse) {
            return warehouse
                ? `[${warehouse.code || "--"}] ${warehouse.name || "--"}`
                : "--";
        },
        formatQuantity(value) {
            return Number(value || 0).toLocaleString("vi-VN", {
                maximumFractionDigits: 6,
            });
        },
    },
};
</script>
