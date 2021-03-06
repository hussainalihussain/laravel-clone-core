<?php
/**
 * Created by PhpStorm.
 * User: qasim
 * Date: 2022-03-31
 * Time: 3:51 PM
 */

namespace qasimlearner\laravelclone\db;


class Database
{
	// for PHP 7.4+
//	public PDO $pdo;
	public $pdo;

	public function __construct(array $config)
	{
		$dsn = $config['dsn'] ?? '';
		$user = $config['user'] ?? '';
		$password = $config['password'] ?? '';

		$this->pdo = new \PDO($dsn, $user, $password);
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}

	public function applyMigrations()
	{
		$this->createMigrationsTable();
		$appliedMigrations = $this->getAppliedMigrations();
		$files = scandir(Application::$ROOT_PATH . '/migrations');
		$toApplyMigrations = array_diff($files, $appliedMigrations);
		$newMigrations = [];

		foreach ($toApplyMigrations as $migration)
		{
			if($migration === '.' || $migration === '..')
			{
				continue;
			}

			require_once Application::$ROOT_PATH . '/migrations/' . $migration;
			$className = pathinfo($migration, PATHINFO_FILENAME);
			$instance = new $className();
			$this->log("Applying migration {$migration}");
			$instance->up();
			$this->log("Applied migration {$migration}");

			$newMigrations[] = $migration;
		}

		if(!empty($newMigrations))
		{
			$this->saveMigrations($newMigrations);
		}
		else
		{
			$this->log('All migrations are applied');
		}
	}

	public function createMigrationsTable()
	{
		$this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
			id INT AUTO_INCREMENT PRIMARY KEY,
			migration VARCHAR(255),
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		)");
	}

	public function getAppliedMigrations()
	{
		$statement = $this->pdo->prepare("SELECT * FROM migrations");
		$statement->execute();

		return $statement->fetchAll(\PDO::FETCH_COLUMN, 1);
	}

	public function saveMigrations(array $migrations)
	{
		$migrations = array_map(function($m) {
			return "('{$m}')";
		}, $migrations);

		$migrations_string = implode(",", $migrations);
		$statement = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES
			{$migrations_string}
		");
		$statement->execute();
	}

	public function prepare($sql)
	{
		return $this->pdo->prepare($sql);
	}

	public function log($message)
	{
		echo '[' . date('Y-m-d H:i:s') . '] - ' . $message . PHP_EOL;
	}
}