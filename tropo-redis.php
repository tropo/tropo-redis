<?php

/*
 * A simple PHP script to allow speech recongition of color selections.
 * Publishes selections on a Tropo channel through a persistent socket connection.
 * 
 */

// Redis settings
define("REDIS_HOST", "bass.redistogo.com");
define("REDIS_PORT", 9219);
define("REDIS_PASS", "1604d5037f6fd79b71f158019a1eff04");

$redis = new Redis(REDIS_HOST, REDIS_PORT);
$redis->connect();
$redis->auth(REDIS_PASS);

say("Welcome to the Redis and socket I O test.");

do {

	$result = ask("Say the color you want to see. When done, say stop.", array("choices" => "white, blue, green, yellow, stop"));
	_log("*** User selected: ".$result->value." ***");
	$response = $redis->publish("tropo.color", $result->value);

} while ($result->value != 'stop');

$redis->disconnect();
say("Goodbye.");
hangup();

class Redis {
    
    // Private class members.
	private $server;
    private $port;
    private $sock;
    private $auth;
 
    function __construct($host='localhost', $port=6379) {
        $this->host = $host;
        $this->port = $port;
    }
    
    public function connect() {
        if ($this->sock)
            return;
        if ($sock = fsockopen($this->host, $this->port, $errno, $errstr)) {
            $this->sock = $sock;
            return;
        }
        $msg = "Cannot open socket to {$this->host}:{$this->port}";
        if ($errno || $errmsg) {
            $msg .= "," . ($errno ? " error $errno" : "") . ($errmsg ? " $errmsg" : "");
	   }
        trigger_error("$msg.", E_USER_ERROR);
    }
    
    public function auth($password) {
    	$this->auth = $password;
    }
    
    public function disconnect() {
        if ($this->sock)
            @fclose($this->sock);
        $this->sock = null;
    }

    public function publish($channel, $message) {
        
    	// Connect to the server.
    	$this->connect();
    	
    	// Authenticate.
    	if($this->auth) {
    		$this->doAuth($this->auth);	
    	}
        
    	// Publish message.
        $this->write("PUBLISH $channel $message\r\n");
        return $this->get_response();
        
	}
        	
	private function doAuth($auth) {
		$this->write("AUTH $auth\r\n");
	}
	
	private function write($s) {
        while ($s) {
            $i = fwrite($this->sock, $s);
            if ($i == 0) // || $i == strlen($s))
                break;
            $s = substr($s, $i);
        }
    }
    
    private function read($len=1024) {
        if ($s = fgets($this->sock))
            return $s;
        $this->disconnect();
        trigger_error("Cannot read from socket.", E_USER_ERROR);
    }
    
    private function get_response() {
        $data = trim($this->read());
        $c = $data[0];
        $data = substr($data, 1);
        switch ($c) {
            case '-':
                trigger_error(substr($data, 0, 4) == 'ERR ' ? substr($data, 4) : $data, E_USER_ERROR);
                break;
            case '+':
                return $data;
            case '*':
                $num = (int)$data;
                if ((string)$num != $data)
                    trigger_error("Cannot convert multi-response header '$data' to integer", E_USER_ERROR);
                $result = array();
                for ($i=0; $i<$num; $i++)
                    $result[] =& $this-get_value();
                return $result;
            default:
                return $this->get_value($c . $data);
        }
    }
    
    private function get_value($data=null) {
        if ($data === null)
            $data =& trim($this->read());
        if ($data == '$-1')
            return null;
        $c = $data[0];
        $data = substr($data, 1);
        $i = strpos($data, '.') !== false ? (int)$data : (float)$data;
        if ((string)$i != $data)
            trigger_error("Cannot convert data '$c$data' to integer", E_USER_ERROR);
        if ($c == ':')
            return $i;
        if ($c != '$')
            trigger_error("Unkown response prefix for '$c$data'", E_USER_ERROR);
        $buffer = '';
        while (true) {
            $data =& $this->read();
            $i -= strlen($data);
            $buffer .= $data;
            if ($i < 0)
                break;
        }
        return substr($buffer, 0, -2);
    }    

}


?>
