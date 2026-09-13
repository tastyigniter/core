<?php

declare(strict_types=1);

namespace Igniter\Flame\Assetic\Filter;

use Igniter\Flame\Assetic\Asset\AssetInterface;
use Igniter\Flame\Assetic\Factory\AssetFactory;
use Igniter\Flame\Assetic\Util\CssUtils;
use League\Uri\Contracts\UriInterface;
use Override;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Importer\FilesystemImporter;
use ScssPhp\ScssPhp\OutputStyle;
use ScssPhp\ScssPhp\Util\Path;
use ScssPhp\ScssPhp\ValueConverter;

/**
 * Loads SCSS files using the PHP implementation of scss, scssphp.
 *
 * Scss files are mostly compatible, but there are slight differences.
 *
 * @link https://scssphp.github.io/scssphp/
 *
 * @author Bart van den Burg <bart@samson-it.nl>
 */
class ScssphpFilter implements DependencyExtractorInterface
{
    private array $importPaths = [];

    private array $customFunctions = [];

    private ?string $formatter = null;

    private array $variables = [];

    public function setFormatter(string $formatter): void
    {
        $legacyFormatters = [
            'scss_formatter' => 'expanded',
            'scss_formatter_nested' => 'expanded',
            'scss_formatter_compressed' => 'compressed',
            'scss_formatter_crunched' => 'compressed',
        ];

        $this->formatter = $legacyFormatters[$formatter] ?? $formatter;
    }

    public function setVariables(array $variables): void
    {
        $this->variables = $variables;
    }

    public function addVariable($variable, $value = null): void
    {
        $this->variables[$variable] = $value;
    }

    public function setImportPaths(array $paths): void
    {
        $this->importPaths = $paths;
    }

    public function addImportPath($path): void
    {
        $this->importPaths[] = $path;
    }

    public function registerFunction($name, $callable, array $argumentDeclaration = ['args...']): void
    {
        $this->customFunctions[$name] = [$callable, $argumentDeclaration];
    }

    #[Override]
    public function filterLoad(AssetInterface $asset): void
    {
        $sc = new Compiler;

        if ($dir = $asset->getSourceDirectory()) {
            $sc->addImportPath($dir);
        }

        foreach ($this->importPaths as $path) {
            $sc->addImportPath($path);
        }

        foreach ($this->customFunctions as $name => [$callable, $argumentDeclaration]) {
            $sc->registerFunction($name, $callable, $argumentDeclaration);
        }

        if ($this->formatter) {
            $sc->setOutputStyle(OutputStyle::fromString($this->formatter));
        }

        if (!empty($this->variables)) {
            $sc->replaceVariables($this->convertVariables($this->variables));
        }

        $asset->setContent($sc->compileString($asset->getContent())->getCss());
    }

    #[Override]
    public function filterDump(AssetInterface $asset) {}

    #[Override]
    public function getChildren(AssetFactory $factory, $content, $loadPath = null): array
    {
        $children = [];
        foreach (CssUtils::extractImports($content) as $match) {
            $file = $this->resolveImport($match, $loadPath);
            if ($file) {
                $children[] = $child = $factory->createAsset($file, [], ['root' => $loadPath]);
                $child->load();
                $children = array_merge($children, $this->getChildren($factory, $child->getContent(), $loadPath));
            }
        }

        return $children;
    }

    private function convertVariables(array $variables): array
    {
        $converted = [];
        foreach ($variables as $name => $value) {
            $converted[$name] = is_string($value)
                ? ValueConverter::parseValue($value)
                : ValueConverter::fromPhp($value);
        }

        return $converted;
    }

    private function resolveImport(string $url, ?string $loadPath): ?string
    {
        if (Compiler::isCssImport($url)) {
            return null;
        }

        $paths = array_filter(
            $loadPath !== null ? [$loadPath, ...$this->importPaths] : $this->importPaths,
            is_string(...),
        );

        foreach ($paths as $path) {
            $canonicalUrl = (new FilesystemImporter($path))->canonicalize(Path::toUri(Path::join($path, $url)));
            if ($canonicalUrl instanceof UriInterface) {
                return Path::fromUri($canonicalUrl);
            }
        }

        return null;
    }
}
