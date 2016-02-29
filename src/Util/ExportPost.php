<?php

namespace Sledgehammer\Wordpress\Util;

use Exception;
use Sledgehammer\Devutils\Util;
use Sledgehammer\Mvc\Component\Form;
use Sledgehammer\Mvc\Component\Input;
use Sledgehammer\Mvc\Template;
use Sledgehammer\Orm\Repository;
use Sledgehammer\Wordpress\Bridge;

class ExportPost extends Util {

    function __construct() {
        parent::__construct('Export object (wp_posts & wp_postmeta)');
    }

    function generateContent() {
        Bridge::initialize();
        $repo = Repository::instance();
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
                'id' => new Input(['name' => 'custom', 'class' => 'form-control']),
                'varname' => new Input(['name' => 'varname', 'class' => 'form-control']),
            ],
            'actions' => [
                'Export'
            ]
        ]);
        $form->initial(['post' => $lastPosts[0]->id, 'varname' => '$post']);
        $values = $form->import($errors);
        if ($values) {
            if ($values['custom'] !== '') {
                $form->initial(['post' => $values['custom']]);
                $id = $values['custom'];
            } else {
                $id = $values['id'];
            }
            if ($id === 'custom') {
                return $form;
            }
            $post = $repo->getPost($id);
            $var = $values['varname'];
            $defaults = $repo->createPost();
                
            $php = $var . " = \$repo->onePost(['AND', 'slug' => " . var_export($post->slug, true) . ", 'type' => " . var_export($post->type, true) . "], true);\n";
            $php .= "if (".$var." === null) {\n";
            $php .= "\n\n" . $var . " = \$repo->createPost([\n";
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
                    $value  = "\$repo->onePost(['AND', 'type' => " . var_export($parent->type, true) . ", 'slug' => " . var_export($parent->slug, true) . "])->id";
                } else {
                    $value = var_export($post->$property, true);
                }
                $php .= "    '" . $property . "' => " . $value . ",\n";
            }
            $php .= "]);\n";
            $meta = $post->getMeta();
            unset($meta['_edit_lock']);
            unset($meta['_edit_last']);
            $php .= $var."->setMeta(" . var_export($meta, true) . ");\n";
            foreach ($post->taxonomies as $taxonomy) {
                if (count($taxonomy->term->getMeta()) !== 0 || $taxonomy->parent_id !== '0' || $taxonomy->description !== '' || $taxonomy->term->group !== '0' || $taxonomy->order !== '0') {
                    throw new Exception('@todo Implement taxonomy feature');
                }
                $php .= $var."->taxonomies[] = Migrate::importTaxonomy(" . var_export($taxonomy->taxonomy, true) . ", " . var_export($taxonomy->term->name, true) . ", " . var_export($taxonomy->term->slug, true) . ");\n";
            }
            if ($post->type == 'attachment') {
                $php .= $var."->guid = " . var_export($post->guid, true) . ";\n";
                $php .= "\$repo->savePost(".$var.");\n";
            } else {
                $guid = var_export($post->guid, true);
                $guid = str_replace("'" . WP_HOME, 'WP_HOME.\'', $guid);
                $guid = str_replace("=" . $post->id . "'", "='.".$var."->id", $guid);
                $php .= "\$repo->savePost(".$var.");\n";
                $php .= $var."->guid = " . $guid . ";\n";
                $php .= "\$repo->savePost(".$var.", ['ignore_relations' => true]);\n";
            }

            $php .= "\n\n}";
            return new Template('sledgehammer/wordpress/templates/export_post.php', ['form' => $form, 'php' => $php]);
        }
        return $form;
    }

}
