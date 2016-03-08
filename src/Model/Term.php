<?php
namespace Sledgehammer\Wordpress\Model;

use ArrayAccess;
use Generated\TermMeta;
use Generated\Taxonomy;
use Sledgehammer\Core\Collection;
use Sledgehammer\Core\Object;
use Sledgehammer\Wordpress\Meta;


class Term extends Object implements ArrayAccess
{
    public $id;
    public $name;
    public $slug;
    public $group;
    /**
     * @var Taxonomy[]|Collection  A collection with the associated Taxonomy
     */
    public $taxonomy;
    /**
     * @var TermMeta[]|Collection  A collection with the associated TermMetas
     */
    public $meta;

    const META_MODEL = 'TermMeta';
    use Meta;
}

