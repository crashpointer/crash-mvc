<?php

class Loader{

	public static function auto_loader($classname){
		$directories = array(
			'rest/',
            'rest/libs/',
            'rest/controllers/',
			'rest/models/'
        );

		foreach($directories as $directory){
			$filename = $directory . $classname . '.php';

			if(is_file($filename)){
				include $filename;
				break;
			}
		}
	}
}

spl_autoload_register('Loader::auto_loader');

?>