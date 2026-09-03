<?php

namespace ModxPro\PdoTools\Parsing\Fenom\Providers;

use PDO;
use Fenom\ProviderInterface;
use MODX\Revolution\modX;
use ModxPro\PdoTools\CoreTools;

/**
 * Shared Chunk/Template Fenom provider: load MODX elements by id or name.
 */
abstract class ElementProvider implements ProviderInterface
{
    /** @var modX */
    public $modx;
    /** @var CoreTools */
    public $pdoTools;

    public function __construct(modX $modx, CoreTools $pdoTools)
    {
        $this->modx = $modx;
        $this->pdoTools = $pdoTools;
    }

    abstract protected function elementClass(): string;

    abstract protected function nameField(): string;

    abstract protected function listColumn(): string;

    public function templateExists(string $tpl): bool
    {
        return (bool)$this->modx->getCount($this->elementClass(), $this->lookupCriteria($tpl));
    }

    public function getSource(string $tpl, float &$time): string
    {
        $content = '';
        $name = $tpl;
        $propertySet = '';
        if ($pos = strpos($tpl, '@')) {
            $propertySet = substr($tpl, $pos + 1);
            $name = substr($tpl, 0, $pos);
        }

        $element = $this->modx->getObject($this->elementClass(), $this->objectCriteria($name));
        if ($element) {
            $content = (string)$element->getContent();
            $properties = [];
            if ($propertySet !== '') {
                if ($tmp = $element->getPropertySet($propertySet)) {
                    $properties = $tmp;
                }
            } else {
                $properties = $element->getProperties();
            }
            if ($content !== '' && !empty($properties)) {
                $useFenom = $this->pdoTools->config('useFenom');
                $this->pdoTools->config(['useFenom' => false]);
                $content = $this->pdoTools->parseChunk('@INLINE ' . $content, $properties);
                $this->pdoTools->config(['useFenom' => $useFenom]);
            }
        }

        // Fenom AUTO_RELOAD compares this to getLastModified(); must be stable.
        $time = $this->getLastModified($name);

        return $content;
    }

    public function getLastModified(string $tpl): float
    {
        if ($pos = strpos($tpl, '@')) {
            $tpl = substr($tpl, 0, $pos);
        }

        $element = $this->modx->getObject($this->elementClass(), $this->objectCriteria($tpl));
        if (!$element) {
            return 0.0;
        }

        if ($element->isStatic() && ($file = $element->getSourceFile())) {
            $mtime = @filemtime($file);

            return $mtime !== false ? (float)$mtime : 0.0;
        }

        if (method_exists($element, 'get')) {
            $stamp = $element->get('editedon') ?: $element->get('createdon');
            if ($stamp !== null && $stamp !== '' && $stamp !== '0' && $stamp !== 0) {
                return is_numeric($stamp) ? (float)$stamp : (float)strtotime((string)$stamp);
            }
        }

        return 0.0;
    }

    public function verify(array $templates): bool
    {
        return true;
    }

    public function getList(): iterable
    {
        $c = $this->modx->newQuery($this->elementClass());
        $c->select($this->listColumn());
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        return [];
    }

    /**
     * @return array<string, int|string>|int
     */
    protected function lookupCriteria(string $tpl)
    {
        if (is_numeric($tpl) && (float)$tpl > 0) {
            return (int)$tpl;
        }

        return [$this->nameField() => $tpl];
    }

    /**
     * @return array<string, int|string>
     */
    protected function objectCriteria(string $tpl): array
    {
        if (is_numeric($tpl) && (float)$tpl > 0) {
            return ['id' => (int)$tpl];
        }

        return [$this->nameField() => $tpl];
    }
}
