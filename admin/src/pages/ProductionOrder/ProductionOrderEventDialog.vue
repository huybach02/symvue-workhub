<template>
    <v-dialog v-model="dialog" max-width="1000" scrollable>
        <v-card>
            <v-card-title
                class="d-flex align-start justify-space-between py-3 px-4 bg-grey-lighten-4 ga-2"
            >
                <div
                    class="d-flex align-start ga-2 text-wrap pr-2"
                    style="word-break: break-word"
                >
                    <v-icon
                        icon="mdi-history"
                        color="info"
                        class="mt-1 flex-shrink-0"
                    />
                    <span
                        class="font-weight-bold text-h6 text-grey-darken-3 text-wrap"
                    >
                        {{ $t("production_order.event.title") }}:
                        {{ item?.code || "--" }}
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
                <div v-if="loading" class="d-flex justify-center py-8">
                    <v-progress-circular indeterminate color="info" />
                </div>

                <div
                    v-else-if="!events.length"
                    class="text-center text-grey py-8"
                >
                    <v-icon
                        icon="mdi-history-off"
                        size="48"
                        color="grey-lighten-1"
                        class="mb-2"
                    />
                    <div>{{ $t("production_order.event.no_events") }}</div>
                </div>

                <v-timeline
                    v-else
                    density="compact"
                    side="end"
                    align="start"
                >
                    <v-timeline-item
                        v-for="event in events"
                        :key="event.id"
                        :dot-color="getEventColor(event.eventType)"
                        :icon="getEventIcon(event.eventType)"
                        size="small"
                    >
                        <v-card variant="flat" border class="pa-3">
                            <div
                                class="d-flex align-center justify-space-between flex-wrap ga-2 mb-1"
                            >
                                <span
                                    class="font-weight-bold text-subtitle-2 text-grey-darken-3"
                                >
                                    {{ getEventTitle(event) }}
                                </span>
                                <span class="text-caption text-grey-darken-1">
                                    {{ event.createdAt || "--" }}
                                </span>
                            </div>

                            <div
                                v-if="event.message"
                                class="text-body-2 text-grey-darken-2 mb-2"
                            >
                                {{ event.message }}
                            </div>

                            <div
                                v-if="
                                    event.fromStatus ||
                                    event.toStatus ||
                                    relatedItem(event)
                                "
                                class="d-flex align-center flex-wrap ga-2 mb-2"
                            >
                                <v-chip
                                    v-if="relatedItem(event)"
                                    size="x-small"
                                    variant="tonal"
                                    color="primary"
                                >
                                    {{
                                        productLabel(relatedItem(event))
                                    }}
                                </v-chip>
                                <v-chip
                                    v-if="event.fromStatus"
                                    size="x-small"
                                    variant="tonal"
                                    color="grey"
                                >
                                    {{ statusLabel(event.fromStatus) }}
                                </v-chip>
                                <v-icon
                                    v-if="event.fromStatus && event.toStatus"
                                    icon="mdi-arrow-right"
                                    size="14"
                                    color="grey"
                                />
                                <v-chip
                                    v-if="event.toStatus"
                                    size="x-small"
                                    variant="flat"
                                    :color="getEventColor(event.eventType)"
                                >
                                    {{ statusLabel(event.toStatus) }}
                                </v-chip>
                            </div>

                            <div
                                v-if="event.actor"
                                class="text-caption text-grey-darken-1 d-flex align-center"
                            >
                                <v-icon
                                    icon="mdi-account-outline"
                                    size="14"
                                    class="mr-1"
                                />
                                {{ event.actor.name || "N/A" }}
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

export default {
    name: "ProductionOrderEventDialog",
    props: {
        item: {
            type: Object,
            default: null,
        },
        modelValue: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["update:modelValue"],
    data() {
        return {
            internalDialog: false,
            loading: false,
            events: [],
            items: [],
        };
    },
    computed: {
        dialog: {
            get() {
                return this.modelValue;
            },
            set(value) {
                this.internalDialog = value;
                this.$emit("update:modelValue", value);
            },
        },
    },
    watch: {
        dialog: {
            immediate: true,
            handler(isOpen) {
                if (isOpen && this.item?.id) {
                    this.loadData();
                }
            },
        },
        item: {
            immediate: true,
            handler(newItem) {
                if (this.dialog && newItem?.id) {
                    this.loadData();
                }
            },
        },
    },
    methods: {
        ...mapActions("productionOrder", ["fetchItemDetail"]),
        async loadData() {
            if (!this.item?.id) {
                return;
            }

            this.loading = true;
            try {
                const detail = await this.fetchItemDetail({
                    id: this.item.id,
                    force: true,
                });
                this.events = detail?.events || [];
                this.items = detail?.items || [];
            } finally {
                this.loading = false;
            }
        },
        relatedItem(event) {
            return this.items.find(
                (item) => item.id === event.productionOrderItemId,
            );
        },
        productLabel(item) {
            const product = item?.finishedProduct || item?.productSnapshot;
            return product
                ? [product.code, product.name].filter(Boolean).join(" - ")
                : "--";
        },
        statusLabel(status) {
            return (
                this.$t("production_order.status." + status) ||
                status ||
                "--"
            );
        },
        getEventTitle(event) {
            return (
                this.$t("production_order.event.types." + event.eventType) ||
                event.eventType ||
                "Sự kiện"
            );
        },
        getEventColor(type) {
            return {
                CREATED: "primary",
                STATUS_CHANGED: "info",
                STARTED: "warning",
                COMPLETED: "success",
                CANCELLED: "error",
            }[type] || "primary";
        },
        getEventIcon(type) {
            return {
                CREATED: "mdi-plus-circle-outline",
                STATUS_CHANGED: "mdi-swap-horizontal",
                STARTED: "mdi-play-circle-outline",
                COMPLETED: "mdi-check-circle-outline",
                CANCELLED: "mdi-close-circle-outline",
            }[type] || "mdi-information-outline";
        },
    },
};
</script>
