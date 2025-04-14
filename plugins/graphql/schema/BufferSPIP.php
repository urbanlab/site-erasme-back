<?php

declare(strict_types=1);

namespace SPIP\GraphQL;

// https://webonyx.github.io/graphql-php/data-fetching/#solving-n1-problem
class BufferSPIP {
	private static $buffer = [];
	private static $objets = [];

	public static function add($id, $collection) {
		if (array_key_exists($collection, self::$buffer) && in_array($id, self::$buffer[$collection]))
			return;

		self::$buffer[$collection][] = $id;
	}

	private static function loadBuffered($collection) {
		if (array_key_exists($collection, self::$objets))
			return;

		$collection_infos = graphql_getCollectionInfos($collection);
		$ids = implode(',', self::$buffer[$collection]);
		$champ_id = $collection_infos['champ_id'];
		$rows = ReponseSPIP::findCollection($collection, ['collection.' . $champ_id . ' IN (' . $ids . ')'], 0);

		foreach ($rows as $row) {
			$row['typeCollection'] = strtoupper($collection);
			self::$objets[$collection][$row['id']] = $row;
		}
	}

	public static function get($id, $collection) {
		self::loadBuffered($collection);
		return self::$objets[$collection][$id];
	}
}
