<?php


namespace WebEtDesign\UserBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]

/**
 * @deprecated use symfony PasswordStrength contraint instead
 */
class PasswordStrength extends Constraint
{
    public function __construct(
        public string $tooShortMessage = 'password_strength.length.error',
        public string $message = 'password_strength.sensibility.error',
        public int $minLength = 6,
        public ?int $minStrength = 3,
        public bool $unicodeEquality = false,
        array $groups = null,
        mixed $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }

    public function getDefaultOption(): string
    {
        return 'minStrength';
    }

    public function getRequiredOptions(): array
    {
        return ['minStrength'];
    }

    public function getTargets(): array
    {
        return array(self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT);
    }
}
