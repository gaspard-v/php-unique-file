<?php
require_once 'vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;


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
        $this->php_files_path = glob($this->php_source_path . '/*.php', GLOB_BRACE);
        switch ($this->php_files_path) {
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

class RemoveIncludeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\Include_) {
            return new PhpParser\Node\Expr\ConstFetch(new PhpParser\Node\Name('null'));
        }
    }
}

class PhpBuilder
{
    private $parser;
    private $traverser;
    private $removeIncludeVisitor;
    private $printer;
    private array $php_source_path;
    private string $build_file;
    public function __construct(array $php_source_path, string $build_file)
    {
        $this->php_source_path = $php_source_path;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();
        $this->removeIncludeVisitor = new RemoveIncludeVisitor();
        $this->traverser->addVisitor($this->removeIncludeVisitor);
        $this->printer = new PrettyPrinter\Standard();
        $this->build_file = $build_file;

        file_put_contents($this->build_file, "<?php\n");
    }
    private function buildOneFile(string $php_file): void
    {
        $code = file_get_contents($php_file);
        $stmts = $this->parser->parse($code);
        $stmts = $this->traverser->traverse($stmts);
        $new_code = $this->printer->prettyPrintFile($stmts);
        $new_code = ltrim($new_code, "<?php");
        file_put_contents($this->build_file, "{$new_code}\n", FILE_APPEND);
    }
    public function build()
    {
        foreach ($this->php_source_path as $php_source) {
            $php_files = new PhpFiles($php_source);
            $php_files->findAll()->forEach(fn ($php_file) => $this->buildOneFile($php_file));
        }
    }
}

$builder = new PhpBuilder(["tests"], "build.php");
$builder->build();
