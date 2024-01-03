<?php
require_once 'vendor/autoload.php';

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class PhpFiles
{
    private string $php_source_path;
    private array $php_files_path = [];
    public function __construct(string $php_source_path)
    {
        $this->php_source_path = $php_source_path;
    }
    public function findAll(): PhpFiles
    {
        $this->php_files_path = glob($this->php_files_path . '/**/*.php', GLOB_BRACE);
        switch ($this->php_source_path) {
            case false:
                throw new Exception('Finding PHP files failed');
            case []:
                trigger_error("No PHP files found", E_USER_WARNING);
        }
        return $this;
    }
    public function forEach(callable $callback)
    {
        foreach ($this->php_files_path as &$value) {
            $callback($value);
        }
    }
}

class IncludeVisitor extends NodeVisitorAbstract
{
    public array $includes = [];
    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Expr\Include_
        ) {
            $nextNode = $node->getAttribute("next");
            if ($nextNode instanceof String_)
                $this->includes[] = $nextNode;
        }
    }
}

class PhpBuilder
{
    private $parser;
    private $traverser;
    private $visitor;
    private array $php_source_path;
    public function __construct(array $php_source_path)
    {
        $this->php_source_path = $php_source_path;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new \PhpParser\NodeTraverser();
        $this->visitor = new IncludeVisitor();
        $this->traverser->addVisitor($this->visitor);
    }
    private function buildOneFile($php_file)
    {
        $code = file_get_contents($php_file);
        $stmts = $this->parser->parse($code);
        $stmts = $this->traverser->traverse($stmts);
        var_dump($this->visitor->includes);
    }
    public function build()
    {
        foreach ($this->php_source_path as $php_source) {
            $php_files = new PhpFiles($php_source);
            $php_files->findAll()->forEach(fn ($php_file) => $this->buildOneFile($php_file));
        }
    }
}
