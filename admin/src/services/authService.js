import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { useToast } from "vue-toastification";

const toast = useToast();

export const authService = {
    login: async (data) => {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.login,
                data,
            );
            if (response.data.token) {
                toast.success("Đăng nhập thành công");
                localStorage.setItem("token", response.data.token);
                if (response.data.refresh_token) {
                    localStorage.setItem(
                        "refresh_token",
                        response.data.refresh_token,
                    );
                }
                if (response.data.device_id) {
                    localStorage.setItem("device_id", response.data.device_id);
                }
                return response;
            }
            return response;
        } catch (error) {
            return handleAxiosError(error);
        }
    },
    getMe: async () => {
        try {
            const response = await axiosInstance.get(API_ROUTES_CONFIG.getMe);
            return response;
        } catch (error) {
            return handleAxiosError(error);
        }
    },
};
