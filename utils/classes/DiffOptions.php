<?php
namespace Sledgehammer\Wordpress;

use SebastianBergmann\Diff\Differ;
use Sledgehammer\Alert;
use Sledgehammer\Button;
use Sledgehammer\Dialog;
use Sledgehammer\Dump;
use Sledgehammer\Form;
use Sledgehammer\Framework;
use Sledgehammer\Input;
use Sledgehammer\Json;
use Sledgehammer\Util;

class DiffOptions extends Util
{
    function __construct()
    {
        parent::__construct('Diff wp_options');
    }

    function init()
    {
        Framework::$autoloader->importFolder($this->paths['project'] . 'sebastian/diff/src', ['mandatory_superclass' => false]);
        Framework::$autoloader->importFolder($this->paths['modules'] . 'orm/classes');
        Framework::initModule($this->paths['modules'] . 'orm');
        Framework::initModule($this->paths['modules'] . 'wordpress');
        require_once(dirname($this->paths['project']) . '/web/wp-config.php');

        init();
    }

    function generateContent()
    {
        $this->init();

        $step1 = new Dialog('1/3) Diff wp_options ', 'Compare changes in the <b>wp_options</b> table', ['snapshot' => 'Create snapshot']);
        $step1->initial('snapshot');
        $answer1 = $step1->import($errors1);
        $step2 = new Form([
            'legend' => '1/3) Snapshot created',
            'fields' => [
                new Input(['name' => 'snapshot', 'type' => 'hidden']),
            ],
            'actions' => ['Compare']
        ]);
        $answer2 = $step2->import($errors2);
        if ($answer1 === 'snapshot') {
            $step2->initial([base64_encode(Json::encode($this->createSnapshot()))]);
            return $step2;
        } elseif ($answer2) {
            $newValues = $this->createSnapshot();
            $newSnapshot = base64_encode(Json::encode($newValues));
            if ($answer2['snapshot'] === $newSnapshot) {
                return new Dialog('3/3) No changes detected', 'No changes detected in the <b>wp_options</b> table.');
            }
            $oldValues = Json::decode(base64_decode($answer2['snapshot']), true);
            $diff = $this->compare($oldValues, $newValues);
            return new Dump($diff);
        }
        return $step1;
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
        $format = JSON_PRETTY_PRINT ^ JSON_UNESCAPED_SLASHES;
        $differ = new Differ();
        foreach ($old as $key => $oldValue) {
            if (array_key_exists($key, $new) === false) {
                continue; // skip newly added
            }
            $oldString = Json::encode($oldValue, $format);
            $newString = Json::encode($new[$key], $format);
            if ($oldString !== $newString) {
                $diff['changes'][$key] = $differ->diff($oldString, $newString);
                $diff['values'][$key] = $newString;
            }
        }
        return $diff;
    }

}