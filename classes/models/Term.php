<?php


namespace Sledgehammer\Wordpress;
class Term extends \Sledgehammer\Object implements \ArrayAccess
{
    public $id;
    public $name;
    public $slug;
    public $group;
    /**
     * @var \Generated\TermTaxonomy|\Sledgehammer\Collection  A collection with the associated TermTaxonomy
     */
    public $taxonomy;
    /**
     * @var \Generated\TermMeta|\Sledgehammer\Collection  A collection with the associated TermMetas
     */
    public $meta;

    const META_MODEL = 'TermMeta';
    use Meta;
}

