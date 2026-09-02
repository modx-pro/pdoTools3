<?php

namespace ModxPro\PdoTools\Support;


use MODX\Revolution\modResource;
use MODX\Revolution\modWebLink;
use MODX\Revolution\modX;
use ModxPro\PdoTools\CoreTools;
use ModxPro\PdoTools\Fetch;

class MenuBuilder
{
    /** @var modX $modx */
    protected $modx;
    /** @var  CoreTools|Fetch $pdoTools */
    public $pdoTools;
    /** @var array $tree */
    protected $tree = [];
    /** @var array $parentTree */
    protected $parentTree = [];
    /** @var int $level */
    protected $level = 1;


    /**
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;

        $config += [
                'firstClass' => 'first',
                'lastClass' => 'last',
                'hereClass' => 'active',
                'parentClass' => '',
                'rowClass' => '',
                'outerClass' => '',
                'innerClass' => '',
                'levelClass' => '',
                'selfClass' => '',
                'webLinkClass' => '',
                'limit' => 0,
                'hereId' => 0,
        ];
        $config['return'] = 'data';

        if (empty($config['tplInner']) && !empty($config['tplOuter'])) {
            $config['tplInner'] = $config['tplOuter'];
        }
        if (empty($config['hereId']) && !empty($modx->resource)) {
            $config['hereId'] = $modx->resource->id;
        }

        $modx->services['pdotools_config'] = $config;
        $this->pdoTools = $modx->services->get(Fetch::class);


        if ($config['hereId']) {
            $here = $this->pdoTools->getObject(modResource::class, ['id' => $config['hereId']], ['select' => 'id,context_key']);
            if ($here) {
                $tmp = $modx->getParentIds($here['id'], 100, [
                    'context' => $here['context_key'],
                ]);
                $tmp[] = $config['hereId'];
                $this->parentTree = array_flip($tmp);
            }
        }

        $modx->lexicon->load('pdotools:pdomenu');
    }


    /**
     * Gets tree of resources and template it
     *
     * @param array $tree
     *
     * @return mixed
     */
    public function templateTree($tree = [])
    {
        $this->tree = $tree;
        $count = count($tree);
        $output = '';

        $idx = $this->pdoTools->idx;
        $this->pdoTools->addTime('Start template tree');
        foreach ($tree as $row) {
            if (empty($row['id'])) {
                continue;
            }
            $this->level = 1;
            $row['idx'] = $idx++;
            $row['last'] = $row['idx'] == $count;

            $output .= $this->templateBranch($row);
        }
        $this->pdoTools->addTime('End template tree');

        if (!empty($output)) {
            $pls = $this->addWayFinderPlaceholders(
                [
                    'wrapper' => $output,
                    'classes' => ' class="' . $this->pdoTools->config('outerClass') . '"',
                    'classNames' => $this->pdoTools->config('outerClass'),
                    'classnames' => $this->pdoTools->config('outerClass'),
                    'level' => $this->level,
                ]
            );
            $output = $this->pdoTools->parseChunk($this->pdoTools->config('tplOuter'), $pls);
        }

        return $output;
    }


    /**
     * Recursive template of branch of menu
     *
     * @param array $row
     *
     * @return mixed|string
     */
    public function templateBranch($row = [])
    {
        $children = '';
        $row['level'] = $this->level;

        if (!empty($row['children']) && ($this->isHere($row['id']) || empty($this->pdoTools->config('hideSubMenus'))) && $this->checkResource($row['id'])) {
            $idx = $this->pdoTools->idx;
            $this->level++;
            $count = count($row['children']);
            foreach ($row['children'] as $v) {
                $v['idx'] = $idx++;
                $v['last'] = $v['idx'] == $count;

                $children .= $this->templateBranch($v);
            }
            $this->level--;
            $row['children'] = $count;
        } else {
            $row['children'] = isset($row['children']) ? count($row['children']) : 0;
        }

        if (!empty($this->pdoTools->config('countChildren'))) {
            if ($ids = $this->modx->getChildIds($row['id'])) {
                $tstart = microtime(true);
                $count = $this->modx->getCount(modResource::class, [
                    'id:IN' => $ids,
                    'published' => true,
                    'deleted' => false,
                ]);
                $this->modx->queryTime += microtime(true) - $tstart;
                $this->modx->executedQueries++;
                $this->pdoTools->addTime('Got the number of active children for resource "' . $row['id'] . '": ' . $count);
            } else {
                $count = 0;
            }
            $row['children'] = $count;
        }

        if (!empty($children)) {
            $pls = $this->addWayFinderPlaceholders([
                'wrapper' => $children,
                'classes' => ' class="' . $this->pdoTools->config('innerClass') . '"',
                'classNames' => $this->pdoTools->config('innerClass'),
                'classnames' => $this->pdoTools->config('innerClass'),
                'level' => $this->level,
                'children' => $row['children'] ?? 0,
            ]);
            $row['wrapper'] = $this->pdoTools->parseChunk($this->pdoTools->config('tplInner'), $pls);
        } else {
            $row['wrapper'] = '';
        }

        if (empty($row['menutitle']) && !empty($row['pagetitle'])) {
            $row['menutitle'] = $row['pagetitle'];
        }

        $state = $this->itemState($row);
        $classes = $state->classes();
        if (!empty($classes)) {
            $row['classNames'] = $row['classnames'] = $classes;
            $row['classes'] = ' class="' . $classes . '"';
        } else {
            $row['classNames'] = $row['classnames'] = $row['classes'] = '';
        }

        if (!empty($this->pdoTools->config('useWeblinkUrl')) && !empty($row['class_key']) && !empty($row['content']) && $row['class_key'] === modWebLink::class) {
            unset($row['context_key']);
            $row['link'] = is_numeric(trim($row['content'], '[]~ '))
                ? $this->pdoTools->makeUrl((int)trim($row['content'], '[]~ '), $row)
                : $row['content'];
        } else {
            $row['link'] = $this->pdoTools->makeUrl($row['id'], $row);
        }

        $row['title'] = !empty($this->pdoTools->config('titleOfLinks'))
            ? $row[$this->pdoTools->config('titleOfLinks')]
            : $row['pagetitle'];

        $row = array_merge($row, $state->placeholders());
        $tpl = $this->tplFromState($state, $row);
        $row = $this->addWayFinderPlaceholders($row);

        return $this->pdoTools->getChunk($tpl, $row, $this->pdoTools->config('fastMode'));
    }


