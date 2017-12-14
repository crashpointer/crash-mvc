<?php

class Response{
	
	protected static $data;

	protected static $format = 'application/json';

	public static function create($data, $format){
		self::$data = $data;
		self::setFormat($format);
	}

    public static function setFormat(){
        $args = trim(strtolower(func_get_arg(0)));
        if(!empty($args) && preg_match('/^[a-z0-9]+\/[a-z0-9]+$/', $args)){
            self::$format = $args;
        }

        else{
            throw new Exception("Invalid Format Type");
        }
    }

	public static function render(){
		header('Content-Type: ' . self::$format);
        print(self::$data);
	}
}

?>