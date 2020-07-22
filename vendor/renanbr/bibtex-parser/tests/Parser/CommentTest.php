<?php

/*
 * This file is part of the BibTex Parser.
 *
 * (c) Renan de Lima Barbosa <renandelima@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RenanBr\BibTexParser\Test\Parser;

use PHPUnit\Framework\TestCase;
use RenanBr\BibTexParser\Parser;
use RenanBr\BibTexParser\Test\DummyListener;

/**
 * @covers \RenanBr\BibTexParser\Parser
 */
class CommentTest extends TestCase
{
    public function testCommentOnly()
    {
        $listener = new DummyListener();

        $parser = new Parser();
        $parser->addListener($listener);
        $parser->parseFile(__DIR__ . '/../resources/valid/comment-only.bib');

        $this->assertCount(0, $listener->calls);
    }

    public function testFileThatContainsEntryAndComment()
    {
        $listener = new DummyListener();

        $parser = new Parser();
        $parser->addListener($listener);
        $parser->parseFile(__DIR__ . '/../resources/valid/comment.bib');

        $this->assertCount(4, $listener->calls);

        list($text, $type, $context) = $listener->calls[0];
        $this->assertSame(Parser::TYPE, $type);
        $this->assertSame('comment', $text);
        $this->assertSame(19, $context['offset']);
        $this->assertSame(7, $context['length']);

        list($text, $type, $context) = $listener->calls[1];
        $this->assertSame(Parser::TAG_NAME, $type);
        $this->assertSame('foo', $text);
        $this->assertSame(27, $context['offset']);
        $this->assertSame(3, $context['length']);

        list($text, $type, $context) = $listener->calls[2];
        $this->assertSame(Parser::RAW_TAG_CONTENT, $type);
        $this->assertSame('bar', $text);
        $this->assertSame(33, $context['offset']);
        $this->assertSame(3, $context['length']);

        list($text, $type, $context) = $listener->calls[3];
        $this->assertSame(Parser::ENTRY, $type);
        $this->assertSame('@comment{foo = bar}', $text);
        $this->assertSame(18, $context['offset']);
        $this->assertSame(19, $context['length']);
    }
}
