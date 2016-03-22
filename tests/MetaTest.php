<?php

use Sledgehammer\Orm\Repository;
use SledgehammerTests\Core\TestCase;

class MetaTest extends TestCase
{

    protected $backupGlobals = false;

    function testMultiRecordRead()
    {
        $repo = Repository::instance();
        $post = $repo->createPost();
        // read: one record
        $post->meta[] = $repo->createPostMeta(['key' => 'gallery', 'value' => 'first']);
        $this->assertEquals('first', $post->getMeta('gallery'));
        // read: multi record
        $post->meta[] = $repo->createPostMeta(['key' => 'gallery', 'value' => 'second']);
        $this->assertEquals(['__MULTIRECORD__', 'first', 'second'], $post->getMeta('gallery'));
    }

    function testMultiRecordWrite()
    {
        $repo = Repository::instance();
        $post = $repo->createPost();
        $post->setMeta('gallery', ['__MULTIRECORD__', 'first', 'second']);
        $this->assertCount(2, $post->meta);
        $this->assertEquals(['__MULTIRECORD__', 'first', 'second'], $post->getMeta('gallery'));
    }

}
