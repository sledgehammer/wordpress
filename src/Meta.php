<?php

namespace Sledgehammer\Wordpress;

use Exception;
use Sledgehammer\Core\Collection;
use Sledgehammer\Core\InfoException;
use Sledgehammer\Orm\HasManyPlaceholder;
use Sledgehammer\Orm\Repository;

/**
 * Cleaner way of setting the meta data.
 */
trait Meta
{
    /**
     * Set meta records.
     *
     * Usage:
     *   $post->setMeta(['my_key' => 'my_value']);
     * or
     *   $post->setMeta('my_key', 'my_value');
     *
     * @param $keyOrvalues
     * @param $value
     */
    public function setMeta($keyOrvalues, $value = null)
    {
        $repo = Repository::instance();
        if (is_array($keyOrvalues)) {
            foreach ($keyOrvalues as $key => $value) {
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

    /**
     * Read meta records.
     *
     * @param string [$key] The name of the meta property, when ommitted getMeta() returns all meta fields in a assoc array.
     * @param mixed [$default] Default returnvalue, when ommitted getMeta() will throw an exception if the property doesn't exist.
     *
     * @return mixed
     */
    public function getMeta($key = null, $default = null)
    {
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
            if (func_num_args() > 1) {
                return $default;
            }
            throw new InfoException('Meta field: "'.$key.'" doesn\'t exist in '.str_replace(__NAMESPACE__.'\\Model\\', '', static::class).' '.$this->id, 'Existing fields: '.\Sledgehammer\quoted_human_implode(' or ', array_keys($meta->selectKey('key')->toArray())));
        }
        $data = ['__MULTIRECORD__'];
        foreach ($value as $row) {
            $data[] = $row->value;
        }

        return $data;
    }

    public function hasMeta($key)
    {
        if ($this->meta instanceof HasManyPlaceholder || $this->meta instanceof Collection) {
            $meta = $this->meta;
        } else {
            throw new Exception('implement support');
        }

        return $meta->where(['key' => $key])->count() !== 0;
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
