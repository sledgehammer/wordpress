<?php

namespace Sledgehammer\Wordpress;

use Sledgehammer\Dump;
use Sledgehammer\Form;
use Sledgehammer\Input;
use Sledgehammer\Util;

class ExportPost extends Util {

    function __construct() {
        parent::__construct('Export object (wp_posts & wp_postmeta)');
    }

    function generateContent() {
        require(__DIR__ . '/../bootstrap.php');
        $repo = \Sledgehammer\getRepository();
        $lastPosts = $repo->allPosts(['status !=' => 'auto-draft'])->orderByDescending('id')->take(25);
        $options = $lastPosts->select(function ($post) {
                    return $post->id . ') ' . $post->type . ' - ' . $post->slug;
                }, 'id')->toArray();
        \Sledgehammer\array_key_unshift($options, 'custom', 'Custom ID');
        $form = new Form([
            'method' => 'GET',
            'fields' => [
                'post' => new Input([
                    'name' => 'id',
                    'class' => 'form-control',
                    'type' => 'select',
                    'options' => $options]),
                'id' => new Input(['name' => 'custom', 'class' => 'form-control'])
            ],
            'actions' => [
                'Export'
            ]
        ]);
        $form->initial(['post' => $lastPosts[0]->id]);
        $values = $form->import($errors);
        if ($values) {
            $id = $values['id'] === 'custom' ? $values['custom'] : $values['id'];
            $post = $repo->getPost($id);
            $defaults = $repo->createPost();
            $php = "\n\n\$post = \$repo->createPost([\n";
            $fields = [
                'type',
                'title',
                'slug',
                'excerpt',
                'status',
                'content',
                'comment_status',
                'ping_status',
                'password',
                'to_ping',
                'pinged',
                'content_filtered',
                'menu_order',
                'mimetype',
                'comment_count',
                'parent_id',
                'author',
                'date',
                'date_gmt',
                'modified',
                'modified_gmt',
            ];
            foreach ($fields as $property) {
                if ($defaults->$property === $post->$property) {
                    continue; // skip default values
                }
                if ($property === 'author') {
                    $value = "\$repo->oneUser(['login' => " . var_export($post->author->login, true) . "])";
                } elseif ($property === 'parent_id') {
                    $parent = $repo->getPost($post->parent_id);
                    $value  = "\$repo->onePost(['type' => " . var_export($parent->type, true) . ", 'slug' => " . var_export($parent->slug, true) . "])->id";
                } else {
                    $value = var_export($post->$property, true);
                }
                $php .= "    '" . $property . "' => " . $value . ",\n";
            }
            $php .= "]);\n";
            $meta = $post->getMeta();
            unset($meta['_edit_lock']);
            unset($meta['_edit_last']);
            $php .= "\$post->setMeta(" . var_export($meta, true) . ");\n";
            foreach ($post->taxonomies as $taxonomy) {
                if (count($taxonomy->term->getMeta()) !== 0 || $taxonomy->parent_id !== '0' || $taxonomy->description !== '' || $taxonomy->term->group !== '0' || $taxonomy->order !== '0') {
                    throw new \Exception('@todo Implement taxonomy feature');
                }
                $php .= "\$post->taxonomies[] = Migrate::importTaxonomy(" . var_export($taxonomy->taxonomy, true) . ", " . var_export($taxonomy->term->name, true) . ", " . var_export($taxonomy->term->slug, true) . ");\n";
            }
            if ($post->type == 'attachment') {
                $php .= "\$post->guid = " . var_export($post - guid, true) . ";\n";
                $php .= "\$repo->savePost(\$post);\n";
            } else {
                $guid = var_export($post->guid, true);
                $guid = str_replace("'" . WP_HOME, 'WP_HOME.\'', $guid);
                $guid = str_replace("=" . $post->id . "'", "='.\$post->id", $guid);
                $php .= "\$repo->savePost(\$post);\n";
                $php .= "\$post->guid = " . $guid . ";\n";
                $php .= "\$repo->savePost(\$post, ['ignore_relations' => true]);\n";
            }

            $php .= "\n\n";
            return new Dump($php);
        }
        return $form;
    }

}
