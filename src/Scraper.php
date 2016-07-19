<?php

namespace CommentScraper;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Closure;
use Exception;

/**
 * Scraper
 */
class Scraper
{
    use LoggerTrait;

    /**
     * Max number of concurrent requests
     *
     * @var integer
     */
    protected $concurrency = 10;

    /**
     * Amount of comments callback receives
     *
     * @var integer
     */
    protected $chunk = 10000;

    /**
     * Promises
     *
     * @var array
     */
    protected $promises = [];

    /**
     * Loaded sources
     *
     * @var array
     */
    protected $sources = [];

    /**
     * Comments returned from sources
     *
     * @var array
     */
    protected $comments = [];

    /**
     * Callback that receives comments
     *
     * @var Closure
     */
    protected $callback;

    /**
     * HTTP client
     *
     * @var Client
     */
    protected $client;

    /**
     * HTTP client options
     *
     * @var array
     */
    protected $clientOptions = [
        'verify' => false, // disable SSL-certs verification
        'http_errors' => false,
        'timeout' => 10,
        'headers' => [
            'Accept'            => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Encoding'   => 'gzip, deflate, sdch',
            'Accept-Language'   => 'pl-PL,pl;q=0.8,en-US;q=0.6,en;q=0.4',
            'Cache-Control'     => 'no-cache',
            'Pragma'            => 'no-cache',
            'User-Agent'        => 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.130 Safari/537.36',
        ],
    ];

    /**
     * Contructor
     */
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = (is_array($value)) ? array_merge_recursive($this->{$key}, $value) : $value;
            }
        }
        $this->log('Scraper created');
        $this->client(new Client($this->clientOptions));
    }

    /**
     * Add source
     *
     * @param Source $source
     */
    public function add(Source $source)
    {
        if (isset($this->sources[$source->key()])) {
            throw new Exception('Source already added');
        }
        $source->scraper($this);
        $this->sources[$source->key()] = $source;
        $this->comments[$source->key()] = [];
        $this->log(['Source added', sprintf('%s -> %s', $source->key(), get_class($source))]);
    }

    /**
     * Get/set callback
     *
     * @param Closure $callback
     * @return Closure
     */
    public function callback($callback = null)
    {
        if ($callback === null) {
            return $this->callback;
        }
        $this->callback = $callback;
        $this->log(['Callback set', get_class($callback)]);
    }

    /**
     * Get/set client
     *
     * @param Client $client
     * @return Client
     */
    public function client($client = null)
    {
        if ($client === null) {
            return $this->client;
        }
        $this->client = $client;
        $this->log(['HTTP client set', get_class($client)]);
    }

    /**
     * Return pending sources
     *
     * @return array
     */
    protected function pendingSources()
    {
        return $this->filterSources(Source::STATUS_COMPLETED, false);
    }

    /**
     * Filter sources
     *
     * @param integer $status
     * @param boolean $exclude
     * @return array
     */
    protected function filterSources($status, $include = true)
    {
        $sources = [];
        foreach ($this->sources as $key => $source) {
            if ($include) {
                if ($source->status() === $status) {
                    $sources[$key] = $source;
                }
            } else {
                if ($source->status() !== $status) {
                    $sources[$key] = $source;
                }
            }
        }
        return $sources;
    }

    /**
     * Scrap
     */
    public function run()
    {
        $runs = 0;
        while (!empty($this->pendingSources())) {
            $runs++;
            $this->log(['-----------------------------------', 'Run ' . $runs]);
            foreach ($this->pendingSources() as $key => $source) {
                $result = $source->process();
                if ($result instanceof Promise\Promise) { // promise
                    $this->log(['Adding promise', $key]);
                    $this->promises[$key] = $result;
                } elseif (is_array($result) || is_object($result)) { // single comment
                    $this->comment($key, $result);
                }
            }
            $this->sendRequests();
        }
        // call remaining comments
        foreach ($this->comments as $key => $comments) {
            if (!empty($comments)) {
                $callback = $this->callback;
                $callback($comments);
            }
        }
        $this->log('Done');
    }

    /**
     * Send awaiting requests
     */
    protected function sendRequests()
    {
        if (count($this->promises) === count($this->filterSources(Source::STATUS_WAITING))) {
            $toSend = [];
            foreach ($this->promises as $key => $promise) {
                if (count($toSend) === $this->concurrency) {
                    break;
                }
                $toSend[$key] = $promise;
                unset($this->promises[$key]);
            }
            if (empty($toSend)) {
                return;
            }
            $this->log('Sending awaiting requests');
            $responses = Promise\unwrap($toSend);
            foreach ($responses as $key => $response) {
                $this->sources[$key]->response($response);
            }
        }
    }

    /**
     * Add comment
     *
     * @param string $key
     * @param array $comment
     */
    protected function comment($key, $comment)
    {
        $this->log(['Adding comment', $key]);
        $this->comments[$key][] = $comment;
        if (count($this->comments[$key]) === $this->chunk) {
            $callback = $this->callback;
            $callback($this->comments[$key]);
            $this->comments[$key] = [];
        }
    }
}
