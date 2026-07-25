<template>
    <v-dialog v-model="dialog" max-width="1000" scrollable>
            <v-card>
                <v-card-title
                    class="d-flex align-start justify-space-between py-3 px-4 bg-grey-lighten-4 ga-2"
                >
                    <div
                        class="d-flex align-start ga-2 text-wrap pr-2"
                        style="word-break: break-word;"
                    >
                        <v-icon
                            icon="mdi-history"
                            color="info"
                            class="mt-1 flex-shrink-0"
                        />
                        <span
                            class="font-weight-bold text-h6 text-grey-darken-3 text-wrap"
                            style="word-break: break-word;"
                        >
                            {{
                                $t("stock_receipt.event.title") ||
                                "Lịch sử thao tác"
                            }}:
                            {{ item?.code }}
                        </span>
                    </div>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        size="small"
                        class="flex-shrink-0 align-self-start"
                        @click="dialog = false"
                    />
                </v-card-title>

                <v-divider />

                <v-card-text class="px-10">
                    <div
                        v-if="loading"
                        class="d-flex justify-center align-center py-8"
                    >
                        <v-progress-circular indeterminate color="info" />
                    </div>

                    <div
                        v-else-if="!events || events.length === 0"
                        class="text-center text-grey py-8"
                    >
                        <v-icon
                            icon="mdi-history-off"
                            size="48"
                            color="grey-lighten-1"
                            class="mb-2"
                        />
                        <div>
                            {{
                                $t("stock_receipt.event.no_events") ||
                                "Chưa có lịch sử thao tác"
                            }}
                        </div>
                    </div>

                    <v-timeline
                        v-else
                        density="compact"
                        side="end"
                        align="start"
                    >
                        <v-timeline-item
                            v-for="evt in events"
                            :key="evt.id"
                            :dot-color="getEventColor(evt.event_type)"
                            :icon="getEventIcon(evt.event_type)"
                            size="small"
                        >
                            <v-card variant="flat" border class="pa-3">
                                <div
                                    class="d-flex align-center justify-space-between flex-wrap ga-2 mb-1"
                                >
                                    <span
                                        class="font-weight-bold text-subtitle-2 text-grey-darken-3"
                                    >
                                        {{ getEventTitle(evt) }}
                                    </span>
                                    <span
                                        class="text-caption text-grey-darken-1"
                                    >
                                        {{ evt.created_at }}
                                    </span>
                                </div>

                                <div
                                    v-if="evt.comment"
                                    class="text-body-2 text-grey-darken-2 mb-2"
                                >
                                    {{ evt.comment }}
                                </div>

                                <div
                                    v-if="evt.from_status || evt.to_status"
                                    class="d-flex align-center ga-2 mb-2"
                                >
                                    <v-chip
                                        v-if="evt.from_status"
                                        size="x-small"
                                        variant="tonal"
                                        color="grey"
                                    >
                                        {{ evt.from_status }}
                                    </v-chip>
                                    <v-icon
                                        v-if="evt.from_status && evt.to_status"
                                        icon="mdi-arrow-right"
                                        size="14"
                                        color="grey"
                                    />
                                    <v-chip
                                        v-if="evt.to_status"
                                        size="x-small"
                                        variant="flat"
                                        :color="getEventColor(evt.event_type)"
                                    >
                                        {{ evt.to_status }}
                                    </v-chip>
                                </div>

                                <div
                                    v-if="evt.actor || evt.actor_type"
                                    class="text-caption text-grey-darken-1 d-flex align-center"
                                >
                                    <v-icon
                                        icon="mdi-account-outline"
                                        size="14"
                                        class="mr-1"
                                    />
                                    <span>
                                        {{
                                            evt.actor?.name ||
                                            (evt.actor_type === "SYSTEM"
                                                ? "Hệ thống"
                                                : evt.actor_type || "N/A")
                                        }}
                                    </span>
                                </div>
                            </v-card>
                        </v-timeline-item>
                    </v-timeline>
                </v-card-text>
            </v-card>
        </v-dialog>
</template>

<script>
import { mapActions } from "vuex";
import { constant } from "@/utils/constants/constant";

export default {
    name: "StockReceiptEventDialog",
    props: {
        item: {
            type: Object,
            required: true,
        },
        modelValue: {
            type: Boolean,
            default: undefined,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            internalDialog: false,
            loading: false,
            events: [],
        };
    },
    computed: {
        dialog: {
            get() {
                return this.modelValue !== undefined
                    ? this.modelValue
                    : this.internalDialog;
            },
            set(val) {
                this.internalDialog = val;
                this.$emit("update:modelValue", val);
            },
        },
    },
    watch: {
        dialog: {
            immediate: true,
            async handler(isOpen) {
                if (isOpen && this.item?.id) {
                    this.loadData();
                }
            },
        },
        item: {
            immediate: true,
            async handler(newItem) {
                if (this.dialog && newItem?.id) {
                    this.loadData();
                }
            },
        },
    },
    methods: {
        ...mapActions("stockReceipt", ["fetchItemDetail"]),
        async loadData() {
            if (!this.item?.id) return;
            this.loading = true;
            try {
                const detail = await this.fetchItemDetail({
                    id: this.item.id,
                    force: true,
                });
                this.events = detail?.events || [];
            } finally {
                this.loading = false;
            }
        },
        getEventColor(type) {
            return constant.STOCK_RECEIPT_EVENT_COLORS[type] || "primary";
        },
        getEventIcon(type) {
            return (
                constant.STOCK_RECEIPT_EVENT_ICONS[type] ||
                "mdi-information-outline"
            );
        },
        getEventTitle(evt) {
            const key = constant.STOCK_RECEIPT_EVENT_TITLE_KEYS[evt.event_type];
            if (key) {
                return this.$t(key);
            }
            return evt.event_type || "Sự kiện";
        },
    },
};
</script>
