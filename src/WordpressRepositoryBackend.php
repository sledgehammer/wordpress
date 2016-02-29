<?php

namespace Sledgehammer\Wordpress;

use Exception;
use Sledgehammer\Orm\Backend\DatabaseRepositoryBackend;
use Sledgehammer\Wordpress\Model\Option;
use Sledgehammer\Wordpress\Model\Post;
use Sledgehammer\Wordpress\Model\Term;

/**
 * Description of WordpressRepositoryBackend
 *
 * @author bob
 */
class WordpressRepositoryBackend extends DatabaseRepositoryBackend
{

    public function __construct()
    {
        if (empty($GLOBALS['table_prefix'])) {
            throw new Exception('No table_prefix configured');
        }
        parent::__construct(["wordpress" => $GLOBALS['table_prefix']]);

        $this->renameModel('Postmetum', 'PostMeta');
        $this->renameModel('Termmetum', 'TermMeta');
        $this->renameModel('Usermetum', 'UserMeta');
        $this->renameModel('Commentmetum', 'CommentMeta');
        $this->renameModel('TermTaxonomy', 'Taxonomy');

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
                $this->renameProperty($model, $from, $to);
            }
        }
        // read & write filters
        $mayContainArray = [
            'Option' => 'option_value',
            'Postmetum' => 'meta_value',
            'Termmetum' => 'meta_value',
        ];

        foreach ($mayContainArray as $model => $column) {
            $this->configs[$model]->readFilters[$column] = [self::class, 'arrayReadFilter'];
            $this->configs[$model]->writeFilters[$column] = [self::class, 'arrayWriteFilter'];
        }

        // Post tweaks
        $this->configs['Post']->belongsTo['author'] = [
            'model' => 'User',
            'reference' => 'post_author'
        ];
        $this->configs['Post']->hasMany['meta'] = [
            'model' => 'PostMeta',
            'reference' => 'post_id',
            'id' => 'post_id',
            'belongsTo' => 'post'
        ];
        $this->configs['Post']->hasMany['taxonomies'] = [
            'model' => 'Taxonomy',
            'through' => 'TermRelationship',
            'reference' => 'object_id',
            'id' => 'term_taxonomy_id',
            'fields' => ['term_order' => 'order']
        ];

        $this->skipProperty('Post', 'post_author');
        $this->configs['Post']->class = Post::class;
        $this->configs['Post']->defaults = array_merge($this->configs['Post']->defaults, [
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
        $this->configs['Postmetum']->belongsTo['post'] = [
            'model' => 'Post',
            'reference' => 'post_id'
        ];
        $this->skipProperty('Postmetum', 'post_id');

        // Term
        $this->configs['Term']->class = Term::class;
        $this->configs['Term']->hasMany['taxonomy'] = [
            'model' => 'Taxonomy',
            'reference' => 'term_id',
            'id' => 't_id',
            'belongsTo' => 'term'
        ];
        $this->configs['Term']->hasMany['meta'] = [
            'model' => 'TermMeta',
            'reference' => 'term_id',
            'id' => 'term_id',
            'belongsTo' => 'term'
        ];
        $this->configs['Term']->defaults['meta'] = [];

        // TermMeta
        $this->configs['Termmetum']->belongsTo['term'] = [
            'model' => 'Term',
            'reference' => 'term_id'
        ];
        $this->skipProperty('Termmetum', 'term_id');

        // Taxonomy
        $this->skipProperty('TermTaxonomy', 'term_id');
        $this->configs['TermTaxonomy']->belongsTo['term'] = [
            'model' => 'Term',
            'reference' => 'term_id'
        ];
        $this->configs['TermTaxonomy']->defaults['description'] = '';

        if ($this->configs['Podsrel']) { // Pods plugin enabled?
            $this->renameModel('Podsrel', 'PodsRelationship');
            $this->skipProperty('Podsrel', 'pod_id');
            $this->skipProperty('Podsrel', 'field_id');
            $this->configs['Podsrel']->belongsTo['pod'] = [
                'model' => 'Post',
                'reference' => 'pod_id'
            ];
            $this->configs['Podsrel']->belongsTo['field'] = [
                'model' => 'Post',
                'reference' => 'field_id'
            ];
        }
        // Option
        $this->configs['Option']->class = Option::class;
    }

    /**
     * Convert php-serialized strings into arrays.
     *
     * @param string $value
     * @return string|array
     */
    static function arrayReadFilter($value)
    {
        if (substr($value, 0, 2) === 'a:') {
            $array = @unserialize($value);
            if (is_array($array)) {
                return $array;
            }
        }
        return $value;
    }

    /**
     * Convert arrays into php-serialized strings.
     *
     * @param string|array $value
     * @return string|array
     */
    static function arrayWriteFilter($value)
    {
        if (is_array($value)) {
            return serialize($value);
        }
        return $value;
    }

}
