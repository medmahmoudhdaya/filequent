<?php

namespace Zidbih\Filequent;

/**
 * Base model for working with JSON file-based collections.
 * Supports basic CRUD and relationship operations.
 */
class Filequent implements \JsonSerializable
{
    /**
     * The collection name (filename without extension).
     * Must be defined in child class.
     */
    protected static string $collection = '';

    /**
     * Optional custom base path to store/read data files.
     */
    protected static ?string $basePath = null;

    /**
     * Holds model attributes.
     */
    protected array $attributes = [];

    /**
     * Filequent constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * Get a specific attribute value.
     */
    public function getAttribute(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Return all records as model instances.
     */
    public static function all(): array
    {
        static::ensureCollectionIsDefined();

        return array_map(
            fn($item) => new static($item),
            (new QueryBuilder(static::$collection, static::$basePath))->get()
        );
    }

    /**
     * Begin a where query.
     */
    public static function where(string $field, string $operator, $value): QueryBuilder
    {
        static::ensureCollectionIsDefined();

        return (new QueryBuilder(static::$collection, static::$basePath))
            ->where($field, $operator, $value)
            ->asModel(static::class);
    }

    /**
     * Insert a new record and return as model instance.
     */
    public static function create(array $data): static
    {
        static::ensureCollectionIsDefined();

        $record = (new FileManager(static::$collection, static::$basePath))->insert($data);
        return new static($record);
    }

    /**
     * Find a record by id.
     */
    public static function find(int $id): ?static
    {
        static::ensureCollectionIsDefined();

        $record = (new QueryBuilder(static::$collection, static::$basePath))
            ->where('id', '=', $id)
            ->first();

        return $record ? new static($record) : null;
    }

    /**
     * Update the current model instance with new data.
     */
    public function update(array $data): bool
    {
        static::ensureCollectionIsDefined();

        if (!isset($this->attributes['id'])) {
            throw new \Exception("Cannot update: missing 'id' in model.");
        }

        $manager = new FileManager(static::$collection, static::$basePath);
        $records = $manager->read();
        $updated = false;

        foreach ($records as &$record) {
            if ($record['id'] === $this->attributes['id']) {
                $data["updated_at"] = date("d-m-y H:i:s");
                $record = array_merge($record, $data);
                $this->attributes = $record;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $manager->write($records);
        }

        return $updated;
    }

    /**
     * Delete the current model instance.
     */
    public function delete(): bool
    {
        static::ensureCollectionIsDefined();

        if (!isset($this->attributes['id'])) {
            throw new \Exception("Cannot delete: missing 'id' in model.");
        }

        $manager = new FileManager(static::$collection, static::$basePath);
        $records = $manager->read();
        $originalCount = count($records);

        $records = array_filter($records, fn($r) => $r['id'] !== $this->attributes['id']);
        $manager->write(array_values($records));

        return count($records) < $originalCount;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Define a belongsTo relationship.
     */
    public function belongsTo(string $relatedClass, ?string $foreignKey = null): ?object
    {
        $foreignKey ??= $this->foreignKeyFromClass($relatedClass);

        if (!array_key_exists($foreignKey, $this->attributes)) {
            throw new \Exception("Foreign key '{$foreignKey}' not found in model [" . static::$collection . "]");
        }

        $foreignId = $this->attributes[$foreignKey];
        if (!$foreignId) {
            return null;
        }

        if (property_exists($relatedClass, 'basePath')) {
            $relatedClass::${'basePath'} = static::$basePath;
        }

        return $relatedClass::find($foreignId);
    }

    /**
     * Define a hasMany relationship.
     */
    public function hasMany(string $relatedClass, ?string $foreignKey = null): array
    {
        $foreignKey ??= $this->foreignKeyFromClass(static::class);

        if (!array_key_exists('id', $this->attributes)) {
            throw new \Exception("Missing 'id' in model [" . static::$collection . "] for hasMany relation.");
        }

        if (property_exists($relatedClass, 'basePath')) {
            $relatedClass::${'basePath'} = static::$basePath;
        }

        return $relatedClass::where($foreignKey, '=', $this->attributes['id'])->get();
    }

    /**
     * Define a hasOne relationship.
     */
    public function hasOne(string $relatedClass, ?string $foreignKey = null): ?object
    {
        $foreignKey ??= $this->foreignKeyFromClass(static::class);

        if (!array_key_exists('id', $this->attributes)) {
            throw new \Exception("Missing 'id' in model [" . static::$collection . "] for hasOne relation.");
        }

        if (property_exists($relatedClass, 'basePath')) {
            $relatedClass::${'basePath'} = static::$basePath;
        }

        return $relatedClass::where($foreignKey, '=', $this->attributes['id'])->first();
    }

    /**
     * Ensure that the collection is defined.
     */
    protected static function ensureCollectionIsDefined(): void
    {
        if (empty(static::$collection)) {
            throw new \RuntimeException("The static property \$collection must be defined in the model: " . static::class);
        }
    }

    /**
     * Generate default foreign key name from a class.
     */
    protected function foreignKeyFromClass(string $className): string
    {
        $parts = explode('\\', $className);
        $shortName = end($parts);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName)) . '_id';
    }
}
