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
};
