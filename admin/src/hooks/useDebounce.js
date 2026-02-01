/**
 * Hook debounce để trì hoãn việc thực thi một hàm cho đến khi sau một khoảng thời gian nhất định
 * kể từ lần cuối cùng nó được gọi
 *
 * @param {Function} func - Hàm cần debounce
 * @param {Number} delay - Thời gian trì hoãn (ms), mặc định 500ms
 * @returns {Function} - Hàm đã được debounce
 */
export function useDebounce(func, delay = 500) {
    let timeoutId = null;

    return function (...args) {
        // Clear timeout trước đó nếu có
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        // Tạo timeout mới
        timeoutId = setTimeout(() => {
            func.apply(this, args);
        }, delay);
    };
}

/**
 * Hook debounce dành cho Vue component (sử dụng trong data hoặc created)
 * Tự động bind context của component
 *
 * @param {Function} func - Hàm cần debounce
 * @param {Number} delay - Thời gian trì hoãn (ms), mặc định 500ms
 * @returns {Function} - Hàm đã được debounce
 */
export function useDebounceVue(func, delay = 500) {
    let timeoutId = null;

    return function (...args) {
        const context = this;

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            func.apply(context, args);
        }, delay);
    };
}

export default useDebounce;
