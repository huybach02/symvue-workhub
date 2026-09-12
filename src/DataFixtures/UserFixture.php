<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Branch;
use App\Entity\Department;
use App\Entity\Image;
use App\Entity\Position;
use App\Entity\User;
use App\Entity\UserPosition;
use App\Entity\Warehouse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private const NUMBER_OF_USERS = 100;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    // Khai báo fixture này thuộc group "user"
    public static function getGroups(): array
    {
        return ['user'];
    }

    public function getDependencies(): array
    {
        return [
            MainBranchAndWarehouseFixture::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('vi_VN'); // Sử dụng locale Tiếng Việt
        $usedNames = [];

        $images = [
            "https://img.freepik.com/free-psd/3d-illustration-person-with-sunglasses_23-2149436188.jpg",
            "https://img.freepik.com/free-psd/3d-illustration-human-avatar-profile_23-2150671122.jpg",
            "https://img.freepik.com/free-psd/3d-illustration-person-with-sunglasses_23-2149436180.jpg",
            "https://img.freepik.com/free-psd/3d-illustration-person-with-sunglasses-green-hair_23-2149436201.jpg",
            "https://img.freepik.com/free-psd/3d-illustration-person-with-glasses_23-2149436191.jpg?w=360",
            "https://img.freepik.com/free-psd/3d-illustration-person-with-long-hair_23-2149436197.jpg?semt=ais_hybrid&w=740",
            "https://img.freepik.com/free-psd/3d-illustration-person-with-pink-hair_23-2149436186.jpg?semt=ais_hybrid&w=740",
            "https://img.freepik.com/premium-psd/3d-render-avatar-character_23-2150611783.jpg",
            "https://i.pinimg.com/736x/37/35/29/373529bb20ebc2b8bbe8162896ae0904.jpg",
        ];

        // Tạo 1 admin user mặc định
        $admin = new User();
        $admin->setMaNhanVien('NV00000');
        $admin->setEmail('huybach2002ct@gmail.com');
        $admin->setName('Administrator');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPhone('0901234567');
        $admin->setGender('male');
        $admin->setStatus(1);
        $admin->setIsFirstLogin(0);
        $admin->setEmailVerifiedAt(new \DateTime());
        $manager->persist($admin);

        $mainBranch = $manager->getRepository(Branch::class)->findOneBy(['code' => 'MAIN_BRANCH']);
        $mainWarehouse = $manager->getRepository(Warehouse::class)->findOneBy(['code' => 'MAIN_WAREHOUSE']);

        if (!$mainBranch instanceof Branch || !$mainWarehouse instanceof Warehouse) {
            throw new \RuntimeException('Không tìm thấy MAIN_BRANCH hoặc MAIN_WAREHOUSE.');
        }

        if ($mainWarehouse->getBranch()?->getId() !== $mainBranch->getId()) {
            throw new \RuntimeException('MAIN_WAREHOUSE không thuộc MAIN_BRANCH.');
        }

        $adminDepartment = new Department();
        $adminDepartment->setMaBoPhan('MAIN_ADMIN_DEPARTMENT');
        $adminDepartment->setTenBoPhan('Ban quản trị hệ thống');
        $adminDepartment->setBranch($mainBranch);
        $adminDepartment->setStatus(1);
        $manager->persist($adminDepartment);

        $adminPosition = new Position();
        $adminPosition->setCode('MAIN_ADMIN_POSITION');
        $adminPosition->setName('Quản trị viên hệ thống');
        $adminPosition->setDepartment($adminDepartment);
        $adminPosition->setStatus(1);
        $adminPosition->setIsManager(1);
        $manager->persist($adminPosition);

        $adminUserPosition = new UserPosition();
        $adminUserPosition->setMember($admin);
        $adminUserPosition->setDepartment($adminDepartment);
        $adminUserPosition->setPosition($adminPosition);
        $adminUserPosition->setStatus(1);
        $adminUserPosition->setIsPrimary(1);
        $manager->persist($adminUserPosition);

        // Tạo danh sách vai trò
        $roles = ['USER', 'MANAGER', 'STAFF'];
        $genders = ['male', 'female'];

        $createdUsers = [$admin];

        $maNhanVien = 1;
        // Tạo các user ngẫu nhiên
        for ($i = 0; $i < self::NUMBER_OF_USERS; $i++) {
            $user = new User();

            // Thông tin cơ bản
            $user->setMaNhanVien('NV' . str_pad((string) $maNhanVien++, 5, '0', STR_PAD_LEFT));
            $user->setEmail($faker->unique()->safeEmail());
            $user->setName($this->generateVietnameseUniqueName($usedNames));
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

            // Vai trò
            $maVaiTro = $faker->randomElement($roles);
            $user->setRoles(['ROLE_' . $maVaiTro]);

            // Thông tin cá nhân
            $user->setPhone($faker->optional(0.8)->numerify('09########'));
            $user->setGender($faker->randomElement($genders));
            $user->setBirthday($faker->optional(0.7)->dateTimeBetween('-60 years', '-18 years')?->format('Y-m-d'));
            $user->setDescription($faker->optional(0.5)->sentence(10));

            // Địa chỉ (sử dụng ID giả cho province, district, ward)
            // $user->setProvinceId($faker->optional(0.6)->numerify('##'));
            // $user->setDistrictId($faker->optional(0.6)->numerify('###'));
            // $user->setWardId($faker->optional(0.6)->numerify('####'));
            $user->setAddress($faker->optional(0.6)->address());

            // Trạng thái
            $user->setStatus($faker->randomElement([1])); // 0: inactive, 1: active
            $user->setIsNgoaiGio($faker->randomElement([0, 1])); // 0: cho phép, 1: không cho phép
            $user->setHinhThucLamViec($faker->randomElement(\App\Class\Constanst::HINH_THUC_LAM_VIEC));
            $user->setIsFirstLogin($faker->randomElement([0, 1])); // 0: đã đổi pass, 1: lần đầu

            $manager->persist($user);
            $createdUsers[] = $user;
        }

        $manager->flush();

        // Tạo ảnh avatar qua entity Image cho các user
        foreach ($createdUsers as $createdUser) {
            $image = new Image();
            $image->setPath($faker->randomElement($images));
            $image->setEntityType(User::class);
            $image->setEntityId($createdUser->getId());
            $image->setType('avatar');
            $manager->persist($image);
        }

        $manager->flush();
    }

    private function generateVietnameseUniqueName(array &$usedNames): string
    {
        static $allNames = null;

        if ($allNames === null) {
            $lastNames = [
                'Nguyễn',
                'Trần',
                'Lê',
                'Phạm',
                'Hoàng',
                'Huỳnh',
                'Phan',
                'Vũ',
                'Võ',
                'Đặng',
                'Bùi',
                'Đỗ',
                'Hồ',
                'Ngô',
                'Dương',
                'Lý'
            ];

            $middleNamesMale = [
                'Văn',
                'Hữu',
                'Đình',
                'Công',
                'Quang',
                'Minh',
                'Gia',
                'Thanh',
                'Xuân',
                'Hoài',
                'Thế',
                'Anh',
                'Trọng',
                'Đức'
            ];

            $middleNamesFemale = [
                'Thị',
                'Ngọc',
                'Thu',
                'Phương',
                'Thanh',
                'Bích',
                'Kim',
                'Diễm',
                'Mai',
                'Hoài',
                'Quỳnh',
                'Như',
                'Mỹ',
                'Tường'
            ];

            $firstNamesMale = [
                'An',
                'Bảo',
                'Cường',
                'Dũng',
                'Đạt',
                'Đức',
                'Hải',
                'Hiếu',
                'Hoàng',
                'Hưng',
                'Khang',
                'Khánh',
                'Kiên',
                'Long',
                'Minh',
                'Nam',
                'Nghĩa',
                'Nguyên',
                'Phong',
                'Phúc',
                'Quân',
                'Sơn',
                'Thành',
                'Thắng',
                'Thiện',
                'Trung',
                'Tuấn',
                'Tùng',
                'Việt',
                'Vinh'
            ];

            $firstNamesFemale = [
                'An',
                'Anh',
                'Chi',
                'Diễm',
                'Dung',
                'Giang',
                'Hà',
                'Hạnh',
                'Hiền',
                'Hoa',
                'Hương',
                'Khánh',
                'Lan',
                'Linh',
                'Mai',
                'My',
                'Ngân',
                'Ngọc',
                'Nhung',
                'Nhi',
                'Oanh',
                'Phương',
                'Quỳnh',
                'Thảo',
                'Thư',
                'Trang',
                'Trâm',
                'Uyên',
                'Vy',
                'Yến'
            ];

            $uniqueNames = [];

            foreach ($lastNames as $lastName) {
                foreach ($middleNamesMale as $middleName) {
                    foreach ($firstNamesMale as $firstName) {
                        $uniqueNames["$lastName $middleName $firstName"] = true;
                    }
                }

                foreach ($middleNamesFemale as $middleName) {
                    foreach ($firstNamesFemale as $firstName) {
                        $uniqueNames["$lastName $middleName $firstName"] = true;
                    }
                }
            }

            $allNames = array_keys($uniqueNames);
            shuffle($allNames);
        }

        if (count($usedNames) >= count($allNames)) {
            throw new \RuntimeException('Đã hết tên duy nhất để tạo.');
        }

        do {
            $name = array_pop($allNames);
        } while (isset($usedNames[$name]));

        $usedNames[$name] = true;

        return $name;
    }
}
