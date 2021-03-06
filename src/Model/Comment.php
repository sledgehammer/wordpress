<?php

namespace Sledgehammer\Wordpress\Model;

use ArrayAccess;
use Sledgehammer\Core\Collection;
use Sledgehammer\Core\Base;
use Sledgehammer\Wordpress\Meta;

class Comment extends Base implements ArrayAccess
{
    public $id;
    public $author;
    public $email;
    public $url;
    public $ip;
    public $date;
    public $date_gmt;
    public $content;
    public $karma;
    public $approved;
    public $useragent;
    public $type;

    public $parent_id;

    
    public $user;

    /**
     * @var Post
     */
    public $post;

    /**
     * @var CommentMeta[]|Collection The meta fields
     */
    public $meta;

    const META_MODEL = 'CommentMeta';
    use Meta;
}
