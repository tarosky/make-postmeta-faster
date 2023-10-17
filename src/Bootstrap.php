<?php

namespace Tarosky\MakePostmetaFaster;


use Tarosky\MakePostmetaFaster\Pattern\SingletonPattern;

/**
 * Plugin Bootstrap.
 */
class Bootstrap extends SingletonPattern {

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		// Register command.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'index', Command::class );
		}
	}
}
