import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { toast } from "@/main";

export const patchData = async (path, id, data, callback = () => {}) => {
    try {
        const resp = await axiosInstance.patch(`${path}/${id}`, data);
        if (resp.success) {
            toast.success(resp.message);
            callback();
            return resp.data;
        } else {
            toast.error(resp.message);
        }
    } catch (error) {
        handleAxiosError(error);
    }
};

export const putData = async (path, id, data, callback = () => {}) => {
    try {
        const resp = await axiosInstance.put(`${path}/${id}`, data);
        if (resp.success) {
            toast.success(resp.message);
            callback();
            return resp.data;
        } else {
            toast.error(resp.message);
        }
    } catch (error) {
        handleAxiosError(error);
    }
};
