<?php

namespace App\Entity;

use DateTime;
use Exception;
use App\Utils\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GroupRepository")
 * @ORM\Table(name="_group")
 * @UniqueEntity(
 *      fields={"slug", "parent"},
 *      errorPath="slug",
 *      message="Another group with this slug already exists in this group.",
 *      ignoreNull=false
 * )
 */
class Group implements InstancierInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Serializer\Groups({"groups", "group_tree", "group_explore", "user"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"lab", "start_lab", "stop_lab", "groups", "group_tree", "group_explore", "user", "instance_manager"})
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\GroupUser", mappedBy="group", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Serializer\Groups({"group_users", "group_tree", "group_explore"})
     */
    private $users;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"groups", "group_tree"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Groups({"groups", "group_tree"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="smallint")
     * @Serializer\Groups({"groups", "group_tree"})
     */
    private $visibility;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Groups({"groups", "group_tree"})
     */
    private $pictureFilename;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"groups", "group_tree"})
     */
    private $slug;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Groups({"groups", "group_tree"})
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Group", inversedBy="children")
     * @Serializer\Groups({"group_parent", "group_explore", "group_details", "instance_manager"})
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Group", mappedBy="parent")
     * @Serializer\Groups({"group_children", "group_tree", "group_explore", "group_details"})
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\LabInstance", mappedBy="_group")
     * @Serializer\Groups({"instances"})
     * @var Collection|LabInstance[]
     */
    private $labInstances;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Groups({"lab", "start_lab", "stop_lab", "groups", "group_tree", "user", "instance_manager"})
     */
    private $uuid;

    public const VISIBILITY_PRIVATE  = 0;
    public const VISIBILITY_INTERNAL = 1;
    public const VISIBILITY_PUBLIC   = 2;
    public const ROLE_USER           = 'user';
    public const ROLE_ADMIN          = 'admin';
    public const ROLE_OWNER          = 'owner';

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->children = new ArrayCollection();
        $this->uuid = (string) new Uuid();
        $this->labInstances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return User
     * 
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("owner")
     * @Serializer\Groups({"group_tree", "group_explore", "group_details", "user"})
     */
    public function getOwner(): User
    {
        $owner = $this->users->filter(function ($user) {
            return $user->getRole() === self::ROLE_OWNER;
        })->first();

        return $owner->getUser();
    }

    public function isOwner(User $user): bool
    {
        $owner = $this->getOwner();

        return $owner === $user;
    }

    /**
     * Returns a collection containing group administratos
     *
     * @return Collection|User[]
     */
    public function getAdmins(): Collection
    {
        $admins = $this->users->filter(function ($user) {
            return $user->getRole() === self::ROLE_ADMIN;
        });

        return $admins;
    }

    public function isAdmin(User $user): bool
    {
        $admins = $this->getAdmins();

        return $admins->exists(function ($key, $admin) use ($user) {
            return $admin->getUser()->getId() == $user->getId();
        });
    }

    public function isElevatedUser(User $user): bool
    {
        return $this->isOwner($user) || $this->isAdmin($user);
    }

    public function getVisibility(): ?int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getPictureFilename(): ?string
    {
        return $this->pictureFilename;
    }

    public function setPictureFilename(?string $pictureFilename): self
    {
        $this->pictureFilename = $pictureFilename;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"groups", "group_tree", "group_explore", "user"})
     */
    public function getPicture(): ?string
    {
        if ($this->getPictureFilename() == null || $this->getPictureFilename() === "") {
            return null;
        }

        $imagePath = 'uploads/group/avatar/' . $this->getId() . '/' . $this->getPictureFilename();

        return $imagePath;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"groups", "group_tree", "group_explore", "user", "instance_manager"})
     */
    public function getPath(): ?string
    {
        $path = $this->slug;
        $group = $this;

        while ($group->getParent() !== null) {
            $group = $group->getParent();

            $path = $group->getSlug() . '/' . $path;
        }

        return $path;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("users")
     * @Serializer\Groups({"group_details"})
     *
     * @return Collection|User[]
     */
    public function getUsers()
    {
        return $this->users->map(function ($value) {
            return $value->getUser();
        });
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"group_details"})
     *
     * @return integer
     */
    public function getUsersCount(): int
    {
        return $this->users->count();
    }

    public function getGroupUserEntry(User $user): ?GroupUser
    {
        return $this->users->filter(function ($value) use ($user) {
            return $value->getUser() === $user;
        })->first() ?: null;
    }

    /**
     * @return User
     */
    public function getUser(User $user): User
    {
        return $this->getGroupUserEntry($user)->getUser();
    }

    public function hasUser(User $user): bool
    {
        return $this->users->exists(function ($key, $value) use ($user) {
            return $value->getUser() === $user;
        });
    }

    public function addUser(User $user, string $role = Group::ROLE_USER): self
    {
        if (!$this->hasUser($user)) {
            $this->users[] = new GroupUser($user, $this, $role);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $result = $this->users->removeElement($this->getGroupUserEntry($user));

        if (!$result) {
            throw new Exception("User not found.");
        }

        return $this;
    }

    public function getUserPermissions(User $user): Collection
    {
        return $this->getGroupUserEntry($user)->getPermissions();
    }

    public function getUserRole(User $user): ?string
    {
        $groupUser = $this->getGroupUserEntry($user);
        return $groupUser ? $groupUser->getRole() : null;
    }

    public function setUserRole(User $user, string $role): self
    {
        if (!in_array($role, [self::ROLE_USER, self::ROLE_ADMIN])) {
            throw new Exception('Incorrect role provided.');
        }

        $this->getGroupUserEntry($user)->setRole($role);

        return $this;
    }

    public function getUserRegistrationDate(User $user): DateTime
    {
        return $this->getGroupUserEntry($user)->getCreatedAt();
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function isRootGroup(): bool
    {
        return $this->parent === null;
    }

    /**
     * @return Collection|Group[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Collection|LabInstance[]
     */
    public function getLabInstances()
    {
        return $this->labInstances;
    }

    public function addLabInstance(LabInstance $labInstance): self
    {
        if (!$this->labInstances->contains($labInstance)) {
            $this->labInstances[] = $labInstance;
            $labInstance->setGroup($this);
        }

        return $this;
    }

    public function removeLabInstance(LabInstance $labInstance): self
    {
        if ($this->labInstances->contains($labInstance)) {
            $this->labInstances->removeElement($labInstance);
            // set the owning side to null (unless already changed)
            if ($labInstance->getGroup() === $this) {
                $labInstance->setGroup(null);
            }
        }

        return $this;
    }

    public function getType(): string
    {
        return 'group';
    }
}
