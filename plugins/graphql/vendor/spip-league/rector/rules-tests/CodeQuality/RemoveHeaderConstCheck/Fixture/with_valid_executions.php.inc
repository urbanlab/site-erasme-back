<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

define('dummy', true);

require_once __DIR__ . 'demo.php';

include __DIR__ . 'dummy.php';

include_spip('inc/texte');

?>
-----
<?php

define('dummy', true);

require_once __DIR__ . 'demo.php';

include __DIR__ . 'dummy.php';

include_spip('inc/texte');

?>