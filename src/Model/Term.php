<?php
namespace Sledgehammer\Wordpress\Model;

use ArrayAccess;
use Generated\TermMeta;
use Generated\TermTaxonomy;
use Sledgehammer\Core\Collection;


class Term extends \Sledgehammer\Object implements ArrayAccess
{
    public $id;
    public $name;
    public $slug;
    public $group;
    /**
     * @var TermTaxonomy[]|Collection  A collection with the associated TermTaxonomy
     */
    public $taxonomy;
    /**
     * @var TermMeta[]|Collection  A collection with the associated TermMetas
     */
    public $meta;

    const META_MODEL = 'TermMeta';
    use Meta;
}

