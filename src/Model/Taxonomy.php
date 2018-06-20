<?php

namespace Sledgehammer\Wordpress\Model;

use Sledgehammer\Core\Base;
use Sledgehammer\Core\Collection;

class Taxonomy extends Base
{
    public $id;
    public $taxonomy;
    public $description;
    public $parent_id;
    public $count;

    /**
     * @var Term The associated Term
     */
    public $term;

    /**
     * @var Collection|Post[] A collection with the associated Posts
     */
    public $posts;
}
