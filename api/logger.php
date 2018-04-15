<?php

class Logger {
    private $correlationId = "";
    private $fileName = "";
    private $filePath = "";

    function __construct() {
        $this->fileName = "newspt_" . (new DateTime(null,new DateTimeZone("UTC")))->format("Ymd");
        $this->filePath = "./" . $this->fileName;
        $this->correlationId = uniqid();
    }

    public function Info($message) {
        $now = new DateTime(null,new DateTimeZone("UTC"));
        error_log("{$now->format("Y-m-d H:i:s.u")} :: {$this->correlationId} :: NFO :: {$message}".PHP_EOL,3,$this->filePath);
    }

    public function Debug($message) {
        $now = new DateTime(null,new DateTimeZone("UTC"));
        error_log("{$now->format("Y-m-d H:i:s.u")} :: {$this->correlationId} :: DBG :: {$message}".PHP_EOL,3,$this->filePath);
    }

    public function Error($message) {
        $now = new DateTime(null,new DateTimeZone("UTC"));
        error_log("{$now->format("Y-m-d H:i:s.u")} :: {$this->correlationId} :: ERR :: {$message}".PHP_EOL,3,$this->filePath);        
    }

    public function SqlError($conn) {
        $error = $conn->error;
        if( $error != null && $error != "" ) {
            $this->Error($error);
            throw new Exception("Error connecting to data. Please try again later");
        }
    }
}
?>