<?php

namespace Sledgehammer\Wordpress;
use function  Sledgehammer\getRepository;

/**
 * Cleaner way of setting the meta data
 * @package Sledgehammer\Wordpress
 */
trait Meta
{
    /**
     * Usage:
     *   $post->setMeta(['my_key' => 'my_value']);
     * or
     *   $post->setMeta('my_key', 'my_value');
     *
     * @param $keyOrvalues
     * @param $value
     */
    function setMeta($keyOrvalues, $value = null) {
        $repo = getRepository();
        if (is_array($keyOrvalues)) {
            foreach($keyOrvalues as $key => $value) {
                $this->meta[$key] = $repo->create(static::META_MODEL, ['key' => $key, 'value' => $value]);
            }
        } else {
            $key = $keyOrvalues;
            $this->meta[$key] = $repo->create(static::META_MODEL, ['key' => $key, 'value' => $value]);
        }
    }

}