    /**
     * Determine the "you are here" point in the menu
     *
     * @param int $id
     *
     * @return bool
     */
    public function isHere($id = 0)
    {
        return isset($this->parentTree[$id]);
    }


    /**
     * @param array $row
     * @return MenuItemState
     */
    protected function itemState(array $row = [])
    {
        $config = $this->pdoTools->config();
        $rowId = MenuItemState::resolveRowId($row, $config);

        return MenuItemState::fromRow($row, $config, $this->isHere($rowId));
    }

    /**
     * Determine style class for current item being processed
     *
     * @param array $row Array with resource properties
     *
     * @return string
     */
    public function getClasses($row = [])
    {
        return $this->itemState($row)->classes();
    }


    /**
     * Chunk name for the current menu row.
     *
     * @param array $row
     *
     * @return mixed
     */
    public function getTpl($row = [])
    {
        return $this->tplFromState($this->itemState($row), $row);
    }

    /**
     * @param MenuItemState $state
     * @param array $row
     * @return mixed
     */
    protected function tplFromState(MenuItemState $state, array $row)
    {
        $key = $state->tplKey();
        if ($key === null) {
            return $this->pdoTools->defineChunk($row);
        }

        return $this->pdoTools->config($key);
    }


    /**
     * This method adds special placeholders for compatibility with Wayfinder
     *
     * @param array $row
     *
     * @return array
     */
    public function addWayFinderPlaceholders($row = [])
    {
        $pl = $this->pdoTools->config('plPrefix');
        foreach ($row as $k => $v) {
            switch ($k) {
                case 'id':
                    if (!empty($this->pdoTools->config('rowIdPrefix'))) {
                        $row[$pl . 'id'] = ' id="' . $this->pdoTools->config('rowIdPrefix') . $v . '"';
                    }
                    $row[$pl . 'docid'] = $v;
                    break;
                case 'menutitle':
                    $row[$pl . 'linktext'] = $v;
                    $row[$pl . 'menutitle'] = $v;
                    break;
                case 'link_attributes':
                    $row[$pl . 'attributes'] = $v;
                    $row['attributes'] = $v;
                    break;
                case 'children':
                    $row[$pl . 'subitemcount'] = $v;
                    break;
                default:
                    $row[$pl . $k] = $v;
            }
        }

        return $row;
    }


    /**
     * Verification of resource status
     *
     * @param int $id
     *
     * @return bool|int
     */
    public function checkResource($id)
    {
        $tmp = [];
        if (empty($this->pdoTools->config('showHidden'))) {
            $tmp['hidemenu'] = 0;
        }
        if (empty($this->pdoTools->config('showUnpublished'))) {
            $tmp['published'] = 1;
        }
        if (!empty($this->pdoTools->config('hideUnsearchable'))) {
            $tmp['searchable'] = 1;
        }

        if (!empty($tmp)) {
            $tmp['id'] = $id;

            return empty($this->pdoTools->config('checkPermissions'))
                ? (bool)$this->modx->getCount(modResource::class, $tmp)
                : (bool)$this->modx->getObject(modResource::class, $tmp);
        }

        return true;
    }

}
