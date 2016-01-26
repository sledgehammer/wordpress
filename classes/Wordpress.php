<?php

namespace Sledgehammer;

use const DB_HOST;
use const DB_NAME;
use const DB_PASSWORD;
use const DB_USER;

/**
 * 
 */
class Wordpress extends Object {

    static function init() {

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
        $backend = new DatabaseRepositoryBackend(["default" => "wp_"]);
        $backend->renameModel('Postmetum', 'PostMeta');

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
                'post_name' => 'name',
                'post_modified' => 'modified',
                'post_modified_gmt' => 'modified_gmt',
                'post_content_filtered' => 'content_filtered',
                'post_parent' => 'parent_id',
                'post_type' => 'type',
                'post_mime_type' => 'mime_type',
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
                'user_nicename' => 'nicename',
                'user_email' => 'email',
                'user_url' => 'url',
                'user_registered' => 'registered',
                'user_activation_key' => 'activation_key',
                'user_status' => 'status',
            ],
            'Option' => [
                'option_id' => 'id',
                'option_name' => 'name',
                'option_value' => 'value',
            ]
        ];
        $backend->configs['Option']->readFilters['option_value'] = 'Sledgehammer\Wordpress::readFilter';
        $backend->configs['Option']->writeFilters['option_value'] = 'Sledgehammer\Wordpress::writeFilter';

        foreach ($map as $model => $properties) {
            foreach ($properties as $from => $to) {
                $backend->renameProperty($model, $from, $to);
            }
        }
        $backend->configs['Post']->belongsTo['author'] = [
            'model' => 'User',
            'reference' => 'post_author'
        ];
        $backend->configs['Post']->hasMany['meta'] = [
            'model' => 'PostMeta',
            'reference' => 'post_id',
            'id' => 'post_id',
        ];
        unset($backend->configs['Post']->properties['post_author']);
        $backend->configs['Postmetum']->belongsTo['post'] = [
            'model' => 'Post',
            'reference' => 'post_id'
        ];
        unset($backend->configs['Postmetum']->properties['post_id']);

        $repo->registerBackend($backend);
    }

    /**
     * Convert php-serialized strings into arrays.
     *
     * @param string $value
     * @return string|array
     */
    public static function readFilter($value) {
        if (substr($value, 0, 2) === 'a:') {
            $array = unserialize($value);
            if (is_array($array)) {
                return $array;
            }
        }
        return $value;
    }

    /**
     * Convert arrays into php-serialized strings.
     *
     * @param string $value
     * @return string|array
     */
    public static function writeFilter($value) {
        if (is_array($array)) {
            return serialize($value);
        }
        return $value;
    }

}
