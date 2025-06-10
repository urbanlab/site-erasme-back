<?php

/** Docblock */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = [
	'key_1' => 'text 1',
	'key_2' => 'text 2',
];

?>
-----
<?php

/** Docblock */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

return [
	'key_1' => 'text 1',
	'key_2' => 'text 2',
];

?>