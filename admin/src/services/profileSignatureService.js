import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { toast } from "@/main";

export const profileSignatureService = {
    getSignature: async () => {
        try {
            const response = await axiosInstance.get(
                API_ROUTES_CONFIG.profileSignature,
            );
            if (response.success) {
                return response.data;
            }
            return null;
        } catch (error) {
            return handleAxiosError(error);
        }
    },
    saveSignature: async (payload) => {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.profileSignature,
                payload,
            );
            if (response.success) {
                toast.success(response.message || "Lưu chữ ký thành công");
                return response.data;
            }
            return null;
        } catch (error) {
            return handleAxiosError(error);
        }
    },
    deleteSignature: async () => {
        try {
            const response = await axiosInstance.delete(
                API_ROUTES_CONFIG.profileSignature,
            );
            if (response.success) {
                toast.success(response.message || "Xóa chữ ký thành công");
                return true;
            }
            return false;
        } catch (error) {
            return handleAxiosError(error);
        }
    },
};
