<?php

// TODO grouping / fingerprints https://docs.sentry.io/learn/rollups/?platform=node#custom-grouping

class NudgifySentryClient
{
    /**
     * @string version of this client package
     */
    const VERSION = "1.0.0";

    /**
     * @var int percentage of events actually sent to the server (the rest will be silently ignored)
     */
    public $sample_rate = 100;

    /**
     * @var NudgifySentryClientExtension[]
     */
    private $extensions;

    /**
     * @var NudgifySentryEventCapture
     */
    private $capture;

    /**
     * @param NudgifySentryEventCapture            $capture    Event Capture implementation to use
     * @param NudgifySentryClientExtension[] $extensions List of Client Extensions to use
     */
    public function __construct($capture, $extensions = [])
    {
        $this->capture = $capture;
        $this->extensions = $extensions;
    }

    /**
     * Create and capture details about a given {@see Throwable} and (optionally) an
     * associated {@see ServerRequestInterface}.
     *
     * @param Throwable                   $exception the Exception to be logged
     * @param ServerRequestInterface|null $request   the related PSR-7 Request (if applicable)
     */
    public function captureException($exception, $request = null, $extra = [])
    {
        if (mt_rand(0, 99) < $this->sample_rate) {
            $event = $this->createEvent($exception, $request);

            $event->extra = $extra;

            $this->capture->captureEvent($event);
        }
    }

    /**
     * Create an {@see Event} instance with details about a given {@see Throwable} and
     * (optionally) an associated {@see ServerRequestInterface}.
     *
     * @param Throwable                   $exception the Exception to be logged
     * @param ServerRequestInterface|null $request   the related PSR-7 Request (if applicable)
     *
     * @return NudgifySentryEvent
     */
    protected function createEvent($exception, $request = null)
    {
        $event = new NudgifySentryEvent(
            $this->createEventID(),
            $this->createTimestamp(),
            $exception->getMessage(),
            new NudgifySentryUserInfo()
        );

        // NOTE: the `transaction` field is actually not intended for the *source* of the error, but for
        //       something that describes the command that resulted in the error - something application
        //       dependent, like the web-route or console-command that triggered the problem. Since those
        //       things can't be established from here, and since we want something meaningful to display
        //       in the title of the Sentry error-page, this is the best we can do for now.

        $event->transaction = $exception->getFile() . "#" . $exception->getLine();

        foreach ($this->extensions as $extension) {
            $extension->apply($event, $exception, $request);
        }

        return $event;
    }

    /**
     * @return int current time
     */
    protected function createTimestamp()
    {
        return time();
    }

    /**
     * @return string UUID v4 without the "-" separators (as required by Sentry)
     */
    protected function createEventID()
    {
        $bytes = unpack('C*', random_bytes(16));

        return sprintf(
            '%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x',
            $bytes[1], $bytes[2], $bytes[3], $bytes[4], $bytes[5], $bytes[6],
            $bytes[7] & 0x0f | 0x40, $bytes[8], $bytes[9] & 0x3f | 0x80, $bytes[10],
            $bytes[11], $bytes[12], $bytes[13], $bytes[14], $bytes[15], $bytes[16]
        );
    }
}
