<?php

namespace Sledgehammer\Wordpress\Model;

use Sledgehammer\Core\Base;

class TermMeta extends Base
{
    public $id;
    public $key;
    public $value;

    /**
     * @var Term The associated Term
     */
    public $term;
}
