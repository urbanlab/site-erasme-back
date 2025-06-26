<?php

if (!defined('_ECRIRE_INC_VERSION')) {
  return;
}

function graphql_header_prive($flux) {
  $flux .= recuperer_fond('prive/js/graphql');
  $flux .= '<link
		rel="stylesheet"
		href="https://esm.sh/graphiql@4.0.0/dist/style.css"
	/>';
  $flux .= '<link
		rel="stylesheet"
		href="https://esm.sh/@graphiql/plugin-explorer@4.0.0/dist/style.css"
	/>';

  return $flux;
}
