<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // $user = new User();
        // $user->setEmail('huybach2002ct@gmail.com');
        // $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        // $user->setName('Admin');
        // $user->setMaVaiTro('ADMIN');
        // $manager->persist($user);

        // $user2 = new User();
        // $user2->setEmail('bach@gmail.com');
        // $user2->setPassword($this->passwordHasher->hashPassword($user2, 'password'));
        // $user2->setName('Bach');
        // $user2->setMaVaiTro('NHANVIEN');
        // $user2->setHinhThucLamViec(2);
        // $manager->persist($user2);

        // $manager->flush();
    }
}
