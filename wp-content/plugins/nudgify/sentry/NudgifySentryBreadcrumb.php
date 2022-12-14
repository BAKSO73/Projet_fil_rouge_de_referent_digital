<?php

/**
 * @link https://docs.sentry.io/clientdev/interfaces/breadcrumbs/
 */
class NudgifySentryBreadcrumb implements JsonSerializable
{
    /**
     * @var int UNIX timestamp
     */
    public $timestamp;

    /**
     * @var string Severity level
     *
     * @see Level
     */
    public $level;

    /**
     * @var string
     */
    public $message;

    /**
     * @var array
     */
    private $data;

    public function __construct($timestamp,  $level,  $message,  $data)
    {
        $this->timestamp = $timestamp;
        $this->level = $level;
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * @internal
     */
    public function jsonSerialize()
    {
        return array_filter(get_object_vars($this));
    }
}
