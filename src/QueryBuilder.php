<?php

namespace Zidbih\Filequent;

class QueryBuilder
{
    protected string $collection;
    protected array $conditions = [];
    protected ?string $modelClass = null;
    protected ?string $basePath = null;

    public function __construct(string $collection, ?string $basePath = null)
    {
        $this->collection = $collection;
        $this->basePath = $basePath;
    }

    /**
     * Specify the model class to wrap records in.
     */
    public function asModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    /**
     * Add a where condition.
     *
     * Supported operators: =, !=, >, <, LIKE
     */
    public function where(string $field, string $operator, $value): self
    {
        $this->conditions[] = compact('field', 'operator', 'value');
        return $this;
    }

    /**
     * Get all records matching the conditions.
     *
     * @return array
     */
    public function get(): array
    {
        $fileManager = new FileManager($this->collection, $this->basePath);
        $data = $fileManager->read();
        $results = $this->applyConditions($data);

        if ($this->modelClass) {
            return array_map(fn($item) => new $this->modelClass($item), $results);
        }

        return $results;
    }

    /**
     * Get the first record matching the conditions or null.
     *
     * @return mixed
     */
    public function first(): mixed
    {
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Apply all where conditions to filter the data.
     *
     * @param array $items
     * @return array
     */
    protected function applyConditions(array $items): array
    {
        foreach ($this->conditions as $condition) {
            $items = array_filter($items, function ($item) use ($condition) {
                $value = $item[$condition['field']] ?? null;
                $search = $condition['value'];

                switch ($condition['operator']) {
                    case '=':
                        return $value == $search;
                    case '!=':
                        return $value != $search;
                    case '>':
                        return $value > $search;
                    case '<':
                        return $value < $search;
                    case 'LIKE':
                        $pattern = '/' . str_replace('%', '.*', preg_quote($search, '/')) . '/i';
                        return is_string($value) && preg_match($pattern, $value);
                    default:
                        return false;
                }
            });
        }

        return array_values($items);
    }
}
