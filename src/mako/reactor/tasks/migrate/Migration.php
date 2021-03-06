<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\reactor\tasks\migrate;

use \mako\database\ConnectionManager;

/**
 * Base migration.
 *
 * @author  Frederic G. Østby
 */

abstract class Migration
{
	/**
	 * Connection manager instance.
	 * 
	 * @var \mako\database\ConnectionManager
	 */
	
	protected $database;

	/**
	 * Constructor.
	 * 
	 * @access  public
	 * @param   \mako\database\ConnectionManager  $connectionManager  Connection manager instance
	 */

	public function __construct(ConnectionManager $connectionManager)
	{
		$this->database = $connectionManager;
	}

	/**
	 * Makes changes to the database structure.
	 *
	 * @access  public
	 */

	abstract public function up();

	/**
	 * Reverts the database changes.
	 *
	 * @access  public
	 */

	abstract public function down();
}