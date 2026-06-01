import store from "@/store";

export const usePermission = (path, permissionName) => {
    const user = store.getters["auth/currentUser"];
    const defaultPermissions = store.getters["department/defaultPermissions"];

    if (user?.roles?.includes("ROLE_ADMIN")) {
        user.permissions = defaultPermissions;
    }

    if (!user?.permissions) {
        user.permissions = defaultPermissions.map((permission) => ({
            ...permission,
            actions: Object.keys(permission?.actions ?? {}).reduce(
                (acc, actionKey) => {
                    acc[actionKey] = false;
                    return acc;
                },
                {},
            ),
        }));
    }

    const phanQuyen = user?.permissions || [];
    const pathNameArr = path.split("/");
    const lastPathName = pathNameArr.pop() || "";

    if (permissionName) {
        return (
            user.permissions.find((item) => item.name === permissionName)
                ?.actions || {}
        );
    }

    const checkPermission = phanQuyen.find((item) => {
        if (pathNameArr.length > 0 && lastPathName.includes(item.name)) {
            return item;
        }
    })?.actions;

    return checkPermission;
};
