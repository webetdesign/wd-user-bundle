<?php

namespace WebEtDesign\UserBundle\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use WebEtDesign\UserBundle\Attribute\Anonymizable;
use WebEtDesign\UserBundle\Attribute\Anonymizer;
use WebEtDesign\UserBundle\Attribute\Exportable;

#[ORM\Entity]
#[Anonymizable]
#[Exportable(name: 'record')]
class RgpdRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column]
    #[Exportable(name: 'label')]
    #[Anonymizer]
    private string $name = 'synthetic';

    #[ORM\Column]
    private string $secret = 'not exported';

    #[ORM\ManyToMany(targetEntity: self::class)]
    #[Exportable]
    #[Anonymizer(action: Anonymizer::ACTION_SET_NULL)]
    private Collection $tags;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[Exportable]
    #[Anonymizer(action: Anonymizer::ACTION_CASCADE)]
    private Collection $children;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[Exportable]
    #[Anonymizer(action: Anonymizer::ACTION_SET_NULL)]
    private ?self $parent = null;

    #[ORM\OneToOne(targetEntity: self::class)]
    #[Exportable]
    #[Anonymizer(action: Anonymizer::ACTION_CASCADE)]
    private ?self $partner = null;

    #[ORM\OneToOne(targetEntity: self::class)]
    #[Exportable(type: Exportable::TYPE_SONATA_MEDIA, name: 'media')]
    private ?self $media = null;

    #[Exportable(type: Exportable::TYPE_VICH_UPLOADER, name: 'attachment')]
    private ?string $file = null;

    private ?\DateTime $anonymizedAt = null;

    public function __construct(int $id)
    {
        $this->id = $id;
        $this->tags = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getSecret(): string { return $this->secret; }
    public function getTags(): Collection { return $this->tags; }
    public function removeTag(self $tag): void { $this->tags->removeElement($tag); }
    public function getChildren(): Collection { return $this->children; }
    public function removeChild(self $child): void { $this->children->removeElement($child); }
    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): void { $this->parent = $parent; }
    public function getPartner(): ?self { return $this->partner; }
    public function setPartner(?self $partner): void { $this->partner = $partner; }
    public function getMedia(): ?self { return $this->media; }
    public function setMedia(?self $media): void { $this->media = $media; }
    public function getAnonymizedAt(): ?\DateTime { return $this->anonymizedAt; }
    public function setAnonymizedAt(\DateTime $at): void { $this->anonymizedAt = $at; }
}

#[ORM\Entity]
class UnselectedRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id = 99;

    #[ORM\Column]
    #[Exportable]
    private string $name = 'must not escape';

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
}
