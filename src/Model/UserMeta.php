<?php

namespace Sledgehammer\Wordpress\Model;

use Sledgehammer\Core\Base;

class UserMeta extends Base
{
    public $id;
    public $key;
    public $value;

    /**
     * @var User The associated User
     */
    public $user;
}
