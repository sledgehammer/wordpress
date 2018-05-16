<?php

namespace Sledgehammer\Wordpress;

use Generated\Taxonomy;
use Sledgehammer\Core\Base;
use Sledgehammer\Orm\Repository;

class Migrate extends Base
{
    /**
     * Create or fetch existing taxonomy.
     * (also increases count).
     *
     * @param string $type  'category', 'post_tag'
     * @param string $title The term
     * @param string [$slug]  The slug of the term
     *
     * @return Taxonomy
     */
    public static function taxonomy($type, $title, $slug = null)
    {
        $slug = $slug ?: sanitize_title($title);
        $repo = Repository::instance();
        $term = $repo->oneTerm(['slug' => $slug], true);
        if ($term) {
            foreach ($term->taxonomy as $taxonomy) {
                if ($taxonomy->taxonomy === $type) {
                    ++$taxonomy->count;

                    return $taxonomy;
                }
            }
            // the given slug belongs to another taxonomy type.
            return static::taxonomy($type, $title, $slug.'-'.$type);
        }
        $taxonomy = $repo->createTaxonomy([
            'taxonomy' => $type,
            'count' => '1',
        ]);
        $term = $repo->createTerm([
            'name' => $title,
            'slug' => $slug,
            'taxonomy' => [$taxonomy],
        ]);
        $repo->saveTerm($term);

        return $taxonomy;
    }

    /**
     * @param string $title
     * @param string $slug
     *
     * @return Taxonomy
     */
    public static function category($title, $slug = null)
    {
        return static::taxonomy('category', $title, $slug);
    }

    /**
     * @param string $title
     * @param string $slug
     *
     * @return Taxonomy
     */
    public static function tag($title, $slug = null)
    {
        return static::taxonomy('post_tag', $title, $slug);
    }
    /**
     * Open or create the post and patch the properties and meta data.
     *
     * @param array  $properties
     * @param array  $meta
     * @param string $guidPrefix Example: WP_HOME.'/?p=' for posts and WP_HOME.'/?page_id=' for pages.
     */
    public static function post($properties, $meta, $guidPrefix)
    {
        $repo = Repository::instance();
        $conditions = [
            'AND',
            'type' => $properties['type'],
            'slug' => $properties['slug'],
        ];
        $post = $repo->onePost($conditions, true);
        if ($post === null) {
            $post = $repo->createPost($properties);
        } else {
            \Sledgehammer\set_object_vars($post, $properties);
        }
        $post->setMeta($meta);
        $repo->savePost($post);
        if (!$post->guid && $guidPrefix !== false) {
            $post->guid = $guidPrefix.$post->id;
            $repo->savePost($post, ['ignore_relations' => true]);
        }

        return $post;
    }
}
