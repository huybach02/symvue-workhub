<template>
    <v-dialog
        :model-value="open"
        fullscreen
        transition="slide-x-reverse-transition"
        @update:model-value="(value) => !value && $emit('close')"
    >
        <div class="d-flex justify-end h-100">
            <v-card width="1500" class="h-100 rounded-0">
                <v-toolbar color="transparent" class="px-4">
                    <div class="text-h6 font-weight-bold">
                        {{ selectedType?.title || "--" }}
                    </div>
                    <v-spacer />
                    <v-btn icon="mdi-close" variant="text" @click="$emit('close')" />
                </v-toolbar>

                <v-divider />

                <div class="d-flex align-center justify-space-between ga-3 px-6 py-4">
                    <v-chip color="primary" variant="tonal">
                        {{ mineTotal }} {{ $t("request.my_requests_short") }}
                    </v-chip>

                    <div class="d-flex align-center ga-2">
                        <v-btn
                            color="primary"
                            variant="text"
                            prepend-icon="mdi-refresh"
                            @click="$emit('refresh')"
                        >
                            {{ $t("button.update") }}
                        </v-btn>
                        <v-btn
                            v-if="permission?.create"
                            color="primary"
                            prepend-icon="mdi-plus"
                            @click="$emit('create')"
                        >
                            {{ $t("request.create_button") }}
                        </v-btn>
                    </div>
                </div>

                <v-divider />

                <div class="px-6 py-4">
                    <RequestTable
                        :items="mineItems"
                        :total-items="mineTotal"
                        :loading="loading"
                        :query="query"
                        :request-types="requestTypes"
                        :show-requester="false"
                        :show-type="false"
                        :show-status-filter="true"
                        @update:query="(value) => $emit('update:query', value)"
                        @reload="$emit('refresh')"
                        @show-detail="(requestId) => $emit('show-detail', requestId)"
                    />
                </div>
            </v-card>
        </div>
    </v-dialog>
</template>

<script>
import RequestTable from "./RequestTable.vue";

export default {
    name: "RequestDrawer",
    components: {
        RequestTable,
    },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        selectedType: {
            type: Object,
            default: null,
        },
        mineItems: {
            type: Array,
            default: () => [],
        },
        mineTotal: {
            type: Number,
            default: 0,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        permission: {
            type: Object,
            default: () => ({}),
        },
        query: {
            type: Object,
            required: true,
        },
        requestTypes: {
            type: Array,
            default: () => [],
        },
    },
    emits: [
        "close",
        "create",
        "refresh",
        "show-detail",
        "update:query",
    ],
};
</script>
