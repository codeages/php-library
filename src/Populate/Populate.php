<?php

namespace Codeages\Library\Populate;

class Populate
{
    private $items;

    private $idKey;

    private $findFunc;

    private $map;

    public function __construct(&$items)
    {
        $this->items = &$items;
    }

    public function id($idKey)
    {
        $this->idKey = $idKey;
        return $this;
    }

    public function find($func)
    {
        $this->findFunc = $func;
        return $this;
    }

    public function map($map)
    {
        $this->map = $map;

        return $this;
    }

    public function exec()
    {
        $ids = array_column($this->items, $this->idKey);
        $ids = array_values(array_unique($ids));

        $func = $this->findFunc;
        $founded = $func($ids);
        $founded = array_column($founded, null, 'id');

        foreach ($this->items as &$item) {
            foreach ($this->map as $k1 => $k2) {
                $item[$k1] = $founded[$item[$this->idKey]][$k2];
            }
            unset($item);
        }
    }
}