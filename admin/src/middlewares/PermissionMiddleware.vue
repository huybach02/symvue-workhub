<template>
    <div>
        <slot></slot>
    </div>
</template>

<script>
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";
import { toast } from "@/main";
import { constant } from "@/utils/constants/constant";

export default {
    name: "NotAuthenticatedMiddleware",
    data() {
        return {
            isLoading: true,
        };
    },
    computed: {
        user() {
            return this.$store.getters["auth/currentUser"];
        },
    },
    watch: {
        $route() {
            this.checkPermission();
        },
    },
    created() {
        this.checkPermission();
    },
    methods: {
        async checkPermission() {
            for (const key of constant.ROUTE_PUBLIC) {
                if (this.$route.fullPath.includes(key)) {
                    return;
                }
            }

            if (this.user?.roles?.includes("ROLE_ADMIN")) {
                return;
            }

            const phanQuyen = this?.user?.permissions || [];
            const pathNameArr = this.$route.fullPath.split("/");
            const lastPathName = pathNameArr.pop() || "";

            const checkPermission = phanQuyen.find((item) => {
                if (
                    pathNameArr.length > 0 &&
                    lastPathName.includes(item.name)
                ) {
                    return item;
                }

                return false;
            });

            if (checkPermission?.actions?.index) {
                return;
            } else {
                toast.error("Bạn không có quyền truy cập vào nội dung này");
                return this.$router.push({
                    name: NAME_ROUTES_CONFIG.dashboard,
                });
            }
        },
    },
};
</script>

<style></style>
