<?php
namespace Sledgehammer\Wordpress\Util;

use Sledgehammer\Core\Json;
use Sledgehammer\Devutils\Util;
use Sledgehammer\Mvc\Component\Dialog;
use Sledgehammer\Mvc\Component\Form;
use Sledgehammer\Mvc\Component\Input;
use Sledgehammer\Mvc\Template;
use Sledgehammer\Orm\Repository;
use Sledgehammer\Wordpress\Bridge;

class DiffOptions extends Util
{
    function __construct()
    {
        parent::__construct('Diff wp_options');
    }

    function generateContent()
    {
        Bridge::initialize();
        $form = new Form([
            'legend' => 'Compare snapshot',
            'fields' => [
                new Input(['name' => 'mode', 'type' => 'select', 'options' => ['as source','as target']]),
                new Input(['name' => 'compare', 'type' => 'submit', 'class'=>'btn btn-primary']),
                new Input(['name' => 'snapshot', 'type' => 'textarea', 'rows' => 30, 'class' => 'form-control', 'style'=> 'font-family: monospace;']),

            ],
        ]);
        $form->initial([
            'source',
            'Compare',
            chunk_split(base64_encode(Json::encode($this->createSnapshot()))),
        ]);
        $data = $form->import($errors);
        if ($data === null) {
            return $form;
        }
        $newValues = $this->createSnapshot();
        $newSnapshot = chunk_split(base64_encode(Json::encode($newValues)));
        if ($data[2] === $newSnapshot) {
            return new Dialog('No changes detected', 'No changes detected in the <b>wp_options</b> table.');
        }
        $oldValues = Json::decode(base64_decode($data[2]), true);
        if ($data[0] === 'as target') {
            $diff = $this->compare($newValues, $oldValues);
        } else {
            $diff = $this->compare($oldValues, $newValues);
        }
        return new Template('sledgehammer/wordpress/templates/diff.php', $diff);
    }

    /**
     * @return string
     */
    function createSnapshot()
    {
        $repo = Repository::instance();
        $options = $repo->allOptions()->orderBy('key');
        $sql = $options->getQuery()->andWhere('option_name NOT LIKE "%_transient_%"');
        $options->setQuery($sql);
        return $options->select('value', 'key')->toArray();
    }

    function compare($old, $new)
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