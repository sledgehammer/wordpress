<?php

namespace Sledgehammer\Wordpress;
use Sledgehammer\HasManyPlaceholder;
use Sledgehammer\InfoException;
use Sledgehammer\RepositoryCollection;

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
        $repo = \Sledgehammer\getRepository();
        if (is_array($keyOrvalues)) {
            foreach($keyOrvalues as $key => $value) {
                $this->meta[$key] = $repo->create(static::META_MODEL, ['key' => $key, 'value' => $value]);
            }
        } else {
            $key = $keyOrvalues;
            $this->meta[$key] = $repo->create(static::META_MODEL, ['key' => $key, 'value' => $value]);
        }
    }

    function getMeta($key = null) {
        if ($this->meta instanceof HasManyPlaceholder || $this->meta instanceof RepositoryCollection) {
            $meta = $this->meta;
        } else {
            throw new \Exception('implement support');
        }
        if ($key === null) {
            return $meta->select('value', 'key')->toArray();
        }
        $value = $meta->where(['key' => $key]);
        if (count($value) == 1) {
            return $value[0]->value;
        } elseif (count($value) == 0) {
            throw new InfoException('Meta field: "'.$key.'" doesn\'t exist in Post('.$this->id.')', 'Existing fields: '.\Sledgehammer\quoted_human_implode(' or ' , array_keys($meta->selectKey('key')->toArray())));
        }
        throw new \Exception('Implement support');
    }

    public function offsetExists($offset)
    {
        if ($this->meta instanceof HasManyPlaceholder || $this->meta instanceof RepositoryCollection) {
            $meta = $this->meta;
        } else {
            throw new \Exception('implement support');
        }
        return $meta->where(['key' => $offset])->count() !== 0;
    }

    public function offsetGet($offset)
    {
        return $this->getMeta($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->setMeta($offset, $value);
    }

    public function offsetUnset($offset)
    {
        if ($this->meta instanceof HasManyPlaceholder || $this->meta instanceof RepositoryCollection) {
            $this->meta->remove(['key' => $offset]);
        } else {
            throw new \Exception('implement support');
        }
    }
}