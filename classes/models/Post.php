<?php
namespace Sledgehammer\Wordpress;
use Sledgehammer\Object;

/**
 * Wordpress "post"
 */
class Post extends Object implements \ArrayAccess
{

    public $id;
    public $date;
    public $date_gmt;
    public $content;
    public $title;
    public $excerpt;
    public $status;
    public $comment_status;
    public $ping_status;
    public $password;
    public $slug;
    public $to_ping;
    public $pinged;
    public $modified;
    public $modified_gmt;
    public $content_filtered;
    public $parent_id;
    public $guid;
    public $menu_order;
    public $type;
    public $mimetype;
    public $comment_count;
    /**
     * @var \Generated\User  The associated User
     */
    public $author;
    /**
     * @var \Generated\PostMeta|\Sledgehammer\Collection  A collection with the associated PostMetas
     */
    public $meta;
    /**
     * @var \Generated\TermTaxonomy|\Sledgehammer\Collection  A collection with the associated TermTaxonomy
     */
    public $taxonomies;

    const META_MODEL = 'PostMeta';
    use Meta;

}