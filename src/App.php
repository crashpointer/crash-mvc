<?php
/**
 * Created by PhpStorm.
 * User: crashpointer
 * Date: 4.3.2015
 * Time: 12:02
 */

date_default_timezone_set('America/Los_Angeles');

include 'Loader.php';

class App{

    protected $res;
    protected $method;
    protected $header = 'application/json';
    protected $parameters;
    protected $url_elements;

    function __construct(){
        if(isset($_SERVER['PATH_INFO'])){
            $this->url_elements = explode('/', trim($_SERVER['PATH_INFO'], '/'));
            if(count($this->url_elements) > 1)
                array_shift($this->url_elements);
        }

        $this->method = $_SERVER['REQUEST_METHOD'];
        switch($this->method){
            case 'GET':
                $this->header = 'text/html';
                $this->parameters = $_GET;
                break;
            case 'POST':
                $this->parameters = $_POST;
                break;
            case 'PUT':
                $this->parameters = json_decode(file_get_contents('php://input'), true);
                break;
        }

        $this->call();
    }

    protected function call(){
        if(!empty($this->url_elements)){
            $controller_name = ucfirst($this->url_elements[0]) . 'Controller';
            if(class_exists($controller_name)){
                $controller = new $controller_name;
                $action_name = strtolower($this->method);
                Response::create(call_user_func_array(array($controller, $action_name), array($this->parameters)), $this->header);
                Response::render();
            }
        }
    }

}

$app = new App;

?>
