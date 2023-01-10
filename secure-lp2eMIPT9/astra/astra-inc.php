<?php
try{
	$path_to_astra = dirname(__FILE__) .'/Astra.php';
	
	if(file_exists($path_to_astra)){
		include($path_to_astra);
		
        if(class_exists('Astra')){
            $astra = new Astra();
        }
	}
}
catch(Exception $e) {
 // Graceful degradation
}
?>