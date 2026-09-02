<?php

declare(strict_types=1);

namespace ModxPro\PdoTools\Tests\Stubs {

    class ServiceBag implements \ArrayAccess
    {
        /** @var array<string, mixed> */
        private $items = [];

        public function has($key): bool
        {
            return array_key_exists($key, $this->items);
        }

        public function add($key, $value): void
        {
            $this->items[$key] = $value;
        }

        public function get($key)
        {
            return $this->items[$key] ?? null;
        }

        public function offsetExists($offset): bool
        {
            return $this->has($offset);
        }

        public function offsetGet($offset)
        {
            return $this->get($offset);
        }

        public function offsetSet($offset, $value): void
        {
            $this->add($offset, $value);
        }

        public function offsetUnset($offset): void
        {
            unset($this->items[$offset]);
        }
    }
}

namespace MODX\Revolution {

    if (!class_exists(modX::class, false)) {
        class modX
        {
            public $user;
            public $context;
            public $resource;
            /** @var \ModxPro\PdoTools\Tests\Stubs\ServiceBag */
            public $services;
            /** @var array<string, mixed> */
            public $config = [];
            /** @var array<string, mixed> */
            public $placeholders = [];
            /** @var array<string, mixed> */
            public $classMap = [];
            /** @var array<string, mixed> */
            public $elementCache = [];
            public $queryTime = 0;
            public $executedQueries = 0;
            /** @var string[] */
            public $logs = [];

            public function __construct()
            {
                $this->user = new \stdClass();
                $this->user->id = 0;
                $this->context = new \stdClass();
                $this->context->key = 'web';
                $this->services = new \ModxPro\PdoTools\Tests\Stubs\ServiceBag();
                $this->services->add('lexicon', new class {
                    public function load($topic = null): void
                    {
                    }
                });
            }

            public function getOption($key, $options = null, $default = null, $skipEmpty = false)
            {
                if (is_array($options) && array_key_exists($key, $options)) {
                    $value = $options[$key];
                    if (!$skipEmpty || ($value !== '' && $value !== null)) {
                        return $value;
                    }
                }

                return $default;
            }

            public function log($level, $message): void
            {
                $this->logs[] = ['level' => $level, 'message' => $message];
            }

            public function lexicon($key, array $params = [], $language = '')
            {
                return $key;
            }

            public function getCacheManager()
            {
                return new class {
                    private $store = [];

                    public function get($key, $options = [])
                    {
                        return $this->store[$key] ?? null;
                    }

                    public function set($key, $var, $lifetime = 0)
                    {
                        $this->store[$key] = $var;

                        return true;
                    }
                };
            }

            public function invokeEvent($name, array $params = [])
            {
                return [];
            }

            public function getCount($class, $criteria = null)
            {
                return 0;
            }

            public function getObject($class, $criteria = null)
            {
                return null;
            }

            public function getAncestry($class)
            {
                return [$class];
            }

            public function getPK($class)
            {
                return 'id';
            }

            public function getChildIds($id, $depth = 10, array $options = [])
            {
                return [];
            }

            public function getParentIds($id, $height = 10, array $options = [])
            {
                return [];
            }

            public function getDebug()
            {
                return false;
            }
        }
    }

    if (!class_exists(modParser::class, false)) {
        class modParser
        {
            /** @var modX */
            public $modx;
            /** @var bool */
            protected $_processingUncacheable = false;

            public function __construct($modx)
            {
                $this->modx = $modx;
            }

            public function processElementTags(
                $parentTag,
                &$content,
                $processUncacheable = false,
                $removeUnprocessed = false,
                $prefix = '[[',
                $suffix = ']]',
                $tokens = [],
                $depth = 0
            ) {
                return 0;
            }

            public function isProcessingUncacheable(): bool
            {
                return $this->_processingUncacheable;
            }
        }
    }

    if (!class_exists(modTag::class, false)) {
        class modTag
        {
            /** @var modX|null */
            public $modx;
            /** @var string */
            protected $_tag = '';
            /** @var string */
            protected $_output = '';
            /** @var string */
            protected $_content = '';
            /** @var array<string, mixed> */
            protected $_properties = [];
            /** @var bool */
            protected $_result = true;
            /** @var bool */
            protected $_processed = false;

            public function filterInput(): void
            {
            }

            public function filterOutput(): void
            {
            }

            public function isCacheable(): bool
            {
                return false;
            }

            public function get($key)
            {
                return $key;
            }
        }
    }
}

namespace xPDO {

    if (!class_exists(xPDO::class, false)) {
        class xPDO
        {
            public const LOG_LEVEL_ERROR = 0;
            public const LOG_LEVEL_WARN = 1;
            public const LOG_LEVEL_INFO = 2;
            public const LOG_LEVEL_DEBUG = 3;
            public const OPT_CACHE_KEY = 'cache_key';
        }
    }
}

namespace {

    if (!defined('MODX_LOG_LEVEL_ERROR')) {
        define('MODX_LOG_LEVEL_ERROR', 0);
    }
    if (!defined('MODX_LOG_LEVEL_INFO')) {
        define('MODX_LOG_LEVEL_INFO', 2);
    }
}
