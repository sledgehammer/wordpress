<?php

namespace Sledgehammer\Wordpress\Util;

use Exception;
use Sledgehammer\Devutils\Util;
use Sledgehammer\Mvc\Component\Form;
use Sledgehammer\Mvc\Component\Input;
use Sledgehammer\Mvc\Template;
use Sledgehammer\Orm\Repository;
use Sledgehammer\Wordpress\Bridge;

class ExportPost extends Util
{

    function __construct()
    {
        parent::__construct('Export object (wp_posts & wp_postmeta)');
    }

    function generateContent()
    {
        Bridge::initialize();
        $repo = Repository::instance();
        $lastPosts = $repo->allPosts(['AND', 'status !=' => 'auto-draft', 'type !=' => 'revision'])->orderByDescending('id')->take(25);
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
                    'options' => $options,
                    'label' => 'Select post'
                    ]),
                'id' => new Input(['name' => 'custom', 'class' => 'form-control', 'label' => 'Custom ID']),
                'varname' => new Input(['name' => 'varname', 'class' => 'form-control', 'label' => 'Variable name']),
                'export_defaults' => new Input(['name' => 'export_defaults', 'type' => 'checkbox', 'label' => 'Force defaults (verbose export)']),
            ],
            'actions' => [
                'Export'
            ]
        ]);
        $form->initial(['post' => $lastPosts[0]->id, 'varname' => '$post']);
        $values = $form->import($errors);
        if ($values) {
            if ($values['id'] !== '') {
                $form->initial(['post' => $values['id']]);
                $id = $values['id'];
            } else {
                $id = $values['post'];
            }
            if ($id === 'custom') {
                return $form;
            }
            $post = $repo->getPost($id);
            $var = $values['varname'];
            $defaults = $repo->createPost();

            $taxonomies = [];
            $php = '';
            foreach ($post->taxonomies as $i => $taxonomy) {
                if ($taxonomy->parent_id !== '0' || $taxonomy->description !== '' || $taxonomy->term->group !== '0' || $taxonomy->order !== '0') {
                    throw new Exception('@todo Implement taxonomy feature');
                }
                if (count($taxonomy->term->getMeta()) === 0) {
                    $taxonomies[] = "Migrate::importTaxonomy(" . var_export($taxonomy->taxonomy, true) . ", " . var_export($taxonomy->term->name, true) . ", " . var_export($taxonomy->term->slug, true) . ")";
                } else {
                    $php .= "\$taxonomy" . $i . " = Migrate::importTaxonomy(" . var_export($taxonomy->taxonomy, true) . ", " . var_export($taxonomy->term->name, true) . ", " . var_export($taxonomy->term->slug, true) . ");\n";
                    $php .= "\$taxonomy" . $i . "->setMeta(" . var_export($taxonomy->term->getMeta(), true) . ");\n";
                    $taxonomies[] = "\$taxonomy" . $i;
                }
            }

            $php .= $var . " = Sledgehammer\Wordpress\Migrate::post([\n";
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
            if ($post->type === 'attachment') {
                $fields[] = 'guid';
            }
            foreach ($fields as $property) {
                if ($values['export_defaults'] === false && $defaults->$property === $post->$property) {
                    continue; // skip default values
                }
                if ($property === 'author') {
                    $value = "\$repo->oneUser(['login' => " . var_export($post->author->login, true) . "])";
                } elseif ($property === 'guid') {
                    $guid = var_export($post->guid, true);
                    $value = str_replace("'" . WP_HOME, 'WP_HOME.\'', $guid);
                } elseif ($property === 'parent_id') {
                    if ($post->parent_id === '0') {
                        $value = "'0'";
                    } else {
                        $parent = $repo->getPost($post->parent_id);
                        $value = "\$repo->onePost(['AND', 'type' => " . var_export($parent->type, true) . ", 'slug' => " . var_export($parent->slug, true) . "])->id";
                    }
                } else {
                    $value = var_export($post->$property, true);
                }
                $php .= "    '" . $property . "' => " . $value . ",\n";
            }
            if ($taxonomies) {
                $php .= "    'taxonomies' => [\n        " . implode(",\n        ", $taxonomies) . "\n    ],\n";   
            }
            $php .= "],[\n";
            $meta = $post->getMeta();
            foreach ($meta as $key => $value) {
                if (in_array($key, ['_edit_lock', '_edit_last'])) {
                    continue;
                }
                if ($key === '_thumbnail_id') {
                    $attachment = $repo->getPost($value);
                    $guid = var_export($attachment->guid, true);
                    $value = "\$repo->onePost(['AND','type' => 'attachment', 'guid' => " . str_replace("'" . WP_HOME, 'WP_HOME.\'', $guid) . "])";
                } else {
                    $value = var_export($value, true);
                }
                $php .= "    '" . $key . "' => " . $value . ",\n";
            }
            $php .= "], ";
            if ($post->type === 'attachment') {
                $php .= 'false';
            } else {
                $guid = var_export($post->guid, true);
                $guid = str_replace("'" . WP_HOME, 'WP_HOME.\'', $guid);
                $php .= str_replace("=" . $post->id . "'", "='", $guid);
            }
            $php .= ");\n";

            return new Template('sledgehammer/wordpress/templates/export_post.php', ['form' => $form, 'php' => $php]);
        }
        return $form;
    }

}
