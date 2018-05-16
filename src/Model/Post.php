<?php

namespace Sledgehammer\Wordpress\Model;

use ArrayAccess;
use Generated\PostMeta;
use Generated\Taxonomy;
use Generated\User;
use Sledgehammer\Core\Base;
use Sledgehammer\Wordpress\Meta;

/**
 * Wordpress "post".
 */
class Post extends Base implements ArrayAccess
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
     * @var User The associated User
     */
    public $author;

    /**
     * @var Collection|PostMeta[] The meta fields
     */
    public $meta;

    /**
     * @var Collection|Taxonomy[] The taxonomies (tags, categories, etc)
     */
    public $taxonomies;

    /**
     * @var Collection|Comment[] The comments
     */
    public $comments;

    const META_MODEL = 'PostMeta';

    use Meta;
}
