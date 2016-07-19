<?php

namespace CommentScraper\Source;

use CommentScraper\Source;

/**
 * Dummy
 */
class Dummy extends Source
{
    /**
     * URL
     *
     * @var string
     */
    protected $url = 'http://localhost:8000/comments.php?source={source}&page={page}';

    /**
     * Source
     *
     * @var string
     */
    protected $source;

    /**
     * URL tokens to replace
     *
     * @return array
     */
    public function urlTokens()
    {
        return ['page', 'source'];
    }

    /**
     * Check if there are any comments on the page
     *
     * @param  \DiDom\Document $dom
     * @return bool
     */
    public function hasComments($dom)
    {
        return true;
    }

    /**
     * Extract comments from DOM
     *
     * @param  \DiDom\Document $dom
     * @return array
     */
    public function extractComments($dom)
    {
        $comments = [];
        $nodes = $dom->find('.single-comment');
        foreach ($nodes as $node) {
            $comment = [];
            $comment['text'] = $node->find('.text')[0]->text();
            $comments[] = $comment;
        }
        return $comments;
    }
}
