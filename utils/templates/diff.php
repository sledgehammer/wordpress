<?php

use Sledgehammer\Dump;
use Sledgehammer\Html;
use Sledgehammer\Json;


if ($changes): ?>
    <div style="overflow-x: scroll">
        <table class="table">
            <thead>
            <tr>
                <th width="33%">Option changed</th>
                <th width="33%">New Value</th>
                <th width="34%">Diff</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($changes as $key => $change): ?>
                <tr>
                    <td style="white-space: nowrap"><?= Html::escape($key) ?></td>
                    <td><?php
                        $value = Json::decode($values[$key], true);
                        ob_start();
                        dump($value);
                        $html = ob_get_clean();
                        echo substr($html, strpos($html, '</div>') + 6);
                        ?></td>
                    <td><?php render($change); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif;
if ($added): ?>
    <table class="table">
        <thead>
        <tr>
            <th width="33%">Option added</th>
            <th width="33%">Value</th>
            <th width="34%"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($added as $key => $value): ?>
            <tr>
                <td><?= Html::escape($key); ?></td>
                <td><?php
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
    <table class="table">
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
