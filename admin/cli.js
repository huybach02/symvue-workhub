/* eslint-disable indent */
/**
 * CLI Tool cho Frontend Boilerplate Generator
 *
 * Usage:
 *   node cli.js make:module [TenModule]
 *   node cli.js remove:module [TenModule]
 *
 * Example:
 *   node cli.js make:module SanPham
 *   node cli.js remove:module SanPham
 */

import MakeModuleCommand from "./src/commands/makeModuleCommand.js";
import RemoveModuleCommand from "./src/commands/removeModuleCommand.js";

// Parse arguments
const args = process.argv.slice(2);

if (args.length === 0) {
    console.log("\n📖 Frontend CLI Tool - Hướng dẫn sử dụng\n");
    console.log("=".repeat(50));
    console.log("\nCác lệnh khả dụng:");
    console.log("  make:module [TenModule]    - Tạo module mới");
    console.log("  remove:module [TenModule]  - Xóa module\n");
    console.log("Ví dụ:");
    console.log("  node cli.js make:module SanPham");
    console.log("  node cli.js remove:module SanPham\n");
    console.log("=".repeat(50) + "\n");
    process.exit(0);
}

const command = args[0];
const commandArgs = args.slice(1);

// Route commands
switch (command) {
    case "make:module": {
        if (commandArgs.length === 0) {
            console.error("\n❌ Lỗi: Thiếu tên module");
            console.log("\n💡 Sử dụng: node cli.js make:module [TenModule]");
            console.log("   Ví dụ: node cli.js make:module SanPham\n");
            process.exit(1);
        }

        const moduleName = commandArgs[0];
        MakeModuleCommand.execute(moduleName);
        break;
    }

    case "remove:module": {
        if (commandArgs.length === 0) {
            console.error("\n❌ Lỗi: Thiếu tên module");
            console.log("\n💡 Sử dụng: node cli.js remove:module [TenModule]");
            console.log("   Ví dụ: node cli.js remove:module SanPham\n");
            process.exit(1);
        }

        const moduleNameToRemove = commandArgs[0];
        RemoveModuleCommand.execute(moduleNameToRemove);
        break;
    }

    default:
        console.error(`\n❌ Lỗi: Lệnh "${command}" không tồn tại\n`);
        console.log("💡 Chạy 'node cli.js' để xem danh sách lệnh khả dụng\n");
        process.exit(1);
}
