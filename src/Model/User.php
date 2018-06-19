<?php

namespace Sledgehammer\Wordpress\Model;

use ArrayAccess;
use Generated\UserMeta;
use Sledgehammer\Core\Base;
use Sledgehammer\Wordpress\Meta;

/**
 * Wordpress "User".
 */
class User extends Base implements ArrayAccess
{
    public $id;
    public $login;
    public $password;
    public $nickname;
    public $email;
    public $url;
    public $registered;
    public $activation_key;
    public $status;
    public $display_name;
    
    
    /**
     * @var Collection|UserMeta[] The meta fields
     */
    public $meta;

    /**
     * @var Collection|Post[] The posts
     */
    public $posts;

    /**
     * @var Collection|Comment[] The comments
     */
    public $comments;

    const META_MODEL = 'UserMeta';

    use Meta;
}
