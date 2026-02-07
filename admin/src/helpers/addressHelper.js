export async function loadWardData() {
    try {
        const response = await fetch("/ward.json");
        return await response.json();
    } catch (error) {
        console.error("Lỗi khi load dữ liệu ward:", error);
        return {};
    }
}

export function getProvinceOptions(provinceData) {
    return Object.values(provinceData);
}

export function getWardOptions(wardData) {
    return Object.values(wardData);
}

export function findProvinceByCode(provinceData, provinceCode) {
    return (
        Object.values(provinceData).find((p) => p.code === provinceCode) || null
    );
}

export function findWardByCode(wardData, wardCode) {
    return Object.values(wardData).find((w) => w.code === wardCode) || null;
}

export const addressHelper = {
    loadWardData,
    getProvinceOptions,
    getWardOptions,
    findProvinceByCode,
    findWardByCode,
};
