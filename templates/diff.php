<?php

use Sledgehammer\Core\Html;
use Sledgehammer\Core\Json;

if ($changes): ?>
        <table class="table">
            <thead>
            <tr>
                <th width="12%">Option changed</th>
                <th width="44%" style="min-width: 250px">New Value</th>
                <th width="44%">Diff</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($changes as $key => $change): ?>
                <tr>
                    <td style="white-space: nowrap"><?= Html::escape($key) ?></td>
                    <td style="max-width: 44vw"><?php
                        $value = Json::decode($values[$key], true);
                        ob_start();
                        dump($value);
                        $html = ob_get_clean();
                        echo substr($html, strpos($html, '</div>') + 6);
                        ?></td>
                    <td style="max-width: 44vw"><?php render($change); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
<?php endif;
if ($added): ?>
    <table class="table" style="width: auto">
        <thead>
        <tr>
            <th>Option added</th>
            <th>Value</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($added as $key => $value): ?>
            <tr>
                <td><?= Html::escape($key); ?></td>
                <td style="max-width: 100em"><?php
                    ob_start();
                    dump($value);
                    $html = ob_get_clean();
                    echo substr($html, strpos($html, '</div>') + 6);
                    ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif;
if ($removed): ?>
    <table class="table" style="width: auto;">
        <thead>
        <tr>
            <th>Option removed</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($removed as $key => $value): ?>
            <tr>
                <td><?= Html::escape($key); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif;
