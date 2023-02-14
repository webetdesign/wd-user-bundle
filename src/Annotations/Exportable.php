<?php

namespace WebEtDesign\UserBundle\Annotations;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Exportable
 * @package WebEtDesign\RgpdBundle\Annotations
 * @Annotation()
 * @Target({"CLASS", "PROPERTY"})
 */
class Exportable
{
    const TYPE_DATA          = 'TYPE_DATA';
    const TYPE_SONATA_MEDIA  = 'TYPE_SONATA_MEDIA';
    const TYPE_VICH_UPLOADER = 'TYPE_VICH_UPLOADER';


    private string  $type;
    private ?string $name;

    /**
     * @inheritDoc
     */
    public function __construct(array $values)
    {
        $this->type = $values['type'] ?? self::TYPE_DATA;
        $this->name = $values['name'] ?? null;
    }

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
