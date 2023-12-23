<?php
	namespace ClipStack\Component;

	use RuntimeException;

	abstract class ORM {
		/**
		 * @var Database
		 */
		protected Database $database;

		/**
		 * @var string
		 */
		protected string $table;

		/**
		 * @var string
		 */
		protected string $primary_key;

		/**
		 * @var array<string, mixed>
		 */
		protected array $schema;

		/**
		 * ORM CONSTRUCTOR.
		 *
		 * @param Database $database - THE DATABASE INSTANCE.
		 * @param string $table - THE DATABASE TABLE NAME.
		 * @param string $primary_key - THE PRIMARY KEY FIELD OF THE TABLE.
		 *
		 * @throws RuntimeException - WHEN UNABLE TO FETCH SCHEMA FOR THE TABLE.
		 */
		public function __construct(Database $database, string $table, string $primary_key) {
			$this -> database = $database;
			$this -> table = $table;
			$this -> primary_key = $primary_key;
			$this->schema = $database -> getSchema($table);

			if (!$this -> schema) {
				throw new RuntimeException("Unable to fetch schema for table $table");
			}
		}

		/**
		 * VALIDATE THE PROVIDED DATA AGAINST THE TABLE'S SCHEMA.
		 *
		 * @param array $data - THE DATA FOR VALIDATION.
		 *
		 * @return void - THIS METHOD DOES NOT RETURN A VALUE.
		 *
		 * @throws RuntimeException - WHEN THE DATA CONTAINS INVALID FIELD NAMES OR DATA TYPES.
		 */
		private function validate(array $data): void {
			foreach ($data as $key => $value) {
				if (!isset($this -> schema[$key])) {
					throw new RuntimeException("Invalid field name $key");
				}

				if (gettype($value) !== $this -> schema[$key]) {
					throw new RuntimeException("Invalid datatype for field $key");
				}
			}

		}

		/**
		 * FINDS THE RECORD WITH THE GIVEN VALUE FOR THE PRIMARY KEY.
		 *
		 * @param mixed $value - THE VALUE OF THE PRIMARY KEY TO SEARCH.
		 *
		 * @return array<string, mixed>|null - THE CORRESPONDING RECORD OR NULL IF NOT FOUND.
		 */
		public function find(mixed $value): ?array {
			$conditions = [$this -> primary_key => $value];
			$result = $this -> database -> select($this -> table, $conditions);

			return $result ? $result[0] : null;
		}

		/**
		 * FINDS ALL RECORDS THAT MATCH THE SPECIFIED ATTRIBUTES.
		 *
		 * @param array<string, mixed> $attributes - THE ATTRIBUTES TO MATCH.
		 * @param callable|null $transform_result - A CALLABLE TRANSFORM FUNCTION TO APPLY TO THE RESULTS.
		 *
		 * @return array<array<string, mixed>>|null - THE FOUND RECORDS OR NULL IF NONE ARE FOUND.
		 */
		public function findAllByAttributes(array $attributes, callable $transform_result = null): ?array {
			$results = $this -> database -> select($this -> table, $attributes);

			if($transform_result !== null && is_callable($transform_result)){
				return array_map($transform_result, $results);
			}

			return $results;
		}

		/**
		 * RETRIEVES THE RELATED RECORDS FOR A GIVEN RECORD IN A SPECIFIED FOREIGN TABLE.
		 *
		 * @param array<string, mixed> $record - THE RECORD WHICH RELATED DATA WE WANT TO FIND.
		 * @param string $related_table - THE FOREIGN TABLE TO FIND THE RELATED DATA IN.
		 * @param string $foreign_key - THE FOREIGN KEY FIELD IN THE RELATED TABLE.
		 *
		 * @return array<array<string, mixed>>|null - THE RELATED RECORDS OR NULL IF NONE ARE FOUND.
		 *
		 * @throws RuntimeException - WHEN THE RECORD OR THE FOREIGN RECORD IS NOT FOUND.
		 */
		public function findForeign(array $record, string $related_table, string $foreign_key): ?array {
			$object_data = $this -> find($record[$this -> primary_key]);

			if (!$object_data) {
				throw new RuntimeException("Object not found");
			}

			$keys = (array) $object_data[$foreign_key];
			$results = [];

			foreach($keys as $key) {
				$conditions = [$foreign_key => $key];
				$result = $this -> database -> select($related_table, $conditions);

				if(!$result) {
					throw new RuntimeException("Foreign object not found");
				}

				$results[] = $result[0];
			}

			return $results;
		}

		/**
		 * INSERTS A NEW RECORD.
		 *
		 * @param array<string, mixed> $record - THE RECORD TO INSERT.
		 *
		 * @return bool - WHETHER THE OPERATION WAS SUCCESSFUL.
		 */
		public function insert(array $record): bool {
			$this -> validate($record);

			return $this -> database -> insert($this -> table, $record);
		}

		/**
		 * UPDATES AN EXISTING RECORD.
		 *
		 * @param array<string, mixed> $record - THE NEW RECORD DATA.
		 *
		 * @return bool - WHETHER THE OPERATION WAS SUCCESSFUL.
		 */
		public function update(array $record): bool {
			$this -> validate($record);
			$conditions = [$this -> primary_key => $record[$this -> primary_key]];

			return $this -> database -> update($this -> table, $record, $conditions);
		}

		/**
		 * DELETES THE RECORD WITH THE GIVEN PRIMARY KEY.
		 *
		 * @param mixed $value - THE VALUE OF THE PRIMARY KEY FOR THE RECORD TO DELETE.
		 *
		 * @return bool - WHETHER THE OPERATION WAS SUCCESSFUL.
		 */
		public function delete(mixed $value): bool {
			$conditions = [$this -> primary_key => $value];

			return $this -> database -> delete($this -> table, $conditions) -> rowCount() > 0;
		}
	}
