<?php

namespace Tarosky\MakePostmetaFaster\Pattern;

/**
 * Singleton pattern.
 */
abstract class SingletonPattern {

	/**
	 * @var static[] Instances.
	 */
	private static $instances = array();

	/**
	 * Constructor.
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * Executed inside constructor.
	 *
	 * @return void
	 */
	protected function init() {
		// Do something here.
	}

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	final public static function get_instance() {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}
		return self::$instances[ $class_name ];
	}
}
