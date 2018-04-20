<?php
class SearchResultsTableView extends SearchResultsView
{
    const DEFAULT_LAYOUT = 1;
    const RELATIONSHIPS_LAYOUT = 6;

    protected $columnsData;
    protected $detailLayoutData;
    protected $layoutId;
    protected $layoutsData;
    protected $limit;
    protected $showRelationships;

    function __construct()
    {
        parent::__construct();

        $this->columnsData = SearchOptions::getOptionDataForColumns();
        $this->layoutsData = SearchOptions::getOptionDataForLayouts();
        $this->detailLayoutData = SearchOptions::getOptionDataForDetailLayout();
        $this->addLayoutIdsToColumns();
        $this->addDetailLayoutIdsToColumns();
        $this->addDescriptionColumn();

        $this->showRelationships = isset($_GET['relationships']) ? intval($_GET['relationships']) == '1' : false;
    }

    protected function addDescriptionColumn()
    {
        // Make sure there's a Description column because it's needed by the L1 detail layout.
        $hasDescriptionColumn = false;
        foreach ($this->columnsData as $column)
        {
            if ($column['name'] == 'Description')
            {
                $hasDescriptionColumn = true;
                break;
            }
        }
        if (!$hasDescriptionColumn)
        {
            $elementId = ItemMetadata::getElementIdForElementName('Description');
            if ($elementId != 0)
            {
                $this->columnsData[$elementId] = self::createColumn('Description', 0);
            }
        }
    }

    protected function addDetailLayoutIdsToColumns()
    {
        foreach ($this->detailLayoutData as $row)
        {
            foreach ($row as $elementId => $elementName)
            {
                if ($elementName == '<tags>')
                {
                    // Tags are special cased elsewhere as a pseudo element.
                    continue;
                }
                if (!isset($this->columnsData[$elementId]))
                {
                    // This column is specified in the Detail Layout option, but is not listed in the Columns option.
                    $this->columnsData[$elementId] = self::createColumn($elementName, 0);
                }
            }
        }
    }

    protected function addLayoutIdsToColumns()
    {
        foreach ($this->layoutsData as $idNumber => $layout)
        {
            foreach ($layout['columns'] as $elementId => $columnName)
            {
                if (!SearchOptions::userHasAccessToLayout($layout))
                {
                    // Don't add admin layouts for non-admin users.
                    continue;
                }

                if ($idNumber == 1 && ($columnName == 'Identifier' || $columnName == 'Title'))
                {
                    // L1 is treated differently so don't add it to the Identifier or Title columns.
                    continue;
                }

                if (!isset($this->columnsData[$elementId]))
                {
                    // This column is specified in the Layouts option, but is not listed in the Columns option.
                    $this->columnsData[$elementId] = self::createColumn($columnName, 0);
                }
                $this->columnsData[$elementId]['layouts'][] = "L$idNumber";
            }
        }
    }

    public static function createColumn($name, $width, $align = '')
    {
        $column = array();
        $column['alias'] = $name;
        $column['width'] = $width;
        $column['align'] = $align;
        $column['layouts'] = array();
        $column['name'] = $name;
        return $column;
    }

    public static function createLayoutClasses($column)
    {
        $classes = '';
        foreach ($column['layouts'] as $layoutID)
        {
            $classes .= $layoutID . ' ';
        }

        return trim($classes);
    }

    public function getColumnsData()
    {
        return $this->columnsData;
    }

    public function getDetailLayoutData()
    {
        return $this->detailLayoutData;
    }

    public function getLayoutsData()
    {
        return $this->layoutsData;
    }

    public function getLayoutId()
    {
        if (isset($this->layoutId))
            return $this->layoutId;

        $firstLayoutId = $this->getLayoutIdFirst();
        $lastLayoutId =$this->getLayoutIdLast();

        $id = isset($_GET['layout']) ? intval($_GET['layout']) : $firstLayoutId;

        // Make sure that the layout Id is valid.
        if ($id < $firstLayoutId || $id > $lastLayoutId)
            $id = $firstLayoutId;

        $this->layoutId = $id;
        return $this->layoutId;
    }

    public function getLayoutIdFirst()
    {
        $keys = array_keys($this->layoutsData);
        return empty($keys) ? 0 : min($keys);
    }

    public function getLayoutIdLast()
    {
        $keys = array_keys($this->layoutsData);
        return empty($keys) ? 0 : max($keys);
    }

    public function getLayoutSelectOptions()
    {
        $layoutsData = $this->layoutsData;
        $layoutSelectOptions = array();
        foreach ($layoutsData as $idNumber => $layout)
        {
            if (!SearchOptions::userHasAccessToLayout($layout))
            {
                // Omit admin layouts for non-admin users.
                continue;
            }

            $layoutSelectOptions[$idNumber] = $layout['name'];
        }
        return $layoutSelectOptions;
    }

    public static function getLimitOptions()
    {
        return array(
            '10' => '10',
            '25' => '25',
            '50' => '50',
            '100' => '100',
            '200' => '200');
    }

    public function getShowRelationships()
    {
        return $this->showRelationships;
    }

    public function hasLayoutL1()
    {
        return isset($this->layoutsData[1]);
    }
}