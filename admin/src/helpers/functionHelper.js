import { menuSidebar } from "@/configs/menuSidebar";

export const functionHelper = {
    findMenuItemByValue(value) {
        const findRecursive = (items) => {
            for (const item of items) {
                if (item.value === value) {
                    return item;
                }
                if (item.children && item.children.length > 0) {
                    const found = findRecursive(item.children);
                    if (found) {
                        return found;
                    }
                }
            }
            return null;
        };

        return findRecursive(menuSidebar);
    },
    updateTime(field, value) {
        let timeString = "";
        if (typeof value === "object" && value !== null) {
            const hours = String(value.hours || 0).padStart(2, "0");
            const minutes = String(value.minutes || 0).padStart(2, "0");
            timeString = `${hours}:${minutes}`;
        } else if (typeof value === "string") {
            timeString = value;
        }

        field.onChange(timeString);
    },
    generateRandomString(length = 8) {
        const characters =
            "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        let password = "";
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * characters.length);
            password += characters.charAt(randomIndex);
        }
        return password;
    },
    timeAgo(dateString) {
        if (!dateString) return "";

        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;

        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);
        const diffMonth = Math.floor(diffDay / 30);
        const diffYear = Math.floor(diffDay / 365);

        if (diffSec < 5) return "Vừa xong";
        if (diffMin < 1) return `${diffSec} giây trước`;
        if (diffMin < 60) return `${diffMin} phút trước`;
        if (diffHour < 24) return `${diffHour} giờ trước`;
        if (diffDay < 30) return `${diffDay} ngày trước`;
        if (diffMonth < 12) return `${diffMonth} tháng trước`;
        return `${diffYear} năm trước`;
    },
    generateMa(tenBoPhan) {
        // Bỏ dấu tiếng Việt
        const withoutAccents = tenBoPhan
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/đ/g, "d")
            .replace(/Đ/g, "D");

        // Thay khoảng trắng và ký tự đặc biệt bằng dấu gạch dưới, chuyển thành chữ in hoa
        const result = withoutAccents
            .trim()
            .replace(/\s+/g, "_")
            .replace(/[^a-zA-Z0-9_]/g, "")
            .toUpperCase();

        return result;
    },
};
