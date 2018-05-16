<?php

namespace Sledgehammer\Wordpress\Model;

use ArrayAccess;
use Generated\TermMeta;
use Generated\Taxonomy;
use Sledgehammer\Core\Collection;
use Sledgehammer\Core\Base;
use Sledgehammer\Wordpress\Meta;

class Term extends Base implements ArrayAccess
{
    public $id;
    public $name;
    public $slug;
    public $group;
    /**
     * @var Collection|Taxonomy[] The taxonomy
     */
    public $taxonomy;
    /**
     * @var Collection|TermMeta[] The meta fields
     */
    public $meta;

    const META_MODEL = 'TermMeta';
    use Meta;
}
