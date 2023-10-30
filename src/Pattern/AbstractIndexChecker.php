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
	abstract protected function key_name(): string;

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
	 * A query to add index for the table.
	 *
	 * @return string
	 */
	abstract protected function add_index_query(): string;

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
	 * Explain query to check if this index is required.
	 *
	 * @return string
	 */
	abstract protected function explain_query(): string;

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
