<?php

use Sledgehammer\Core\Collection;
use Sledgehammer\Orm\Repository;
use SledgehammerTests\Core\TestCase;

class MetaTest extends TestCase
{

    protected $backupGlobals = false;
    
    function testRead() {
        $repo = Repository::instance();
        $post = $repo->createPost();
        $post->meta = new Collection([
            $repo->createPostMeta(['key' => 'one', 'value' => '1']),
            $repo->createPostMeta(['key' => 'two', 'value' => '2']),
        ]);
        $this->assertEquals('1', $post->getMeta('one'));
        $this->assertEquals('2', $post->getMeta('two'));
        $this->assertEquals(['one' => '1', 'two' => '2'], $post->getMeta());
    }
    
    function testWrite() {
        $repo = Repository::instance();
        $post = $repo->createPost();
        $post->setMeta('one', 1);
        $this->assertCount(1, $post->meta);
        $post->setMeta('one', 1);
        $this->assertCount(1, $post->meta);
        $post->setMeta(['one' => 1, 'two' => 2]);
        $this->assertCount(2, $post->meta);
        $post->setMeta(['three' => 3, 'four' => 4]);
        $this->assertCount(4, $post->meta);
        $post->setMeta(['one' => 'een', 'four' => 'vier']);
        $this->assertCount(4, $post->meta);
        $this->assertEquals(['one' => 'een', 'two' => 2, 'three' => 3, 'four' => 'vier'], $post->getMeta());
    }

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
