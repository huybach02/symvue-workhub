import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { toast } from "@/main";

export const postData = async (path, data, callback = () => {}) => {
    try {
        const res = await axiosInstance.post(path, data);
        if (res.success) {
            toast.success(res.message);
            callback();
            return res.data;
        } else {
            toast.error(res.message);
        }
    } catch (error) {
        handleAxiosError(error);
    }
};
