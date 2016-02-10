<?php

use Sledgehammer\Framework;
use Sledgehammer\Wordpress\DiffOptions;
Framework::$autoloader->importFolder(__DIR__.'/classes');

return array(
    'diff-options.html' => new DiffOptions(),
);
