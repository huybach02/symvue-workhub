import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { toast } from "@/main";

export const authService = {
    login: async (data) => {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.login,
                data,
            );
            if (response?.data?.token) {
                toast.success(response.message);
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
    verifyOtp: async (data) => {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.verifyOtp,
                data,
            );
            if (response?.data) {
                toast.success(response.message);
                localStorage.setItem("device_id", response.data);
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
    logout: async () => {
        try {
            const refreshToken = localStorage.getItem("refresh_token");
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.logout,
                {
                    refresh_token: refreshToken,
                },
            );
            if (response.success) {
                toast.success(response.message);
                localStorage.removeItem("token");
                localStorage.removeItem("refresh_token");
                // localStorage.removeItem("device_id");
                return response;
            }
            return response;
        } catch (error) {
            return handleAxiosError(error);
        }
    },
};
