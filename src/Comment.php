<?php

namespace CommentScraper;

/**
 * Comment
 */
class Comment
{
    /**
     * Data
     *
     * @var array
     */
    protected $data;

    /**
     * Source
     *
     * @var string
     */
    protected $source;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get/set source
     *
     * @param string $source
     */
    public function source($source = null)
    {
        if ($source === null) {
            return $this->source;
        }
        $this->source = $source;
    }

    /**
     * Return object data as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}
