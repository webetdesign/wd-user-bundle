<?php

namespace WebEtDesign\UserBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS)]
class Exportable
{
    const TYPE_DATA          = 'TYPE_DATA';
    const TYPE_SONATA_MEDIA  = 'TYPE_SONATA_MEDIA';
    const TYPE_VICH_UPLOADER = 'TYPE_VICH_UPLOADER';

    public function __construct(
        public ?string $type = null,
        public ?string $name = null
    ) {}

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return Exportable
     */
    public function setName($name): Exportable
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getType()
    {
        return $this->type;
    }
}
