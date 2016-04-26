<?php
/**
 * Expose actions for https://github.com/sledgehammer/devutils
 */

use Sledgehammer\Wordpress\Util\DiffOptions;
use Sledgehammer\Wordpress\Util\ExportPost;
use Sledgehammer\Wordpress\Util\ExportTaxonomy;

return array(
    'export-post.html' => new ExportPost(),
    'export-taxonomy.html' => new ExportTaxonomy(),
    'diff-options.html' => new DiffOptions(),
);
