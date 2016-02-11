<?php

use Sledgehammer\Framework;
use Sledgehammer\Wordpress\DiffOptions;
use Sledgehammer\Wordpress\ExportPost;

Framework::$autoloader->importFolder(__DIR__.'/classes');

return array(
    'export-post.html' => new ExportPost(),
    'diff-options.html' => new DiffOptions(),
);
