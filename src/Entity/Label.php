<?php

namespace Classifier\Entity\Label;

class Label extends \Spot\Entity
{
    protected static $table = 'labels';

    public static function fields()
    {
        return [
            'id'   => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'name' => ['type' => 'string', 'required' => true, 'index' => true]
        ];
    }
}
