<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use App\Repository\PostRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    collectionOperations: [
        'get',
        'post' => ['security' => 'is_granted("ROLE_ADMIN")']
    ],
    itemOperations: [
        'delete',
        'get' => [
            'normalization_context' => ['groups' => ['read:post:item']]
        ],
        'put'
    ],
    denormalizationContext: ['groups' => ['write:post:item']],
    normalizationContext: ['groups' => ['read:post:collection']],
    paginationItemsPerPage: 5
)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact'])]
class Post
{
    const STATUS_PUBLISHED = 'published';
    const STATUS_DRAFT     = 'draft';
    const STATUS_DELETED   = 'deleted';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['read:post:collection', 'read:post:item', 'read:user:item'])]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    #[
        Groups(['read:post:collection', 'read:post:item', 'write:post:item', 'read:user:item']),
        Assert\Length(max: 128, maxMessage: "The title of the post must be less than 128 characters"),
        Assert\NotBlank(message: "The title cannot be blank")
    ]
    private $title;

    #[ORM\Column(type: 'text')]
    #[
        Groups(['read:post:item', 'write:post:item']),
        Assert\NotBlank(message: "The content cannot be blank")
    ]
    private $content;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[
        Groups(['read:post:collection', 'read:post:item', 'write:post:item']),
        Assert\Type("\DateTimeInterface"),
        Assert\GreaterThanOrEqual('today')
    ]
    private $publishedAt;

    #[ORM\Column(type: 'string', length: 255)]
    #[
        Groups(['read:post:collection', 'read:post:item', 'write:post:item']),
        Assert\Choice(
            choices: [
                Post::STATUS_PUBLISHED,
                Post::STATUS_DRAFT,
                Post::STATUS_DELETED
            ],
        ),
        Assert\NotBlank(message: "The status cannot be blank")
    ]
    private $status;

    #[ORM\ManyToOne(targetEntity: Author::class, cascade: ['persist'], inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[
        Groups(['read:post:collection', 'read:post:item', 'write:post:item']),
        Assert\NotNull(message: "The author cannot be null")
    ]
    private $author;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPublishedAt(): ?DateTime
    {
        return $this->publishedAt;
    }
    
    public function setPublishedAt(?DateTime $publishedAt): self
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    #[ORM\PreFlush]
    public function managePublishedAt()
    {
        switch ($this->getStatus()) {
            case Post::STATUS_PUBLISHED:
                $this->publishedAt = new DateTime();
                break;
            case Post::STATUS_DRAFT:
                if (null === $this->publishedAt) {
                    $this->publishedAt = new DateTime();
                }
                break;
            case Post::STATUS_DELETED:
                $this->publishedAt = null;
                break;
        }
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;

        return $this;
    }
}
