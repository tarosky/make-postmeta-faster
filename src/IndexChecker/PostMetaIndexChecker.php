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
	 * Explain query.
	 *
	 * @return string
	 */
	protected function explain_query(): string {
		$query = <<<SQL
			EXPLAIN SELECT * FROM %i
			WHERE meta_key = %s
			  AND meta_value LIKE %s
SQL;
		$query = $this->db->prepare( $query, $this->table(), '_wp_attached_file', wp_date( 'Y/m%' ) );
		return $query;
	}

	/**
	 * Key length.
	 *
	 * @return int[]
	 */
	protected function key_length() {
		$length = get_option( 'mpmf-postmeta-key-length', [ 255, 64 ] );
		if ( ! is_array( $length ) || count( $length ) !== 2 ) {
			return [ 255, 64 ];
		}
		list( $key_len, $val_len ) = array_map( 'intval', $length );
		return [
			min( max( 32, $key_len ), 255 ),
			max( 64, $val_len ),
		];
	}

}
