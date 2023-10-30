<?php

namespace Tarosky\MakePostmetaFaster;

use cli\Table;
use Tarosky\MakePostmetaFaster\IndexChecker\PostMetaIndexChecker;

/**
 * Utility commands for Make Postmeta Faster plugin.
 */
class Command extends \WP_CLI_Command {

	/**
	 * Get DB instance by cli arguments.
	 *
	 * @param string $name DB name for command.
	 * @return Pattern\AbstractIndexChecker
	 */
	protected function map_dbname( $name )  {
		$map = [
			'postmeta' => PostMetaIndexChecker::get_instance(),
		];
		if ( ! isset( $map[ $name ]) ) {
			\WP_CLI::error( sprintf( __( '%s is not available', 'mpmf' ), $name ) );
		}
		return $map[ $name ];
	}

	/**
	 * Check indices for table.
	 *
	 * ## Option
	 *
	 * <table>
	 * : Table name.
	 *
	 * @synopsis <table>
	 * @return void
	 */
	public function display( $args ) {
		list( $table ) = $args;
		$result = $this->map_dbname( $table )->get_indices();
		if ( empty( $result ) ) {
			\WP_CLI::error( __( 'No index found for this table.', 'mpmf' ) );
		}
		$this->array_to_table( $result );
	}

	/**
	 * Does this table has valid index?
	 *
	 * ## Option
	 *
	 * <table>
	 * : Table name.
	 *
	 * @subcommand is-valid
	 * @synopsis <table>
	 * @return void
	 */
	public function is_valid( $args ) {
		list( $table ) = $args;
		$result = $this->map_dbname( $table )->has_index();
		if ( $result ) {
			\WP_CLI::success( sprintf( __( '%s has a valid index.', 'mpmf' ), $table ) );
		} else {
			\WP_CLI::error( sprintf( __( '%s has no valid index yet.', 'mpmf' ), $table ) );
		}
	}

	/**
	 * Clear index of table.
	 *
	 * ## Option
	 *
	 * <table>
	 * : Table name.
	 *
	 * @param array $args
	 * @synopsis <table>
	 * @return void
	 */
	public function clear( $args ) {
		list( $table ) = $args;
		$db = $this->map_dbname( $table );
		if ( ! $db->has_index() ) {
			\WP_CLI::error( sprintf( __( '%s has no valid index yet.', 'mpmf' ), $table ) );
		}
		if ( ! $db->drop_index() ) {
			\WP_CLI::error( sprintf( __( 'Failed to remove index from %s.', 'mpmf' ), $table ) );
		}
		\WP_CLI::success( sprintf( __( 'Index is successfully removed from %s.', 'mpmf' ), $table ) );
	}

	/**
	 * Add index of table.
	 *
	 * ## Option
	 *
	 * <table>
	 * : Table name.
	 *
	 * [--update]
	 * : If set, force update.
	 *
	 * @param array $args
	 * @synopsis <table> [--update]
	 * @return void
	 */
	public function add( $args, $assoc ) {
		list( $table ) = $args;
		$update        = $assoc['update'] ?? false;
		$db            = $this->map_dbname( $table );
		if ( $db->has_index() ) {
			if ( ! $update ) {
				\WP_CLI::error( sprintf( __( '%s already has valid index. Add "--update" option for forcible update.', 'mpmf' ), $table ) );
			} elseif ( ! $db->drop_index() ) {
				\WP_CLI::error( sprintf( __( 'Failed to remove old index of %s.', 'mpmf' ), $table ) );
			}
		}
		if ( ! $result = $db->add() ) {
			\WP_CLI::error( sprintf( __( 'Failed to add index of %s.', 'mpmf' ), $table ) );
		}
		\WP_CLI::success( sprintf( __( 'Index is successfully added to %s.', 'mpmf' ), $table ) );
	}

	/**
	 * Explain query
	 *
	 * ## Option
	 *
	 * <table>
	 * : Table name.
	 *
	 * @param array $args
	 * @synopsis <table>
	 * @return void
	 */
	public function explain( $args ) {
		list( $table ) = $args;
		$db = $this->map_dbname( $table );
		$result = $db->explain();
		$this->array_to_table( $result );
		$score = array_sum( $db->explain_score() );
		$s = $db->explain_score();
		if ( $score ) {
			\WP_CLI::error( sprintf( __( 'Bad filesort count: %d', 'mpmf' ), $score ) );
		} else {
			\WP_CLI::success( __( 'Query is fast and good.', 'mpmf' ) );
		}
	}

	/**
	 * Convert table to array.
	 *
	 * @param array $result
	 * @return void
	 */
	protected function array_to_table( $result ) {
		$table = new Table();
		foreach ( $result as $index => $row ) {
			if ( ! $index ) {
				$table->setHeaders( array_keys( $row ) );
			}
			$table->addRow( array_values( $row ) );
		}
		$table->display();
	}
}
