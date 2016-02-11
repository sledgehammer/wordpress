<?php

namespace Sledgehammer\Wordpress;

use Sledgehammer\Form;
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
        $form = new Form([
            'method' => 'GET',
            'fields' => [
                'id' => new Input(['name' => 'id', 'class' =>'form-control'])
            ],
            'actions' => [
                'Export'
            ]
        ]);
        $lastPosts = $repo->allPosts()->orderByDescending('id')->take(25);
        dump($lastPosts->select(function ($post) {
            return $post->id.') '.$post->type.' - '.$post->slug;
        }, 'id'));
        $form->initial(['id' => $lastPosts[0]->id]);
        $values = $form->import($errors);
        if ($values) {
            
        }
        return $form;
    }
}