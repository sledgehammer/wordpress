<?php

namespace Sledgehammer\Wordpress\Util;

use Sledgehammer\Core\Json;
use Sledgehammer\Devutils\Util;
use Sledgehammer\Mvc\Component\Dialog;
use Sledgehammer\Mvc\Component\Form;
use Sledgehammer\Mvc\Component\Input;
use Sledgehammer\Mvc\Component\Template;
use Sledgehammer\Orm\Repository;
use Sledgehammer\Wordpress\Bridge;

class DiffOptions extends Util
{
    public function __construct()
    {
        parent::__construct('Diff wp_options');
    }

    public function generateContent()
    {
        Bridge::initialize();
        $value = chunk_split(base64_encode(Json::encode($this->createSnapshot())));
        $form = new Form([
            'legend' => 'Compare snapshot',
            'fields' => [
                'direction' => new Input(['name' => 'mode', 'type' => 'select', 'options' => ['as source', 'as target'], 'value' => 'as source']),
                'btn' => new Input(['name' => 'compare', 'type' => 'submit', 'class' => 'btn btn-primary', 'value' => 'Compare']),
                'snapshot' => new Input(['name' => 'snapshot', 'type' => 'textarea', 'rows' => 30, 'class' => 'form-control', 'style' => 'font-family: monospace;', 'value' => $value]),
            ],
        ]);

        $data = $form->import();

        if ($form->isSent() === false) {
            return $form;
        }
        $newValues = $this->createSnapshot();
        $newSnapshot = chunk_split(base64_encode(Json::encode($newValues)));
        if ($data['snapshot'] === $newSnapshot) {
            return new Dialog('No changes detected', 'No changes detected in the <b>wp_options</b> table.');
        }
        $oldValues = Json::decode(base64_decode($data['snapshot']), true);
        if ($data['direction'] === 'as target') {
            $diff = $this->compare($newValues, $oldValues);
        } else {
            $diff = $this->compare($oldValues, $newValues);
        }

        return new Template('sledgehammer/wordpress/templates/diff.php', $diff);
    }

    /**
     * @return string
     */
    public function createSnapshot()
    {
        $repo = Repository::instance();
        $options = $repo->allOptions()->orderBy('key');
        $sql = $options->getQuery()->andWhere('option_name NOT LIKE "%_transient_%"');
        $options->setQuery($sql);

        return $options->select('value', 'key')->toArray();
    }

    public function compare($old, $new)
    {
        $diff = [
            'added' => array_diff_key($new, $old),
            'removed' => array_diff_key($old, $new),
            'changes' => [],
            'values' => [],
        ];
        $format = JSON_PRETTY_PRINT ^ \JSON_UNESCAPED_SLASHES;
        foreach ($old as $key => $oldValue) {
            if (array_key_exists($key, $new) === false) {
                continue; // skip newly added
            }
            $oldString = Json::encode($oldValue, $format);
            $newString = Json::encode($new[$key], $format);
            if ($oldString !== $newString) {
                $diff['changes'][$key] = new ColorDiff($oldString, $newString);
                $diff['values'][$key] = $newString;
            }
        }

        return $diff;
    }
}
