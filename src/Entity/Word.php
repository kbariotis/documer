<?php

namespace Classifier\Entity;

class Word extends \Spot\Entity
{
    protected static $table = 'words';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'name'         => ['type' => 'string', 'required' => true, 'index' => true],
            'label'        => ['type' => 'string', 'required' => true, 'index' => true],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()]

        ];
    }
}
