<?php

/**
 * @copyright  Frederic G. Østby
 * @license    http://www.makoframework.com/license
 */

namespace mako\reactor\tasks;

use \StdClass;

use \mako\reactor\io\Input;
use \mako\reactor\io\Output;
use \mako\syringe\Container;

/**
 * Database migrations.
 *
 * @author  Frederic G. Østby
 */

class Migrate extends \mako\reactor\Task
{
	/**
	 * IoC container instance.
	 * 
	 * @var \mako\syringe\Container
	 */

	protected $container;

	/**
	 * Application instance.
	 * 
	 * @var \mako\application\Application
	 */

	protected $application;

	/**
	 * File system instance.
	 * 
	 * @var \mako\file\FileSystem
	 */

	protected $fileSystem;

	/**
	 * Database connection.
	 *
	 * @var \mako\database\Connection
	 */

	protected $connection;

	/**
	 * Task information.
	 * 
	 * @var array
	 */

	protected static $taskInfo = 
	[
		'status' => 
		[
			'description' => 'Checks if there are any outstanding migrations.'
		],
		'create' => 
		[
			'description' => 'Creates a new migration.',
		],
		'up' => 
		[
			'description' => 'Runs all outstanding migrations.',
		],
		'down' => 
		[
			'description' => 'Rolls back the last batch of migrations.'
		],
		'reset' => 
		[
			'description' => 'Rolls back all migrations.',
			'options'     => 
			[
				'force' => 'Force the schema reset?'
			],
		],
	];

	/**
	 * Constructor.
	 * 
	 * @access  public
	 * @param   \mako\reactor\io\Input   $input      Input
	 * @param   \mako\reactor\io\Output  $output     Output
	 * @param   \mako\syringe\Containe   $container  IoC container instance
	 */

	public function __construct(Input $input, Output $output, Container $container)
	{
		parent::__construct($input, $output);

		$this->container = $container;

		$this->application = $container->get('app');

		$this->fileSystem = $container->get('fileSystem');
	}

	/**
	 * Returns the database connection.
	 * 
	 * @access  protected
	 * @return  \mako\database\Connection
	 */

	protected function connection()
	{
		if(empty($this->connection))
		{
			$this->connection = $this->container->get('database')->connection();
		}

		return $this->connection;
	}

	/**
	 * Returns a query builder instance.
	 *
	 * @access  protected
	 * @return  \mako\database\query\Query
	 */

	protected function table()
	{
		return $this->connection()->builder()->table('mako_migrations');
	}

	/**
	 * Returns array of all outstanding migrations.
	 *
	 * @access  protected
	 * @return  array
	 */

	protected function getOutstanding()
	{
		$migrations = [];

		// Get application migrations

		$files = glob($this->application->getApplicationPath() . '/migrations/*.php');

		if(is_array($files))
		{
			foreach($files as $file)
			{
				$migration = new StdClass();
				
				$migration->version = basename($file, '.php');
				$migration->package = '';

				$migrations[] = $migration;
			}
		}

		// Get package migrations

		$packages = glob($this->application->getApplicationPath() . '/packages/*');

		if(is_array($packages))
		{
			foreach($packages as $package)
			{
				if(is_dir($package))
				{
					$files = glob($package . '/migrations/*.php');

					if(is_array($files))
					{
						foreach($files as $file)
						{
							$migration = new StdClass();

							$migration->version = basename($file, '.php');
							$migration->package = basename($package);

							$migrations[] = $migration;
						}
					}
				}
			}
		}

		// Remove migrations that have already been executed

		foreach($this->table()->all() as $ran)
		{
			foreach($migrations as $key => $migration)
			{
				if($ran->package === $migration->package && $ran->version === $migration->version)
				{
					unset($migrations[$key]);
				}
			}
		}

		// Sort remaining migrations so that they get executed in the right order

		usort($migrations, function($a, $b)
		{
			return strcmp($a->version, $b->version);
		});

		return $migrations;
	}

	/**
	 * Returns an array of migrations to roll back.
	 * 
	 * @access  protected
	 * @param   int        $batches  Number of batches to roll back
	 * @return  array
	 */

