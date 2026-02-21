import { useStore } from "vuex";

export const usePermission = (path) => {
    const store = useStore();

    const user = store.getters["auth/currentUser"];

    if (user?.roles?.includes("ROLE_ADMIN")) {
        return {
            index: true,
            create: true,
            show: true,
            edit: true,
            delete: true,
            export: true,
            import: true,
            showMenu: true,
        };
    }

    if (!user?.permissions) {
        return {
            index: false,
            create: false,
            show: false,
            edit: false,
            delete: false,
            export: false,
            showMenu: false,
        };
    }

    const phanQuyen = user?.permissions || [];
    const pathNameArr = path.split("/");
    const lastPathName = pathNameArr.pop() || "";

    const checkPermission = phanQuyen.find((item) => {
        if (pathNameArr.length > 0 && lastPathName.includes(item.name)) {
            return item;
        }
    })?.actions;

    return checkPermission;
};
