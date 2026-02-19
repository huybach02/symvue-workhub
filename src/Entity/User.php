<?php

namespace App\Entity;

use App\Interface\ImageableInterface;
use App\Traits\TimestampableTrait;
use App\Repository\UserRepository;
use App\Traits\SoftDeleteableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_IDENTIFIER_EMAIL',
    fields: ['email'],
    options: ['where' => 'deleted_at IS NULL'] // Chỉ unique khi chưa bị xóa
)]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, ImageableInterface
{
    use TimestampableTrait;
    use SoftDeleteableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column(nullable: true)]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $province = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $district = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ward = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerifiedAt = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1, 'comment' => '1: active, 0: inactive'])]
    private int $status = 1;

    #[ORM\Column(type: 'integer', options: ['default' => 0, 'comment' => '0: cho phép ngoại giờ, 1: không cho phép ngoại giờ'])]
    private int $isNgoaiGio = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 1, 'comment' => '1: full time, 2: part time'])]
    private int $hinhThucLamViec = 1;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $boPhanId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $rememberToken = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1, 'comment' => '0: tài khoản đã đổi mật khẩu, 1: tài khoản đăng nhập lần đầu tiên'])]
    private int $isFirstLogin = 1;

    /**
     * @var Collection<int, Folder>
     */
    #[ORM\OneToMany(targetEntity: Folder::class, mappedBy: 'owner')]
    private Collection $folders;

    /**
     * @var Collection<int, Media>
     */
    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'owner')]
    private Collection $media;

    /**
     * @var Collection<int, BoPhan>
     */
    #[ORM\OneToMany(targetEntity: BoPhan::class, mappedBy: 'quanLyBoPhan')]
    private Collection $boPhans;

    public function __construct()
    {
        $this->folders = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->boPhans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getProvince(): ?string
    {
        return $this->province;
    }

    public function setProvince(?string $province): static
    {
        $this->province = $province;

        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): static
    {
        $this->district = $district;

        return $this;
    }

    public function getWard(): ?string
    {
        return $this->ward;
    }

    public function setWard(?string $ward): static
    {
        $this->ward = $ward;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getBirthday(): ?string
    {
        return $this->birthday;
    }

    public function setBirthday(?string $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeInterface
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeInterface $emailVerifiedAt): static
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIsNgoaiGio(): int
    {
        return $this->isNgoaiGio;
    }

    public function setIsNgoaiGio(int $isNgoaiGio): static
    {
        $this->isNgoaiGio = $isNgoaiGio;

        return $this;
    }

    public function getHinhThucLamViec(): int
    {
        return $this->hinhThucLamViec;
    }

    public function setHinhThucLamViec(int $hinhThucLamViec): static
    {
        $this->hinhThucLamViec = $hinhThucLamViec;

        return $this;
    }

    public function getBoPhanId(): ?int
    {
        return $this->boPhanId;
    }

    public function setBoPhanId(?int $boPhanId): static
    {
        $this->boPhanId = $boPhanId;

        return $this;
    }

    public function getRememberToken(): ?string
    {
        return $this->rememberToken;
    }

    public function setRememberToken(?string $rememberToken): static
    {
        $this->rememberToken = $rememberToken;

        return $this;
    }

    public function getIsFirstLogin(): int
    {
        return $this->isFirstLogin;
    }

    public function setIsFirstLogin(int $isFirstLogin): static
    {
        $this->isFirstLogin = $isFirstLogin;

        return $this;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function isLocked(): bool
    {
        return $this->status === 0;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'province' => $this->province,
            'district' => $this->district,
            'ward' => $this->ward,
            'address' => $this->address,
            'birthday' => $this->birthday,
            'image' => $this->image,
            'description' => $this->description,
            'status' => $this->status,
            'boPhanId' => $this->boPhanId,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return Collection<int, Folder>
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    public function addFolder(Folder $folder): static
    {
        if (!$this->folders->contains($folder)) {
            $this->folders->add($folder);
            $folder->setOwner($this);
        }

        return $this;
    }

    public function removeFolder(Folder $folder): static
    {
        if ($this->folders->removeElement($folder)) {
            // set the owning side to null (unless already changed)
            if ($folder->getOwner() === $this) {
                $folder->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedium(Media $medium): static
    {
        if (!$this->media->contains($medium)) {
            $this->media->add($medium);
            $medium->setOwner($this);
        }

        return $this;
    }

    public function removeMedium(Media $medium): static
    {
        if ($this->media->removeElement($medium)) {
            // set the owning side to null (unless already changed)
            if ($medium->getOwner() === $this) {
                $medium->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BoPhan>
     */
    public function getBoPhans(): Collection
    {
        return $this->boPhans;
    }

    public function addBoPhan(BoPhan $boPhan): static
    {
        if (!$this->boPhans->contains($boPhan)) {
            $this->boPhans->add($boPhan);
            $boPhan->setQuanLyBoPhan($this);
        }

        return $this;
    }

    public function removeBoPhan(BoPhan $boPhan): static
    {
        if ($this->boPhans->removeElement($boPhan)) {
            // set the owning side to null (unless already changed)
            if ($boPhan->getQuanLyBoPhan() === $this) {
                $boPhan->setQuanLyBoPhan(null);
            }
        }

        return $this;
    }
}
