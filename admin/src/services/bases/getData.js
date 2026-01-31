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

export const getDataById = async (path, id) => {
    try {
        if (id === undefined) {
            return;
        }
        const resp = await axiosInstance.get(`${path}/${id}`);
        if (resp.success) {
            return resp.data;
        }
    } catch (error) {
        handleAxiosError(error);
    }
};

export const getDataSelect = async (path, params = {}) => {
    try {
        const respSelect = await axiosInstance.get(path, {
            params,
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

// export const getListPhanQuyenMacDinh = async () => {
//     try {
//         const resp = await axiosInstance.get(
//             API_ROUTES_CONFIG.DANH_SACH_PHAN_QUYEN
//         );
//         if (resp.success) {
//             return resp.data;
//         }
//     } catch (error) {
//         handleAxiosError(error);
//     }
// };
