<?php
	namespace Tests\Component;

	use PHPUnit\Framework\TestCase;
	use ClipStack\Component\Database;
	use ClipStack\Component\Backbone\Config;
	use ClipStack\Component\ErrorHandler;
	use ClipStack\Component\DateTimeUtility;
	use ClipStack\Component\Validate;
	use Exception;

	class DatabaseTest extends TestCase {
		private Database $database;

		/**
		 * @throws Exception
		 */
		protected function setUp(): void {
			$config_array = [
				'database' => [
					'driver' => 'sqlite',
					'host' => 'localhost',
					'port' => '3306',
					'name' => ':memory:',
					'username' => 'root',
					'password' => '',
					'charset' => 'utf8mb4',
					'collation' => 'utf8mb4_unicode_ci',
					'prefix' => 'test_'
				]
			];

			$config = Config::getInstance($config_array);
			$error_handler = new ErrorHandler($config);
			$date_time_utility = new DateTimeUtility($config);
			$validate = new Validate($error_handler, $date_time_utility);
			$this -> database = Database::getInstance($config, $validate);
		}

		protected function tearDown(): void {
			$this -> database -> closeConnection();
		}

		public function testCreateTable(): void {
			$table = 'example_table';
			$fields = 'id INT PRIMARY KEY, name VARCHAR(255)';
			$result = $this -> database -> createTable($table, $fields);
			$this -> assertTrue($result);
		}

		public function testInsertAndFetchRow(): void {
			$table = 'example_table';
			$data = ['name' => 'John Doe'];

			$this -> database -> createTable($table, 'id INT PRIMARY KEY, name VARCHAR(255)');
			$insert_result = $this -> database -> insert($table, $data);

			$this -> assertTrue($insert_result);

			$this -> database -> query("SELECT * FROM {$table}");
			$row = $this -> database -> result();

			$this -> assertEquals($data['name'], $row['name']);
		}

		public function testUpdateRow(): void {
			$table = 'example_table';
			$data = ['name' => 'John Doe'];
			$where = ['id' => 1];

			$this -> database -> createTable($table, 'id INT PRIMARY KEY, name VARCHAR(255)');
			$this -> database -> insert($table, $data);

			$update_result = $this -> database -> update($table, ['name' => 'Updated Name'], $where);

			$this -> assertTrue($update_result);

			$this -> database -> query("SELECT * FROM {$table} WHERE id = :id", $where);
			$row = $this -> database -> result();

			$this -> assertEquals('Updated Name', $row['name']);
		}

		public function testTransactionCommit(): void {
			$table = 'example_table';
			$data = ['name' => 'John Doe'];

			$this -> database -> createTable($table, 'id INT PRIMARY KEY, name VARCHAR(255)');

			$this -> database -> beginTransaction();

			$this -> database -> insert($table, $data);

			$this -> database -> query("SELECT * FROM {$table}");
			$this -> assertEmpty($this -> database -> result());

			$this -> database -> commitTransaction();

			$this -> database -> query("SELECT * FROM {$table}");
			$row = $this -> database -> result();
			$this -> assertEquals($data['name'], $row['name']);
		}

		public function testTransactionRollback(): void {
			$table = 'example_table';
			$data = ['name' => 'John Doe'];

			$this -> database -> createTable($table, 'id INT PRIMARY KEY, name VARCHAR(255)');

			$this -> database -> beginTransaction();

			$this -> database -> insert($table, $data);

			$this -> database -> query("SELECT * FROM {$table}");
			$this -> assertEmpty($this -> database -> result());

			$this -> database -> rollBackTransaction();

			$this -> database -> query("SELECT * FROM {$table}");
			$this -> assertEmpty($this -> database -> result());
		}
	}
