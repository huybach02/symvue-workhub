<template>
    <div class="filter-pagination">
        <!-- Thông tin hiển thị -->
        <div class="pagination-info">
            <span v-if="totalItems > 0">
                {{ $t("filter.pagination.showing") }} {{ from }}
                {{ $t("filter.pagination.to") }} {{ to }}
                {{ $t("filter.pagination.of") }} {{ totalItems }}
                {{ $t("filter.pagination.records") }}
            </span>
            <span v-else>{{ $t("filter.pagination.no_data") }}</span>
        </div>

        <!-- Pagination controls -->
        <div class="pagination-controls">
            <!-- Items per page selector -->
            <div class="items-per-page">
                <span class="label">{{
                    $t("filter.pagination.items_per_page")
                }}</span>
                <v-select
                    :model-value="itemsPerPage"
                    :items="itemsPerPageOptions"
                    density="compact"
                    variant="outlined"
                    hide-details
                    @update:model-value="onItemsPerPageChange"
                />
            </div>

            <!-- Page navigation -->
            <div class="page-navigation">
                <!-- First button -->
                <v-btn
                    size="small"
                    variant="text"
                    :disabled="currentPage === 1"
                    @click="goToPage(1)"
                >
                    {{ $t("filter.pagination.first") }}
                </v-btn>

                <!-- Page numbers -->
                <v-btn
                    v-for="page in visiblePages"
                    :key="page"
                    size="small"
                    :variant="page === currentPage ? 'flat' : 'text'"
                    :color="page === currentPage ? 'primary' : ''"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </v-btn>

                <!-- Last button -->
                <v-btn
                    size="small"
                    variant="text"
                    :disabled="currentPage === totalPages"
                    @click="goToPage(totalPages)"
                >
                    {{ $t("filter.pagination.last") }}
                </v-btn>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "FilterPagination",
    props: {
        totalItems: {
            type: Number,
            default: 0,
        },
        currentPage: {
            type: Number,
            default: 1,
        },
        itemsPerPage: {
            type: Number,
            default: 10,
        },
    },
    emits: ["update:page", "update:itemsPerPage"],
    data() {
        return {
            itemsPerPageOptions: [
                { title: "10", value: 10 },
                { title: "25", value: 25 },
                { title: "50", value: 50 },
                { title: "100", value: 100 },
            ],
        };
    },
    computed: {
        // Tổng số trang
        totalPages() {
            if (this.itemsPerPage <= 0) return 1;
            return Math.ceil(this.totalItems / this.itemsPerPage);
        },

        // Vị trí bắt đầu (from)
        from() {
            if (this.totalItems === 0) return 0;
            return (this.currentPage - 1) * this.itemsPerPage + 1;
        },

        // Vị trí kết thúc (to)
        to() {
            const calculatedTo = this.currentPage * this.itemsPerPage;
            return Math.min(calculatedTo, this.totalItems);
        },

        // Các số trang hiển thị (tối đa 5 trang)
        visiblePages() {
            const total = this.totalPages;
            const current = this.currentPage;
            const maxVisible = 5;

            if (total <= maxVisible) {
                // Nếu tổng số trang <= 5, hiển thị tất cả
                return Array.from({ length: total }, (_, i) => i + 1);
            }

            // Logic hiển thị 5 trang với current page ở giữa
            let start = Math.max(1, current - 2);
            let end = Math.min(total, start + maxVisible - 1);

            // Điều chỉnh nếu end đã chạm tới cuối
            if (end === total) {
                start = Math.max(1, end - maxVisible + 1);
            }

            return Array.from({ length: end - start + 1 }, (_, i) => start + i);
        },
    },
    methods: {
        goToPage(page) {
            if (
                page !== this.currentPage &&
                page >= 1 &&
                page <= this.totalPages
            ) {
                this.$emit("update:page", page);
            }
        },

        onItemsPerPageChange(newLimit) {
            this.$emit("update:itemsPerPage", newLimit);
        },
    },
};
</script>

<style scoped>
.filter-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-top: 1px solid rgba(0, 0, 0, 0.12);
}

.pagination-info {
    font-size: 14px;
    color: rgba(0, 0, 0, 0.6);
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 24px;
}

.items-per-page {
    display: flex;
    align-items: center;
    gap: 8px;
}

.items-per-page .label {
    font-size: 14px;
    color: rgba(0, 0, 0, 0.6);
}

.items-per-page .v-select {
    width: 100px;
    min-width: 100px;
}

.items-per-page :deep(.v-field__input) {
    font-size: 14px;
    padding: 8px 12px;
}

.page-navigation {
    display: flex;
    align-items: center;
    gap: 4px;
}
</style>
