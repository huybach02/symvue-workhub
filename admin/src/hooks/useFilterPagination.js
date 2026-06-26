import { ref, computed } from "vue";

/**
 * Hook xử lý logic filter và pagination cho data table
 * @param {Function} fetchCallback - Callback function được gọi khi cần fetch data
 * @param {Object} initialQuery - Query object khởi tạo ban đầu
 * @returns {Object} - Object chứa các methods và reactive state
 */
export function useFilterPagination(fetchCallback, initialQuery = {}) {
    // Khởi tạo query object với giá trị mặc định
    const query = ref({
        page: 1,
        limit: 20,
        sort_column: null,
        sort_direction: null,
        f: [],
        ...initialQuery,
    });

    /**
     * Computed property để tạo sortArray cho v-data-table
     */
    const sortArray = computed(() => {
        if (!query.value.sort_column) return [];
        return [
            {
                key: query.value.sort_column,
                order: query.value.sort_direction,
            },
        ];
    });

    /**
     * Xử lý sự kiện thay đổi options từ v-data-table (sorting)
     * @param {Object} opt - Options object từ v-data-table
     */
    const onOptions = (opt) => {
        // Chỉ xử lý sorting, không xử lý pagination (đã tắt pagination mặc định)
        if (opt.sortBy && opt.sortBy.length) {
            query.value.sort_column = opt.sortBy[0].key;
            query.value.sort_direction = opt.sortBy[0].order;
        } else {
            query.value.sort_column = null;
            query.value.sort_direction = null;
        }

        query.value.page = 1; // Reset về trang 1 khi sort
        fetchCallback(query.value);
    };

    /**
     * Xử lý sự kiện thay đổi trang
     * @param {Number} page - Số trang mới
     */
    const onPageChange = (page) => {
        query.value.page = page;
        fetchCallback(query.value);
    };

    /**
     * Xử lý sự kiện thay đổi số items per page
     * @param {Number} limit - Số items per page mới
     */
    const onLimitChange = (limit) => {
        query.value.limit = limit;
        query.value.page = 1; // Reset về trang 1 khi thay đổi limit
        fetchCallback(query.value);
    };

    /**
     * Xử lý sự kiện filter
     * @param {String} field - Tên field cần filter
     * @param {Object} filterObj - Object chứa type và value của filter
     */
    const onFilter = (field, filterObj) => {
        // Xóa filter cũ của field này
        query.value.f = query.value.f.filter((f) => f.field !== field);

        // Logic check: Chấp nhận cả giá trị 0 (trường hợp status blocked)
        const hasValue =
            filterObj.value !== undefined &&
            filterObj.value !== null &&
            filterObj.value !== "";

        if (hasValue && filterObj.type) {
            query.value.f.push({
                field,
                operator: filterObj.type,
                value: filterObj.value,
            });
        }

        query.value.page = 1; // Reset về trang 1 khi filter
        fetchCallback(query.value);
    };

    /**
     * Reset toàn bộ query về giá trị mặc định
     */
    const resetQuery = () => {
        query.value = {
            page: 1,
            limit: 10,
            sort_column: null,
            sort_direction: null,
            f: [],
            ...initialQuery,
        };
        fetchCallback(query.value);
    };

    return {
        query,
        sortArray,
        onOptions,
        onPageChange,
        onLimitChange,
        onFilter,
        resetQuery,
    };
}
