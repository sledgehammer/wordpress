<?php

namespace Sledgehammer\Wordpress;

use Exception;
use Sledgehammer\Orm\Backend\DatabaseRepositoryBackend;
use Sledgehammer\Wordpress\Model\Comment;
use Sledgehammer\Wordpress\Model\CommentMeta;
use Sledgehammer\Wordpress\Model\Option;
use Sledgehammer\Wordpress\Model\Post;
use Sledgehammer\Wordpress\Model\PostMeta;
use Sledgehammer\Wordpress\Model\Term;
use Sledgehammer\Wordpress\Model\TermMeta;
use Sledgehammer\Wordpress\Model\User;
use Sledgehammer\Wordpress\Model\UserMeta;
use Sledgehammer\Wordpress\Model\Link;

/**
 * Description of WordpressRepositoryBackend.
 *
 * @author bob
 */
class WordpressRepositoryBackend extends DatabaseRepositoryBackend
{
    public $identifier = 'wordpress';
    public static $cacheTimeout = '5min';

    public function __construct()
    {
        if (empty($GLOBALS['table_prefix'])) {
            throw new Exception('No table_prefix configured');
        }
        parent::__construct(['wordpress' => $GLOBALS['table_prefix']]);

        $this->renameModel('Postmetum', 'PostMeta');
        $this->renameModel('Termmetum', 'TermMeta');
        $this->renameModel('Usermetum', 'UserMeta');
        $this->renameModel('Commentmetum', 'CommentMeta');
        $this->renameModel('TermTaxonomy', 'Taxonomy');

        // Column to property mapping
        $map = [
            'Comment' => [
                'comment_ID' => 'id',
                'comment_author' => 'author',
                'comment_author_email' => 'email',
                'comment_author_url' => 'url',
                'comment_date' => 'date',
                'comment_date_gmt' => 'date_gmt',
                'comment_content' => 'content',
                'comment_karma' => 'karma',
                'comment_approved' => 'approved',
                'comment_author_IP' => 'ip',
                'comment_agent' => 'useragent',
                'comment_type' => 'type',
                'comment_parent' => 'parent_id',
            ],
            'Commentmetum' => [
                'meta_id' => 'id',
                'meta_key' => 'key',
                'meta_value' => 'value',
            ],
            'Link'=> [
                'link_id' => 'id',
                'link_url' => 'url',
                'link_name' => 'name',
                'link_image' => 'image',
                'link_target' => 'target',
                'link_description' => 'description',
                'link_visible' => 'visible',
                'link_owner' => 'owner',
                'link_rating' => 'rating',
                'link_updated' => 'updated',
                'link_rel' => 'rel',
                'link_notes' => 'notes',
                'link_rss' => 'rss',
            ],
            'Option' => [
                'option_id' => 'id',
                'option_name' => 'key',
                'option_value' => 'value',
            ],
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
            'Usermetum' => [
                'umeta_id' => 'id',
                'meta_key' => 'key',
                'meta_value' => 'value',
            ],
        ];

        foreach ($map as $model => $properties) {
            foreach ($properties as $from => $to) {
                $this->renameProperty($model, $from, $to);
            }
        }
        // read & write array filters
        $mayContainArray = [
            'Option' => 'option_value',
            'Postmetum' => 'meta_value',
            'Termmetum' => 'meta_value',
            'Commentmetum' => 'meta_value',
            'Usermetum' => 'meta_value',
        ];

        foreach ($mayContainArray as $model => $column) {
            $this->configs[$model]->readFilters[$column] = [self::class, 'arrayReadFilter'];
            $this->configs[$model]->writeFilters[$column] = [self::class, 'arrayWriteFilter'];
        }

        // Comment
        $this->skipProperty('Comment', 'comment_post_ID');
        $this->configs['Comment']->class = Comment::class;
        $this->configs['Comment']->belongsTo['post'] = [
            'model' => 'Post',
            'reference' => 'comment_post_ID',
        ];
        $this->configs['Comment']->belongsTo['user'] = [
            'model' => 'User',
            'reference' => 'user_id',
        ];
        $this->configs['Comment']->hasMany['meta'] = [
            'model' => 'CommentMeta',
            'reference' => 'comment_id',
            'id' => 'comment_id',
            'belongsTo' => 'comment',
        ];
        $this->configs['Comment']->defaults = array_merge($this->configs['Comment']->defaults, [
            'meta' => [],
        ]);
        $this->skipProperty('Comment', 'user_id');
        $this->configs['Commentmetum']->class = CommentMeta::class;
        $this->configs['Commentmetum']->belongsTo['comment'] = [
            'model' => 'Comment',
            'reference' => 'comment_id',
        ];
        $this->skipProperty('Commentmetum', 'comment_id');

        // Link
        $this->configs['Link']->class = Link::class;

        // Option
        $this->configs['Option']->class = Option::class;
        
        // Post
        $this->configs['Post']->class = Post::class;
        $this->configs['Post']->belongsTo['author'] = [
            'model' => 'User',
            'reference' => 'post_author',
        ];
        $this->configs['Post']->hasMany['meta'] = [
            'model' => 'PostMeta',
            'reference' => 'post_id',
            'id' => 'post_id',
            'belongsTo' => 'post',
        ];
        $this->configs['Post']->hasMany['taxonomies'] = [
            'model' => 'Taxonomy',
            'through' => 'TermRelationship',
            'reference' => 'object_id',
            'id' => 'term_taxonomy_id',
            'fields' => ['term_order' => 'order'],
        ];
        $this->configs['Post']->hasMany['comments'] = [
            'model' => 'Comment',
            'reference' => 'comment_post_ID',
            'id' => 'post_id',
            'belongsTo' => 'post',
        ];

        $this->skipProperty('Post', 'post_author');
        $this->configs['Post']->defaults = array_merge($this->configs['Post']->defaults, [
            'excerpt' => '',
            'to_ping' => '',
            'pinged' => '',
            'content' => '',
            'content_filtered' => '',
            'meta' => [],
            'taxonomies' => [],
            'comments' => [],
            'date' => current_time('mysql'),
            'date_gmt' => current_time('mysql', true),
            'modified' => current_time('mysql'),
            'modified_gmt' => current_time('mysql', true),
        ]);

        $this->configs['Postmetum']->class = PostMeta::class;
        $this->configs['Postmetum']->belongsTo['post'] = [
            'model' => 'Post',
            'reference' => 'post_id',
        ];
        $this->skipProperty('Postmetum', 'post_id');

        // Taxonomy
        $this->skipProperty('TermTaxonomy', 'term_id');
        $this->configs['TermTaxonomy']->belongsTo['term'] = [
            'model' => 'Term',
            'reference' => 'term_id',
        ];
        $this->configs['TermTaxonomy']->defaults['description'] = '';
        $this->configs['TermTaxonomy']->hasMany['posts'] = [
           'model' => 'Post',
           'through' => 'TermRelationship',
           'reference' => 'term_taxonomy_id',
           'id' => 'object_id',
           'fields' => ['term_order' => 'order'],
        ];
        $this->configs['TermTaxonomy']->defaults['posts'] = [];

        // Term
        $this->configs['Term']->class = Term::class;
        $this->configs['Term']->hasMany['taxonomy'] = [
            'model' => 'Taxonomy',
            'reference' => 'term_id',
            'id' => 't_id',
            'belongsTo' => 'term',
        ];
        $this->configs['Term']->hasMany['meta'] = [
            'model' => 'TermMeta',
            'reference' => 'term_id',
            'id' => 'term_id',
            'belongsTo' => 'term',
        ];
        $this->configs['Term']->defaults['meta'] = [];

        $this->configs['Termmetum']->class = TermMeta::class;
        $this->configs['Termmetum']->belongsTo['term'] = [
            'model' => 'Term',
            'reference' => 'term_id',
        ];
        $this->skipProperty('Termmetum', 'term_id');

        // User
        $this->configs['User']->class = User::class;
        $this->configs['User']->hasMany['meta'] = [
            'model' => 'UserMeta',
            'reference' => 'user_id',
            'id' => 'ID',
            'belongsTo' => 'user',
        ];
        $this->configs['User']->hasMany['posts'] = [
            'model' => 'Post',
            'reference' => 'post_author',
            'id' => 'ID',
            'belongsTo' => 'post',
        ];
        $this->configs['User']->hasMany['comments'] = [
            'model' => 'Comment',
            'reference' => 'comment_author',
             'id' => 'ID',
             'belongsTo' => 'comment',
         ];
        $this->configs['User']->defaults['posts'] = [];

        $this->configs['Usermetum']->class = UserMeta::class;
        $this->configs['Usermetum']->belongsTo['user'] = [
            'model' => 'User',
            'reference' => 'user_id',
        ];
        $this->skipProperty('Usermetum', 'user_id');

        // Pods plugin enabled?
        if (array_key_exists('Podsrel', $this->configs)) {
            $this->renameModel('Podsrel', 'PodsRelationship');
            $this->skipProperty('Podsrel', 'pod_id');
            $this->skipProperty('Podsrel', 'field_id');
            $this->configs['Podsrel']->belongsTo['pod'] = [
                'model' => 'Post',
                'reference' => 'pod_id',
            ];
            $this->configs['Podsrel']->belongsTo['field'] = [
                'model' => 'Post',
                'reference' => 'field_id',
            ];
        }
    }

    /**
     * Convert php-serialized strings into arrays.
     *
     * @param string $value
     *
     * @return string|array
     */
    public static function arrayReadFilter($value)
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
     *
     * @return string|array
     */
    public static function arrayWriteFilter($value)
    {
        if (is_array($value)) {
            return serialize($value);
        }
        return $value;
    }

    public function getSchema($db, $prefix = '')
    {
        $schema = parent::getSchema($db, $prefix);
        $whitelist = [
            'commentmeta',
            'comments',
            'links',
            'options',
            'postmeta',
            'posts',
            'term_relationships',
            'term_taxonomy',
            'termmeta',
            'terms',
            'usermeta',
            'users'
        ];
        $filteredSchema = [];
        foreach ($whitelist as $item) {
            $table = $prefix.$item;
            if (array_key_exists($table, $schema)) {
                $filteredSchema[$table] = $schema[$table];
            }
        }
        return $filteredSchema;
    }
}
