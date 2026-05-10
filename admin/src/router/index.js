import { createRouter, createWebHistory } from "vue-router";
import { routes } from "@/router/routes.js";
import { NAME_ROUTES_CONFIG } from "@/configs/nameRouteConfig";
import { authService } from "@/services/authService";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: routes,
});

// Middleware check trạng thái đã đăng nhập
router.beforeEach(async (to, from, next) => {
    if (to.name === NAME_ROUTES_CONFIG.login) {
        try {
            const token = localStorage.getItem("token");
            if (token) {
                const response = await authService.getMe();
                if (response.success) {
                    next({ name: NAME_ROUTES_CONFIG.dashboard });
                    return;
                }

                localStorage.removeItem("token");
                localStorage.removeItem("refresh_token");
                localStorage.removeItem("mercure_token");
            }
        } catch {
            console.log("Token invalid or expired");
            localStorage.removeItem("token");
            localStorage.removeItem("refresh_token");
            localStorage.removeItem("mercure_token");
        }
    }
    next();
});

export default router;
