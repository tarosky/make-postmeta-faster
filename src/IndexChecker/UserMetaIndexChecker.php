<?php

namespace Tarosky\MakePostmetaFaster\IndexChecker;


use Tarosky\MakePostmetaFaster\Pattern\AbstractIndexChecker;

/**
 * User meta index checker.
 */
class UserMetaIndexChecker extends AbstractIndexChecker {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return $this->db->usermeta;
	}

	/**
	 * {@inheritDoc}
	 */
	public function key_name(): string {
		return 'meta_key_meta_value';
	}

	/**
	 * {@inheritDoc}
	 */
	public function option_name(): string {
		return 'mpmf-usermeta-key-length';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Uses `first_name` which is a common user meta for a realistic sample.
	 */
	protected function explain_query(): string {
		$query = <<<SQL
			EXPLAIN SELECT * FROM %i
			WHERE meta_key = %s
			  AND meta_value LIKE %s
SQL;
		return $this->db->prepare( $query, $this->table(), 'first_name', 'A%' );
	}
}
