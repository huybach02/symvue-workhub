import fs from "fs";
import path from "path";

/**
 * Utility để update các config files
 */
class ConfigUpdater {
    /**
     * Chuyển PascalCase sang camelCase
     */
    static toCamelCase(str) {
        return str.charAt(0).toLowerCase() + str.slice(1);
    }

    /**
     * Chuyển PascalCase sang kebab-case
     */
    static toKebabCase(str) {
        return str.replace(/([a-z0-9])([A-Z])/g, "$1-$2").toLowerCase();
    }

    /**
     * Chuyển PascalCase sang snake_case
     */
    static toSnakeCase(str) {
        return str.replace(/([a-z0-9])([A-Z])/g, "$1_$2").toLowerCase();
    }

    /**
     * Update apiRouteConfig.js
     * @param {string} configPath - Đường dẫn đến apiRouteConfig.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static updateApiRouteConfig(configPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);
        const moduleKebab = this.toKebabCase(moduleName);

        let content = fs.readFileSync(configPath, "utf-8");

        const closingBraceIndex = content.lastIndexOf("};");

        // Thêm config mới
        const newConfig = `    ${moduleLower}: "/${moduleKebab}",\n`;

        content =
            content.slice(0, closingBraceIndex) +
            newConfig +
            content.slice(closingBraceIndex);

        fs.writeFileSync(configPath, content, "utf-8");
        console.log(`✓ Đã update ${configPath}`);
    }

    /**
     * Update nameRouteConfig.js
     * @param {string} configPath - Đường dẫn đến nameRouteConfig.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static updateNameRouteConfig(configPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);

        let content = fs.readFileSync(configPath, "utf-8");

        const closingBraceIndex = content.lastIndexOf("};");

        // Thêm config mới
        const newConfig = `    ${moduleLower}: "system.${moduleLower}",\n`;

        content =
            content.slice(0, closingBraceIndex) +
            newConfig +
            content.slice(closingBraceIndex);

        fs.writeFileSync(configPath, content, "utf-8");
        console.log(`✓ Đã update ${configPath}`);
    }

    /**
     * Update menuSidebar.js
     * @param {string} configPath - Đường dẫn đến menuSidebar.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static updateMenuSidebar(configPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);
        const moduleSnake = this.toSnakeCase(moduleName);

        let content = fs.readFileSync(configPath, "utf-8");

        const closingBracketIndex = content.lastIndexOf("];");

        // Thêm menu item mới
        const newMenuItem = `    {
        title: i18n.global.t("sidebar.${moduleSnake}"),
        icon: "mdi-view-dashboard",
        value: NAME_ROUTES_CONFIG.${moduleLower},
        to: { name: NAME_ROUTES_CONFIG.${moduleLower} },
    },\n`;

        content =
            content.slice(0, closingBracketIndex) +
            newMenuItem +
            content.slice(closingBracketIndex);

        fs.writeFileSync(configPath, content, "utf-8");
        console.log(`✓ Đã update ${configPath}`);
    }

    /**
     * Update routes.js
     * @param {string} routesPath - Đường dẫn đến routes.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static updateRoutes(routesPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);
        const moduleKebab = this.toKebabCase(moduleName);

        let content = fs.readFileSync(routesPath, "utf-8");

        // Tìm vị trí children của /system
        const systemPathIndex = content.indexOf('path: "/system"');
        const childrenIndex = content.indexOf("children: [", systemPathIndex);
        const closingBracketIndex = content.indexOf("],", childrenIndex);

        // Tạo route mới với indentation đúng
        const newRoute = `            {
                path: "${moduleKebab}",
                name: NAME_ROUTES_CONFIG.${moduleLower},
                component: () => import("../pages/${moduleName}/${moduleName}.vue"),
                meta: {
                    title:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.${moduleLower},
                        ).title || "",
                    icon:
                        functionHelper.findMenuItemByValue(
                            NAME_ROUTES_CONFIG.${moduleLower},
                        ).icon || "",
                },
            },
`;

        content =
            content.slice(0, closingBracketIndex) +
            newRoute +
            content.slice(closingBracketIndex);

        fs.writeFileSync(routesPath, content, "utf-8");
        console.log(`✓ Đã update ${routesPath}`);
    }

    /**
     * Update store/index.js
     * @param {string} storeIndexPath - Đường dẫn đến store/index.js
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static updateStoreIndex(storeIndexPath, moduleName) {
        const moduleLower = this.toCamelCase(moduleName);

        let content = fs.readFileSync(storeIndexPath, "utf-8");

        if (!content.includes(`./modules/${moduleLower}`)) {
            const lastImportMatch = [
                ...content.matchAll(/^import .+;$/gm),
            ].pop();

            if (lastImportMatch) {
                const insertIndex =
                    lastImportMatch.index + lastImportMatch[0].length;
                content =
                    content.slice(0, insertIndex) +
                    `\nimport ${moduleLower} from "./modules/${moduleLower}";` +
                    content.slice(insertIndex);
            }
        }

        const modulesIndex = content.indexOf("modules: {");
        const closingBraceIndex = content.indexOf("},", modulesIndex);
        const moduleEntry = `        ${moduleLower},\n`;

        if (!content.includes(moduleEntry)) {
            content =
                content.slice(0, closingBraceIndex) +
                moduleEntry +
                content.slice(closingBraceIndex);
        }

        fs.writeFileSync(storeIndexPath, content, "utf-8");
        console.log(`✓ Đã update ${storeIndexPath}`);
    }

    /**
     * Update locale files (base.vi.json, base.en.json)
     * @param {string} localeBasePath - Đường dẫn đến file locale base
     * @param {string} moduleName - Tên module (PascalCase)
     * @param {string} language - Ngôn ngữ (vi hoặc en)
     */
    static updateLocaleBase(localeBasePath, moduleName, language) {
        const moduleSnake = this.toSnakeCase(moduleName);
        const moduleLabel = language === "vi" ? moduleName : moduleName;

        let content = fs.readFileSync(localeBasePath, "utf-8");
        const data = JSON.parse(content);

        // Thêm vào sidebar
        if (!data.sidebar) {
            data.sidebar = {};
        }
        data.sidebar[moduleSnake] = moduleLabel;

        // Ghi lại file với format đẹp
        fs.writeFileSync(
            localeBasePath,
            JSON.stringify(data, null, 4),
            "utf-8",
        );
        console.log(`✓ Đã update ${localeBasePath}`);
    }

