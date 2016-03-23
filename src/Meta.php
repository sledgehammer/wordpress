<?php

namespace Sledgehammer\Wordpress;

use Exception;
use Sledgehammer\Core\Collection;
use Sledgehammer\Core\InfoException;
use Sledgehammer\Orm\HasManyPlaceholder;
use Sledgehammer\Orm\Repository;

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
        $repo = Repository::instance();
        if (is_array($keyOrvalues)) {
            foreach($keyOrvalues as $key => $value) {
                $this->setMeta($key, $value);
            }
        } else {
            $key = $keyOrvalues;
            if (array_value($value, 0) === '__MULTIRECORD__') {
                $this->meta->remove(['key' => $key], true);
                array_shift($value);
                foreach ($value as $item) {
                    $this->meta[] = $repo->create(static::META_MODEL, ['key' => $key, 'value' => $item]);
                }
            } else {
                $found = false;
                foreach ($this->meta as $i => $old) {
                    if ($old->key === $key) {
                        if ($found) {  // was a multirecord
                            unset($this->meta[$i]); // remove other values
                        } else {
                            $old->value = $value; // Update existing value
                            $found = true;
                        }
                    }
                }
                if ($found === false) { // Not found? add new key/value
                    $this->meta[] = $repo->create(static::META_MODEL, ['key' => $key, 'value' => $value]);
                }
            }
        }
    }

    function getMeta($key = null) {
        if ($this->meta instanceof HasManyPlaceholder || $this->meta instanceof Collection) {
            $meta = $this->meta;
        } else {
            throw new Exception('implement support');
        }
        if ($key === null) {
            $data = [];
            foreach ($meta as $row) {
                if (array_key_exists($row->key, $data)) {
                    if (array_value($data, $row->key, 0) === '__MULTIRECORD__') {
                        $data[$row->key][] = $row->value;
                    } else {
                      $data[$row->key] = ['__MULTIRECORD__', $data[$row->key], $row->value];
                    }
                } else {
                    $data[$row->key] = $row->value;
                }
            }
            return $data;
        }
        $value = $meta->where(['key' => $key]);
        if (count($value) == 1) {
            foreach ($value as $metaField) {
                return $metaField->value;
            }
        } elseif (count($value) == 0) {
            throw new InfoException('Meta field: "'.$key.'" doesn\'t exist in Post('.$this->id.')', 'Existing fields: '.\Sledgehammer\quoted_human_implode(' or ' , array_keys($meta->selectKey('key')->toArray())));
        }
        $data = ['__MULTIRECORD__'];
        foreach ($value as $row) {
            $data[] = $row->value;
        }
        return $data;
    }
    
    function hasMeta($key) {
        if ($this->meta instanceof HasManyPlaceholder || $this->meta instanceof Collection) {
            $meta = $this->meta;
        } else {
            throw new Exception('implement support');
        }
        return $meta->where(['key' => $offset])->count() !== 0;
    }

    public function offsetExists($offset)
    {
        return $this->hasMeta($offset);
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
        if ($this->meta instanceof HasManyPlaceholder || $this->meta instanceof Collection) {
            $this->meta->remove(['key' => $offset], true);
        } else {
            throw new Exception('implement support');
        }
    }
}