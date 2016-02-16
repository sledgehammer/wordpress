<?php

namespace Sledgehammer\Wordpress;

use Sledgehammer\Dump;
use Sledgehammer\Form;
use Sledgehammer\Html;
use Sledgehammer\Input;
use Sledgehammer\Util;

class ExportPost extends Util
{
    function __construct()
    {
        parent::__construct('Export object (wp_posts & wp_postmeta)');
    }

    function generateContent()
    {
        require(__DIR__ . '/../bootstrap.php');
        $repo = \Sledgehammer\getRepository();
        $lastPosts = $repo->allPosts(['status !=' => 'auto-draft'])->orderByDescending('id')->take(25);
        $options = $lastPosts->select(function ($post) {
            return $post->id.') '.$post->type.' - '.$post->slug;
        }, 'id')->toArray();
        \Sledgehammer\array_key_unshift($options, 'custom', 'Custom ID');
        $form = new Form([
            'method' => 'GET',
            'fields' => [
                'post' => new Input([
                    'name' => 'id',
                    'class' =>'form-control',
                    'type' => 'select',
                    'options' =>  $options]),
                'id' => new Input(['name' => 'custom', 'class' =>'form-control'])

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
            $meta = $post->getMeta();
            $php = "\n\n\$post = \$repo->createPost([\n";
            foreach (get_object_vars($post) as $property => $value) {
                if (in_array($property, ['author', 'id', 'meta','taxonomies'])) {
                    continue;
                }
                $php .= "    '".$property."' => ".var_export($value, true).",\n";
            }
            $php .= "]);\n";
            $php .= "\$post->author = \$repo->oneUser(['login' => ".var_export($post->author->login, true)."]);\n";
            $php .= "\$post->setMeta(".var_export($post->getMeta(), true).");\n";
            foreach ($post->taxonomies as $taxonomy) {
                if (count($taxonomy->term->getMeta()) !== 0 || $taxonomy->parent_id !== '0' || $taxonomy->description !== '' ||  $taxonomy->term->group !== '0' || $taxonomy->order !== '0') {
                    throw new \Exception('@todo Implement taxonomy feature');
                }
                $php .= "\$post->taxonomies[] = \$importer->importTaxonomy(".var_export($taxonomy->taxonomy, true).", ".var_export($taxonomy->term->name, true).", ".var_export($taxonomy->term->slug, true).");\n";
            }
            $php .= "\$repo->savePost(\$post);\n";
            if ($post->type !== 'attachment') {
                $php .= "\$post->guid = str_replace(".$post->id.", \$post->id, \$post->guid);\n";
                $php .= "\$repo->savePost(\$post, ['ignore_relations' => true]);\n";
            }
            $php .= "\n\n";
            return new Dump($php);
        }
        return $form;
    }
}