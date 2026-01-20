import { menuSidebar } from "@/configs/menuSidebar";

export const functionHelper = {
    findMenuItemByKey(key) {
        return menuSidebar.find((item) => item.key === key);
    },
};
