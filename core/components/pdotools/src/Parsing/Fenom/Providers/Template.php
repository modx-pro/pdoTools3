<?php

namespace ModxPro\PdoTools\Parsing\Fenom\Providers;

use PDO;
use Fenom\ProviderInterface;
use MODX\Revolution\modX;
use MODX\Revolution\modTemplate;
use ModxPro\PdoTools\CoreTools;

class Template implements ProviderInterface
{
    /** @var modX $modx */
    public $modx;
    /** @var CoreTools $pdoTools */
    public $pdoTools;


    public function __construct(modX $modx, CoreTools $pdoTools)
    {
        $this->modx = $modx;
        $this->pdoTools = $pdoTools;
    }


    /**
     * @param string $tpl
     */
    public function templateExists(string $tpl): bool
    {
        $c = is_numeric($tpl) && $tpl > 0
            ? $tpl
            : ['templatename' => $tpl];

        return (bool)$this->modx->getCount(modTemplate::class, $c);
    }


    /**
     * @param string $tpl
     * @param float $time
     */
    public function getSource(string $tpl, float &$time): string
    {
        $content = '';
        if ($pos = strpos($tpl, '@')) {
            $propertySet = substr($tpl, $pos + 1);
            $tpl = substr($tpl, 0, $pos);
        }
        $c = is_numeric($tpl) && $tpl > 0
            ? $tpl
            : ['templatename' => $tpl];
        /** @var modTemplate $element */
        if ($element = $this->modx->getObject(modTemplate::class, $c)) {
            $content = $element->getContent();

            $properties = [];
            if (!empty($propertySet)) {
                if ($tmp = $element->getPropertySet($propertySet)) {
                    $properties = $tmp;
                }
            } else {
                $properties = $element->getProperties();
            }
            if (!empty($content) && !empty($properties)) {
                $useFenom = $this->pdoTools->config('useFenom');
                $this->pdoTools->config(['useFenom' => false]);

                $content = $this->pdoTools->parseChunk('@INLINE ' . $content, $properties);
                $this->pdoTools->config(['useFenom' => $useFenom]);
            }
        }

        return $content;
    }


    /**
     * @param string $tpl
     */
    public function getLastModified(string $tpl): float
    {
        $c = is_numeric($tpl) && $tpl > 0
            ? $tpl
            : ['templatename' => $tpl];
        /** @var modTemplate $chunk */
        if ($chunk = $this->modx->getObject(modTemplate::class, $c)) {
            if ($chunk->isStatic() && $file = $chunk->getSourceFile()) {
                return (float)filemtime($file);
            }
        }

        return (float)time();
    }


    /**
     * Verify templates (check mtime)
     *
     * @param array $templates [template_name => modified, ...] By conversation, you may trust the template's name
     */
    public function verify(array $templates): bool
    {
        return true;
    }


    /**
     * Get all names of template from provider
     */
    public function getList(): iterable
    {
        $c = $this->modx->newQuery(modTemplate::class);
        $c->select('templatename');
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        return [];
    }

}
