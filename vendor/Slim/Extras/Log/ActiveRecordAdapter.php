<?php

namespace Slim\Extras\Log;

class ActiveRecordAdapter {

    private $logger;
    
    function __construct($logger) {
        $this->logger = $logger;
    }
    
    public function log($message) {
        $this->logger->debug($message);
    }

}
