<?php

namespace Sledgehammer\Wordpress\Model;

use Sledgehammer\Core\Base;
use Sledgehammer\Orm\Repository;

class Option extends Base
{
    public $id;
    public $key;
    public $value;
    public $autoload;

    /**
     * Set or create the option.
     *
     * @param string       $key
     * @param string|array $value
     */
    public static function overwrite($key, $value)
    {
        $repo = Repository::instance();
        $option = $repo->oneOption(['key' => $key], true);
        if ($option === null) {
            $option = $repo->createOption(['key' => $key]);
        }
        $option->value = $value;
        $repo->saveOption($option);
    }
}
