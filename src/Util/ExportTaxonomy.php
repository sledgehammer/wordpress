<?php

namespace Sledgehammer\Wordpress\Util;

use Sledgehammer\Devutils\Util;
use Sledgehammer\Mvc\Component\Button;
use Sledgehammer\Mvc\Component\Form;
use Sledgehammer\Mvc\Component\Input;
use Sledgehammer\Mvc\Component\Template;
use Sledgehammer\Orm\Repository;
use Sledgehammer\Wordpress\Bridge;

class ExportTaxonomy extends Util
{

    public function __construct()
    {
        parent::__construct('Export taxonomies (wp_term_taxonomy, wp_terms & wp_termmeta)');
    }

    public function generateContent()
    {
        Bridge::initialize();
        $repo = Repository::instance();
        $lastPosts = $repo->allTaxonomies()->orderByDescending('id')->take(25); //['AND', 'status !=' => 'auto-draft', 'type !=' => 'revision']
        $options = $lastPosts->select(function ($taxonomy) {
                return $taxonomy->id.') '.$taxonomy->taxonomy.' - '.$taxonomy->term->name;
        }, 'id')->toArray();
        \Sledgehammer\array_key_unshift($options, 'custom', 'Custom ID');
        $form = new Form([
            'method' => 'GET',
            'fields' => [
                'taxonomy' => new Input([
                    'name' => 'id',
                    'class' => 'form-control',
                    'type' => 'select',
                    'options' => $options,
                    'label' => 'Select post',
                    'value' => $lastPosts[0]->id,
                    ]),
                'id' => new Input(['name' => 'custom', 'class' => 'form-control', 'label' => 'Custom ID']),
                'varname' => new Input(['name' => 'varname', 'class' => 'form-control', 'label' => 'Variable name', 'value' => '$taxonomy']),
            ],
            'actions' => [
                new Button('Export', ['class' => 'btn btn-primary']),
            ],
        ]);
        $values = $form->import();
        if ($form->isSent()) {
            if ($values['id'] !== '') {
                $form->setValue(['taxonomy' => $values['id']]);
                $id = $values['id'];
            } else {
                $id = $values['taxonomy'];
            }
            if ($id === 'custom') {
                return $form;
            }
            $taxonomy = $repo->getTaxonomy($id);
            $var = $values['varname'];
            $mustSave = false;
            $php = $var.' = Sledgehammer\Wordpress\Migrate::taxonomy('.var_export($taxonomy->taxonomy, true).', '.var_export($taxonomy->term->name, true).', '.var_export($taxonomy->term->slug, true).');';
            if (count($taxonomy->term->getMeta()) > 0) {
                $php .= $var.'->setMeta('.var_export($taxonomy->term->getMeta(), true).");\n";
                $mustSave = true;
            }
            
            $php .= "\n\n";
            
//            if ($taxonomy->posts->count() > 0) {
//                $php .= "\$repo->resolveProperty(".$var.", 'posts', ['model' => 'Taxonomy']);\n";
//                foreach ($taxonomy->posts as $post) {
//                    $php .= $var."->posts[] = ";
//                    $phpPost = "\$repo->onePost(['AND', 'type' => ".var_export($post->type, true).", 'slug' => ".var_export($post->slug, true)."])";
//                    if ($post->order == '0') {
//                        $php .= $phpPost.";\n";
//                    } else {
//                        $php .= "new Sledgehammer\Orm\Junction(".$phpPost.", ['order' => ".var_export($post->order, true)."]);\n";
//                    }
//                }
//                $php .= $var."->count = ".$var."->posts->count();\n";
//                $mustSave = true;
//            }
            if ($mustSave) {
                $php .= "\$repo->saveTaxonomy(".$var.");\n";
            }

            return new Template('sledgehammer/wordpress/templates/export_post.php', ['form' => $form, 'php' => $php]);
        }

        return $form;
    }
}
