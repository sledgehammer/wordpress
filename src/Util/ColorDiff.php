<?php

namespace Sledgehammer\Wordpress\Util;

use SebastianBergmann\Diff\Differ;
use Sledgehammer\Core\Html;
use Sledgehammer\Core\Object;
use Sledgehammer\Mvc\View;

class ColorDiff extends Object implements View
{
    /**
     * @var string Output from the SebastianBergmann\Diff\Differ
     */
    private $diff;

    /**
     * @param $text
     */
    function __construct($old, $new)
    {
        $differ = new Differ('');
        $this->diff = $differ->diff($old, $new);;
    }

    function render()
    {
        echo '<div style="white-space: pre">';
        $lines = explode("\n", $this->diff);
        array_shift($lines);// skip @@ @@
        foreach ($lines as $line) {
            $firstCharacter = substr($line, 0, 1);
            if ($firstCharacter == '+') {
                echo '<span style="background: lightgreen">';
            } elseif ($firstCharacter == '-') {
                echo '<span style="background: pink">';
            } else {
                echo '<span>';
            }
            echo Html::escape($line);
            echo "</span>\n";
        }
        echo '</div>';

    }

}