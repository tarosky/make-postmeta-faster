<?php

namespace Tarosky\MakePostmetaFaster\IndexChecker;


use Tarosky\MakePostmetaFaster\Pattern\AbstractIndexChecker;

/**
 * Post meta index checker.
 */
class PostMetaIndexChecker extends AbstractIndexChecker {

	/**
	 * {@inheritDoc}
	 */
	protected function table(): string {
		return $this->db->postmeta;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function key_name(): string {
		return 'meta_key_meta_value';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function add_index_query(): string {
		$query = <<<SQL
			ALTER TABLE %i ADD INDEX meta_key_meta_value (meta_key(%d), meta_value (%d));
SQL;
		list( $meta_key_len, $meta_value_len ) = $this->key_length();
		return $this->db->prepare( $query, $this->table(), $meta_key_len, $meta_value_len );
	}

	/**
	 * Key length.
	 *
	 * @return int[]
	 */
	protected function key_length() {
		return [ 255, 64 ];
	}
}