    /**
     * Update locale files (locale.vi.json, locale.en.json)
     * @param {string} localePath - Đường dẫn đến file locale
     * @param {string} moduleName - Tên module (PascalCase)
     * @param {string} language - Ngôn ngữ (vi hoặc en)
     */
    static updateLocale(localePath, moduleName, language) {
        const moduleSnake = this.toSnakeCase(moduleName);
        const moduleLabel = language === "vi" ? moduleName : moduleName;

        let content = fs.readFileSync(localePath, "utf-8");
        const data = JSON.parse(content);

        // Thêm object mới cho module
        data[moduleSnake] = {
            title: moduleLabel,
            columns: {
                id: "ID",
                name: language === "vi" ? "Tên" : "Name",
            },
        };

        // Ghi lại file với format đẹp
        fs.writeFileSync(localePath, JSON.stringify(data, null, 4), "utf-8");
        console.log(`✓ Đã update ${localePath}`);
    }

    /**
     * Update tất cả các config files
     * @param {string} srcDir - Đường dẫn thư mục src
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static updateAllConfigs(srcDir, moduleName) {
        console.log("\n📝 Đang update các config files...\n");

        // Update apiRouteConfig.js
        this.updateApiRouteConfig(
            path.join(srcDir, "configs", "apiRouteConfig.js"),
            moduleName,
        );

        // Update nameRouteConfig.js
        this.updateNameRouteConfig(
            path.join(srcDir, "configs", "nameRouteConfig.js"),
            moduleName,
        );

        // Update menuSidebar.js
        this.updateMenuSidebar(
            path.join(srcDir, "configs", "menuSidebar.js"),
            moduleName,
        );

        // Update routes.js
        this.updateRoutes(path.join(srcDir, "router", "routes.js"), moduleName);

        // Update store/index.js
        this.updateStoreIndex(
            path.join(srcDir, "store", "index.js"),
            moduleName,
        );

        // Update locale base files
        this.updateLocaleBase(
            path.join(srcDir, "locales", "base", "base.vi.json"),
            moduleName,
            "vi",
        );
        this.updateLocaleBase(
            path.join(srcDir, "locales", "base", "base.en.json"),
            moduleName,
            "en",
        );

        // Update locale files
        this.updateLocale(
            path.join(srcDir, "locales", "locale.vi.json"),
            moduleName,
            "vi",
        );
        this.updateLocale(
            path.join(srcDir, "locales", "locale.en.json"),
            moduleName,
            "en",
        );

        console.log("\n✓ Đã update xong tất cả config files\n");
    }
}

export default ConfigUpdater;
