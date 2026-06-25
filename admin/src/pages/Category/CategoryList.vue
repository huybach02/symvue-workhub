<template>
    <div class="category-tree">
        <v-progress-linear
            v-if="loading"
            color="primary"
            indeterminate
            height="2"
        />

        <Draggable
            ref="treeRef"
            :class="{ 'category-tree-disabled': loading }"
            :tree-data="items"
            id-key="id"
            parent-id-key="parentId"
            children-key="children"
            text-key="name"
            trigger-class="drag-handle"
            :indent="34"
            :gap="4"
            :unfold-when-dragover="true"
            :unfold-when-dragover-delay="420"
            :placeholder-max-height="44"
            :edge-scroll="true"
            :edge-scroll-trigger-margin="80"
            :edge-scroll-speed="12"
            :default-folded="true"
            :dragging-node-position-mode="draggingNodePositionMode"
            @drop-change="onDropChange"
        >
            <template #default="{ node, tree }">
                <div class="category-row">
                    <span class="drag-handle mr-2">
                        <v-icon size="24">mdi-drag</v-icon>
                    </span>

                    <v-btn
                        v-if="hasChildren(node)"
                        variant="text"
                        density="compact"
                        icon
                        @click.stop="toggleFold(node, tree)"
                    >
                        <v-icon size="24">
                            {{
                                node.$folded
                                    ? "mdi-chevron-right"
                                    : "mdi-chevron-down"
                            }}
                        </v-icon>
                    </v-btn>

                    <!-- <span v-else class="empty-toggle"></span> -->

                    <div
                        :class="{
                            'category-title ms-3': true,
                            'ms-10': !hasChildren(node),
                        }"
                    >
                        {{ node.name }}
                    </div>

                    <v-chip
                        v-if="!isMobile"
                        size="x-small"
                        :color="node.isActive ? 'success' : 'grey'"
                        variant="tonal"
                        class="mr-2"
                    >
                        {{ node.isActive ? $t("status_values.active") : $t("status_values.inactive") }}
                    </v-chip>

                    <v-icon
                        v-else
                        :color="node.isActive ? 'success' : 'error'"
                        size="18"
                        class="category-status-icon mr-2"
                    >
                        {{
                            node.isActive
                                ? "mdi-check-circle"
                                : "mdi-close-circle"
                        }}
                    </v-icon>

                    <div class="d-flex align-center justify-space-between ga-1">
                        <v-tooltip
                            v-if="permission?.show"
                            :text="$t('button.update')"
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <CreateEditCategory
                                    v-bind="tooltipProps"
                                    :path="path"
                                    :type="type"
                                    mode="update"
                                    :item="node"
                                    @reload="$emit('reload', { ...query })"
                                />
                            </template>
                        </v-tooltip>
                        <v-tooltip
                            v-if="permission?.delete"
                            :text="$t('button.delete')"
                            location="top"
                        >
                            <template #activator="{ props: tooltipProps }">
                                <v-btn
                                    v-bind="tooltipProps"
                                    icon
                                    size="small"
                                    variant="outlined"
                                    color="error"
                                    @click="openDeleteDialog(node.id)"
                                >
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-tooltip>
                    </div>
                </div>
            </template>
        </Draggable>

        <ConfirmDialog
            v-model="showConfirmDelete"
            :message="
                $t('media_library.delete_confirm_message', {
                    count: 1,
                })
            "
            :loading="isDeleting"
            @confirm="handleDelete"
            @cancel="showConfirmDelete = false"
        />
    </div>
</template>

<script>
import { Draggable } from "@he-tree/vue3";
import "@he-tree/vue3/dist/he-tree-vue3.css";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { mapActions, mapGetters } from "vuex";
import CreateEditCategory from "@/pages/Category/CreateEditCategory.vue";

