<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'remove:module',
    description: 'Xóa Controller, Service và DTO của module',
)]
class RemoveModuleCommand extends Command
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this->addArgument('module', InputArgument::REQUIRED, 'Tên module cần xóa (VD: UserManagement)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $moduleName = $input->getArgument('module');

        // Xác định các file paths
        $files = [
            'Controller' => $this->projectDir . '/src/Controller/' . $moduleName . 'Controller.php',
            'Service' => $this->projectDir . '/src/Service/' . $moduleName . 'Service.php',
            'DTO' => $this->projectDir . '/src/DTO/' . $moduleName . 'DTO.php',
        ];

        // Kiểm tra files nào tồn tại
        $existingFiles = [];
        $missingFiles = [];

        foreach ($files as $type => $path) {
            if (file_exists($path)) {
                $existingFiles[$type] = $path;
            } else {
                $missingFiles[$type] = $path;
            }
        }

        // Nếu không có file nào tồn tại
        if (empty($existingFiles)) {
            $io->warning("Không tìm thấy module '{$moduleName}'.");
            $io->note('Các file sau không tồn tại:');
            foreach ($missingFiles as $type => $path) {
                $io->text("  - {$type}: {$path}");
            }
            return Command::FAILURE;
        }

        // Hiển thị danh sách files sẽ xóa
        $io->section("Module '{$moduleName}' - Files sẽ bị xóa:");
        foreach ($existingFiles as $type => $path) {
            $io->text("  ✓ {$type}: {$path}");
        }

        if (!empty($missingFiles)) {
            $io->note('Files không tồn tại (sẽ bỏ qua):');
            foreach ($missingFiles as $type => $path) {
                $io->text("  - {$type}: {$path}");
            }
        }

        // Confirmation prompt
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "\nBạn có chắc chắn muốn xóa " . count($existingFiles) . " file(s)? (yes/no) [no]: ",
            false
        );

        if (!$helper->ask($input, $output, $question)) {
            $io->info('Hủy bỏ. Không có file nào bị xóa.');
            return Command::SUCCESS;
        }

        // Xóa files
        $deletedCount = 0;
        $errors = [];

        foreach ($existingFiles as $type => $path) {
            try {
                if (unlink($path)) {
                    $io->success("{$type} đã được xóa: {$path}");
                    $deletedCount++;
                } else {
                    $errors[] = "Không thể xóa {$type}: {$path}";
                }
            } catch (\Exception $e) {
                $errors[] = "Lỗi khi xóa {$type}: " . $e->getMessage();
            }
        }

        // Hiển thị kết quả
        if (!empty($errors)) {
            $io->warning('Một số file không thể xóa:');
            foreach ($errors as $error) {
                $io->text("  - {$error}");
            }
        }

        if ($deletedCount > 0) {
            $io->success("Đã xóa thành công {$deletedCount} file(s) của module '{$moduleName}'.");

            // Xóa permission tương ứng khỏi file permission.php
            try {
                $removed = $this->removePermission($moduleName);
                if ($removed) {
                    $io->success("Permission của module '{$moduleName}' đã được xóa khỏi config/permission.php");
                } else {
                    $io->warning("Không tìm thấy permission của module '{$moduleName}' trong config/permission.php (có thể đã bị xóa thủ công)");
                }
            } catch (\Exception $e) {
                $io->warning('Không thể cập nhật permission.php: ' . $e->getMessage());
            }

            return Command::SUCCESS;
        } else {
            $io->error('Không có file nào được xóa.');
            return Command::FAILURE;
        }
    }

    private function removePermission(string $moduleName): bool
    {
        $permissionFilePath = $this->projectDir . '/config/permission.php';

        if (!file_exists($permissionFilePath)) {
            throw new \Exception("File permission không tồn tại: {$permissionFilePath}");
        }

        $moduleNameKebab = $this->toKebabCase($moduleName);

        $content = file_get_contents($permissionFilePath);

        // T\u00ecm v\u00e0 x\u00f3a to\u00e0n b\u1ed9 block item c\u00f3 name t\u01b0\u01a1ng \u1ee9ng trong m\u1ea3ng
        // Pattern kh\u1edbp v\u1edbi c\u1ea3 \r\n (Windows) l\u1eabn \n (Unix)
        $pattern = '/\r?\n    \[\r?\n        "name" => "' . preg_quote($moduleNameKebab, '/') . '",\r?\n        "actions" => \[[\s\S]*?\]\r?\n    \],/';

        $updatedContent = preg_replace($pattern, '', $content);

        if ($updatedContent === $content) {
            // Không tìm thấy item nào khớp
            return false;
        }

        file_put_contents($permissionFilePath, $updatedContent);

        return true;
    }

    private function toKebabCase(string $string): string
    {
        // Convert PascalCase sang kebab-case
        $result = preg_replace('/([a-z])([A-Z])/', '$1-$2', $string);
        return strtolower($result);
    }
}
