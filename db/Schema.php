<?php

final class Schema
{
    public const DEFAULT_OPTIONS = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci';

    private PDO $pdo;
    private array $context;
    private array $resolvedContext = [];
    private array $catalog = [];
    private ?array $indexCatalog = null;

    public function __construct(PDO $pdo, array $context = [])
    {
        $this->pdo = $pdo;
        $this->context = $context;
        $this->loadCatalog();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function context(string $key)
    {
        if (!array_key_exists($key, $this->resolvedContext)) {
            $value = $this->context[$key] ?? null;
            $this->resolvedContext[$key] = $value instanceof Closure ? $value() : $value;
        }
        return $this->resolvedContext[$key];
    }

    public function tableExists(string $table): bool
    {
        return isset($this->catalog[strtolower($table)]);
    }

    public function hasColumn(string $table, string $column): bool
    {
        return isset($this->catalog[strtolower($table)][strtolower($column)]);
    }

    public function applyTables(string $directory, array $order = []): void
    {
        foreach ($this->orderedFiles($directory, $order) as $table => $file) {
            try {
                $this->apply($table, $this->loadDefinition($file));
            } catch (Throwable $e) {
                $this->logFailure("table:$table", $e);
            }
        }
    }

    public function apply(string $table, array $definition): void
    {
        if (!$this->tableExists($table)) {
            $this->create($table, $definition['create'], $definition['options'] ?? self::DEFAULT_OPTIONS);
            foreach ($definition['seedOnCreate'] ?? [] as $row) {
                $this->insert($table, $row, true);
            }
        } else {
            if (!empty($definition['ensureUtf8mb4'])) {
                ensureTableUtf8mb4($table);
            }
            foreach ($definition['columns'] ?? [] as $column) {
                $this->addColumn($table, $column[0], $column[1] ?? null, $column[2] ?? 'VARCHAR(500)');
            }
        }

        foreach ($definition['seed'] ?? [] as $row) {
            $this->insert($table, $row, true);
        }

        if (isset($definition['after'])) {
            ($definition['after'])($this->pdo, $this);
        }
    }

    public function create(string $table, string $body, string $options = self::DEFAULT_OPTIONS): void
    {
        assertSqlIdentifier($table);
        $this->pdo->exec(sprintf("CREATE TABLE `%s` (\n%s\n) %s", $table, $body, $options));
        $this->refreshTable($table);
    }

    public function addColumn(string $table, string $column, $default = null, string $type = 'VARCHAR(500)'): bool
    {
        if (!$this->tableExists($table) || $this->hasColumn($table, $column)) {
            return false;
        }
        assertSqlIdentifier($table);
        assertSqlIdentifier($column);
        $this->pdo->exec("ALTER TABLE `$table` ADD `$column` $type");
        $this->catalog[strtolower($table)][strtolower($column)] = true;

        if ($default !== null && $default !== '') {
            $statement = $this->pdo->prepare("UPDATE `$table` SET `$column` = ?");
            $statement->execute([$default]);
        }
        error_log("Schema: column {$column} added");
        return true;
    }

    public function dropColumn(string $table, string $column): bool
    {
        if (!$this->hasColumn($table, $column)) {
            return false;
        }
        assertSqlIdentifier($table);
        assertSqlIdentifier($column);
        $this->pdo->exec("ALTER TABLE `$table` DROP `$column`");
        unset($this->catalog[strtolower($table)][strtolower($column)]);
        return true;
    }

    public function insert(string $table, array $row, bool $ignore = false): void
    {
        assertSqlIdentifier($table);
        $columns = array_keys($row);
        foreach ($columns as $column) {
            assertSqlIdentifier($column);
        }
        $sql = sprintf(
            'INSERT %sINTO `%s` (`%s`) VALUES (%s)',
            $ignore ? 'IGNORE ' : '',
            $table,
            implode('`, `', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        $this->pdo->prepare($sql)->execute(array_values($row));
    }

    public function runMigrations(string $directory): void
    {
        $files = glob($directory . '/*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            $name = basename($file, '.php');
            try {
                $migration = $this->loadDefinition($file);
                if (is_callable($migration)) {
                    $migration($this->pdo, $this);
                }
            } catch (Throwable $e) {
                $this->logFailure("migration:$name", $e);
            }
        }
    }

    public function applyIndexes(array $indexes): void
    {
        foreach ($indexes as $index) {
            [$table, $name, $columns] = $index;
            $unique = ($index[3] ?? false) ? 'UNIQUE ' : '';
            if (!$this->tableExists($table) || $this->hasIndex($table, $name)) {
                continue;
            }
            try {
                assertSqlIdentifier($table);
                assertSqlIdentifier($name);
                $this->pdo->exec("ALTER TABLE `$table` ADD {$unique}INDEX `$name` ($columns)");
                $this->indexCatalog[strtolower($table)][strtolower($name)] = true;
            } catch (Throwable $e) {
                $this->logFailure("index:$table.$name", $e);
            }
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        if ($this->indexCatalog === null) {
            $this->indexCatalog = [];
            $rows = $this->pdo
                ->query('SELECT TABLE_NAME, INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE()')
                ->fetchAll(PDO::FETCH_NUM);
            foreach ($rows as [$indexTable, $indexName]) {
                $this->indexCatalog[strtolower($indexTable)][strtolower($indexName)] = true;
            }
        }
        return isset($this->indexCatalog[strtolower($table)][strtolower($name)]);
    }

    private function loadCatalog(): void
    {
        $rows = $this->pdo
            ->query('SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()')
            ->fetchAll(PDO::FETCH_NUM);
        foreach ($rows as [$table, $column]) {
            $this->catalog[strtolower($table)][strtolower($column)] = true;
        }
    }

    private function refreshTable(string $table): void
    {
        $statement = $this->pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $statement->execute([$table]);
        $this->catalog[strtolower($table)] = [];
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $column) {
            $this->catalog[strtolower($table)][strtolower($column)] = true;
        }
    }

    private function loadDefinition(string $file)
    {
        $schema = $this;
        return (static function () use ($file, $schema) {
            return require $file;
        })();
    }

    private function orderedFiles(string $directory, array $order): array
    {
        $files = [];
        foreach (glob($directory . '/*.php') ?: [] as $path) {
            $files[basename($path, '.php')] = $path;
        }
        $ordered = [];
        foreach ($order as $name) {
            if (isset($files[$name])) {
                $ordered[$name] = $files[$name];
                unset($files[$name]);
            }
        }
        ksort($files);
        return $ordered + $files;
    }

    private function logFailure(string $scope, Throwable $e): void
    {
        error_log("[db:$scope] " . $e->getMessage());
    }
}
