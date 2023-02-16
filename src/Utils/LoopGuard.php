<?php


namespace WebEtDesign\UserBundle\Utils;


class LoopGuard
{
    private array $items = [];

    public function add(string $class, int $id)
    {
        if (!isset($this->items[$class])) {
            $this->items[$class] = [];
        }

        if (!in_array($id, $this->items[$class])) {
            $this->items[$class][] = $id;
        }
    }

    public function contains(string $class, int $id): bool
    {
        if (!isset($this->items[$class])) {
            return false;
        }

        if (in_array($id, $this->items[$class])) {
            return true;
        }

        return false;
    }

    public function reset()
    {
        $this->items = [];
    }

}
