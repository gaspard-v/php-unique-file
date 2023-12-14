<?php
class PhpFiles {
    private string $php_source_path;
    private array $php_files_path = [];
    public function __construct(string $php_source_path) {
        $this->php_source_path = $php_source_path;
    }
    public function findAll(): PhpFiles {
        $this->php_files_path = glob($this->php_files_path . '/**/*.php', GLOB_BRACE);
        switch ($this->php_source_path) {
            case false:
                throw new Exception('Finding PHP files failed');
            case []:
                trigger_error("No PHP files found", E_USER_WARNING);
        }
        return $this;
    }
    public function forEach(callable $callback) {
        foreach ($this->php_files_path as &$value) {
            $callback($value);
        }
    }
}