<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private const int NUMBER_OF_USERS = 100;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('en_US'); // Sử dụng locale Tiếng Việt

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
        $admin->setEmail('huybach2002ct@gmail.com');
        $admin->setName('Administrator');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setMaVaiTro('ADMIN');
        $admin->setPhone('0901234567');
        $admin->setGender('male');
        $admin->setStatus(1);
        $admin->setIsFirstLogin(0);
        $admin->setEmailVerifiedAt(new \DateTime());
        $manager->persist($admin);

        // Tạo danh sách vai trò
        $roles = ['USER', 'MANAGER', 'STAFF'];
        $genders = ['male', 'female'];

        // Tạo các user ngẫu nhiên
        for ($i = 0; $i < self::NUMBER_OF_USERS; $i++) {
            $user = new User();

            // Thông tin cơ bản
            $user->setEmail($faker->unique()->safeEmail());
            $user->setName($faker->name());
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

            // Vai trò
            $maVaiTro = $faker->randomElement($roles);
            $user->setMaVaiTro($maVaiTro);
            $user->setRoles(['ROLE_' . $maVaiTro]);

            // Thông tin cá nhân
            $user->setPhone($faker->optional(0.8)->numerify('09########'));
            $user->setGender($faker->randomElement($genders));
            $user->setBirthday($faker->optional(0.7)->dateTimeBetween('-60 years', '-18 years'));
            $user->setDescription($faker->optional(0.5)->sentence(10));

            // Địa chỉ (sử dụng ID giả cho province, district, ward)
            // $user->setProvinceId($faker->optional(0.6)->numerify('##'));
            // $user->setDistrictId($faker->optional(0.6)->numerify('###'));
            // $user->setWardId($faker->optional(0.6)->numerify('####'));
            $user->setAddress($faker->optional(0.6)->address());

            // Hình ảnh (URL giả)
            $user->setImage($faker->randomElement($images));

            // Trạng thái
            $user->setStatus($faker->randomElement([0, 1])); // 0: inactive, 1: active
            $user->setIsNgoaiGio($faker->randomElement([0, 1])); // 0: cho phép, 1: không cho phép
            $user->setHinhThucLamViec($faker->randomElement([1, 2])); // 1: full time, 2: part time
            $user->setIsFirstLogin($faker->randomElement([0, 1])); // 0: đã đổi pass, 1: lần đầu

            // Email verification (70% đã verify)
            // if ($faker->boolean(70)) {
            //     $user->setEmailVerifiedAt($faker->dateTimeBetween('-1 year', 'now'));
            // }

            // Remember token (30% có token)
            // if ($faker->boolean(30)) {
            //     $user->setRememberToken($faker->sha256());
            // }

            $manager->persist($user);
        }

        $manager->flush();
    }
}
