import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { toast } from "@/main";

export const thoiGianLamViecService = {
    async findAll() {
        try {
            const response = await axiosInstance.get(
                API_ROUTES_CONFIG.thoiGianLamViec.findAll,
            );
            return response.data;
        } catch (error) {
            handleAxiosError(error);
        }
    },
    async findById(id) {
        try {
            const response = await axiosInstance.get(
                API_ROUTES_CONFIG.thoiGianLamViec.findById.replace(":id", id),
            );
            return response.data;
        } catch (error) {
            handleAxiosError(error);
        }
    },
    async updateFulltime(id, data) {
        try {
            const response = await axiosInstance.put(
                API_ROUTES_CONFIG.thoiGianLamViec.updateFulltime.replace(
                    ":id",
                    id,
                ),
                data,
            );
            toast.success(response.message);
            return response.data;
        } catch (error) {
            handleAxiosError(error);
        }
    },
    async createParttime(data) {
        try {
            const response = await axiosInstance.post(
                API_ROUTES_CONFIG.thoiGianLamViec.createParttime,
                data,
            );
            toast.success(response.message);
            return response.data;
        } catch (error) {
            handleAxiosError(error);
        }
    },
    async findAllParttimeByThoiGianLamViecId(id) {
        try {
            const response = await axiosInstance.get(
                API_ROUTES_CONFIG.thoiGianLamViec.findAllParttimeByThoiGianLamViecId.replace(
                    ":id",
                    id,
                ),
            );
            return response.data;
        } catch (error) {
            handleAxiosError(error);
        }
    },
};
