<?php

namespace CommentScraper;

use DiDom\Document;
use ReflectionClass;

/**
 * Source
 */
abstract class Source
{
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
     * Class to wrap comment data within
     *
     * @var string|bool
     */
    protected $commentClass = '\\CommentScraper\\Comment';

    /**
     * Short source class name
     *
     * @var string
     */
    protected $source;

    /**
     * Request options
     *
     * @var array
     */
    protected $requestOptions = [];

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
     * Get/set request options
     *
     * @param  null|array $options
     * @return void|array
     */
    public function requestOptions($options = null)
    {
        if ($options === null) {
            return $this->requestOptions;
        }
        $this->requestOptions = $options;
    }

    /**
     * Return short source class name
     *
     * @return string
     */
    public function source()
    {
        if ($this->source === null) {
            $reflect = new ReflectionClass($this);
            $this->source = $reflect->getShortName();
        }
        return $this->source;
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
            $this->scraper->log(['Source URL request', $this->key(), $url]);
            $this->status = static::STATUS_WAITING;
            return $this->scraper->client()->getAsync($url, $this->requestOptions());
        }
        if ($this->status === static::STATUS_COMMENTS) { // has comments
            if (empty($this->comments)) {
                $this->status = ($this->paginated === false) ? static::STATUS_COMPLETED : static::STATUS_NEW;
            } else {
                $comment = array_shift($this->comments);
                if ($this->commentClass === false) {
                    return $comment;
                }
                $commentClass = $this->commentClass;
                $commentObj = new $commentClass($comment);
                $commentObj->source($this->source());
                return $commentObj;
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
            $dom = new Document($body);
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
     * @param  \DiDom\Document $dom
     * @return boolean
     */
    abstract public function hasComments($dom);

    /**
     * Extract comments from DOM
     *
     * @param  \DiDom\Document $dom
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
