<?php

// Include Libraries
include_once(dirname(__FILE__) . '/../../../Libraries/log4php/Logger.php');

// Include Application Scripts
include_once(dirname(__FILE__) . '/../../../Libraries/Helper/DataHelper.php');

// configure logging
Logger::configure(dirname(__FILE__) . '/../log4php.xml');
$log = Logger::getLogger(__FILE__);

/* * ********************************************************************** */
/**
 * Handles SENDs to STOMP
 * Only tested with the rabbitmq stomp gateway and rabbitmq server 1.4.0
 * Only supports sending message to stomp, no support for other commands
 *
 * Original source: http://code.google.com/p/simplisticstompclient/
 *
 */
class StompSender {

    private $host;
    private $port;
    private $login;
    private $passcode;
    private $virtual_host;
    private $realm;
    private $timeout;

    /**
     * The following are required for a connection to be established
     */
    public function __construct($host, $login, $passcode, $vhost, $realm){
        global $rabbitmq_default_port;
    //public function __construct($host, $login, $passcode) {
        $this->host = 'tcp://' . $host;
        $this->port = $rabbitmq_default_port; // default
        $this->login = (string) $login;
        $this->passcode = (string) $passcode;
        $this->virtual_host = $vhost;
        $this->realm = $realm;

        $this->timeout = 5;
    }

    /**
     * Sets timeout for socket connection, default is 5 seconds
     * @param type $sec
     */
    public function setTimeout($sec) {
        $this->timeout = (int) $sec;
    }

    /**
     * Sets host where stomp is listening, default 'http://localhost'
     * @param type $host
     */
    public function setHost($host) {
        $this->host = (string)$host;
    }

    /**
     * Sets port where stomp is listening
     * @param type $port
     */
    public function setPort($port) {
        $this->port = (string)$port;
    }

    /**
     * Sends a message to queue
     * FIXME: could fsockopen/fwrite/fread be used to create exchanges and queues?
     * @param type $message
     * @param type $queue
     * @throws StompException on socket fails and unexpected responses
     * @returns boolean true on success (throws exception on fail)
     */
    public function send($message, $queue) {
        global $log;
        $m = (string)$message;
        $q = (string)$queue;
        
    	$msg_connect = "CONNECT\nlogin:$this->login\npasscode:$this->passcode\nvirtual-host:$this->virtual_host\nrealm:$this->realm\n\n\x00";
        $msg_send = "SEND\ndestination:$q\nreceipt:ok\n\n$m\x00";
        $msg_disconnect = "DISCONNECT\n\n\x00";

        if (!($r = fsockopen($this->host, $this->port))) throw new StompException('fsockopen failed');
        
        // set the timeout on the stream
        stream_set_timeout($r, $this->timeout);

        // ----------------------------------------------------------------------------------
        // connect to queue
        $writeReturn = fwrite($r, $msg_connect);//.$msg_send.$msg_disconnect);
        if(!$writeReturn){
            $md = stream_get_meta_data($r);
            if ($md['timed_out']) throw new StompException('connection timed out');
            throw new StompException('fwrite failed');
        }
        $receipt = fread($r, 9);
        // check response: connected, receipt ok.
        if (!('CONNECTED' == $receipt)){
            $md = stream_get_meta_data($r);
            if ($md['timed_out']) throw new StompException('connection timed out');
            throw new StompException('did not get response CONNECTED');
        }

        // ----------------------------------------------------------------------------------
        // send message
        $writeReturn = fwrite($r, $msg_send);//.$msg_send.$msg_disconnect);
        $log->debug("Message written: " . $msg_send);

        $receipt = fread($r, 99); // skip the session, heartbeat and version info
        $receipt = fread($r, 13); //13
        $log->debug('receipt: ' . $receipt);
        
        $md = stream_get_meta_data($r);
        if ($md['timed_out']) throw new StompException('connection timed out');
        if (!("receipt-id:ok" == $receipt)){
            $md = stream_get_meta_data($r);
            if ($md['timed_out']) throw new StompException('connection timed out');
            throw new StompException('did not get response RECEIPT: ' . $receipt);
        }
        // ----------------------------------------------------------------------------------
        // disconnect from queue
        $writeReturn = fwrite($r, $msg_disconnect);//.$msg_send.$msg_disconnect);
        if(!$writeReturn){
            $md = stream_get_meta_data($r);
            if ($md['timed_out']) throw new StompException('connection timed out');
            throw new StompException('fwrite failed');
        }

        fclose($r);
        return true;
    }

}

class StompException extends Exception {}
?>
