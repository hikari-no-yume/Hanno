<?php
namespace ajf\Hanno;

require_once '../vendor/autoload.php';

class Stream {
    private $internalStream;
    private $errored = false;
    
    public function __construct($streamOrURL, $mode = null, $context = null)
    {
        if (is_string($streamOrURL)) {
            if ($mode === null) {
                throw new \Exception("Mode must be specified if URL given");
            }
            // the $context param of fopen has no default value so we have to do this
            if ($context) {
                $stream = fopen($streamOrURL, $mode, false, $context);
            } else {
                $stream = fopen($streamOrURL, $mode);
            }
            if ($stream === false) {
                throw new \Exception("Failed to open resource \"$streamOrURL\" with mode \"$mode\" and context $context");
            }
            stream_set_blocking($stream, 0);
            stream_set_timeout($stream, 0);
            $this->internalStream = $stream;
        } else if (is_resource($streamOrURL)) {
            stream_set_blocking($streamOrURL, 0);
            stream_set_timeout($streamOrURL, 0);
            $this->internalStream = $streamOrURL;
        } else {
            throw new \Exception("Argument passed to constructor for Stream must be a string or a stream, " . gettype($streamOrURL) . " given");
        }
    }
    
    public function errored()
    {
        return $this->errored;
    }
    
    public function eof()
    {
        return feof($this->internalStream);
    }
    
    public function read($bytes = -1)
    {
        $awaitable = new Awaitable;
        $task = function () use ($awaitable, $bytes) {
            $data = "";
            while (($bytes === -1) ? true : (strlen($data) < $bytes)) {
                yield;
                
                /* stream_select has a horrible by-reference API, so we must make dummy variables */
                $null = NULL;
                $write = [$this->internalStream];
                $select = stream_select($null, $write, $null, 0);
                /* error */
                if ($select === false) {
                    break;
                }
                /* no change */
                if ($select === 0) {
                    yield;
                }
                
                $newData = fread($this->internalStream, ($bytes === -1) ? 8192 : ($bytes - strlen($data)));
                if ($newData === false) {
                    break;
                }
                $data .= $newData;
                
                if (feof($this->internalStream)) {
                    break;
                }
            }
            $awaitable->finish($data);
        };
        $awaitable->setTask($task());
        return $awaitable;
    }
}