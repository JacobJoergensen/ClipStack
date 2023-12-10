<?php
	namespace ClipStack\Component;

	use PDO;
	use PDOException;
	use PDOStatement;

	use ClipStack\Component\Backbone\Singleton;
	use ClipStack\Component\Backbone\Config;
	use RuntimeException;

	class Database {
		use Singleton;

		/**
		 * @var Database|null
		 */
		private static ?Database $instance = null;

		private PDO $pdo;
		private ?PDOStatement $statement = null;

		private bool $is_connected = false;
		private string $prefix = '';

		private Config $config;
		private Validate $validate;

		/**
		 * DATABASE CONSTRUCTOR.
		 *
		 * @param Config $config - THE CONFIGURATION INSTANCE.
		 */
		public function __construct(Config $config, Validate $validate) {
			$this -> config = $config;
			$this -> validate = $validate;

			$database_config = $this -> config -> get('database');

			if (is_array($database_config) && isset($database_config['prefix'])) {
				$this -> prefix = $database_config['prefix'];
			} else {
				$this -> prefix = '';
			}

			$this -> connect();
		}

		/**
		 * DATABASE DESTRUCTOR.
		 */
		public function __destruct() {
			$this -> closeConnection();
		}

		/**
		 * RETRIEVE THE SINGLETON INSTANCE OF THE DATABASE CLASS.
		 *
		 * @param Config $config - THE CONFIGURATION INSTANCE.
		 * @param Validate $validate - THE VALIDATION INSTANCE.
		 *
		 * @return Database - THE SINGLETON INSTANCE OF THE DATABASE CLASS.
		 *
		 * @example
		 * $config = new Config();
		 * $validate = new Validate();
		 * $db = Database::getInstance($config, $validate);
		 */
		public static function getInstance(Config $config, Validate $validate): Database {
			if (self::$instance === null) {
				self::$instance = new self($config, $validate);
			}

			return self::$instance;
		}

		/**
		 * ENSURE DATABASE CONNECTION IS ESTABLISHED.
		 *
		 * @return void
		 */
		private function ensureConnected(): void {
			if (!$this -> is_connected) {
				$this -> connect();
				$this -> is_connected = true;
			}
		}

		/**
		 * GET THE PREFIXED TABLE NAME.
		 *
		 * @param string $table_name
		 *
		 * @return string
		 */
		private function prefixedTableName(string $table_name): string {
			return $this -> prefix . $table_name;
		}

		/**
		 * ESTABLISH A DATABASE CONNECTION.
		 *
		 * @return void
		 */
		private function connect(): void {
			$configurations = $this -> config -> get('database');

			if (!is_array($configurations)) {
				throw new RuntimeException('Database configuration is not valid.');
			}

			$driver = $configurations['driver'] ?? '';
			$host = $configurations['host'] ?? '';
			$port = $configurations['port'] ?? '';
			$db = $configurations['name'] ?? '';
			$user = $configurations['username'] ?? '';
			$pass = $configurations['password'] ?? '';
			$charset = $configurations['charset'] ?? 'utf8mb4';
			$collation = $configurations['collation'] ?? 'utf8mb4_unicode_ci';

			if (empty($driver) || empty($host) || empty($port) || empty($db) || empty($user) || empty($pass)) {
				throw new RuntimeException('Invalid database configuration.');
			}

			$dsn = "{$driver}:host={$host};port={$port};dbname={$db};charset={$charset}";

			$options = [
				PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES   => false,
				PDO::ATTR_PERSISTENT         => true
			];

			$attempt = 0;
			$max_attempts = 3;

			while ($attempt < $max_attempts) {
				try {
					$this -> pdo = new PDO($dsn, $user, $pass, $options);
					$this -> pdo -> exec("SET NAMES '{$charset}' COLLATE '{$collation}'");
					break;
				} catch (PDOException $exception) {
					if ($attempt === $max_attempts - 1) {
						throw new PDOException($exception -> getMessage(), (int)$exception -> getCode());
					}

					$attempt++;
				}
			}
		}

		/**
		 * CLOSE THE DATABASE CONNECTION.
		 *
		 * @return void
		 */
		public function closeConnection(): void {
			$this -> pdo = new PDO('sqlite::memory:');
		}

		/**
		 * RUN A QUERY.
		 *
		 * @param string $query
		 * @param array<string, mixed> $params - Associative array of query parameters.
		 * @param array<string, int> $types - Associative array of parameter types.
		 *
		 * @return bool
		 */
		public function query(string $query, array $params = [], array $types = []): bool {
			$this -> ensureConnected();
			$this -> statement = $this -> pdo -> prepare($query);

			foreach ($types as $param => $type) {
				$this -> statement -> bindParam($param, $params[$param], $type);
			}

			return $this -> statement -> execute($params);
		}


		/**
		 * CREATE A NEW TABLE.
		 *
		 * @param string $table_name
		 * @param string $fields
		 *
		 * @return bool
		 */
		public function createTable(string $table_name, string $fields): bool {
			$this -> ensureConnected();

			if (!$this -> validate -> isValidSqlName($this -> prefixedTableName($table_name))) {
				return false;
			}

			if (!$this -> validate -> isValidSqlFieldDefinitions($fields)) {
				return false;
			}

			$query = "CREATE TABLE IF NOT EXISTS {$this -> prefixedTableName($table_name)} ({$fields})";
			return (bool) $this -> pdo -> exec($query);
		}


		/**
		 * ALTER AN EXISTING TABLE.
		 *
		 * @param string $table_name
		 * @param string $alterations
		 *
		 * @return bool
		 */
		public function alterTable(string $table_name, string $alterations): bool {
			$this -> ensureConnected();

			if (!$this -> validate -> isValidSqlName($this -> prefixedTableName($table_name))) {
				return false;
			}

			if (!$this -> validate -> isValidSqlFieldDefinitions($alterations)) {
				return false;
			}

			$query = "ALTER TABLE {$this -> prefixedTableName($table_name)} {$alterations}";
			return (bool) $this -> pdo -> exec($query);
		}

		/**
		 * CHECK IF A ROW EXISTS BASED ON A QUERY.
		 *
		 * @param string $query
		 * @param array<string, mixed> $params
		 *
		 * @return bool
		 */
		public function exists(string $query, array $params = []): bool {
			$this -> query($query, $params);
			return $this -> rowCount() > 0;
		}

		/**
		 * INSERT A NEW ROW INTO A TABLE.
		 *
		 * @param string $table
		 * @param array<string, mixed> $data
		 *
		 * @return bool
		 */
		public function insert(string $table, array $data): bool {
			$table = $this -> prefixedTableName($table);

			$fields = implode(', ', array_keys($data));
			$placeholders = ':' . implode(', :', array_keys($data));

			$sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";

			return $this -> query($sql, $data) !== null;
		}

		/**
		 * UPDATE A ROW IN A TABLE.
		 *
		 * @param string $table
		 * @param array<string, mixed> $data
		 * @param array<string, mixed> $where
		 *
		 * @return bool
		 */
		public function update(string $table, array $data, array $where): bool {
			$table = $this -> prefixedTableName($table);

			$data_placeholders = array_map(static fn($key) => "{$key} = :data_{$key}", array_keys($data));
			$where_placeholders = array_map(static fn($key) => "{$key} = :where_{$key}", array_keys($where));

			$sql = "UPDATE {$table} SET " . implode(', ', $data_placeholders) . " WHERE " . implode(' AND ', $where_placeholders);

			$data_params = array_combine(array_map(static fn($k) => "data_{$k}", array_keys($data)), $data);
			$where_params = array_combine(array_map(static fn($k) => "where_{$k}", array_keys($where)), $where);

			$params = array_merge($data_params, $where_params);

			return $this -> query($sql, $params) !== null;
		}

		/**
		 * FETCH A SINGLE ROW.
		 *
		 * @return array<string, mixed>|null - ASSOCIATIVE ARRAY REPRESENTING THE FETCHED ROW.
		 */
		public function result(): ?array {
			if ($this -> statement) {
				$result = $this -> statement -> fetch(PDO::FETCH_ASSOC);

				if (is_array($result)) {
					return $result;
				}
			}

			return null;
		}

		/**
		 * FETCH ALL ROWS.
		 *
		 * @param string[] $columns - OPTIONAL: AN ARRAY OF COLUMN NAMES TO FETCH. DEFAULT IS ['*'].
		 *
		 * @return array<array<string, mixed>> - ARRAY OF ASSOCIATIVE ARRAYS REPRESENTING THE FETCHED ROWS.
		 */
		public function results(array $columns = ['*']): array {
			if ($this -> statement) {
				return $this -> statement -> fetchAll(PDO::FETCH_ASSOC);
			}

			return [];
		}

		/**
		 * GET ROW COUNT.
		 *
		 * @return int
		 */
		public function rowCount(): int {
			if ($this -> statement) {
				return $this -> statement -> rowCount();
			}

			return 0;
		}


		/**
		 * BEGIN A DATABASE TRANSACTION.
		 *
		 * @return bool
		 */
		public function beginTransaction(): bool {
			return $this -> pdo -> beginTransaction();
		}

		/**
		 * COMMIT A DATABASE TRANSACTION.
		 *
		 * @return bool
		 */
		public function commitTransaction(): bool {
			return $this -> pdo -> commit();
		}

		/**
		 * ROLLBACK A DATABASE TRANSACTION.
		 *
		 * @return bool
		 */
		public function rollBackTransaction(): bool {
			return $this -> pdo -> rollBack();
		}

		/**
		 * CHECK IF CURRENTLY INSIDE A TRANSACTION.
		 *
		 * @return bool
		 */
		public function inTransaction(): bool {
			return $this->pdo->inTransaction();
		}
	}
