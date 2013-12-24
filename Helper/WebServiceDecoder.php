<?php
/* retrieved from http://seancode.blogspot.com/2006/05/my-own-jsonphp-web-services.html */
require_once("JSON.php");
class WebServiceDecoder {
    var $methods;
    var $json;
   
    function initialize() {
        $this->json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
    }
   
    /*
    add a function to the server, so it can be accessed
    */
    function register($name) {
        $this->methods[$name] = true;
    }
   
    /*
    set a registered function to be inaccessible
    */
    function deregister($name) {
        $this->methods[$name] = false;
    }
   
    /*
    execute the given method, passing its single parameter
    JSON-encodes the return value, which should be an object or associative array
    */
    function call($name, $param) {
        if ($this->methods[$name] == true) {
            $evalstring = $name."(\$param);";
            eval("\$rval=".$evalstring.";");
            //return $this->json->encode($rval);
            // already jsonized
            return $rval;
        }
    }
   
    /*
    decode the JSON param into a native object, and call the given method
    return the JSON-encoded object to the browser via echo
    */
    function serve($method, $param) {
		// SCM 08/31/2013
		//session_start();
        //$obj = $this->json->decode(stripslashes($param));
        $obj = stripslashes($param);
        if ($this->methods[$method] == true) {
            $res = $this->call($method, $obj);
        } else {
            $res = $this->json->encode("Not a registered function.");
        }
       
        echo $res;
    }
}

?>