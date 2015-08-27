<?php

namespace CommentScraper;

use Sunra\PhpSimple\HtmlDomParser;

/**
 * Source
 */
abstract class Source
{
    use LoggerTrait;

    const STATUS_NEW = 0;
    const STATUS_WAITING = 1;
    const STATUS_COMMENTS = 2;
    const STATUS_COMPLETED = 3;

    /**
     * Status
     *
     * @var integer
     */
    protected $status = 0;

    /**
     * URL
     *
     * @var string
     */
    protected $url;

    /**
     * Initial page
     *
     * @var integer
     */
    protected $page = 1;

    /**
     * Paginated source
     *
     * @var boolean
     */
    protected $paginated = false;

    /**
     * Comments
     *
     * @var array
     */
    protected $comments = [];

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Return source key
     *
     * @return string
     */
    public function key()
    {
        return spl_object_hash($this);
    }

    /**
     * Get status
     *
     * @return string
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Set scraper
     */
    public function scraper(Scraper $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * Get URL
     *
     * @return string
     */
    public function url()
    {
        $url = $this->url;
        foreach ($this->urlTokens() as $token) {
            if (property_exists($this, $token)) {
                $url = str_replace('{' . $token . '}', $this->{$token}, $url);
            }
        }
        $this->page++;
        return $url;
    }

    /**
     * Process
     *
     * @return mixed
     */
    public function process()
    {
        if ($this->status === static::STATUS_NEW) {
            $url = $this->url();
            $this->log(['Source URL request', $this->key(), $url]);
            $this->status = static::STATUS_WAITING;
            return $this->scraper->client()->getAsync($url);
        }
        if ($this->status === static::STATUS_COMMENTS) { // has comments
            if (empty($this->comments)) {
                $this->status = ($this->paginated === false) ? static::STATUS_COMPLETED : static::STATUS_NEW;
            } else {
                $comment = array_shift($this->comments);
                return $comment;
            }
        }
        return $this->status();
    }

    /**
     * Set response
     *
     * @param object $response
     */
    public function response($response)
    {
        $statusCode = (int) $response->getStatusCode();
        if ($statusCode !== 200) {
            $this->status = static::STATUS_COMPLETED;
            return;
        }
        $this->status = static::STATUS_COMMENTS;
        $body = (string) $response->getBody();
        if (!empty($body)) {
            $dom = HtmlDomParser::str_get_html($body);
            if ($this->hasComments($dom)) {
                $comments = $this->extractComments($dom);
                $this->comments = $comments;
                return;
            }
        }
        $this->status = static::STATUS_COMPLETED;
    }

    /**
     * Check if there are any comments on the page
     *
     * @param object $dom
     * @return boolean
     */
    abstract public function hasComments($dom);

    /**
     * Extract comments from DOM
     *
     * @param object $dom
     * @return array
     */
    abstract public function extractComments($dom);

    /**
     * URL tokens to replace
     *
     * @return array
     */
    abstract public function urlTokens();
}
