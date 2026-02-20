import { API_ROUTES_CONFIG } from "@/configs/apiRouteConfig";
import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";

export const getAllData = async (path, params = {}) => {
    try {
        const resp = await axiosInstance.get(path, {
            params,
        });
        if (resp.success) {
            return resp.data;
        }
    } catch (error) {
        handleAxiosError(error);
    }
};

export const getDataById = async (path, id, slug = "") => {
    try {
        if (id === undefined) {
            return;
        }
        let resp;
        if (slug) {
            resp = await axiosInstance.get(`${path}/${id}/${slug}`);
        } else {
            resp = await axiosInstance.get(`${path}/${id}`);
        }
        if (resp.success) {
            return resp.data;
        }
    } catch (error) {
        handleAxiosError(error);
    }
};

export const getDataSelect = async (path, params = {}) => {
    try {
        const respSelect = await axiosInstance.get(path + "/select", {
            params: {
                ...params,
                limit: -1, // Lấy tất cả dữ liệu
            },
        });
        if (respSelect.success) {
            return respSelect.data;
        }
    } catch (error) {
        handleAxiosError(error);
    }
};

export const getListData = async (path, params = {}) => {
    try {
        const resp = await axiosInstance.get(path, { params });
        if (resp.success) {
            if (resp.data?.collection) {
                return { data: resp.data.collection, total: resp.data.total };
            } else {
                return resp.data;
            }
        }
    } catch (error) {
        handleAxiosError(error);
    }
};

export const exportData = async (path) => {
    try {
        const resp = await axiosInstance.get(path, {
            responseType: "blob",
        });

        return resp;
    } catch (error) {
        handleAxiosError(error);
        throw error;
    }
};

export const getListPhanQuyenMacDinh = async () => {
    try {
        const resp = await axiosInstance.get(
            API_ROUTES_CONFIG.phanQuyenMacDinh,
        );
        if (resp.success) {
            return resp.data;
        }
    } catch (error) {
        handleAxiosError(error);
    }
};
