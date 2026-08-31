<template>
    <v-dialog v-model="dialog" max-width="1600" scrollable>
        <v-card>
            <v-card-title
                class="d-flex align-center justify-space-between bg-grey-lighten-4"
            >
                <div class="d-flex align-center ga-2">
                    <v-icon icon="mdi-warehouse" color="info" />
                    <span>
                        {{ $t("warehouse.inventory.title") }}:
                        {{ warehouseLabel }}
                    </span>
                </div>
                <v-btn
                    icon="mdi-close"
                    variant="text"
                    size="small"
                    @click="dialog = false"
                />
            </v-card-title>

            <v-divider />

            <v-card-text>
                <v-tabs v-model="activeTab" color="primary" class="mb-4">
                    <v-tab value="balance">
                        {{ $t("warehouse.inventory.balance_tab") }}
                    </v-tab>
                    <v-tab value="movement">
                        {{ $t("warehouse.inventory.movement_tab") }}
                    </v-tab>
                </v-tabs>

                <v-divider />

                <v-window v-model="activeTab" :touch="false">
                    <v-window-item value="balance">
                        <WarehouseInventoryBalanceTable
                            :warehouse-id="warehouse?.id"
                            :active="dialog && activeTab === 'balance'"
                        />
                    </v-window-item>

                    <v-window-item value="movement">
                        <WarehouseInventoryMovementTable
                            :warehouse-id="warehouse?.id"
                            :active="dialog && activeTab === 'movement'"
                        />
                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import WarehouseInventoryBalanceTable from "./WarehouseInventoryBalanceTable.vue";
import WarehouseInventoryMovementTable from "./WarehouseInventoryMovementTable.vue";

export default {
    name: "WarehouseInventoryDialog",
    components: {
        WarehouseInventoryBalanceTable,
        WarehouseInventoryMovementTable,
    },
    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },
        warehouse: {
            type: Object,
            default: null,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            activeTab: "balance",
        };
    },
    computed: {
        dialog: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.$emit("update:modelValue", value);
            },
        },
        warehouseLabel() {
            return [this.warehouse?.code, this.warehouse?.name]
                .filter(Boolean)
                .join(" - ") || "--";
        },
    },
    watch: {
        dialog(isOpen) {
            if (isOpen) {
                this.activeTab = "balance";
            }
        },
    },
};
</script>
