<template>
    <div>
        <v-tabs v-model="tab" color="primary">
            <v-tab v-for="item in tabs" :key="item.value" :value="item.value">
                {{ $t(item.key) }}
            </v-tab>
        </v-tabs>

        <v-tabs-window v-model="tab">
            <v-tabs-window-item
                v-for="item in tabs"
                :key="item.value"
                :value="item.value"
            >
                <v-row class="mt-3">
                    <v-col cols="12" md="5">
                        <v-text-field
                            v-model="searchKeyword"
                            clearable
                            density="compact"
                            hide-details
                            prepend-inner-icon="mdi-magnify"
                            variant="outlined"
                            :placeholder="$t('category.search_placeholder')"
                        />
                    </v-col>
                    <v-col cols="12" md="7">
                        <CreateEditCategory
                            v-if="permission?.create"
                            :path="path"
                            :type="item.value"
                            mode="create"
                            @reload="getDanhSach"
                        />
                    </v-col>
                </v-row>
                <v-row>
                    <v-col cols="12">
                        <CategoryList
                            v-if="permission?.index"
                            :items="filteredItems"
                            :path="path"
                            :permission="permission"
                            :type="item.value"
                            :expand-all="Boolean(searchKeyword)"
                            @reload="getDanhSach"
                            @edit="openEditDialog"
                            @node-dropped="onNodeDropped"
                        />
                    </v-col>
                </v-row>
            </v-tabs-window-item>
        </v-tabs-window>

        <CreateEditCategory
            v-if="selectedItem"
            v-model="showEditDialog"
            :path="path"
            :type="selectedItem.type"
            mode="update"
            :item="selectedItem"
            @reload="getDanhSach"
        />
    </div>
</template>

<script>
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { constant } from "@/utils/constants/constant";
import CategoryList from "./CategoryList.vue";
import CreateEditCategory from "./CreateEditCategory.vue";
import { usePermission } from "@/hooks/usePermission";
import { mapActions, mapGetters } from "vuex";
import { functionHelper } from "@/helpers/functionHelper";

export default {
    name: "Category",
    components: {
        CategoryList,
        CreateEditCategory,
    },
    data() {
        return {
            tab: constant.CATEGORY_TABS[0]?.value ?? "ingredient",
            tabs: constant.CATEGORY_TABS,
            path: API_ROUTES_CONFIG.category,
            searchKeyword: "",
            selectedItem: null,
            showEditDialog: false,
        };
    },
    computed: {
        ...mapGetters("category", ["items"]),
        permission() {
            return usePermission(this.path);
        },
        filteredItems() {
            const keyword = String(this.searchKeyword ?? "").trim();

            if (!functionHelper.normalizeText(keyword)) {
                return this.items;
            }

            return this.filterTree(this.items, keyword);
        },
    },
    watch: {
        tab() {
            this.searchKeyword = "";
            this.showEditDialog = false;
            this.selectedItem = null;
            this.getDanhSach();
        },
        showEditDialog(value) {
            if (!value) {
                this.selectedItem = null;
            }
        },
    },
    mounted() {
        this.getDanhSach();
    },
    methods: {
        ...mapActions("category", ["fetchItems", "moveItem"]),
        async getDanhSach(params) {
            await this.fetchItems({
                type: this.tab,
                ...params,
            });
        },
        normalizeCase(value) {
            return String(value ?? "").toLowerCase();
        },
        hasDiacritics(value) {
            return (
                functionHelper.normalizeText(value) !==
                this.normalizeCase(value)
            );
        },
        filterTree(items, keyword) {
            return items.reduce((result, item) => {
                const children = this.filterTree(item.children ?? [], keyword);
                const isMatched = this.isMatchedKeyword(item.name, keyword);

                if (isMatched || children.length > 0) {
                    result.push({
                        ...item,
                        children,
                    });
                }

                return result;
            }, []);
        },
        isMatchedKeyword(value, keyword) {
            const words = functionHelper.normalizeText(value)
                .split(/\s+/)
                .filter(Boolean);
            const rawWords = this.normalizeCase(value)
                .split(/\s+/)
                .filter(Boolean);
            const rawParts = this.normalizeCase(keyword)
                .split(/\s+/)
                .filter(Boolean);
            const keywordParts = functionHelper.normalizeText(keyword)
                .split(/\s+/)
                .filter(Boolean);

            return keywordParts.every((part, index) =>
                words.some((word, wordIndex) => {
                    if (part.length > 2) {
                        return word.includes(part);
                    }

                    if (this.hasDiacritics(rawParts[index])) {
                        return rawWords[wordIndex]?.startsWith(rawParts[index]);
                    }

                    return word === part;
                }),
            );
        },
        openEditDialog(item) {
            this.selectedItem = item;
            this.showEditDialog = true;
        },
        async onNodeDropped(context) {
            const payload = {
                newParentId: context.newParentId,
                targetOrderedIds: context.targetOrderedIds,
                sourceOrderedIds: context.sourceOrderedIds,
            };

            await this.moveItem({
                id: context.id,
                payload,
                params: {
                    type: this.tab,
                },
            });
        },
    },
};
</script>

<style></style>
