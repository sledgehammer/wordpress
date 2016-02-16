<?php
namespace Sledgehammer\Wordpress;

use Sledgehammer\Dialog;
use Sledgehammer\Form;
use Sledgehammer\Input;
use Sledgehammer\Json;
use Sledgehammer\Template;
use Sledgehammer\Util;

class DiffOptions extends Util
{
    function __construct()
    {
        parent::__construct('Diff wp_options');
    }

    function generateContent()
    {
        require(__DIR__.'/../bootstrap.php');
        $form = new Form([
            'legend' => 'Compare snapshot',
            'fields' => [
                new Input(['name' => 'snapshot', 'type' => 'textarea', 'cols' => 50, 'rows' => 20]),
                new Input(['name' => 'mode', 'type' => 'select', 'options' => ['as source','as target']]),
                new Input(['name' => 'compare', 'type' => 'submit', 'class'=>'btn btn-primary']),
            ],
        ]);
        $form->initial([
            base64_encode(Json::encode($this->createSnapshot())),
            'source',
            'Compare'
        ]);
        $data = $form->import($errors);
        if ($data === null) {
            return $form;
        }
        $newValues = $this->createSnapshot();
        $newSnapshot = base64_encode(Json::encode($newValues));
        if ($data['snapshot'] === $newSnapshot) {
            return new Dialog('No changes detected', 'No changes detected in the <b>wp_options</b> table.');
        }
        $oldValues = Json::decode(base64_decode($data['snapshot']), true);
        if ($data['mode'] === 'as target') {
            $diff = $this->compare($newValues, $oldValues);
        } else {
            $diff = $this->compare($oldValues, $newValues);
        }
        return new Template(__DIR__.'/../templates/diff.php', $diff);
    }

    /**
     * @return string
     */
    function createSnapshot()
    {
        $repo = \Sledgehammer\getRepository();
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
        $format = \JSON_PRETTY_PRINT ^ \JSON_UNESCAPED_SLASHES;
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