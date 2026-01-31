<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'make:module',
    description: 'Tạo Controller, Service và DTO với CRUD boilerplate cho module',
)]
class MakeModuleCommand extends Command
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('module', InputArgument::REQUIRED, 'Tên module (VD: UserManagement)')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'Tên Entity (VD: User)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $moduleName = $input->getArgument('module');
        $entityName = $input->getOption('entity');

        // Validate input
        if (!$entityName) {
            $io->error('Option --entity là bắt buộc. VD: --entity=User');
            return Command::FAILURE;
        }

        // Validate entity exists
        $entityPath = $this->projectDir . '/src/Entity/' . $entityName . '.php';
        if (!file_exists($entityPath)) {
            $io->error("Entity '{$entityName}' không tồn tại tại: {$entityPath}");
            return Command::FAILURE;
        }

        // Prepare placeholders
        $placeholders = [
            '{{MODULE_NAME}}' => $moduleName,
            '{{ENTITY_NAME}}' => $entityName,
            '{{MODULE_NAME_LOWER}}' => $this->toKebabCase($moduleName),
            '{{ENTITY_VAR}}' => lcfirst($entityName),
        ];

        try {
            // Generate Controller
            $controllerContent = $this->generateFromTemplate('controller.boilerplate', $placeholders);
            $controllerPath = $this->projectDir . '/src/Controller/' . $moduleName . 'Controller.php';
            $this->writeFile($controllerPath, $controllerContent);
            $io->success("Controller đã được tạo: {$controllerPath}");

            // Generate Service
            $serviceContent = $this->generateFromTemplate('service.boilerplate', $placeholders);
            $servicePath = $this->projectDir . '/src/Service/' . $moduleName . 'Service.php';
            $this->writeFile($servicePath, $serviceContent);
            $io->success("Service đã được tạo: {$servicePath}");

            // Generate DTO
            $dtoContent = $this->generateFromTemplate('dto.boilerplate', $placeholders);
            $dtoPath = $this->projectDir . '/src/DTO/' . $moduleName . 'DTO.php';
            $this->writeFile($dtoPath, $dtoContent);
            $io->success("DTO đã được tạo: {$dtoPath}");

            $io->note([
                'Các file đã được tạo thành công!',
                'Lưu ý: Cập nhật properties trong DTO (xem TODO comments)',
                'Và cập nhật logic mapping trong Service (xem TODO comments)',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Lỗi: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function generateFromTemplate(string $templateName, array $placeholders): string
    {
        $templatePath = $this->projectDir . '/boilerplates/' . $templateName;

        if (!file_exists($templatePath)) {
            throw new \Exception("Template không tồn tại: {$templatePath}");
        }

        $content = file_get_contents($templatePath);

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $content
        );
    }

    private function writeFile(string $path, string $content): void
    {
        if (file_exists($path)) {
            throw new \Exception("File đã tồn tại: {$path}");
        }

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
    }

    private function toKebabCase(string $string): string
    {
        // Convert PascalCase to kebab-case
        $result = preg_replace('/([a-z])([A-Z])/', '$1-$2', $string);
        return strtolower($result);
    }
}
