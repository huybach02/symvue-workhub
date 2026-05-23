import { menuSidebar } from "@/configs/menuSidebar";
import dayjs from "dayjs";
import i18n from "@/plugins/i18n";

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
    generateMa(string) {
        // Bỏ dấu tiếng Việt
        const withoutAccents = string
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
    formatMessageTime(dateString) {
        if (!dateString) return "";
        const date = dayjs(dateString);
        if (!date.isValid()) return dateString;
        const today = dayjs().startOf("day");
        const yesterday = dayjs().subtract(1, "day").startOf("day");
        const messageDate = date.startOf("day");

        const timeFormatted = date.format("HH:mm");
        if (messageDate.isSame(today)) {
            return i18n.global.t("base.today") + ` ${timeFormatted}`;
        } else if (messageDate.isSame(yesterday)) {
            return i18n.global.t("base.yesterday") + ` ${timeFormatted}`;
        } else {
            return date.format("DD/MM/YYYY HH:mm");
        }
    },
    formatNumber(value) {
        const digits = String(value ?? "").replace(/[^\d]/g, "");
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ".") || "--";
    },
    getParttimeScheduleFetchRange(date = dayjs(), beforeMonths = 2, afterMonths = 2) {
        const baseDate = dayjs(date);

        return {
            startDate: baseDate
                .subtract(beforeMonths, "month")
                .startOf("month")
                .format("YYYY-MM-DD"),
            endDate: baseDate
                .add(afterMonths, "month")
                .endOf("month")
                .format("YYYY-MM-DD"),
        };
    },
    isDateRangeCovered(targetRange, cachedRanges = []) {
        if (!targetRange?.startDate || !targetRange?.endDate) {
            return false;
        }

        return cachedRanges.some(
            (range) =>
                range.startDate <= targetRange.startDate &&
                range.endDate >= targetRange.endDate,
        );
    },
    mergeDateRanges(ranges = []) {
        const sortedRanges = ranges
            .filter((range) => range?.startDate && range?.endDate)
            .sort((left, right) => left.startDate.localeCompare(right.startDate));

        return sortedRanges.reduce((mergedRanges, range) => {
            const lastRange = mergedRanges[mergedRanges.length - 1];

            if (!lastRange) {
                mergedRanges.push({ ...range });
                return mergedRanges;
            }

            const nextAllowedDate = dayjs(lastRange.endDate)
                .add(1, "day")
                .format("YYYY-MM-DD");

            if (range.startDate <= nextAllowedDate) {
                lastRange.endDate =
                    range.endDate > lastRange.endDate
                        ? range.endDate
                        : lastRange.endDate;
            } else {
                mergedRanges.push({ ...range });
            }

            return mergedRanges;
        }, []);
    },
    getRequestStatusLabel(status) {
        const labels = {
            pending: i18n.global.t("request.status.pending"),
            rejected: i18n.global.t("request.status.rejected"),
            approved: i18n.global.t("request.status.approved"),
            cancelled: i18n.global.t("request.status.cancelled"),
        };

        return labels[status] || status || "--";
    },
    getRequestStatusColor(status) {
        const colors = {
            pending: "warning",
            rejected: "error",
            approved: "success",
            cancelled: "grey",
        };

        return colors[status] || "primary";
    },
    getRequestEventTitle(eventType) {
        const labels = {
            created: i18n.global.t("request.event.created"),
            submitted: i18n.global.t("request.event.submitted"),
            edited: i18n.global.t("request.event.edited"),
            resubmitted: i18n.global.t("request.event.resubmitted"),
            approved: i18n.global.t("request.event.approved"),
            rejected: i18n.global.t("request.event.rejected"),
            cancelled: i18n.global.t("request.event.cancelled"),
            effect_applied: i18n.global.t("request.event.effect_applied"),
            deleted: i18n.global.t("request.event.deleted"),
        };

        return labels[eventType] || eventType || "--";
    },
    getRequestEventColor(eventType) {
        const colors = {
            created: "primary",
            submitted: "primary",
            edited: "warning",
            resubmitted: "warning",
            approved: "success",
            rejected: "error",
            cancelled: "grey",
            effect_applied: "info",
            deleted: "error",
        };

        return colors[eventType] || "primary";
    },
};
