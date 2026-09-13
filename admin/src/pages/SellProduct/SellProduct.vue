<template>
    <div :style="columnHeightStyle">
        <v-row class="ma-0 h-100">
            <v-col
                cols="12"
                md="7"
                lg="8"
                class="d-flex flex-column pa-0 pr-md-3 h-100 overflow-hidden"
            >
                <v-card
                    variant="flat"
                    border
                    class="pa-3 mb-3 bg-surface flex-shrink-0"
                >
                    <div class="d-flex align-center ga-2">
                        <v-text-field
                            v-model="searchQuery"
                            prepend-inner-icon="mdi-magnify"
                            :placeholder="$t('sell_product.search_placeholder')"
                            density="compact"
                            variant="outlined"
                            hide-details
                            clearable
                            class="flex-grow-1"
                        />
                        <v-tooltip
                            :text="$t('sell_product.reload_list')"
                            location="top"
                        >
                            <template #activator="{ props }">
                                <v-btn
                                    v-bind="props"
                                    icon="mdi-refresh"
                                    variant="tonal"
                                    color="primary"
                                    density="comfortable"
                                    :loading="loadingProducts"
                                    @click="loadData"
                                />
                            </template>
                        </v-tooltip>
                    </div>

                    <v-chip-group
                        v-model="selectedCategory"
                        mandatory
                        selected-class="bg-primary text-white"
                        class="pt-2"
                    >
                        <v-chip
                            value="all"
                            filter
                            variant="tonal"
                            rounded="pill"
                            size="small"
                            prepend-icon="mdi-apps"
                        >
                            {{
                                $t("sell_product.all_with_total", {
                                    total: totalActiveProducts,
                                })
                            }}
                        </v-chip>
                        <v-chip
                            v-for="cat in rootCategories"
                            :key="cat.id"
                            :value="cat.id"
                            filter
                            variant="tonal"
                            rounded="pill"
                            size="small"
                        >
                            {{ cat.name }}
                        </v-chip>
                    </v-chip-group>

                    <v-chip-group
                        v-if="subcategories.length > 0"
                        v-model="selectedSubcategory"
                        mandatory
                        selected-class="bg-secondary text-white"
                        class="pt-1"
                    >
                        <v-chip
                            value="all"
                            filter
                            variant="tonal"
                            rounded="pill"
                            size="x-small"
                        >
                            {{
                                $t("sell_product.all_category", {
                                    name: selectedCategoryNode?.name || "",
                                })
                            }}
                        </v-chip>
                        <v-chip
                            v-for="sub in subcategories"
                            :key="sub.id"
                            :value="sub.id"
                            filter
                            variant="tonal"
                            rounded="pill"
                            size="x-small"
                        >
                            {{ sub.name }}
                        </v-chip>
                    </v-chip-group>
                </v-card>

                <div
                    class="flex-grow-1 overflow-y-auto pr-1"
                    style="
                        overscroll-behavior: contain;
                        scroll-behavior: smooth;
                        will-change: scroll-position;
                    "
                >
                    <div
                        v-if="loadingProducts"
                        class="d-flex justify-center align-center pa-12"
                    >
                        <v-progress-circular
                            indeterminate
                            color="primary"
                            size="50"
                        />
                    </div>

                    <v-card
                        v-else-if="filteredProducts.length === 0"
                        variant="flat"
                        border
                        class="pa-12 text-center"
                    >
                        <v-avatar color="grey-lighten-4" size="80" class="mb-3">
                            <v-icon
                                icon="mdi-food-off-outline"
                                size="40"
                                color="grey-lighten-1"
                            />
                        </v-avatar>
                        <div
                            class="text-subtitle-1 font-weight-bold text-medium-emphasis"
                        >
                            {{ $t("sell_product.empty_products_title") }}
                        </div>
                        <div class="text-caption text-grey-darken-1 mt-1">
                            {{ $t("sell_product.empty_products_desc") }}
                        </div>
                    </v-card>

                    <v-row v-else dense>
                        <v-col
                            v-for="product in filteredProducts"
                            :key="product.id"
                            cols="6"
                            sm="6"
                            md="6"
                            lg="4"
                            xl="3"
                        >
                            <v-card
                                border
                                elevation="1"
                                class="d-flex flex-column h-100 overflow-hidden cursor-pointer"
                                @click="handleProductClick(product)"
                            >
                                <div class="position-relative">
                                    <v-img
                                        v-if="product.image"
                                        :src="product.image"
                                        :height="
                                            $vuetify.display.xs ? 110 : 135
                                        "
                                        cover
                                        class="bg-grey-lighten-4"
                                    >
                                        <template #placeholder>
                                            <div
                                                class="d-flex align-center justify-center fill-height"
                                            >
                                                <v-icon
                                                    icon="mdi-image-outline"
                                                    color="grey-lighten-1"
                                                    size="24"
                                                />
                                            </div>
                                        </template>
                                    </v-img>
                                    <v-sheet
                                        v-else
                                        :height="
                                            $vuetify.display.xs ? 110 : 135
                                        "
                                        color="primary-lighten-5"
                                        class="d-flex align-center justify-center"
                                    >
                                        <v-icon
                                            icon="mdi-silverware-variant"
                                            :size="
                                                $vuetify.display.xs ? 36 : 48
                                            "
                                            color="primary"
                                        />
                                    </v-sheet>

                                    <v-chip
                                        v-if="product.category?.name"
                                        size="x-small"
                                        color="surface"
                                        variant="flat"
                                        elevation="1"
                                        class="position-absolute ma-1 ma-sm-2 font-weight-bold"
                                        style="top: 0; right: 0"
                                    >
                                        {{ product.category.name }}
                                    </v-chip>
                                </div>

                                <v-card-text
                                    class="pa-2 pa-sm-3 flex-grow-1 d-flex flex-column justify-space-between"
                                >
                                    <div>
                                        <div
                                            class="text-caption text-medium-emphasis font-weight-medium mb-1"
                                        >
                                            #{{ product.code }}
                                        </div>
                                        <div
                                            class="font-weight-bold text-body-2 text-sm-body-1 text-truncate mb-1 mb-sm-2"
                                            :title="product.name"
                                        >
                                            {{ product.name }}
                                        </div>
                                    </div>

                                    <div class="pt-2 border-t mt-1 mt-sm-2">
                                        <div
                                            v-if="
                                                (product.variantPrices || [])
                                                    .length === 1
                                            "
                                            class="d-flex align-center justify-space-between ga-1"
                                        >
                                            <div>
                                                <div
                                                    class="text-caption text-medium-emphasis"
                                                >
                                                    {{
                                                        $t(
                                                            "sell_product.unit_price",
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    class="text-body-2 text-sm-subtitle-1 font-weight-bold text-primary"
                                                >
                                                    {{
                                                        formatPrice(
                                                            product
                                                                .variantPrices[0]
                                                                ?.price,
                                                        )
                                                    }}
                                                    {{
                                                        $t(
                                                            "base.currency_symbol",
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                            <v-btn
                                                :size="
                                                    $vuetify.display.xs
                                                        ? 'x-small'
                                                        : 'small'
                                                "
                                                icon="mdi-plus"
                                                color="primary"
                                                variant="flat"
                                                rounded="circle"
                                                elevation="1"
                                                @click.stop="
                                                    addToCart(
                                                        product,
                                                        product
                                                            .variantPrices[0],
                                                    )
                                                "
                                            />
                                        </div>

                                        <div
                                            v-else-if="
                                                (product.variantPrices || [])
                                                    .length > 1
                                            "
                                            class="d-flex align-center justify-space-between ga-1"
                                        >
                                            <div>
                                                <div
                                                    class="text-caption text-medium-emphasis"
                                                >
                                                    {{
                                                        $t(
                                                            "sell_product.price_from",
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    class="text-body-2 text-sm-subtitle-1 font-weight-bold text-primary"
                                                >
                                                    {{
                                                        formatPrice(
                                                            getMinPrice(
                                                                product,
                                                            ),
                                                        )
                                                    }}
                                                    {{
                                                        $t(
                                                            "base.currency_symbol",
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                            <v-btn
                                                color="primary"
                                                variant="tonal"
                                                :size="
                                                    $vuetify.display.xs
                                                        ? 'x-small'
                                                        : 'small'
                                                "
                                                rounded="pill"
                                                :prepend-icon="
                                                    $vuetify.display.xs
                                                        ? undefined
                                                        : 'mdi-format-list-bulleted'
                                                "
                                                class="text-caption font-weight-bold px-2"
                                                @click.stop="
                                                    handleProductClick(product)
                                                "
                                            >
                                                {{
                                                    $t(
                                                        "sell_product.variant_count",
                                                        {
                                                            count: (
                                                                product.variantPrices ||
                                                                []
                                                            ).length,
                                                        },
                                                    )
                                                }}
                                            </v-btn>
                                        </div>

                                        <div
                                            v-else
                                            class="text-caption text-grey"
                                        >
                                            {{ $t("sell_product.no_price") }}
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </div>
            </v-col>

            <v-col
                cols="12"
                md="5"
                lg="4"
                class="d-flex flex-column pa-0 pl-md-3 h-100 mt-4 mt-md-0"
            >
                <v-card
                    border
                    elevation="1"
                    class="d-flex flex-column h-100 overflow-hidden"
                >
                    <div
                        class="py-3 px-4 bg-primary-lighten-5 border-b d-flex align-center justify-space-between rounded-t-xl flex-shrink-0"
                    >
                        <div class="d-flex align-center ga-2">
                            <v-avatar
                                color="primary"
                                size="26"
                                class="text-white"
                            >
                                <v-icon icon="mdi-receipt-text" size="15" />
                            </v-avatar>
                            <div>
                                <div
                                    class="text-body-2 font-weight-bold text-primary"
                                    style="line-height: 1.2"
                                >
                                    {{ $t("sell_product.cart_title") }}
                                </div>
                                <div
                                    class="text-caption text-medium-emphasis"
                                    style="
                                        font-size: 11px !important;
                                        line-height: 1.1;
                                    "
                                >
                                    {{
                                        $t("sell_product.items_selected", {
                                            count: totalQuantity,
                                        })
                                    }}
                                </div>
                            </div>
                        </div>
                        <v-btn
                            v-if="cartItems.length > 0"
                            variant="text"
                            color="error"
                            density="compact"
                            size="small"
                            prepend-icon="mdi-trash-can-outline"
                            @click="confirmClearDialog = true"
                        >
                            {{ $t("sell_product.clear_all") }}
                        </v-btn>
                    </div>

                    <div class="px-4 py-3 bg-surface flex-shrink-0 border-b">
                        <v-autocomplete
                            v-model="selectedDiningTableId"
                            :items="diningTableOptions"
                            item-title="label"
                            item-value="value"
                            :label="$t('sell_product.select_table_placeholder')"
                            density="compact"
                            variant="outlined"
                            clearable
                            hide-details="auto"
                            :rules="[
                                (v) => !!v || $t('sell_product.table_required'),
                            ]"
                            :loading="loadingTables"
                            :auto-select-first="false"
                        >
                            <template #item="{ item, props }">
                                <v-list-item
                                    v-bind="props"
                                    :title="item.raw.label"
                                    :disabled="item.raw.isUsing"
                                >
                                    <template #append>
                                        <v-chip
                                            :color="
                                                item.raw.isUsing
                                                    ? 'warning'
                                                    : 'success'
                                            "
                                            size="x-small"
                                            variant="tonal"
                                            class="font-weight-medium"
                                        >
                                            {{
                                                item.raw.isUsing
                                                    ? $t(
                                                          "sell_product.table_in_use",
                                                      )
                                                    : $t(
                                                          "sell_product.table_available",
                                                      )
                                            }}
                                        </v-chip>
                                    </template>
                                </v-list-item>
                            </template>
                        </v-autocomplete>
                    </div>

                    <div
                        class="pa-3 px-4 flex-grow-1 overflow-y-auto"
                        style="
                            overscroll-behavior: contain;
                            scroll-behavior: smooth;
                        "
                    >
                        <div
                            v-if="cartItems.length === 0"
                            class="d-flex flex-column align-center justify-center fill-height pa-4 text-center text-medium-emphasis"
                        >
                            <v-avatar
                                color="grey-lighten-4"
                                size="52"
                                class="mb-2"
                            >
                                <v-icon
                                    icon="mdi-cart-off"
                                    size="26"
                                    color="grey-lighten-1"
                                />
                            </v-avatar>
                            <div
                                class="font-weight-bold text-body-2 text-grey-darken-2"
                            >
                                {{ $t("sell_product.empty_cart_title") }}
                            </div>
                            <div class="text-caption text-grey-darken-1 mt-1">
                                {{ $t("sell_product.empty_cart_desc") }}
                            </div>
                        </div>

                        <div v-else class="d-flex flex-column ga-2">
                            <v-card
                                v-for="(item, index) in cartItems"
                                :key="item.cartKey"
                                variant="flat"
                                border
                                class="pa-3 bg-grey-lighten-5 rounded-lg"
                            >
                                <div
                                    class="d-flex align-start justify-space-between"
                                >
                                    <div class="flex-grow-1 mr-2">
                                        <div
                                            class="font-weight-bold text-body-2 text-grey-darken-3"
                                        >
                                            {{ item.productName }}
                                        </div>
                                        <v-chip
                                            v-if="item.variantName"
                                            size="x-small"
                                            color="primary"
                                            variant="tonal"
                                            class="mt-1 font-weight-medium"
                                        >
                                            {{ item.variantName }}
                                        </v-chip>
                                    </div>
                                    <v-btn
                                        icon="mdi-close"
                                        size="x-small"
                                        variant="text"
                                        color="grey-darken-1"
                                        @click="removeFromCart(index)"
                                    />
                                </div>

                                <div
                                    class="d-flex align-center justify-space-between mt-2 pt-2 border-t"
                                >
                                    <span
                                        class="text-caption text-medium-emphasis"
                                    >
                                        {{ formatPrice(item.price) }}
                                        {{ $t("base.currency_symbol") }}
                                    </span>

                                    <v-sheet
                                        border
                                        rounded="pill"
                                        class="d-inline-flex align-center px-1 bg-surface"
                                    >
                                        <v-btn
                                            icon="mdi-minus"
                                            variant="text"
                                            density="compact"
                                            @click="decreaseQuantity(index)"
                                        />
                                        <span
                                            class="text-body-2 font-weight-bold px-2 text-center"
                                            style="min-width: 24px"
                                        >
                                            {{ item.quantity }}
                                        </span>
                                        <v-btn
                                            icon="mdi-plus"
                                            variant="text"
                                            density="compact"
                                            @click="increaseQuantity(index)"
                                        />
                                    </v-sheet>

                                    <span
                                        class="font-weight-bold text-body-2 text-primary"
                                    >
                                        {{
                                            formatPrice(
                                                item.price * item.quantity,
                                            )
                                        }}
                                        {{ $t("base.currency_symbol") }}
                                    </span>
                                </div>

                                <div class="mt-2">
                                    <v-text-field
                                        v-model="item.note"
                                        density="compact"
                                        variant="outlined"
                                        :placeholder="
                                            $t(
                                                'sell_product.item_note_placeholder',
                                            )
                                        "
                                        prepend-inner-icon="mdi-pencil-outline"
                                        hide-details
                                        class="text-caption bg-surface"
                                    />
                                </div>
                            </v-card>
                        </div>
                    </div>

                    <div class="pa-4 border-t bg-grey-lighten-5 flex-shrink-0">
                        <div class="mb-3">
                            <v-text-field
                                v-model="orderNote"
                                density="compact"
                                variant="outlined"
                                :placeholder="
                                    $t('sell_product.order_note_placeholder')
                                "
                                prepend-inner-icon="mdi-note-edit-outline"
                                hide-details
                                class="bg-surface"
                            />
                        </div>

                        <div
                            class="d-flex align-stretch ga-2 ga-sm-3"
                            :class="isMobile ? 'flex-column' : 'flex-row'"
                        >
                            <div
                                class="d-flex align-center justify-space-between pa-3 bg-primary-lighten-5 rounded-lg border border-primary-lighten-3 flex-grow-1"
                            >
                                <div class="mr-2">
                                    <div
                                        class="text-caption font-weight-bold text-primary text-uppercase"
                                        style="
                                            letter-spacing: 0.5px;
                                            line-height: 1.2;
                                        "
                                    >
                                        {{ $t("sell_product.total_amount") }}
                                    </div>
                                    <div
                                        class="text-caption text-medium-emphasis"
                                    >
                                        {{
                                            $t("sell_product.items_count", {
                                                count: totalQuantity,
                                            })
                                        }}
                                    </div>
                                </div>
                                <div
                                    class="text-h6 font-weight-bold text-primary text-no-wrap"
                                >
                                    {{ formatPrice(totalAmount) }}
                                    {{ $t("base.currency_symbol") }}
                                </div>
                            </div>

                            <v-btn
                                color="primary"
                                variant="flat"
                                elevation="1"
                                prepend-icon="mdi-clipboard-plus-outline"
                                :disabled="
                                    cartItems.length === 0 ||
                                    !selectedDiningTableId ||
                                    submittingOrder
                                "
                                :loading="submittingOrder"
                                class="font-weight-bold flex-shrink-0"
                                :class="{ 'w-100': isMobile }"
                                :style="
                                    isMobile
                                        ? 'height: 44px'
                                        : 'width: 34%; min-width: 115px'
                                "
                                @click="handleCheckout"
                            >
                                {{ $t("sell_product.create_order_btn") }}
                            </v-btn>
                        </div>
                    </div>
                </v-card>
            </v-col>
        </v-row>

        <v-dialog v-model="variantDialog.visible" max-width="460">
            <v-card>
                <div
                    class="pa-4 bg-primary-lighten-5 border-b d-flex align-center ga-3 rounded-t-xl"
                >
                    <v-avatar color="primary" size="42" class="text-white">
                        <v-icon icon="mdi-food" size="24" />
                    </v-avatar>
                    <div>
                        <div class="text-subtitle-1 font-weight-bold">
                            {{ variantDialog.product?.name }}
                        </div>
                        <div class="text-caption text-medium-emphasis">
                            {{
                                $t("sell_product.item_code", {
                                    code: variantDialog.product?.code || "",
                                })
                            }}
                        </div>
                    </div>
                </div>

                <v-card-text class="pa-4">
                    <div class="text-subtitle-2 font-weight-bold mb-2">
                        {{ $t("sell_product.select_variant") }}
                    </div>

                    <div class="d-flex flex-column ga-2 mb-3">
                        <v-card
                            v-for="(v, idx) in variantDialog.product
                                ?.variantPrices || []"
                            :key="idx"
                            :variant="
                                variantDialog.selectedVariantIndex === idx
                                    ? 'flat'
                                    : 'outlined'
                            "
                            :color="
                                variantDialog.selectedVariantIndex === idx
                                    ? 'primary'
                                    : undefined
                            "
                            class="pa-3 cursor-pointer d-flex align-center justify-space-between"
                            @click="variantDialog.selectedVariantIndex = idx"
                        >
                            <div class="d-flex align-center ga-2">
                                <v-icon
                                    :icon="
                                        variantDialog.selectedVariantIndex ===
                                        idx
                                            ? 'mdi-radiobox-marked'
                                            : 'mdi-radiobox-blank'
                                    "
                                />
                                <span class="font-weight-medium">{{
                                    v.name
                                }}</span>
                            </div>
                            <span class="font-weight-bold">
                                {{ formatPrice(v.price) }}
                                {{ $t("base.currency_symbol") }}
                            </span>
                        </v-card>
                    </div>

                    <div class="mb-2">
                        {{ $t("sell_product.detailed_notes") }}
                        <span class="text-red"> * </span>
                    </div>
                    <v-textarea
                        rows="2"
                        v-model="variantDialog.note"
                        density="compact"
                        variant="outlined"
                        hide-details
                    />
                </v-card-text>

                <v-card-actions class="pa-3 border-t justify-end ga-2">
                    <v-btn
                        variant="text"
                        @click="variantDialog.visible = false"
                    >
                        {{ $t("button.close") }}
                    </v-btn>
                    <v-btn
                        color="primary"
                        variant="flat"
                        prepend-icon="mdi-plus"
                        class="px-4 font-weight-bold"
                        @click="confirmAddVariantToCart"
                    >
                        {{ $t("sell_product.add_to_order") }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <ConfirmDialog
            v-model="confirmClearDialog"
            :title="$t('sell_product.confirm_clear_title')"
            :message="$t('sell_product.confirm_clear_message')"
            :confirm-text="$t('sell_product.clear_all')"
            :cancel-text="$t('sell_product.keep')"
            confirm-color="error"
            @confirm="clearCart"
            @cancel="confirmClearDialog = false"
        />

        <ConfirmDialog
            v-model="confirmCheckoutDialog"
            :title="$t('sell_product.confirm_checkout_title')"
            :message="checkoutConfirmMessage"
            :confirm-text="$t('sell_product.confirm_checkout_button')"
            :cancel-text="$t('sell_product.cancel')"
            :confirm-loading="submittingOrder"
            confirm-color="primary"
            header-color="primary"
            icon="mdi-clipboard-plus-outline"
            @confirm="handleConfirmCheckout"
            @cancel="confirmCheckoutDialog = false"
        />
    </div>
</template>

<script>
import { defineComponent } from "vue";
import ConfirmDialog from "@/components/ConfirmDialog.vue";
import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import { usePermission } from "@/hooks/usePermission";
import { mapActions, mapState } from "vuex";
import { functionHelper } from "@/helpers/functionHelper";
import { toast } from "@/main";

export default defineComponent({
    name: "SellProduct",
    components: {
        ConfirmDialog,
    },
    data() {
        return {
            path: API_ROUTES_CONFIG.sellProduct,
            searchQuery: "",
            selectedCategory: "all",
            selectedSubcategory: "all",
            selectedDiningTableId: null,
            submittingOrder: false,
            cartItems: [],
            orderNote: "",
            variantDialog: {
                visible: false,
                product: null,
                selectedVariantIndex: 0,
                note: "",
            },
            confirmClearDialog: false,
            confirmCheckoutDialog: false,
        };
    },
    watch: {
        selectedCategory() {
            this.selectedSubcategory = "all";
        },
    },
    computed: {
        permission() {
            return usePermission(this.path);
        },
        ...mapState("businessProduct", {
            products: (state) => state.items || [],
            loadingProducts: (state) => state.loading,
        }),
        ...mapState("category", {
            categoryList: (state) => state.items || [],
        }),
        ...mapState("diningTable", {
            diningTableList: (state) => state.items || [],
            loadingTables: (state) => state.loading,
        }),
        isMobile() {
            return this.$vuetify.display.mobile;
        },
        diningTableOptions() {
            const list = (this.diningTableList || []).filter(
                (t) => t.status === 1,
            );
            list.sort((a, b) => (a.tableNumber || 0) - (b.tableNumber || 0));
            return list.map((t) => ({
                value: t.id,
                label: this.$t("sell_product.table_label", {
                    number: t.tableNumber,
                }),
                tableNumber: t.tableNumber,
                isUsing: Boolean(t.isUsing),
                disabled: Boolean(t.isUsing),
                props: {
                    disabled: Boolean(t.isUsing),
                },
            }));
        },
        selectedTableLabel() {
            if (!this.selectedDiningTableId) return "";
            const found = this.diningTableOptions.find(
                (t) => t.value === this.selectedDiningTableId,
            );
            return found ? found.label : "";
        },
        checkoutConfirmMessage() {
            return this.$t("sell_product.confirm_checkout_message", {
                table: this.selectedTableLabel,
                count: this.totalQuantity,
                total: this.formatPrice(this.totalAmount),
                currency: this.$t("base.currency_symbol"),
            });
        },
        rootCategories() {
            return this.categoryList || [];
        },
        selectedCategoryNode() {
            if (this.selectedCategory === "all") return null;
            return (
                this.rootCategories.find(
                    (c) => c.id === this.selectedCategory,
                ) || null
            );
        },
        subcategories() {
            return this.selectedCategoryNode?.children || [];
        },
        activeProducts() {
            return this.products.filter((p) => p.status === 1);
        },
        totalActiveProducts() {
            return this.activeProducts.length;
        },
        filteredProducts() {
            let list = this.activeProducts;

            if (this.selectedCategory && this.selectedCategory !== "all") {
                const descendantIds = this.getCategoryAndDescendantIds(
                    this.selectedCategory,
                );

                if (
                    this.selectedSubcategory &&
                    this.selectedSubcategory !== "all"
                ) {
                    list = list.filter((p) => {
                        const catId = p.categoryId || p.category?.id;
                        return catId === this.selectedSubcategory;
                    });
                } else {
                    list = list.filter((p) => {
                        const catId = p.categoryId || p.category?.id;
                        const parentId =
                            p.category?.parentId || p.category?.parent_id;
                        return (
                            descendantIds.has(catId) ||
                            descendantIds.has(parentId)
                        );
                    });
                }
            }

            if (this.searchQuery && this.searchQuery.trim()) {
                const keyword = functionHelper.normalizeText(
                    this.searchQuery.trim(),
                );
                list = list.filter((p) => {
                    const name = functionHelper.normalizeText(p.name || "");
                    const code = functionHelper.normalizeText(p.code || "");
                    return name.includes(keyword) || code.includes(keyword);
                });
            }

            return list;
        },
        totalQuantity() {
            return this.cartItems.reduce(
                (sum, item) => sum + (item.quantity || 0),
                0,
            );
        },
        subTotal() {
            return this.cartItems.reduce(
                (sum, item) => sum + (item.price || 0) * (item.quantity || 0),
                0,
            );
        },
        totalAmount() {
            return this.subTotal;
        },
        columnHeightStyle() {
            return this.$vuetify.display.mdAndUp
                ? {
                      height: "calc(100vh - 128px)",
                      maxHeight: "calc(100vh - 128px)",
                  }
                : {};
        },
    },
    async mounted() {
        await this.loadData();
    },
    methods: {
        ...mapActions("businessProduct", {
            fetchProducts: "fetchItems",
        }),
        ...mapActions("category", {
            fetchCategories: "fetchItems",
        }),
        ...mapActions("diningTable", {
            fetchDiningTables: "fetchItems",
        }),
        ...mapActions("sellProduct", {
            createSaleOrder: "createItem",
        }),
        async loadData() {
            await Promise.all([
                this.fetchProducts({ limit: -1 }),
                this.fetchCategories({ type: "business_product" }),
                this.fetchDiningTables({ limit: -1 }),
            ]);
        },
        getCategoryAndDescendantIds(categoryId) {
            const ids = new Set([categoryId]);
            const findNode = (nodes) => {
                for (const node of nodes || []) {
                    if (node.id === categoryId) {
                        const collect = (children) => {
                            for (const child of children || []) {
                                ids.add(child.id);
                                if (child.children?.length) {
                                    collect(child.children);
                                }
                            }
                        };
                        collect(node.children);
                        return;
                    }
                    if (node.children?.length) {
                        findNode(node.children);
                    }
                }
            };
            findNode(this.rootCategories);
            return ids;
        },
        formatPrice(value) {
            return functionHelper.formatNumber(parseFloat(value) || 0);
        },
        getMinPrice(product) {
            const variants = product?.variantPrices || [];
            if (variants.length === 0) return 0;
            const prices = variants.map((v) => parseFloat(v.price) || 0);
            return Math.min(...prices);
        },
        handleProductClick(product) {
            const variants = product.variantPrices || [];
            if (variants.length > 1) {
                this.variantDialog.product = product;
                this.variantDialog.selectedVariantIndex = 0;
                this.variantDialog.note = "";
                this.variantDialog.visible = true;
            } else if (variants.length === 1) {
                this.addToCart(product, variants[0]);
            }
        },
        confirmAddVariantToCart() {
            const product = this.variantDialog.product;
            const variants = product?.variantPrices || [];
            const selected = variants[this.variantDialog.selectedVariantIndex];
            if (product && selected) {
                this.addToCart(product, selected, this.variantDialog.note);
            }
            this.variantDialog.visible = false;
        },
        addToCart(product, variant, initialNote = "") {
            const variantId = variant?.id ?? null;
            const variantCode = variant?.code ?? "";
            const variantName = variant?.name || "";
            const price = parseFloat(variant?.price) || 0;
            const cartKey = variantId
                ? `${product.id}_${variantId}`
                : `${product.id}_${variantName}`;

            const existingIndex = this.cartItems.findIndex(
                (item) => item.cartKey === cartKey,
            );

            if (existingIndex > -1) {
                this.cartItems[existingIndex].quantity += 1;
                if (initialNote && !this.cartItems[existingIndex].note) {
                    this.cartItems[existingIndex].note = initialNote;
                }
            } else {
                this.cartItems.push({
                    cartKey,
                    productId: product.id,
                    productName: product.name,
                    productCode: product.code,
                    variantId,
                    variantCode,
                    variantName,
                    price,
                    quantity: 1,
                    note: initialNote,
                });
            }
        },
        increaseQuantity(index) {
            if (this.cartItems[index]) {
                this.cartItems[index].quantity += 1;
            }
        },
        decreaseQuantity(index) {
            if (this.cartItems[index]) {
                if (this.cartItems[index].quantity > 1) {
                    this.cartItems[index].quantity -= 1;
                } else {
                    this.removeFromCart(index);
                }
            }
        },
        removeFromCart(index) {
            this.cartItems.splice(index, 1);
        },
        clearCart() {
            this.cartItems = [];
            this.orderNote = "";
            this.selectedDiningTableId = null;
            this.confirmClearDialog = false;
        },
        handleCheckout() {
            if (!this.selectedDiningTableId) {
                toast.warning(this.$t("sell_product.table_required"));
                return;
            }
            this.confirmCheckoutDialog = true;
        },
        async handleConfirmCheckout() {
            if (this.cartItems.length === 0 || !this.selectedDiningTableId)
                return;

            this.submittingOrder = true;
            const currentTableLabel = this.selectedTableLabel;
            const orderData = {
                diningTableId: this.selectedDiningTableId,
                orderNote: this.orderNote || null,
                totalAmount: this.totalAmount,
                items: this.cartItems.map((item) => ({
                    productId: item.productId,
                    productCode: item.productCode,
                    productName: item.productName,
                    variantId: item.variantId,
                    variantCode: item.variantCode,
                    variantName: item.variantName,
                    quantity: item.quantity,
                    note: item.note || null,
                })),
            };

            await this.createSaleOrder(orderData);

            this.clearCart();
            this.confirmCheckoutDialog = false;
            this.submittingOrder = false;
        },
    },
});
</script>

<style></style>
