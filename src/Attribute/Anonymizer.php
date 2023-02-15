<?php

namespace WebEtDesign\UserBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Anonymizer
{
    const TYPE_STRING     = 'TYPE_STRING';
    const TYPE_EMAIL      = 'TYPE_EMAIL';
    const TYPE_UNIQ       = 'TYPE_UNIQ';
    const TYPE_BOOL_TRUE  = 'TYPE_BOOL_TRUE';
    const TYPE_BOOL_FALSE = 'TYPE_BOOL_FALSE';
    const TYPE_NULL       = 'TYPE_NULL';
    const TYPE_DATE       = 'TYPE_DATE';
    const TYPE_CUSTOM     = 'TYPE_CUSTOM';
    const TYPE_VICH       = 'TYPE_VICH';

    const ACTION_SET_NULL = 'SET_NULL';
    const ACTION_CASCADE  = 'CASCADE';

    public function __construct(
        public string $type = self::TYPE_STRING,
        public ?string $action = null,
        public ?string $service = null,
        public ?string $method = null
    ) {}

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Anonymizer
     */
    public function setType(string $type): Anonymizer
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return Anonymizer
     */
    public function setAction(string $action): Anonymizer
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param mixed|string|null $service
     * @return Anonymizer
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed|string|null $method
     * @return Anonymizer
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }
}
