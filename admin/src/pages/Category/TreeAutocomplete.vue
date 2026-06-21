<template>
    <v-autocomplete
        :search="search"
        :model-value="innerValue"
        :items="options"
        item-title="name"
        item-value="id"
        variant="outlined"
        clearable
        hide-details="auto"
        :label="label"
        :placeholder="placeholder"
        :error-messages="errorMessages"
        @update:search="search = $event"
        @update:model-value="onChange"
        @blur="$emit('blur')"
    >
        <template #item="{ props, item }">
            <v-list-item
                v-bind="props"
                :style="{ paddingLeft: `${16 + item.raw.level * 20}px` }"
            >
                <template #prepend>
                    <v-btn
                        v-if="item.raw.hasChildren"
                        :icon="
                            isExpanded(item.raw.id)
                                ? 'mdi-chevron-down'
                                : 'mdi-chevron-right'
                        "
                        variant="text"
                        density="compact"
                        @click.stop.prevent="toggle(item.raw.id)"
                    />
                    <span v-else class="tree-empty-toggle"></span>
                </template>
            </v-list-item>
        </template>

        <template #selection="{ item }">
            <span>{{ item.raw.name }}</span>
        </template>
    </v-autocomplete>
</template>

<script>
export default {
    name: "TreeAutocomplete",

    props: {
        modelValue: {
            type: [Number, String, null],
            default: null,
        },
        items: {
            type: Array,
            default: () => [],
        },
        label: {
            type: String,
            default: "",
        },
        placeholder: {
            type: String,
            default: "",
        },
        errorMessages: {
            type: [String, Array],
            default: "",
        },
        excludeId: {
            type: [Number, String, null],
            default: null,
        },
        rootLabel: {
            type: String,
            default: () => "",
        },
        rootValue: {
            type: String,
            default: "__root__",
        },
    },

    emits: ["update:modelValue", "blur"],

    data() {
        return {
            expandedIds: [],
            search: "",
        };
    },

    computed: {
        innerValue() {
            return this.modelValue;
        },
        options() {
            const rootLabel = this.rootLabel || this.$t("category.no_parent_category");
            return [
                {
                    id: this.rootValue,
                    name: rootLabel,
                    level: 0,
                    hasChildren: false,
                },
                ...this.flatten(this.items, 0, Boolean(this.search)),
            ];
        },
    },

    methods: {
        isExpanded(id) {
            return this.expandedIds.includes(id);
        },
        toggle(id) {
            if (this.isExpanded(id)) {
                this.expandedIds = this.expandedIds.filter(
                    (item) => item !== id,
                );
            } else {
                this.expandedIds.push(id);
            }
        },
        onChange(value) {
            this.$emit("update:modelValue", value);
        },
        flatten(items, level = 0, forceOpen = false) {
            return items.flatMap((item) => {
                if (item.id === this.excludeId) {
                    return [];
                }

                const children = item.children ?? [];
                const isOpen =
                    forceOpen ||
                    this.isExpanded(item.id) ||
                    this.hasSelectedDescendant(children);

                return [
                    {
                        id: item.id,
                        name: item.name,
                        level,
                        hasChildren: children.length > 0,
                    },
                    ...(isOpen
                        ? this.flatten(children, level + 1, forceOpen)
                        : []),
                ];
            });
        },
        hasSelectedDescendant(items) {
            return items.some((item) => {
                if (item.id === this.modelValue) {
                    return true;
                }

                return this.hasSelectedDescendant(item.children ?? []);
            });
        },
    },
};
</script>

<style scoped>
.tree-empty-toggle {
    display: inline-block;
    width: 28px;
}
</style>
