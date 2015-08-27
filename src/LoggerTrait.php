<?php

namespace CommentScraper;

/**
 * LoggerTrait
 */
trait LoggerTrait
{
    /**
     * CLI flag
     *
     * @var boolean
     */
    protected $isCli;

    /**
     * Log message
     *
     * @param string|array $message
     */
    public function log($message)
    {
        if (!$this->isCli()) {
            return;
        }
        $message = (array) $message;
        foreach ($message as $k => $v) {
            $prefix = ($k === 0) ? '-> ' : '   ';
            echo $prefix . $v . PHP_EOL;
        }
    }

    /**
     * CLI check
     *
     * @return boolean
     */
    public function isCli()
    {
        if ($this->isCli === null) {
            $this->isCli = (php_sapi_name() === 'cli') ? true : false;
        }
        return $this->isCli;
    }
}