export default {
    name: "CategoryList",

    components: {
        Draggable,
        ConfirmDialog,
        CreateEditCategory,
    },

    props: {
        items: {
            type: Array,
            default: () => [],
        },
        path: {
            type: String,
            default: "",
        },
        permission: {
            type: Object,
            default: () => ({}),
        },
        type: {
            type: String,
            default: "ingredient",
        },
        expandAll: {
            type: Boolean,
            default: false,
        },
    },

    emits: ["reload", "node-dropped", "edit", "delete"],

    data() {
        return {
            showConfirmDelete: false,
            isDeleting: false,
            deletingId: null,
            openIds: [],
        };
    },

    computed: {
        ...mapGetters("category", ["loading", "totalItems"]),
        isMobile() {
            return this.$vuetify.display.mobile;
        },
        draggingNodePositionMode() {
            return this.isMobile ? "top_left_corner" : "mouse";
        },
    },

    watch: {
        items() {
            if (this.expandAll) {
                this.openAllNodes();
            } else {
                this.restoreOpenNodes();
            }
        },
        expandAll(value) {
            if (value) {
                this.openAllNodes();
            } else {
                this.restoreOpenNodes();
            }
        },
    },

    mounted() {
        if (this.expandAll) {
            this.openAllNodes();
        } else {
            this.restoreOpenNodes();
        }
    },

    methods: {
        ...mapActions("category", ["deleteItem"]),
        hasChildren(node) {
            return node.$children && node.$children.length > 0;
        },
        toggleFold(node, tree) {
            tree.toggleFold(node);

            this.$nextTick(() => {
                this.rememberOpenNodes();
            });
        },
        rememberOpenNodes() {
            const tree = this.$refs.treeRef;

            if (!tree?.nodes) {
                return;
            }

            this.openIds = tree.nodes
                .filter((node) => this.hasChildren(node) && !node.$folded)
                .map((node) => node.id);
        },
        restoreOpenNodes() {
            this.$nextTick(() => {
                const tree = this.$refs.treeRef;

                if (!tree?.nodes || this.openIds.length === 0) {
                    return;
                }

                tree.nodes.forEach((node) => {
                    if (this.hasChildren(node)) {
                        node.$folded = !this.openIds.includes(node.id);
                    }
                });
            });
        },
        openAllNodes() {
            this.$nextTick(() => {
                this.$refs.treeRef?.unfoldAll();
            });
        },
        onDropChange(store) {
            this.rememberOpenNodes();

            const oldParentId = store.startPath.parent?.id ?? null;
            const newParentId = store.targetPath.parent?.id ?? null;

            this.$emit("node-dropped", {
                id: store.draggingNode.id,
                oldParentId,
                newParentId,
                targetOrderedIds: this.getOrderedIds(store.targetPath.parent),
                sourceOrderedIds:
                    oldParentId === newParentId
                        ? null
                        : this.getOrderedIds(store.startPath.parent),
            });
        },
        getOrderedIds(parent) {
            const tree = this.$refs.treeRef;
            const children = tree.getChildren(parent);

            return children.map((node) => node.id);
        },
        openDeleteDialog(id) {
            this.deletingId = id;
            this.showConfirmDelete = true;
        },
        async handleDelete() {
            this.isDeleting = true;
            await this.deleteItem(this.deletingId);
            this.isDeleting = false;
            this.showConfirmDelete = false;
            this.deletingId = null;
            this.$emit("reload", { ...this.query });
        },
    },
};
</script>

<style scoped>
.category-tree {
    position: relative;
    width: 100%;
    min-height: 120px;
}

.category-tree-disabled {
    pointer-events: none;
    opacity: 0.55;
}

.category-tree :deep(.he-tree) {
    width: 100%;
}

.category-tree :deep(.tree-node-outer) {
    width: 100%;
}

.category-tree :deep(.tree-node) {
    min-height: 44px;
}

.category-row {
    display: flex;
    align-items: center;
    width: 100%;
    min-height: 44px;
    border-radius: 8px;
    padding: 0 8px;
    transition:
        background-color 0.16s ease,
        box-shadow 0.16s ease;
}

.category-row:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

.category-title {
    flex: 1;
    min-width: 0;
    white-space: normal;
    word-break: break-word;
}

.drag-handle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    cursor: grab;
    touch-action: none;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

.drag-handle:active {
    cursor: grabbing;
}

.category-status-icon {
    flex: 0 0 auto;
    filter: saturate(1.35);
}

.empty-toggle {
    display: inline-block;
    width: 32px;
    flex: 0 0 32px;
}

.category-tree :deep(.tree-placeholder) {
    height: 40px;
    border: 1px dashed rgb(var(--v-theme-primary));
    border-radius: 8px;
    background-color: rgb(var(--v-theme-primary), 0.08);
}

.category-tree :deep(.tree-node-outer.dragging) .category-row,
.category-tree :deep(.tree-node-outer.dragging-node) .category-row {
    background-color: rgb(var(--v-theme-surface));
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
}

@media (max-width: 600px) {
    .category-row {
        align-items: flex-start;
        padding-top: 8px;
        padding-bottom: 8px;
    }

    .category-title {
        line-height: 1.35;
        padding-top: 2px;
    }
}
</style>
