<?php

// don’t touch if file execute code.

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

require_once __DIR__ . '/bootstrap/config/initial.php';

function dummy() 
{

}

define('my_constant', true);

dummy();

$x = 3;
if (1 === 1) {
	$x = 1;
}

?>