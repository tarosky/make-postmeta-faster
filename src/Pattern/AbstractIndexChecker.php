<?php

namespace Tarosky\MakePostmetaFaster\Pattern;


/**
 * Index checker patter.
 *
 * @property-read \wpdb $db WordPress database object.
 */
abstract class AbstractIndexChecker extends SingletonPattern {

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	abstract protected function table(): string;

	/**
	 * Key name of index.
	 *
	 * @return string
	 */
	abstract public function key_name(): string;

	/**
	 * Option name to store key length setting.
	 *
	 * @return string
	 */
	abstract public function option_name(): string;

	/**
	 * Explain query to check if this index is required.
	 *
	 * @return string
	 */
	abstract protected function explain_query(): string;

	/**
	 * Key length for (meta_key, meta_value) composite index.
	 *
	 * Reads the per-table option and sanitizes the values. Values are clamped
	 * to MySQL / InnoDB acceptable ranges:
	 * - meta_key: 32 - 255 (varchar(255) native size)
	 * - meta_value: min 64 (longtext, realistic prefix length)
	 *
	 * @return int[] [meta_key_length, meta_value_length]
	 */
	public function key_length(): array {
		$length = get_option( $this->option_name(), array( 255, 64 ) );
		if ( ! is_array( $length ) || count( $length ) !== 2 ) {
			return array( 255, 64 );
		}
		list( $key_len, $val_len ) = array_map( 'intval', $length );
		return array(
			min( max( 32, $key_len ), 255 ),
			max( 64, $val_len ),
		);
	}

	/**
	 * A query to add index for the table.
	 *
	 * Common implementation for all meta tables (postmeta/usermeta/termmeta).
	 * All three have the same meta_key/meta_value columns.
	 *
	 * @return string
	 */
	protected function add_index_query(): string {
		$query                       = <<<SQL
			ALTER TABLE %i ADD INDEX %i (meta_key(%d), meta_value(%d));
SQL;
		list( $key_len, $value_len ) = $this->key_length();
		return $this->db->prepare( $query, $this->table(), $this->key_name(), $key_len, $value_len );
	}

	/**
	 * Get index from key name.
	 *
	 * @param string $name Index name. If empty, all indices.
	 * @return array Return an array of indices if exists.
	 */
	public function get_indices( $name = '' ) {
		if ( ! $name ) {
			$query = <<<SQL
				SHOW INDEX FROM %i
SQL;
			$query = $this->db->prepare( $query, $this->table() );
		} else {
			$query = <<<SQL
				SHOW INDEX FROM %i WHERE Key_name = %s
SQL;
			$query = $this->db->prepare( $query, $this->table(), $name );
		}
		return $this->db->get_results( $query, ARRAY_A );
	}

	/**
	 * Drop index query.
	 *
	 * @return string
	 */
	public function drop_index_query() {
		$query = <<<SQL
		DROP INDEX %i ON %i;
SQL;
		return $this->db->prepare( $query, $this->key_name(), $this->table() );
	}

	/**
	 * Drop index from table.
	 *
	 * @return bool
	 */
	public function drop_index() {
		return (bool) $this->db->query( $this->drop_index_query() );
	}

	/**
	 * Create index for table.
	 *
	 * @return bool
	 */
	public function add() {
		$query = $this->add_index_query();
		return (bool) $this->db->query( $query );
	}

	/**
	 * Where if the index exists.
	 *
	 * @return bool
	 */
	public function has_index() {
		return count( $this->get_indices( $this->key_name() ) ) > 0;
	}

	/**
	 * Explain query.
	 *
	 * @return array
	 */
	public function explain() {
		return $this->db->get_results( $this->explain_query(), ARRAY_A );
	}

	/**
	 * Get index score.
	 *
	 * @return array{filesort:int, temporary:int}
	 */
	public function explain_score() {
		$explain = $this->explain();
		$result  = array(
			'filesort'  => 0,
			'temporary' => 0,
		);
		foreach ( $explain as $row ) {
			$extra = $row['Extra'];
			if ( str_contains( $extra, 'Using filesort' ) ) {
				++$result['filesort'];
			}
			if ( str_contains( $extra, 'Using temporary' ) ) {
				++$result['temporary'];
			}
		}
		return $result;
	}

	/**
	 * Getter
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'db':
				global $wpdb;
				return $wpdb;
			default:
				return null;
		}
	}
}