	protected function getBatch($batches = 1)
	{
		$query = $this->table();

		if($batches > 0)
		{
			$query->where('batch', '>', ($this->table()->max('batch') - $batches));
		}

		return $query->select(['version', 'package'])->orderBy('version', 'desc')->all();
	}

	/**
	 * Returns a migration instance.
	 *
	 * @access  protected
	 * @param   StdClass   $migration  Migration object
	 * @return  Migration
	 */

	protected function resolve($migration)
	{
		$class = $migration->version;

		if(empty($migration->package))
		{
			$namespace = $this->application->getApplicationNamespace(true) . '\\migrations\\';
		}
		else
		{
			$namespace = '\\' . $migration->package . '\\migrations\\';
		}

		return $this->container->get($namespace . $class);
	}

	/**
	 * Displays the number of outstanding migrations.
	 *
	 * @access  public
	 */

	public function status()
	{
		if(($count = count($this->getOutstanding())) > 0)
		{
			$this->output->writeln(vsprintf(($count === 1 ? 'There is %s outstanding migration.' : 'There are %s outstanding migrations.'), ['<yellow>' . $count . '</yellow>']));
		}
		else
		{
			$this->output->writeln('<green>There are no outstanding migrations.</green>');
		}
	}

	/**
	 * Runs all outstanding migrations.
	 *
	 * @access  public
	 */

	public function up()
	{
		$migrations = $this->getOutstanding();

		if(empty($migrations))
		{
			return $this->output->writeln('<blue>There are no outstanding migrations.</blue>');
		}

		$batch = $this->table()->max('batch') + 1;

		foreach($migrations as $migration)
		{
			$this->resolve($migration)->up();

			$this->table()->insert(['batch' => $batch, 'package' => $migration->package, 'version' => $migration->version]);

			$name = $migration->version;

			if(!empty($migration->package))
			{
				$name = $migration->package . '::' . $name;
			}

			$this->output->writeln('Ran the ' . $name . ' migration.');
		}
	}

	/**
	 * Rolls back the n last migration batches.
	 *
	 * @access  public
	 * @param   int     $batches  Number of batches to roll back
	 */

	public function down($batches = 1)
	{
		$migrations = $this->getBatch($batches);

		if(empty($migrations))
		{
			$this->output->writeln('<blue>There are no migrations to roll back.</blue>');
		}

		foreach($migrations as $migration)
		{
			$this->resolve($migration)->down();

			$this->table()->where('version', '=', $migration->version)->delete();

			$name = $migration->version;

			if(!empty($migration->package))
			{
				$name = $migration->package . '::' . $name;
			}

			$this->output->writeln('Rolled back the ' . $name . ' migration.');
		}
	}

	/**
	 * Rolls back all migrations.
	 *
	 * @access  public
	 */

	public function reset()
	{
		if($this->input->param('force', false) || $this->input->confirm('Are you sure you want to reset your database?'))
		{
			$this->down(0);
		}
	}

	/**
	 * Creates a migration template.
	 *
	 * @access  public
	 * @param   string  $package  (optional) Package name
	 */

	public function create($package = '')
	{
		// Get file path

		$file = 'Migration_' . $version = gmdate('YmdHis');

		if(empty($package))
		{
			$namespace = $this->application->getApplicationNamespace() . '\\migrations';
		}
		else
		{
			$file = $package . '::' . $file;

			$namespace = $package . '\migrations';
		}

		$file = \mako\get_path($this->application->getApplicationPath(), 'migrations', $file);

		// Create migration

		$migration = str_replace
		(
			['{{namespace}}', '{{version}}'], 
			[$namespace, $version], 
			$this->fileSystem->getContents(__DIR__ . '/migrate/migration.tpl')
		);

		if(!@$this->fileSystem->putContents($file, $migration))
		{
			return $this->output->error('Failed to create migration. Make sure that the migrations directory is writable.');
		}

		$this->output->writeln(vsprintf('Migration created at "%s".', [$file]));
	}
}