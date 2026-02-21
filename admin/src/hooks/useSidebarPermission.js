import { constant } from "@/utils/constants/constant";
import { useStore } from "vuex";

export const useSidebarPermission = (menuSidebar) => {
    const isKeyValid = (key) => {
        return constant.ROUTE_PUBLIC.includes(key);
    };

    const store = useStore();

    const user = store.getters["auth/currentUser"];

    if (user?.roles?.includes("ROLE_ADMIN")) {
        return menuSidebar;
    }

    const phanQuyen = user?.permissions || [];

    const checkRole = menuSidebar.map((item) => {
        if (item.children) {
            const checkChildren = item.children.filter((child) => {
                return phanQuyen.some(
                    (role) => role.actions?.showMenu && role.name === child.key,
                );
            });
            return { ...item, children: checkChildren };
        } else {
            if (!isKeyValid(item.key)) {
                const data = phanQuyen.filter(
                    (role) => role.actions?.showMenu && role.name === item.key,
                );
                return data.length > 0 ? item : null;
            } else {
                return item;
            }
        }
    });

    return checkRole.filter((item) =>
        item?.children ? item.children.length > 0 : item !== null,
    );
};
