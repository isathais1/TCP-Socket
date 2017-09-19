<?php

namespace Parsidev\Socket;

use RuntimeException;

class Socket
{

    protected $ip;
    protected $port;
    protected $protocol;
    protected $socket;
    protected $isConnected = false;
    protected $myIp;
    protected $myPort;

    public function __construct($ip, $port, $protocol)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->protocol = $protocol;
        if (!($this->socket = socket_create(AF_INET, SOCK_STREAM, $protocol))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            $this->isConnected = false;
            throw new RuntimeException($errormsg, $errorcode);
        }
    }

    public function connect()
    {
        if (!socket_connect($this->socket, $this->ip, intval($this->port))) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            $this->isConnected = false;
            throw new RuntimeException($errormsg, $errorcode);
        } else {
            $this->isConnected = true;
            socket_getsockname($this->socket, $IP, $PORT);
            $this->myIp=$IP;
            $this->myPort = $PORT;
            return ['IP' => $IP, "PORT" => $PORT];
        }
    }


    public function disconnect()
    {
        socket_close($this->socket);
    }

    public function readMessage($length = 2048, $type = PHP_BINARY_READ)
    {
        $result = socket_read($this->socket, $length, $type);
        if (is_null($result))
            $this->readMessage($length, $type);

        return $result;
    }

    public function receiveMessage(){
        $result = null;
        $result = $this->sendMessage("pp@".$this->myIp. "-" . $this->myPort . "\r\n");

        if(is_null($result))
            $result = $this->sendMessage("pp@".$this->myIp. "-" . $this->myPort . "\r\n");

        return $result;
    }


    public function sendMessage($message)
    {

        $message = "$message\n\0";
        $length = strlen($message);

        while(true) {
            $sent = socket_write($this->socket,$message,$length);
            if($sent === false) {
                $errorCode = socket_last_error();
                $errorMessage = socket_strerror($errorCode);
                $this->isConnected = false;
                throw new RuntimeException($errorMessage, $errorCode);
            }
            if($sent < $length) {
                $message = substr($message, $sent);
                $length -= $sent;
                print("Message truncated: Resending: $message");
            } else {
                return true;
            }
        }
        return false;
    }

    public function sendMessageTo($message, $ip, $port)
    {
        $result = socket_sendto($this->socket, $message, strlen($message), 0, $ip, $port);
        if (!$result) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            $this->isConnected = false;
            throw new RuntimeException($errormsg, $errorcode);
        }
        return $result;
    }
}
