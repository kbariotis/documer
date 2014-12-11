<?php

namespace Classifier\Entity\Word;

class Word extends \Spot\Entity
{
    protected static $table = 'words';

    public static function fields()
    {
        return [
            'id'   => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'name' => ['type' => 'string', 'required' => true, 'index' => true],
            'label' => ['type' => 'string', 'required' => true, 'index' => true]
        ];
    }
}
