<?php

namespace Sledgehammer\Wordpress\Model;

use Sledgehammer\Core\Base;

class CommentMeta extends Base
{
    public $id;
    public $key;
    public $value;

    /**
     * @var Comment The associated Comment
     */
    public $comment;
}
