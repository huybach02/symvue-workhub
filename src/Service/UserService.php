<?php

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\UserDTO;
use App\DTO\UserPositionDTO;
use App\Entity\Department;
use App\Entity\ConversationUser;
use App\Entity\Position;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Entity\UserPosition;
use App\Repository\ImageRepository;
use App\Repository\UserPermissionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\DepartmentService;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private readonly ParameterBagInterface $parameterBag,
        private readonly ImageRepository $imageRepository,
        private readonly DepartmentService $boPhanService,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->userRepository->createQueryBuilder('u');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'u');

        // Map collection to JSON
        $result['collection'] = array_map(
            function (User $user) {
                $data = $user->jsonSerialize();
                $data['image'] = $this->imageRepository->getImages($user, 'avatar');
                return $data;
            },
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->userRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $data = $item->jsonSerialize();
        $data['image'] = $this->imageRepository->getImages($item, 'avatar');

        return $data;
    }

    public function create(UserDTO $dto): array
    {
        $item = new User();

        $password = generateRandomString(10);

        $checkMaNhanVien = $this->userRepository->findOneBy([
            'maNhanVien' => $dto->maNhanVien,
        ]);

        if ($checkMaNhanVien) {
            throw new \Exception(t('error.ma_nhan_vien_exists', [
                '%ma_nhan_vien%' => $dto->maNhanVien,
            ]));
        }

        // Thông tin cá nhân
        $item->setMaNhanVien($dto->maNhanVien);
        $item->setName($dto->name);
        $item->setGender($dto->gender);
        $item->setBirthday($dto->birthday);
        $item->setCmnd($dto->cmnd);
        $item->setNgayCapCmnd($dto->ngayCapCmnd);
        $item->setNoiCapCmnd($dto->noiCapCmnd);

        // Thông tin công việc
        $item->setBoPhanId($dto->boPhanId);
        $item->setNgayVaoLam($dto->ngayVaoLam);
        $item->setStatus($dto->status);

        // Thông tin liên hệ
        $item->setEmail($dto->email);
        $item->setPhone($dto->phone);
        $item->setProvince($dto->province);
        $item->setWard($dto->ward);
        $item->setAddress($dto->address);

        $item->setPassword($this->passwordHasher->hashPassword($item, $password));

        $this->entityManager->persist($item);
        $this->entityManager->flush();

        if ($dto->avatar) {
            $this->imageRepository->addOneImage($item, $dto->avatar, "avatar");
        }

        return $item->jsonSerialize();
    }

    public function update(int $id, UserDTO $dto): array
    {
        $item = $this->userRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        // Thông tin cá nhân
        $item->setMaNhanVien($dto->maNhanVien);
        $item->setName($dto->name);
        $item->setGender($dto->gender);
        $item->setBirthday($dto->birthday);
        $item->setCmnd($dto->cmnd);
        $item->setNgayCapCmnd($dto->ngayCapCmnd);
        $item->setNoiCapCmnd($dto->noiCapCmnd);

        // Thông tin công việc
        $item->setBoPhanId($dto->boPhanId);
        $item->setNgayVaoLam($dto->ngayVaoLam);
        $item->setStatus($dto->status);

        // Thông tin liên hệ
        $item->setEmail($dto->email);
        $item->setPhone($dto->phone);
        $item->setProvince($dto->province);
        $item->setWard($dto->ward);
        $item->setAddress($dto->address);

        $this->entityManager->flush();

        if ($dto->avatar) {
            $this->imageRepository->removeImages($item);
            $this->imageRepository->addOneImage($item, $dto->avatar, "avatar");
        }

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->userRepository->find($id);

        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        // Nếu user này là quản lý của 1 bộ phận thì báo lỗi không cho xóa
        $checkIsManager = $this->entityManager->getRepository(Department::class)->findOneBy([
            'quanLyBoPhan' => $item,
        ]);

        if ($checkIsManager) {
            throw new \Exception(t('error.user_is_manager', [
                '%name%' => $item->getName(),
                '%bo_phan%' => $checkIsManager->getTenBoPhan(),
            ]));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function getDataSelect(array $params): array
    {
        $qb = $this->userRepository->createQueryBuilder('u');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'u');

        // Map collection to JSON
        $result['collection'] = array_map(
            function (User $user) {
                $data = $user->jsonSerialize();
                return [
                    'label' => $data['name'],
                    'value' => $data['id'],
                ];
            },
            $result['collection']
        );

        return $result['collection'];
    }

    public function getMaNhanVien(): string
    {
        $conn = $this->entityManager->getConnection();
        $sql = "SELECT MAX(CAST(SUBSTRING(ma_nhan_vien FROM 3) AS INTEGER)) FROM \"user\" WHERE ma_nhan_vien ~ '^NV[0-9]+$'";
        $maxSoThuTu = (int) $conn->executeQuery($sql)->fetchOne();

        $newSoThuTu = $maxSoThuTu + 1;

        return "NV" . str_pad((string) $newSoThuTu, 5, '0', STR_PAD_LEFT);
    }

    public function getProvince(): array
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $filePath = $projectDir . '/public/province.json';

        if (!file_exists($filePath)) {
            throw new \Exception(t('error.province_file_not_found'));
        }

        $content = file_get_contents($filePath);
        $items = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(t('error.province_file_invalid_json'));
        }

        return $items ?? [];
    }

    public function getWard(string $provinceCode): array
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $filePath = $projectDir . '/public/ward.json';

        if (!file_exists($filePath)) {
            throw new \Exception(t('error.ward_file_not_found'));
        }

        $content = file_get_contents($filePath);
        $items = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(t('error.ward_file_invalid_json'));
        }

        $items = array_filter($items, fn($item) => $item['parent_code'] === $provinceCode);

        return $items ?? [];
    }

    public function getUserPosition(int $userId): ?array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception(t('error.not_found'));
        }

        $userPosition = $this->entityManager->getRepository(UserPosition::class)->findOneBy(
            ['member' => $user],
            ['id' => 'DESC']
        );

        return $userPosition?->jsonSerialize();
    }

    public function saveUserPosition(int $userId, UserPositionDTO $dto): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception(t('error.not_found'));
        }

        $department = $this->entityManager->getRepository(Department::class)->find($dto->departmentId);
        if (!$department) {
            throw new \Exception(t('error.not_found'));
        }

        $position = $this->entityManager->getRepository(Position::class)->find($dto->positionId);
        if (!$position) {
            throw new \Exception(t('error.not_found'));
        }

        if ($position->getDepartment()?->getId() !== $department->getId()) {
            throw new \Exception('Chức vụ không thuộc phòng ban/bộ phận đã chọn.');
        }

        $userPosition = $this->entityManager->getRepository(UserPosition::class)->findOneBy(
            ['member' => $user],
            ['id' => 'DESC']
        );

        if (!$userPosition) {
            $userPosition = new UserPosition();
            $userPosition->setMember($user);
            $this->entityManager->persist($userPosition);
        }

        $userPosition->setDepartment($department);
        $userPosition->setPosition($position);
        $userPosition->setSalary($dto->salary);
        $userPosition->setAllowances($dto->allowances);
        $userPosition->setAllowancesTotal(array_merge($position->getAllowances() ?? [], $dto->allowances ?? []));
        $userPosition->setEffectiveFrom(new \DateTime($dto->effectiveFrom));
        $userPosition->setEffectiveTo(new \DateTime($dto->effectiveTo));
        $userPosition->setProbationFrom(new \DateTime($dto->probationFrom));
        $userPosition->setProbationTo(new \DateTime($dto->probationTo));
        $userPosition->setSalaryNet($dto->salary);
        $userPosition->setInsuranceSalary($dto->insuranceSalary);
        $userPosition->setInsuranceCode($dto->insuranceCode);
        $userPosition->setNote($dto->note);
        $userPosition->setIsPrimary(true);
        $userPosition->setPositionSnapshot($position->jsonSerialize());

        $this->entityManager->flush();

        $this->createUserPermission($user, $position);

        return $userPosition->jsonSerialize();
    }

    public function uploadUserContracts(int $userId, Request $request): array
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new \Exception(t('error.not_found'));
        }

        $userPosition = $this->entityManager->getRepository(UserPosition::class)->findOneBy(
            ['member' => $user],
            ['id' => 'DESC']
        );

        if (!$userPosition) {
            throw new \Exception('Vui lòng cập nhật vị trí công việc trước khi tải hợp đồng.');
        }

        $files = $request->files->get('files', []);
        if ($files && !is_array($files)) {
            $files = [$files];
        }

        if (!$files || count($files) === 0) {
            throw new \Exception('Vui lòng chọn file hợp đồng.');
        }

        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $baseUrl = $request->getSchemeAndHttpHost();
        $contracts = $userPosition->getContracts() ?? [];

        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $allowedExtensions, true)) {
                throw new \Exception('Chỉ hỗ trợ file .doc, .docx, .pdf.');
            }

            $originalName = $file->getClientOriginalName();
            $size = $file->getSize();
            $mime = $file->getMimeType();
            $url = uploadFile($file, 'contracts', $baseUrl, 'contract');
            $contracts[] = [
                'name' => $originalName,
                'url' => $url,
                'size' => $size,
                'mime' => $mime,
                'extension' => $extension,
                'uploadedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
            ];
        }

        $userPosition->setContracts($contracts);
        $this->entityManager->flush();

        return $userPosition->jsonSerialize();
    }

    public function createUserPermission(User $user, Position $position)
    {
        $department = $position->getDepartment();
        if (!$department) {
            throw new \Exception(t('error.not_found'));
        }

        $userPermission = $this->entityManager->getRepository(UserPermission::class)->findOneBy(
            ['userId' => $user->getId(), 'departmentId' => $department->getId(), 'positionId' => $position->getId()]
        );

        if (!$userPermission) {
            $userPermission = new UserPermission();
        }

        $departmentPermissions = $department->getPhanQuyen() ?? [];
        $permissions = $departmentPermissions[$position->getCode()] ?? [];

        $userPermission->setUserId($user->getId());
        $userPermission->setDepartmentId($department->getId());
        $userPermission->setPositionId($position->getId());
        $userPermission->setPhanQuyen($permissions);
        $this->entityManager->persist($userPermission);
        $this->entityManager->flush();

        $this->boPhanService->mergeUserPermissions($user->getId());
    }

    public function createConversationByDepartment()
    {
        // $conversation = new Conversation();
        // $conversation->setType(Constanst::TYPE_CONVERSATION['department']);
        // $conversation->setName($item->getTenBoPhan());
        // $this->entityManager->persist($conversation);
        // $this->entityManager->flush();

        // $item->setConversation($conversation);
        // $this->entityManager->flush();

        // $conversationUser = new ConversationUser();
        // $conversationUser->setConversation($conversation);
        // $conversationUser->setMember($user);
        // $this->entityManager->persist($conversationUser);
        // $this->entityManager->flush();
    }
}
