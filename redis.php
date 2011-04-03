<?php
/**
 * 
 * Simple PHP class for Redis (stand alone version)
 * Adapted from Redis.php by Ludovico Magnocavallo. (http://redis.io/topics/twitter-clone)
 * @author Mark Headd
 *
 */
class Redis {
    
    // Private class members.
	private $server;
    private $port;
    private $sock;
    private $auth;
 
    /**
     * 
     * Class consrtuctor
     * @param string $host
     * @param int $port
     */
    function __construct($host='localhost', $port=6379) {
        $this->host = $host;
        $this->port = $port;
    }
    
    /**
     * 
     * Connect to a Redis server.
     */
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
    
	/**
	 * 
	 * Set authentication credentials
	 * @param string $password
	 */
    public function auth($password) {
    	$this->auth = $password;
    }
    
    /**
     * 
     * Disconnect from a Redis server.
     */
    public function disconnect() {
        if ($this->sock)
            @fclose($this->sock);
        $this->sock = null;
    }

    /**
     * 
     * Publish a message on a channel.
     * @param string $channel
     * @param string $message
     */
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
        
    /**
     * 
     * Private methods.
	 * 
     */
	
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
