<?php

namespace Sledgehammer\Wordpress;

use Sledgehammer\Object;

class Migrate extends Object {

    /**
     * Create or fetch existing taxonomy.
     * (also increases count)
     *
     * @param string $type  'category', 'post_tag'
     * @param string $title  The term
     * @param string [$slug]  The slug of the term
     * @return \Generated\TermTaxonomy
     */
    static function importTaxonomy($type, $title, $slug = null) {
        $slug = $slug ?: sanitize_title($title);
        $repo = \Sledgehammer\getRepository();
        $term = $repo->oneTerm(['slug' => $slug], true);
        if ($term) {
            foreach ($term->taxonomy as $taxonomy) {
                if ($taxonomy->taxonomy === $type) {
                    $taxonomy->count++;
                    return $taxonomy;
                }
            }
            // the given slug belongs to another taxonomy type.
            return static::importTaxonomy($type, $title, $slug . '-' . $type);
        }
        $taxonomy = $repo->createTermTaxonomy([
            'taxonomy' => $type,
            'count' => '1'
        ]);
        $term = $repo->createTerm([
            'name' => $title,
            'slug' => $slug,
            'taxonomy' => [$taxonomy]
        ]);
        $repo->saveTerm($term);
        return $taxonomy;
    }

    static function importCategory($title, $slug = null) {
        return static::importTaxonomy('category', $title, $slug);
    }

    static function importTag($title, $slug = null) {
        return static::importTaxonomy('post_tag', $title, $slug);
    }
}