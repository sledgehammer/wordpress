<?php
/**
 * Expose actions for https://github.com/sledgehammer/devutils
 */

use Sledgehammer\Wordpress\Util\DiffOptions;
use Sledgehammer\Wordpress\Util\ExportPost;

return array(
    'export-post.html' => new ExportPost(),
    'diff-options.html' => new DiffOptions(),
);
