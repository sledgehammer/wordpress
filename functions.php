<?php

namespace Sledgehammer\Wordpress;

use const DB_HOST;
use const DB_NAME;
use const DB_PASSWORD;
use const DB_USER;
use function Sledgehammer\getDatabase;
use function Sledgehammer\getRepository;
use Sledgehammer\Database;
use Sledgehammer\Logger;
use Sledgehammer\DatabaseRepositoryBackend;

/**
 * Inspect the worpress database and
 */
 function init() {
    if (defined('DB_USER') === false) {
        notice('No database configured');
        return;
    }
    // @todo implement SH lazy database connection
    Database::$instances['default'] = new Database('mysql://' . DB_USER . ':' . DB_PASSWORD . '@' . DB_HOST . '/' . DB_NAME);
    $db = getDatabase('default');
    if (current(Logger::$instances) === $db->logger && empty(Logger::$instances['Database'])) {
        unset(Logger::$instances[key(Logger::$instances)]);
        Logger::$instances['Database'] = $db->logger;
    }
    // Sledgehammer ORM configuration
    $repo = getRepository();
    $backend = new DatabaseRepositoryBackend(["default" => $GLOBALS['table_prefix']]);
    $backend->renameModel('Postmetum', 'PostMeta');
    $backend->renameModel('Termmetum', 'TermMeta');
    $backend->renameModel('Usermetum', 'UserMeta');
    $backend->renameModel('Commentmetum', 'CommentMeta');

    // Column to property mapping
    $map = [
        'Post' => [
            'iD' => 'id',
            'post_title' => 'title',
            'post_date' => 'date',
            'post_date_gmt' => 'date_gmt',
            'post_content' => 'content',
            'post_title' => 'title',
            'post_excerpt' => 'excerpt',
            'post_status' => 'status',
            'post_password' => 'password',
            'post_name' => 'slug',
            'post_modified' => 'modified',
            'post_modified_gmt' => 'modified_gmt',
            'post_content_filtered' => 'content_filtered',
            'post_parent' => 'parent_id',
            'post_type' => 'type',
            'post_mime_type' => 'mimetype',
        ],
        'Postmetum' => [
            'meta_id' => 'id',
            'meta_key' => 'key',
            'meta_value' => 'value',
        ],
        'User' => [
            'iD' => 'id',
            'user_login' => 'login',
            'user_pass' => 'password',
            'user_nicename' => 'nickname',
            'user_email' => 'email',
            'user_url' => 'url',
            'user_registered' => 'registered',
            'user_activation_key' => 'activation_key',
            'user_status' => 'status',
        ],
        'Option' => [
            'option_id' => 'id',
            'option_name' => 'key',
            'option_value' => 'value',
        ],
        'Term' => [
            'term_id' => 'id',
            'term_group' => 'group',
        ],
        'Termmetum' => [
            'meta_id' => 'id',
            'meta_key' => 'key',
            'meta_value' => 'value',
        ],
        'TermTaxonomy' => [
            'term_taxonomy_id' => 'id',
            'parent' => 'parent_id',
        ]
    ];
    foreach ($map as $model => $properties) {
        foreach ($properties as $from => $to) {
            $backend->renameProperty($model, $from, $to);
        }
    }
    // read & write filters
    $mayContainArray = [
        'Option' => 'option_value',
        'Postmetum' => 'meta_value',
        'Termmetum' => 'meta_value',
    ];
     /**
      * Convert php-serialized strings into arrays.
      *
      * @param string $value
      * @return string|array
      */
     $arrayReadFilter = function ($value) {
        if (substr($value, 0, 2) === 'a:') {
            $array = @unserialize($value);
            if (is_array($array)) {
                return $array;
            }
        }
        return $value;
     };

     /**
      * Convert arrays into php-serialized strings.
      *
      * @param string|array $value
      * @return string|array
      */
     $arrayWriteFilter = function ($value) {
         if (is_array($value)) {
             return serialize($value);
         }
         return $value;
     };

     foreach ($mayContainArray as $model => $column) {
        $backend->configs[$model]->readFilters[$column] = $arrayReadFilter;
        $backend->configs[$model]->writeFilters[$column] = $arrayWriteFilter;
    }
    $hierachical = ['TermTaxonomy'];
    foreach ($hierachical as $model) {
//        function ($id) {
//            if ($id === '0') {
//                return null;
//            }
//            return $id;
//        }
//
//        function ($id) {
//            if ($id === null) {
//                return '0';
//            }
//            return $id;
//        }
//            $backend->configs[$model]->readFilters['parent'] = __CLASS__ . '::parentReadFilter';
//            $backend->configs[$model]->writeFilters['parent'] = __CLASS__ . '::parentWriteFilter';
    }
    // Post tweaks
    $backend->configs['Post']->belongsTo['author'] = [
        'model' => 'User',
        'reference' => 'post_author'
    ];
    $backend->configs['Post']->hasMany['meta'] = [
        'model' => 'PostMeta',
        'reference' => 'post_id',
        'id' => 'post_id',
        'belongsTo' => 'post'
    ];
    $backend->configs['Post']->hasMany['taxonomies'] = [
        'model' => 'TermTaxonomy',
        'through' => 'TermRelationship',
        'reference' => 'object_id',
        'id' => 'term_taxonomy_id',
        'fields' => [],
//            'fields' => ['term_order']
    ];

    $backend->skipProperty('Post', 'post_author');
    $backend->configs['Post']->class = 'Sledgehammer\Wordpress\Post';
    $backend->configs['Post']->defaults = array_merge($backend->configs['Post']->defaults, [
        'excerpt' => '',
        'to_ping' => '',
        'pinged' => '',
        'content' => '',
        'content_filtered' => '',
        'meta' => [],
        'taxonomies' => [],
        'date' => current_time('mysql'),
        'date_gmt' => current_time('mysql', true),
        'modified' => current_time('mysql'),
        'modified_gmt' => current_time('mysql', true),

    ]);

    // PostMeta tweaks
    $backend->configs['Postmetum']->belongsTo['post'] = [
        'model' => 'Post',
        'reference' => 'post_id'
    ];
    $backend->skipProperty('Postmetum', 'post_id');

    // Term
    $backend->configs['Term']->hasMany['taxonomy'] = [
        'model' => 'TermTaxonomy',
        'reference' => 'term_id',
        'id' => 't_id',
        'belongsTo' => 'term'
    ];
    $backend->configs['Term']->hasMany['meta'] = [
        'model' => 'TermMeta',
        'reference' => 'term_id',
        'id' => 'term_id',
        'belongsTo' => 'term'
    ];
    $backend->configs['Term']->defaults['meta'] = [];

    // TermMeta
    $backend->configs['Termmetum']->belongsTo['term'] = [
        'model' => 'Term',
        'reference' => 'term_id'
    ];
    $backend->skipProperty('Termmetum', 'term_id');

    // TermTaxonomy
    $backend->skipProperty('TermTaxonomy', 'term_id');
    $backend->configs['TermTaxonomy']->belongsTo['term'] = [
        'model' => 'Term',
        'reference' => 'term_id'
    ];
    $backend->configs['TermTaxonomy']->defaults['description'] = '';

    if ($backend->configs['Podsrel']) { // Pods plugin enabled?
        $backend->renameModel('Podsrel', 'PodsRelationship');
        $backend->skipProperty('Podsrel', 'pod_id');
        $backend->skipProperty('Podsrel', 'field_id');
        $backend->configs['Podsrel']->belongsTo['pod'] = [
            'model' => 'Post',
            'reference' => 'pod_id'
        ];
        $backend->configs['Podsrel']->belongsTo['field'] = [
            'model' => 'Post',
            'reference' => 'field_id'
        ];
    }

    $repo->registerBackend($backend);
}
