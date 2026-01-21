<template>
    <div>
        <div v-if="isLoading">
            <Loading :model-value="isLoading" />
        </div>
        <slot v-else></slot>
    </div>
</template>

<script>
import Loading from "@/components/Loading.vue";
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";
import { authService } from "@/services/authService";

export default {
    name: "NotAuthenticatedMiddleware",
    components: {
        Loading,
    },
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
    created() {
        this.checkAuth();
    },
    methods: {
        async checkAuth() {
            try {
                const response = await authService.getMe();
                if (response.success) {
                    this.$store.commit("auth/SET_USER", response.data);
                    this.$store.commit(
                        "auth/SET_IS_AUTHENTICATED",
                        response.success,
                    );
                } else {
                    this.$store.commit("auth/CLEAR_AUTH_DATA");
                    this.$router.push({ name: NAME_ROUTES_CONFIG.login });
                }
            } catch (error) {
                console.log(error);
                this.$store.commit("auth/CLEAR_AUTH_DATA");
                this.$router.push({ name: NAME_ROUTES_CONFIG.login });
            } finally {
                setTimeout(() => {
                    this.isLoading = false;
                }, 500);
            }
        },
    },
};
</script>

<style></style>
