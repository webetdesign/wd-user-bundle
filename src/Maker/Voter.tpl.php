<?= /** @noinspection ALL */
"<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

use <?= $abstract_voter_namespace ?>;
use <?= $entity_namespace ?>;

final class <?= $class_name ?> extends <?= $abstract_voter_short_name ?><?= "\n" ?>
{
    public function getPrefixRole(): string
    {
        return '<?= $voter_name ?>';
    }

    public function getSupportedClass(): string
    {
        return <?= $entity_short_name ?>::class;
    }
}
