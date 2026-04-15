<?php

namespace Tarosky\MakePostmetaFaster\IndexChecker;


use Tarosky\MakePostmetaFaster\Pattern\AbstractIndexChecker;

/**
 * Term meta index checker.
 */
class TermMetaIndexChecker extends AbstractIndexChecker {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return $this->db->termmeta;
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
		return 'mpmf-termmeta-key-length';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Uses `order` which is a common term meta (e.g., WooCommerce category ordering) for a realistic sample.
	 */
	protected function explain_query(): string {
		$query = <<<SQL
			EXPLAIN SELECT * FROM %i
			WHERE meta_key = %s
			  AND meta_value LIKE %s
SQL;
		return $this->db->prepare( $query, $this->table(), 'order', '1%' );
	}
}
