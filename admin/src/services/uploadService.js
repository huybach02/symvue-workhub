import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { toast } from "@/main";

export const uploadService = {
    async upload(formData) {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.media.upload,
                formData,
                {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                },
            );
            return response;
        } catch (error) {
            handleAxiosError(error);
        }
    },

    async getAll() {
        try {
            const response = await axiosInstance.get(
                API_ROUTES_CONFIG.media.getAll,
            );
            return response;
        } catch (error) {
            handleAxiosError(error);
        }
    },

    async delete(ids) {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.media.delete,
                { ids },
            );
            toast.success(response.message);
            return response;
        } catch (error) {
            handleAxiosError(error);
        }
    },

    async getMediaTrash() {
        try {
            const response = await axiosInstance.get(
                API_ROUTES_CONFIG.media.getMediaTrash,
            );
            return response;
        } catch (error) {
            handleAxiosError(error);
        }
    },

    async restore(ids) {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.media.restore,
                { ids },
            );
            toast.success(response.message);
            return response;
        } catch (error) {
            handleAxiosError(error);
        }
    },

    async deletePermanently(ids) {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.media.deletePermanently,
                { ids },
            );
            toast.success(response.message);
            return response;
        } catch (error) {
            handleAxiosError(error);
        }
    },
};
