import axiosInstance from "@/configs/axios";
import { handleAxiosError } from "@/helpers/axiosHelper";
import { toast } from "@/main";

export const deleteData = async (path, id) => {
    try {
        const res = await axiosInstance.delete(`${path}/${id}`);
        if (res.success) {
            toast.success(res.message);
        } else {
            toast.error(res.message);
        }
    } catch (error) {
        handleAxiosError(error);
    }
};
