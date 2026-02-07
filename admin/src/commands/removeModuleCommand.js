import path from "path";
import fs from "fs";
import { fileURLToPath } from "url";
import ConfigRemover from "./configRemover.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

/**
 * Handler cho command remove:module
 */
class RemoveModuleCommand {
    /**
     * Validate tên module
     * @param {string} moduleName - Tên module
     * @returns {boolean} - True nếu hợp lệ
     */
    static validateModuleName(moduleName) {
        // Kiểm tra tên module phải là PascalCase
        const pascalCaseRegex = /^[A-Z][a-zA-Z0-9]*$/;

        if (!pascalCaseRegex.test(moduleName)) {
            console.error(
                "❌ Lỗi: Tên module phải là PascalCase (ví dụ: NguoiDung, SanPham)",
            );
            return false;
        }

        return true;
    }

    /**
     * Thực thi command remove:module
     * @param {string} moduleName - Tên module (PascalCase)
     */
    static execute(moduleName) {
        console.log("\n🗑️  Bắt đầu xóa module:", moduleName);
        console.log("=".repeat(50));

        // Validate tên module
        if (!this.validateModuleName(moduleName)) {
            process.exit(1);
        }

        try {
            // Xác định đường dẫn
            const adminDir = path.resolve(__dirname, "../..");
            const srcDir = path.join(adminDir, "src");
            const moduleDir = path.join(srcDir, "pages", moduleName);

            // Kiểm tra module có tồn tại không
            if (!fs.existsSync(moduleDir)) {
                console.error(
                    `\n❌ Lỗi: Module ${moduleName} không tồn tại tại ${moduleDir}\n`,
                );
                process.exit(1);
            }

            // Bước 1: Xóa thư mục module
            console.log("\n📁 Bước 1: Xóa thư mục module...\n");
            fs.rmSync(moduleDir, { recursive: true, force: true });
            console.log(`✓ Đã xóa thư mục ${moduleDir}`);

            // Bước 2: Xóa khỏi config files
            console.log("\n📝 Bước 2: Xóa khỏi config files...\n");
            ConfigRemover.removeAllConfigs(srcDir, moduleName);

            // Hoàn thành
            console.log("=".repeat(50));
            console.log(
                "✅ Hoàn thành! Module",
                moduleName,
                "đã được xóa thành công!",
            );
            console.log("\n📋 Tóm tắt:");
            console.log("   - Đã xóa thư mục module và tất cả files");
            console.log("   - Đã xóa khỏi apiRouteConfig.js");
            console.log("   - Đã xóa khỏi nameRouteConfig.js");
            console.log("   - Đã xóa khỏi menuSidebar.js");
            console.log("   - Đã xóa khỏi routes.js");
            console.log("   - Đã xóa khỏi các file locale (vi & en)");
            console.log("=".repeat(50) + "\n");
        } catch (error) {
            console.error("\n❌ Lỗi khi xóa module:", error.message);
            console.error(error.stack);
            process.exit(1);
        }
    }
}

export default RemoveModuleCommand;
