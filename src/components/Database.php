<?php
	namespace ClipStack\Component;

	use ClipStack\Component\Backbone\Singleton;
	use ClipStack\Component\Backbone\Config;

	use Closure;
	use Exception;
	use InvalidArgumentException;
	use PDO;
	use PDOException;
	use PDOStatement;
	use RuntimeException;

	class Database {
		use Singleton;

		/**
		 * @var PDO
		 */
		private PDO $pdo;

		/**
		 * @var PDOStatement|null
		 */
		private ?PDOStatement $statement = null;

		/**
		 * @var bool
		 */
		private bool $is_connected = false;

		/**
		 * @var string
		 */
		private string $prefix;

		/**
		 * @var Config
		 */
		private Config $config;

		/**
		 * @var Validate
		 */
		private Validate $validate;

		/**
		 * DATABASE CONSTRUCTOR.
		 *
		 * @param Config $config - THE CONFIGURATION INSTANCE.
		 * @param Validate $validate - THE VALIDATION INSTANCE.
		 */
		public function __construct(Config $config, Validate $validate) {
			$this -> config = $config;
			$this -> validate = $validate;

			$configurations = $this -> config -> get('database');

			if (is_array($configurations) && isset($configurations['prefix'])) {
				$this -> prefix = $configurations['prefix'];
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
		 * ENSURE DATABASE CONNECTION IS ESTABLISHED.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
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
		 * @param string $table_name - THE ORIGINAL TABLE NAME.
		 *
		 * @return string - THE TABLE NAME PREFIXED WITH THE PREDEFINED PREFIX.
		 */
		private function  getPrefixedTableName(string $table_name): string {
			return $this -> prefix . $table_name;
		}

		/**
		 * ESTABLISH A DATABASE CONNECTION.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws PDOException - IF THE CONNECTION TO THE DATABASE SERVER CANNOT BE ESTABLISHED.
		 * @throws RuntimeException - IF THE DATABASE CONFIGURATION IS NOT VALID.
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

			$dsn = "$driver:host=$host;port=$port;dbname=$db;charset=$charset";

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
					$this -> pdo -> exec("SET NAMES '$charset' COLLATE '$collation'");
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
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 */
		public function closeConnection(): void {
			$this -> pdo = new PDO('sqlite::memory:');
		}

		/**
		 * RUN A SQL QUERY AGAINST THE DATABASE.
		 *
		 * @param string $query - SQL QUERY TO BE EXECUTED.
		 * @param array<string, mixed> $params - PARAMETERS FOR PREPARED STATEMENT.
		 * @param array<string, int> $types - TYPES FOR THE PARAMETERS.
		 *
		 * @return PDOStatement|null - RETURNS THE RESULTING PDOStatement OBJECT ON SUCCESS, NULL ON FAILURE.
		 *
		 * @throws PDOException - IF THERE IS AN ERROR WITH THE SQL QUERY.
		 */
		public function query(string $query, array $params = [], array $types = []): ?PDOStatement {
			$this -> ensureConnected();

			$this -> statement = $this -> pdo -> prepare($query);

			if (!empty($types)) {
				foreach ($types as $param => $type) {
					$this -> statement -> bindParam($param, $params[$param], $type);
				}

				$execute_status = $this -> statement -> execute();
			} else{
				$execute_status = $this -> statement -> execute($params);
			}

			if ($execute_status) {
				return $this -> statement;
			}

			return null;
		}

		/**
		 * PERFORM A SQL SELECT OPERATION ON A SPECIFIED TABLE.
		 *
		 * @param string $table - THE NAME OF THE TABLE TO SELECT FROM.
		 * @param array<string, mixed> $conditions - AN ASSOCIATIVE ARRAY OF COLUMN-VALUE PAIRS USED IN SQL WHERE CLAUSE.
		 *
		 * @return false|array<array<string, mixed>> - AN ARRAY WITH THE RESULTING ROWS AS ASSOCIATIVE ARRAYS, FALSE IF THE QUERY FAILED.
		 */
		public function select(string $table, array $conditions = []): false|array {
			$where = implode(' AND ', array_map(static function ($k) {
				return "`$k` = :$k";
			}, array_keys($conditions)));

			$stmt = $this -> pdo -> prepare("SELECT * FROM $table WHERE $where");

			$stmt -> execute($conditions);

			return $stmt -> fetchAll(PDO::FETCH_ASSOC);
		}

		/**
		 * PERFORM A SQL DELETE OPERATION ON A SPECIFIED TABLE.
		 *
		 * @param string $table - THE NAME OF THE TABLE TO DELETE FROM.
		 * @param array<string, mixed> $conditions - AN ASSOCIATIVE ARRAY OF COLUMN-VALUE PAIRS USED IN SQL WHERE CLAUSE.
		 *
		 * @return PDOStatement - THE RESULTING PDO STATEMENT OBJECT AFTER EXECUTION.
		 *
		 * @throws PDOException - IF THERE IS AN ERROR WITH THE SQL QUERY.
		 */
		public function delete(string $table, array $conditions = []): PDOStatement
		{
			$where = $this -> buildWhereClause($conditions);

			return $this -> query("DELETE FROM $table WHERE $where");
		}

		/**
		 * INSERT NEW ROW INTO A SPECIFIED TABLE.
		 *
		 * @param string $table - THE NAME OF THE TABLE TO INSERT INTO.
		 * @param array<string, mixed> $data - AN ASSOCIATIVE ARRAY WHERE THE KEY IS THE COLUMN NAME AND THE VALUE IS THE DATA TO BE INSERTED.
		 *
		 * @return bool - TRUE IF THE INSERT QUERY WAS SUCCESSFUL, FALSE OTHERWISE.
		 *
		 * @throws InvalidArgumentException - IF THE DATA ARRAY IS EMPTY.
		 * @throws PDOException - IF THE SQL QUERY EXECUTION FAILS.
		 */
		public function insert(string $table, array $data): bool {
			if (empty($data)) {
				throw new InvalidArgumentException('Cannot insert an empty row into the table.');
			}

			$table = $this -> getPrefixedTableName($table);

			$fields = implode(', ', array_keys($data));
			$placeholders = ':' . implode(', :', array_keys($data));

			$sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";

			return $this -> query($sql, $data) !== null;
		}

		/**
		 * UPDATE SPECIFIC ROWS IN A SPECIFIED TABLE.
		 *
		 * @param string $table - THE NAME OF THE TABLE TO UPDATE.
		 * @param array<string, mixed> $data - AN ASSOCIATIVE ARRAY WHERE THE KEY IS THE COLUMN NAME AND THE VALUE IS THE NEW DATA FOR THAT COLUMN.
		 * @param array<string, mixed> $where - AN ASSOCIATIVE ARRAY DEFINING THE CONDITIONS FOR THE ROWS TO BE UPDATED.
		 *
		 * @return bool - TRUE IF THE UPDATE QUERY WAS SUCCESSFUL, FALSE OTHERWISE.
		 *
		 * @throws PDOException - IF THE SQL QUERY EXECUTION FAILS.
		 */
		public function update(string $table, array $data, array $where): bool {
			$table = $this -> getPrefixedTableName($table);

			$set_placeholders = array_map(static fn($key) => "$key = :set_$key", array_keys($data));
			$where_placeholders = array_map(static fn($key) => "$key = :where_$key", array_keys($where));

			$sql = "UPDATE $table SET " . implode(', ', $set_placeholders) . " WHERE " . implode(' AND ', $where_placeholders);

			$set_data = array_combine(array_map(static fn($key) => "set_$key", array_keys($data)), array_values($data));
			$where_data = array_combine(array_map(static fn($key) => "where_$key", array_keys($where)), array_values($where));

			$params = array_merge($set_data, $where_data);

			return $this -> query($sql, $params) !== null;
		}

		/**
		 * COUNTS THE NUMBER OF ROWS IN A SPECIFIED TABLE.
		 *
		 * @param string $table - THE NAME OF THE TABLE TO COUNT ROWS IN.
		 *
		 * @return int - NUMBER OF ROWS IN THE SPECIFIED TABLE.
		 *
		 * @throws PDOException - IF THERE IS AN ERROR WITH THE SQL QUERY OR EXECUTION
		 */
		public function count(string $table): int {
			$statement = $this -> query('SELECT COUNT(*) as count FROM ' . $this -> prefix . $table);

			if ($statement === null) {
				throw new PDOException('Query failed: cannot count the rows.');
			}

			/** @var array<string, mixed> $result */
			$result = $statement -> fetch(PDO::FETCH_ASSOC);

			return isset($result['count']) ? (int)$result['count'] : 0;
		}

		/**
		 * GET THE NUMBER OF ROWS AFFECTED BY THE LAST DELETE, INSERT, OR UPDATE STATEMENT.
		 *
		 * @return int - THE NUMBER OF ROWS AFFECTED BY THE LAST DELETE, INSERT, OR UPDATE STATEMENT.
		 */
		public function rowCount(): int {
			if ($this -> statement) {
				return $this -> statement -> rowCount();
			}

			return 0;
		}

		/**
		 * FETCH A SINGLE ROW FROM THE RESULT SET OF THE LAST EXECUTED STATEMENT.
		 *
		 * @return array<string, mixed>|null - ASSOCIATIVE ARRAY REPRESENTING THE FETCHED ROW OR NULL IF THERE ARE NO MORE ROWS.
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
		 * FETCH ALL ROWS FROM THE RESULT SET OF THE LAST EXECUTED STATEMENT.
		 *
		 * @return array<array<string, mixed>> - ARRAY OF ASSOCIATIVE ARRAYS REPRESENTING THE FETCHED ROWS, OR AN EMPTY ARRAY IF THERE ARE NO MORE ROWS.
		 */
		public function results(): array {
			return $this -> statement ? $this -> statement -> fetchAll(PDO::FETCH_ASSOC) : [];
		}

		/**
		 * CHECK IF A ROW EXISTS BASED ON A SPECIFIED SQL QUERY.
		 *
		 * @param string $query - THE SQL QUERY TO CHECK FOR EXISTENCE.
		 * @param array<string, mixed> $params - OPTIONAL PARAMETERS FOR THE SQL QUERY.
		 *
		 * @return bool - TRUE IF AT LEAST ONE ROW EXISTS THAT MATCHES THE CONDITIONS OF THE QUERY, FALSE OTHERWISE.
		 *
		 * @throws PDOException - IF THERE IS AN ERROR WITH THE SQL QUERY OR EXECUTION.
		 */
		public function exists(string $query, array $params = []): bool {
			$this -> query($query, $params);

			return $this -> rowCount() > 0;
		}

		/**
		 * CREATE A SQL WHERE CLAUSE.
		 *
		 * @param array<string, mixed> $conditions - AN ASSOCIATIVE ARRAY OF COLUMN => VALUE CONDITIONS.
		 *
		 * @return string - A SQL WHERE CLAUSE.
		 */
		private function buildWhereClause(array $conditions): string {
			return implode(' AND ', array_map(static function ($field, $value) {
				if (is_numeric($value) || is_string($value)) {
					return "`$field` = '{$value}'";
				}

				throw new InvalidArgumentException("The value for '$field' cannot be cast to string");
			}, array_keys($conditions), $conditions));
		}

		/**
		 * CREATE A NEW TABLE IN THE DATABASE.
		 *
		 * @param string $table_name - THE NAME OF THE NEW TABLE.
		 * @param string $fields - THE SQL STRING DEFINED FOR THE COLUMNS OF THIS TABLE.
		 *
		 * @return bool - TRUE IF THE TABLE WAS SUCCESSFULLY CREATED, FALSE OTHERWISE.
		 *
		 * @throws PDOException - IF THERE IS AN ERROR WITH THE SQL QUERY OR EXECUTION.
		 */
		public function createTable(string $table_name, string $fields): bool {
			$this -> ensureConnected();

			if (!$this -> validate -> isValidSqlName($this -> getPrefixedTableName($table_name))) {
				return false;
			}

			if (!$this -> validate -> isValidSqlFieldDefinitions($fields)) {
				return false;
			}

			$query = "CREATE TABLE IF NOT EXISTS {$this -> getPrefixedTableName($table_name)} ($fields)";

			return (bool) $this -> pdo -> exec($query);
		}


		/**
		 * MODIFY THE STRUCTURE OF AN EXISTING TABLE IN THE DATABASE.
		 *
		 * @param string $table_name - THE NAME OF THE TABLE TO ALTER.
		 * @param string $alterations - THE SQL ALTER TABLE STATEMENT STRING.
		 *
		 * @return bool - TRUE IF THE TABLE WAS SUCCESSFULLY ALTERED, FALSE OTHERWISE.
		 *
		 * @throws PDOException - IF THERE IS AN ERROR WITH THE SQL QUERY OR EXECUTION.
		 */
		public function alterTable(string $table_name, string $alterations): bool {
			$this -> ensureConnected();

			if (!$this -> validate -> isValidSqlName($this -> getPrefixedTableName($table_name))) {
				return false;
			}

			if (!$this -> validate -> isValidSqlFieldDefinitions($alterations)) {
				return false;
			}

			$query = "ALTER TABLE {$this -> getPrefixedTableName($table_name)} $alterations";

			return (bool) $this -> pdo -> exec($query);
		}

		/**
		 * EXECUTE A DATABASE TRANSACTION.
		 *
		 * @param Closure $callback - THE CALLBACK FUNCTION TO PERFORM DATABASE OPERATIONS.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws Exception - IF ANY EXCEPTION IS THROWN INSIDE THE TRANSACTION.
		 */
		public function transaction(Closure $callback): void {
			try {
				$this -> pdo -> beginTransaction();

				$callback($this);

				$this -> pdo -> commit();
			} catch (Exception $e) {
				$this -> pdo -> rollBack();
				throw $e;
			}
		}

		/**
		 * BEGIN A DATABASE TRANSACTION.
		 *
		 * @return bool - TRUE ON SUCCESS, FALSE OTHERWISE. ALSO RETURNS FALSE IF THE DRIVER DOES NOT SUPPORT TRANSACTIONS.
		 *
		 * @throws PDOException - IF THERE IS ALREADY AN ACTIVE TRANSACTION, OR THE DRIVER ENCOUNTERS AN ERROR.
		 */
		public function beginTransaction(): bool {
			return $this -> pdo -> beginTransaction();
		}

		/**
		 * COMMIT THE CURRENT DATABASE TRANSACTION.
		 *
		 * @return bool - TRUE ON SUCCESS, FALSE ON FAILURE. ALSO RETURNS FALSE IF THE DRIVER DOES NOT SUPPORT TRANSACTIONS OR THERE IS NO ACTIVE TRANSACTION.
		 *
		 * @throws PDOException - IF THERE IS NO ACTIVE TRANSACTION, OR THE DRIVER ENCOUNTERS AN ERROR.
		 */
		public function commitTransaction(): bool {
			return $this -> pdo -> commit();
		}

		/**
		 * ROLLBACK THE CURRENT DATABASE TRANSACTION.
		 *
		 * @return bool - TRUE ON SUCCESS, FALSE ON FAILURE. ALSO RETURNS FALSE IF THE DRIVER DOES NOT SUPPORT TRANSACTIONS OR THERE IS NO ACTIVE TRANSACTION.
		 *
		 * @throws PDOException - IF THERE IS NO ACTIVE TRANSACTION, OR THE DRIVER ENCOUNTERS AN ERROR.
		 */
		public function rollBackTransaction(): bool {
			return $this -> pdo -> rollBack();
		}

		/**
		 * CHECK IF CURRENTLY INSIDE A TRANSACTION.
		 *
		 * @return bool - TRUE IF A TRANSACTION IS CURRENTLY ACTIVE, FALSE OTHERWISE.
		 */
		public function inTransaction(): bool {
			return $this -> pdo -> inTransaction();
		}
	}
