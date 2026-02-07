export async function loadProvinceData() {
    try {
        const response = await fetch("/province.json");
        return await response.json();
    } catch (error) {
        console.error("Lỗi khi load dữ liệu province:", error);
        return {};
    }
}

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

export function getWardOptionsByProvince(
    wardData,
    provinceData,
    selectedProvince,
) {
    if (!selectedProvince) {
        return [];
    }

    const selectedProvinceObj = Object.values(provinceData).find(
        (p) => p.code === selectedProvince,
    );

    if (!selectedProvinceObj) {
        return [];
    }

    return Object.values(wardData).filter(
        (ward) => ward.parent_code === selectedProvinceObj.code,
    );
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
    loadProvinceData,
    loadWardData,
    getProvinceOptions,
    getWardOptionsByProvince,
    findProvinceByCode,
    findWardByCode,
};
