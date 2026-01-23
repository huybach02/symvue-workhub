import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { toast } from "@/main";

export const cauHinhChungService = {
    getAll() {
        return axiosInstance.get(API_ROUTES_CONFIG.cauHinhChung);
    },
    update(data) {
        return axiosInstance
            .post(API_ROUTES_CONFIG.cauHinhChung, data)
            .then((response) => {
                toast.success(response.message);
                return response;
            })
            .catch((error) => {
                handleAxiosError(error);
            });
    },
};
