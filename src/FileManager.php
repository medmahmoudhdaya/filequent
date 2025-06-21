<?php

namespace Zidbih\Filequent;

class FileManager {
    protected string $collection;
    protected string $path;

    public function __construct(string $collection, ?string $basePath = null) {
        $this->collection = $collection;

        $basePath = $basePath ?? (__DIR__ . '/../data');
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $this->path = rtrim($basePath, '/') . '/' . $collection . '.json';

        if (!file_exists($this->path)) {
            file_put_contents($this->path, json_encode([]));
        }
    }

    public function read(): array {
        return json_decode(file_get_contents($this->path), true) ?? [];
    }

    public function write(array $data): void {
        file_put_contents($this->path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function insert(array $record): array {
        $data = $this->read();
        $record['id'] = $this->generateId($data);
        $data[] = $record;
        $this->write($data);
        return $record;
    }

    protected function generateId(array $data): int {
        $ids = array_column($data, 'id');
        return empty($ids) ? 1 : max($ids) + 1;
    }
}
