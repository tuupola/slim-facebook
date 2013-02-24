<?php

namespace Slim\Extras\Log;

class ActiveRecordAdapter {

    private $logger;
    private $level;
    
    function __construct($logger, $level = \Slim\Log::DEBUG) {
        $this->logger = $logger;
        $this->level = $level;
    }
    
    public function log($message) {
        /* $this->logger->log($message, $this->level); */

        /* \Slim\Log::log() is protected so have to do this the hard way. */
        switch($this->level) {
            case \Slim\Log::FATAL:
              $this->logger->fatal($message);
              break;
            case \Slim\Log::ERROR:
              $this->logger->error($message);
              break;
            case \Slim\Log::WARN:
              $this->logger->warn($message);
              break;
            case \Slim\Log::INFO:
              $this->logger->info($message);
              break;
            case \Slim\Log::DEBUG:
              $this->logger->debug($message);
              break;
        }
    }

}
