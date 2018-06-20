<?php

namespace Sledgehammer\Wordpress\Model;

use Sledgehammer\Core\Base;

class PostMeta extends Base
{
    public $id;
    public $key;
    public $value;

    /**
     * @var Post The associated Post
     */
    public $post;
}
