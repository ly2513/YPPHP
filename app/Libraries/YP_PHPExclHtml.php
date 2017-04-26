<?php

/**
 * User: yongli
 * Date: 17/4/26
 * Time: 14:43
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
class YP_PHPExclHtml extends \PHPExcel_Writer_HTML
{
    /**
     * PHPExcel object
     *
     * @var PHPExcel
     */
    protected $_phpExcel;

    /**
     * Sheet index to write
     *
     * @var int
     */
    private $_sheetIndex = 0;

    /**
     * Images root
     *
     * @var string
     */
    private $_imagesRoot = '.';

    /**
     * embed images, or link to images
     *
     * @var boolean
     */
    private $_embedImages = false;

    /**
     * Use inline CSS?
     *
     * @var boolean
     */
    private $_useInlineCss = false;

    /**
     * Array of CSS styles
     *
     * @var array
     */
    private $_cssStyles = null;

    /**
     * Array of column widths in points
     *
     * @var array
     */
    private $_columnWidths = null;

    /**
     * Default font
     *
     * @var PHPExcel_Style_Font
     */
    private $_defaultFont;

    /**
     * Flag whether spans have been calculated
     *
     * @var boolean
     */
    private $_spansAreCalculated = false;

    /**
     * Excel cells that should not be written as HTML cells
     *
     * @var array
     */
    private $_isSpannedCell = [];

    /**
     * Excel cells that are upper-left corner in a cell merge
     *
     * @var array
     */
    private $_isBaseCell = [];

    /**
     * Excel rows that should not be written as HTML rows
     *
     * @var array
     */
    private $_isSpannedRow = [];

    /**
     * Is the current writer creating PDF?
     *
     * @var boolean
     */
    protected $_isPdf = false;

    private $_type = '';

    /**
     * Generate the Navigation block
     *
     * @var boolean
     */
    private $_generateSheetNavigationBlock = true;
    
    /**
     * YP_PHPExclHtml constructor.
     *
     * @param array ...$param
     */
    public function __construct(...$param)
    {
        $excl = isset($param[0]) ? $param[0] : '';
        if (!($excl instanceof \PHPExcel)) {
            throw new exception('Param Must Be Type of PHPExcl');
        }
        $this->initParent($excl);
        $this->_type = isset($param[1]) ? $param[1] : 'report';
    }

    /**
     * 执行父类方法
     *
     * @param PHPExcel $phpExcel
     *
     * @throws PHPExcel_Exception
     */
    private function initParent(\PHPExcel $phpExcel)
    {
        parent::__construct($phpExcel);
        $this->_defaultFont = $this->_phpExcel->getDefaultStyle()->getFont();
    }

    /**
     * 修改logo地址
     *
     * @param $content
     *
     * TODO 根据相应的项目进行调整路径
     *
     * @return mixed
     */
    private function fixWebUrl($content)
    {
        //将图片替换为 外网可访问的路径
        $content = str_replace('.' . ROOT_PATH . 'public/', '/api/', $content);

        return $content;
    }

    public function saveHtml()
    {
        $this->writeAllSheets();
        ob_start();
        $this->save("php://output");
        $content = ob_get_contents();
        ob_clean();

        return $this->fixWebUrl($content);
    }

    /**
     * Build CSS styles
     *
     * @param  boolean $generateSurroundingHTML Generate surrounding HTML style? (html { })
     *
     * @return array
     * @throws PHPExcel_Writer_Exception
     */
    public function buildCSS($generateSurroundingHTML = true)
    {
        // PHPExcel object known?
        if (is_null($this->_phpExcel)) {
            throw new \PHPExcel_Writer_Exception('Internal PHPExcel object not set to an instance of an object.');
        }
        // Cached?
        if (!is_null($this->_cssStyles)) {
            return $this->_cssStyles;
        }
        // Ensure that spans have been calculated
        if (!$this->_spansAreCalculated) {
            $this->_calculateSpans();
        }
        // Construct CSS
        $css = [];
        // Start styles
        if ($generateSurroundingHTML) {
            // html { }
            $css['html']['font-family']      = 'Calibri, Arial, Helvetica, sans-serif';
            $css['html']['font-size']        = '11pt';
            $css['html']['background-color'] = 'white';
        }
        // table { }
        $css['table']['border-collapse'] = 'collapse';
        if (!$this->_isPdf) {
            $css['table']['page-break-after']      = 'always';
            $css['table']['margin']                = '36pt 14.5pt 0';
            $css['table']['display']               = 'none';
            $css['table:first-of-type']['display'] = 'block';
        }
        //----baozhiying---图表预览表格溢出问题---------------
        // .gridlines td { }
        //$css['.gridlines td']['border'] = '1px dotted black';
        //$css['.gridlines th']['border'] = '1px dotted black';
        //------------------------end------------------------
        // .b {}
        $css['.b']['text-align'] = 'center'; // BOOL
        // .e {}
        $css['.e']['text-align'] = 'center'; // ERROR
        // .f {}
        $css['.f']['text-align'] = 'right'; // FORMULA
        // .inlineStr {}
        $css['.inlineStr']['text-align'] = 'left'; // INLINE
        // .n {}
        $css['.n']['text-align'] = 'right'; // NUMERIC
        // .s {}
        $css['.s']['text-align']    = 'left'; // STRING
        $css['.s']['padding-left']  = '5px'; // 列的padding
        $css['.s']['padding-right'] = '5px'; // 列的padding
        /******  追加样式区域   *****/
        // body
        $css['body']['min-width'] = '1200px';
        $css['body']['max-width'] = '100vw';
        $css['body']['overflow']  = 'auto';
        $css['body']['margin']    = '7.5pt 0 30pt';
        // ul.navigation
        $css['ul.navigation']['list-style']            = 'none';
        $css['ul.navigation']['position']              = 'fixed';
        $css['ul.navigation']['left']                  = '0';
        $css['ul.navigation']['top']                   = '0';
        $css['ul.navigation']['z-index']               = '3';
        $css['ul.navigation']['-webkit-padding-start'] = '0';
        $css['ul.navigation']['font-size']             = '10.5pt';
        $css['ul.navigation']['background']            = '#f5f5f5';
        $css['ul.navigation']['margin']                = '0 14.5pt';
        $css['ul.navigation']['width']                 = '100%';
        $css['ul.navigation']['height']                = '30pt';
        $css['ul.navigation']['line-height']           = '30pt';
        $css['ul.navigation']['box-sizing']            = 'border-box';
        $css['ul.navigation']['padding']               = '0 14.5pt';
        // ul.navigation li
        $css['ul.navigation li']['float'] = 'left';
        //ul.navigation li span
        $css['ul.navigation li span']['cursor']          = 'pointer';
        $css['ul.navigation li span']['color']           = '#3e3e3e';
        $css['ul.navigation li span']['padding-left']    = '6pt';
        $css['ul.navigation li span']['padding-right']   = '6pt';
        $css['ul.navigation li span']['text-decoration'] = 'none';
        $css['ul.navigation li span']['border-bottom']   = '2px solid #f5f5f5';
        $css['ul.navigation li span']['height']          = '30pt';
        $css['ul.navigation li span']['display']         = 'inline-block';
        $css['ul.navigation li span']['box-sizing']      = 'border-box';
        //默认使 第一个 sheet 被选中 ul.navigation li.sheet0 span
        $css['ul.navigation li.sheet0 span']['color']         = '#80b7e7';
        $css['ul.navigation li.sheet0 span']['border-bottom'] = '2px solid #80b7e7';
        // ul.navigation li a
        $css['ul.navigation li a']['color']           = '#3e3e3e';
        $css['ul.navigation li a']['padding-left']    = '6pt';
        $css['ul.navigation li a']['padding-right']   = '6pt';
        $css['ul.navigation li a']['text-decoration'] = 'none';
        $css['ul.navigation li a']['border-bottom']   = '2px solid #f5f5f5';
        $css['ul.navigation li a']['height']          = '30px';
        $css['ul.navigation li a']['display']         = 'inline-block';
        $css['ul.navigation li a']['box-sizing']      = 'border-box';
        // td
        $css['td']['white-space'] = 'nowrap';
        // th
        $css['th']['white-space'] = 'nowrap';
        // table:first-of-type
        $css['table:first-of-type']['display'] = 'block';
        // Calculate cell style hashes
        foreach ($this->_phpExcel->getCellXfCollection() as $index => $style) {
            $css['td.style' . $index] = $this->_createCSSStyle($style);
            $css['th.style' . $index] = $this->_createCSSStyle($style);
        }
        // Fetch sheets
        $sheets = [];
        if (is_null($this->_sheetIndex)) {
            $sheets = $this->_phpExcel->getAllSheets();
        } else {
            $sheets[] = $this->_phpExcel->getSheet($this->_sheetIndex);
        }
        // Build styles per sheet
        foreach ($sheets as $sheet) {
            // Calculate hash code
            $sheetIndex = $sheet->getParent()->getIndex($sheet);
            // Build styles
            // Calculate column widths
            $sheet->calculateColumnWidths();
            // col elements, initialize
            $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn()) - 1;
            $column             = -1;
            while ($column++ < $highestColumnIndex) {
                $this->_columnWidths[$sheetIndex][$column]                        = 42; // approximation
                $css['table.sheet' . $sheetIndex . ' col.col' . $column]['width'] = '42pt';
            }
            // col elements, loop through columnDimensions and set width
            foreach ($sheet->getColumnDimensions() as $columnDimension) {
                if (($width = PHPExcel_Shared_Drawing::cellDimensionToPixels($columnDimension->getWidth(),
                        $this->_defaultFont)) >= 0
                ) {
                    $width                                                            = \PHPExcel_Shared_Drawing::pixelsToPoints($width);
                    $column                                                           = \PHPExcel_Cell::columnIndexFromString($columnDimension->getColumnIndex()) - 1;
                    $this->_columnWidths[$sheetIndex][$column]                        = $width;
                    $css['table.sheet' . $sheetIndex . ' col.col' . $column]['width'] = $width . 'pt';
                    if ($columnDimension->getVisible() === false) {
                        $css['table.sheet' . $sheetIndex . ' col.col' . $column]['visibility'] = 'collapse';
                        $css['table.sheet' . $sheetIndex . ' col.col' . $column]['*display']   = 'none'; // target IE6+7
                    }
                }
            }
            // Default row height
            $rowDimension = $sheet->getDefaultRowDimension();
            // table.sheetN tr { }
            $css['table.sheet' . $sheetIndex . ' tr'] = [];
            if ($rowDimension->getRowHeight() == -1) {
                $pt_height = \PHPExcel_Shared_Font::getDefaultRowHeightByFont($this->_phpExcel->getDefaultStyle()->getFont());
            } else {
                $pt_height = $rowDimension->getRowHeight();
            }
            $css['table.sheet' . $sheetIndex . ' tr']['height'] = $pt_height . 'pt';
            if ($rowDimension->getVisible() === false) {
                $css['table.sheet' . $sheetIndex . ' tr']['display']    = 'none';
                $css['table.sheet' . $sheetIndex . ' tr']['visibility'] = 'hidden';
            }
            // Calculate row heights
            foreach ($sheet->getRowDimensions() as $rowDimension) {
                $row = $rowDimension->getRowIndex() - 1;
                // table.sheetN tr.rowYYYYYY { }
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row] = [];
                if ($rowDimension->getRowHeight() == -1) {
                    $pt_height = \PHPExcel_Shared_Font::getDefaultRowHeightByFont($this->_phpExcel->getDefaultStyle()->getFont());
                } else {
                    $pt_height = $rowDimension->getRowHeight();
                }
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['height'] = $pt_height . 'pt';
                if ($rowDimension->getVisible() === false) {
                    $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['display']    = 'none';
                    $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['visibility'] = 'hidden';
                }
            }
        }
        // 设置前三列宽度
        $css['td.style1']['padding-left'] = '5px';
        $css['td.style3']['padding-left'] = '5px';
        // Cache
        if (is_null($this->_cssStyles)) {
            $this->_cssStyles = $css;
        }

        // Return
        return $css;
    }

    /**
     * Map VAlign
     *
     * @param  string $vAlign Vertical alignment
     *
     * @return string
     */
    private function _mapVAlign($vAlign)
    {
        switch ($vAlign) {
            case \PHPExcel_Style_Alignment::VERTICAL_BOTTOM:
                return 'bottom';
            case \PHPExcel_Style_Alignment::VERTICAL_TOP:
                return 'top';
            case \PHPExcel_Style_Alignment::VERTICAL_CENTER:
            case \PHPExcel_Style_Alignment::VERTICAL_JUSTIFY:
                return 'middle';
            default:
                return 'baseline';
        }
    }

    /**
     * Map HAlign
     *
     * @param  string $hAlign Horizontal alignment
     *
     * @return string|false
     */
    private function _mapHAlign($hAlign)
    {
        switch ($hAlign) {
            case \PHPExcel_Style_Alignment::HORIZONTAL_GENERAL:
                return false;
            case \PHPExcel_Style_Alignment::HORIZONTAL_LEFT:
                return 'left';
            case \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT:
                return 'right';
            case \PHPExcel_Style_Alignment::HORIZONTAL_CENTER:
            case \PHPExcel_Style_Alignment::HORIZONTAL_CENTER_CONTINUOUS:
                return 'center';
            case \PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY:
                return 'justify';
            default:
                return false;
        }
    }

    /**
     * Map border style
     *
     * @param  int $borderStyle Sheet index
     *
     * @return string
     */
    private function _mapBorderStyle($borderStyle)
    {
        switch ($borderStyle) {
            case \PHPExcel_Style_Border::BORDER_NONE:
                return 'none';
            case \PHPExcel_Style_Border::BORDER_DASHDOT:
                return '1px dashed';
            case \PHPExcel_Style_Border::BORDER_DASHDOTDOT:
                return '1px dotted';
            case \PHPExcel_Style_Border::BORDER_DASHED:
                return '1px dashed';
            case \PHPExcel_Style_Border::BORDER_DOTTED:
                return '1px dotted';
            case \PHPExcel_Style_Border::BORDER_DOUBLE:
                return '3px double';
            case \PHPExcel_Style_Border::BORDER_HAIR:
                return '1px solid';
            case \PHPExcel_Style_Border::BORDER_MEDIUM:
                return '2px solid';
            case \PHPExcel_Style_Border::BORDER_MEDIUMDASHDOT:
                return '2px dashed';
            case \PHPExcel_Style_Border::BORDER_MEDIUMDASHDOTDOT:
                return '2px dotted';
            case \PHPExcel_Style_Border::BORDER_MEDIUMDASHED:
                return '2px dashed';
            case \PHPExcel_Style_Border::BORDER_SLANTDASHDOT:
                return '2px dashed';
            case \PHPExcel_Style_Border::BORDER_THICK:
                return '3px solid';
            case \PHPExcel_Style_Border::BORDER_THIN:
                return '1px solid';
            default:
                return '1px solid'; // map others to thin
        }
    }

    /**
     * Get sheet index
     *
     * @return int
     */
    public function getSheetIndex()
    {
        return $this->_sheetIndex;
    }

    /**
     * Set sheet index
     *
     * @param  int $pValue Sheet index
     *
     * @return PHPExcel_Writer_HTML
     */
    public function setSheetIndex($pValue = 0)
    {
        $this->_sheetIndex = $pValue;

        return $this;
    }

    /**
     * Get sheet index
     *
     * @return boolean
     */
    public function getGenerateSheetNavigationBlock()
    {
        return $this->_generateSheetNavigationBlock;
    }

    /**
     * Set sheet index
     *
     * @param  boolean $pValue Flag indicating whether the sheet navigation block should be generated or not
     *
     * @return PHPExcel_Writer_HTML
     */
    public function setGenerateSheetNavigationBlock($pValue = true)
    {
        $this->_generateSheetNavigationBlock = (bool)$pValue;

        return $this;
    }

    /**
     * Write all sheets (resets sheetIndex to NULL)
     */
    public function writeAllSheets()
    {
        $this->_sheetIndex = null;

        return $this;
    }

    /**
     * Generate HTML header
     *
     * @param  boolean $pIncludeStyles Include styles?
     *
     * @return string
     * @throws PHPExcel_Writer_Exception
     */
    public function generateHTMLHeader($pIncludeStyles = false)
    {
        // PHPExcel object known?
        if (is_null($this->_phpExcel)) {
            throw new \PHPExcel_Writer_Exception('Internal PHPExcel object not set to an instance of an object.');
        }
        // Construct HTML
        $properties = $this->_phpExcel->getProperties();
        $html       = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">' . PHP_EOL;
        $html .= '<!-- Generated by PHPExcel - http://www.phpexcel.net -->' . PHP_EOL;
        $html .= '<html>' . PHP_EOL;
        $html .= '  <head>' . PHP_EOL;
        $html .= '	  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . PHP_EOL;
        if ($properties->getTitle() > '') {
            $html .= '	  <title>' . htmlspecialchars($properties->getTitle()) . '</title>' . PHP_EOL;
        }
        if ($properties->getCreator() > '') {
            $html .= '	  <meta name="author" content="' . htmlspecialchars($properties->getCreator()) . '" />' . PHP_EOL;
        }
        if ($properties->getTitle() > '') {
            $html .= '	  <meta name="title" content="' . htmlspecialchars($properties->getTitle()) . '" />' . PHP_EOL;
        }
        if ($properties->getDescription() > '') {
            $html .= '	  <meta name="description" content="' . htmlspecialchars($properties->getDescription()) . '" />' . PHP_EOL;
        }
        if ($properties->getSubject() > '') {
            $html .= '	  <meta name="subject" content="' . htmlspecialchars($properties->getSubject()) . '" />' . PHP_EOL;
        }
        if ($properties->getKeywords() > '') {
            $html .= '	  <meta name="keywords" content="' . htmlspecialchars($properties->getKeywords()) . '" />' . PHP_EOL;
        }
        if ($properties->getCategory() > '') {
            $html .= '	  <meta name="category" content="' . htmlspecialchars($properties->getCategory()) . '" />' . PHP_EOL;
        }
        if ($properties->getCompany() > '') {
            $html .= '	  <meta name="company" content="' . htmlspecialchars($properties->getCompany()) . '" />' . PHP_EOL;
        }
        if ($properties->getManager() > '') {
            $html .= '	  <meta name="manager" content="' . htmlspecialchars($properties->getManager()) . '" />' . PHP_EOL;
        }
        if ($pIncludeStyles) {
            $html .= $this->generateStyles(true);
        }
        $html .= '  </head>' . PHP_EOL;
        $html .= '' . PHP_EOL;
        $html .= '  <body>' . PHP_EOL;

        // Return
        return $html;
    }

    /**
     * Generate sheet data
     *
     * @return string
     * @throws PHPExcel_Writer_Exception
     */
    public function generateSheetData()
    {
        // PHPExcel object known?
        if (is_null($this->_phpExcel)) {
            throw new \PHPExcel_Writer_Exception('Internal PHPExcel object not set to an instance of an object.');
        }
        // Ensure that Spans have been calculated?
        if (!$this->_spansAreCalculated) {
            $this->_calculateSpans();
        }
        // Fetch sheets
        $sheets = [];
        if (is_null($this->_sheetIndex)) {
            $sheets = $this->_phpExcel->getAllSheets();
        } else {
            $sheets[] = $this->_phpExcel->getSheet($this->_sheetIndex);
        }
        // Construct HTML
        $html = '';
        // Loop all sheets
        $sheetId = 0;
        foreach ($sheets as $sheet) {
            // Write table header
            $html .= $this->_generateTableHeader($sheet);
            // Get worksheet dimension
            $dimension       = explode(':', $sheet->calculateWorksheetDimension());
            $dimension[0]    = \PHPExcel_Cell::coordinateFromString($dimension[0]);
            $dimension[0][0] = \PHPExcel_Cell::columnIndexFromString($dimension[0][0]) - 1;
            $dimension[1]    = \PHPExcel_Cell::coordinateFromString($dimension[1]);
            $dimension[1][0] = \PHPExcel_Cell::columnIndexFromString($dimension[1][0]) - 1;
            // row min,max
            $rowMin = $dimension[0][1];
            $rowMax = $dimension[1][1];
            // calculate start of <tbody>, <thead>
            $tbodyStart = $rowMin;
            $theadStart = $theadEnd = 0; // default: no <thead>	no </thead>
            if ($sheet->getPageSetup()->isRowsToRepeatAtTopSet()) {
                $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();
                // we can only support repeating rows that start at top row
                if ($rowsToRepeatAtTop[0] == 1) {
                    $theadStart = $rowsToRepeatAtTop[0];
                    $theadEnd   = $rowsToRepeatAtTop[1];
                    $tbodyStart = $rowsToRepeatAtTop[1] + 1;
                }
            }
            // Loop through cells
            $row = $rowMin - 1;
            while ($row++ < $rowMax) {
                // <thead> ?
                if ($row == $theadStart) {
                    $html .= '		<thead>' . PHP_EOL;
                    $cellType = 'th';
                }
                // <tbody> ?
                if ($row == $tbodyStart) {
                    $html .= '		<tbody>' . PHP_EOL;
                    $cellType = 'td';
                }
                // Write row if there are HTML table cells in it
                if (!isset($this->_isSpannedRow[$sheet->getParent()->getIndex($sheet)][$row])) {
                    // Start a new rowData
                    $rowData = [];
                    // Loop through columns
                    $column = $dimension[0][0] - 1;
                    while ($column++ < $dimension[1][0]) {
                        // Cell exists?
                        if ($sheet->cellExistsByColumnAndRow($column, $row)) {
                            $rowData[$column] = \PHPExcel_Cell::stringFromColumnIndex($column) . $row;
                        } else {
                            $rowData[$column] = '';
                        }
                    }
                    $html .= $this->_generateRow($sheet, $rowData, $row - 1, $cellType);
                }
                // </thead> ?
                if ($row == $theadEnd) {
                    $html .= '		</thead>' . PHP_EOL;
                }
            }
            $html .= $this->_extendRowsForChartsAndImages($sheet, $row);
            // Close table body.
            $html .= '		</tbody>' . PHP_EOL;
            // Write table footer
            $html .= $this->_generateTableFooter();
            // Writing PDF?
            if ($this->_isPdf) {
                if (is_null($this->_sheetIndex) && $sheetId + 1 < $this->_phpExcel->getSheetCount()) {
                    $html .= '<div style="page-break-before:always" />';
                }
            }
            // Next sheet
            ++$sheetId;
        }

        // Return
        return $html;
    }

    /**
     * Generate sheet tabs
     *
     * @return string
     * @throws PHPExcel_Writer_Exception
     */
    public function generateNavigation()
    {
        // PHPExcel object known?
        if (is_null($this->_phpExcel)) {
            throw new \PHPExcel_Writer_Exception('Internal PHPExcel object not set to an instance of an object.');
        }
        // Fetch sheets
        $sheets = [];
        if (is_null($this->_sheetIndex)) {
            $sheets = $this->_phpExcel->getAllSheets();
        } else {
            $sheets[] = $this->_phpExcel->getSheet($this->_sheetIndex);
        }
        // Construct HTML
        $html = '';
        // Only if there are more than 1 sheets
        if (count($sheets) > 1) {
            // Loop all sheets
            $sheetId = 0;
            $html .= '<ul class="navigation">' . PHP_EOL;
            foreach ($sheets as $sheet) {
                //$html .= '  <li class="sheet' . $sheetId . '"><a href="#sheet' . $sheetId . '">' . $sheet->getTitle() . '</a></li>' . PHP_EOL;
                $html .= '  <li class="sheet' . $sheetId . '"><span>' . $sheet->getTitle() . '</span></li>' . PHP_EOL;
                ++$sheetId;
            }
            $html .= '</ul>' . PHP_EOL;
        }

        return $html;
    }

    private function _extendRowsForChartsAndImages(\PHPExcel_Worksheet $pSheet, $row)
    {
        $rowMax = $row;
        $colMax = 'A';
        if ($this->_includeCharts) {
            foreach ($pSheet->getChartCollection() as $chart) {
                if ($chart instanceof \PHPExcel_Chart) {
                    $chartCoordinates = $chart->getTopLeftPosition();
                    $chartTL          = \PHPExcel_Cell::coordinateFromString($chartCoordinates['cell']);
                    $chartCol         = \PHPExcel_Cell::columnIndexFromString($chartTL[0]);
                    if ($chartTL[1] > $rowMax) {
                        $rowMax = $chartTL[1];
                        if ($chartCol > \PHPExcel_Cell::columnIndexFromString($colMax)) {
                            $colMax = $chartTL[0];
                        }
                    }
                }
            }
        }
        foreach ($pSheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof \PHPExcel_Worksheet_Drawing) {
                $imageTL  = \PHPExcel_Cell::coordinateFromString($drawing->getCoordinates());
                $imageCol = \PHPExcel_Cell::columnIndexFromString($imageTL[0]);
                if ($imageTL[1] > $rowMax) {
                    $rowMax = $imageTL[1];
                    if ($imageCol > \PHPExcel_Cell::columnIndexFromString($colMax)) {
                        $colMax = $imageTL[0];
                    }
                }
            }
        }
        $html = '';
        $colMax++;
        while ($row < $rowMax) {
            $html .= '<tr>';
            for ($col = 'A'; $col != $colMax; ++$col) {
                $html .= '<td>';
                $html .= $this->_writeImageInCell($pSheet, $col . $row);
                if ($this->_includeCharts) {
                    $html .= $this->_writeChartInCell($pSheet, $col . $row);
                }
                $html .= '</td>';
            }
            ++$row;
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * Generate image tag in cell
     *
     * @param  PHPExcel_Worksheet $pSheet      PHPExcel_Worksheet
     * @param  string             $coordinates Cell coordinates
     *
     * @return string
     * @throws PHPExcel_Writer_Exception
     */
    private function _writeImageInCell(\PHPExcel_Worksheet $pSheet, $coordinates)
    {
        // Construct HTML
        $html = '';
        // Write images
        foreach ($pSheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof \PHPExcel_Worksheet_Drawing) {
                if ($drawing->getCoordinates() == $coordinates) {
                    $filename = $drawing->getPath();
                    // Strip off eventual '.'
                    if (substr($filename, 0, 1) == '.') {
                        $filename = substr($filename, 1);
                    }
                    // Prepend images root
                    $filename = $this->getImagesRoot() . $filename;
                    // Strip off eventual '.'
                    if (substr($filename, 0, 1) == '.' && substr($filename, 0, 2) != './') {
                        $filename = substr($filename, 1);
                    }
                    // Convert UTF8 data to PCDATA
                    $filename = htmlspecialchars($filename);
                    $html .= PHP_EOL;
                    if ((!$this->_embedImages) || ($this->_isPdf)) {
                        $imageData = $filename;
                    } else {
                        $imageDetails = getimagesize($filename);
                        if ($fp = fopen($filename, "rb", 0)) {
                            $picture = fread($fp, filesize($filename));
                            fclose($fp);
                            // base64 encode the binary data, then break it
                            // into chunks according to RFC 2045 semantics
                            $base64    = chunk_split(base64_encode($picture));
                            $imageData = 'data:' . $imageDetails['mime'] . ';base64,' . $base64;
                        } else {
                            $imageData = $filename;
                        }
                    }
                    $html .= '<div style="position: relative;">';
                    //由于logo与名称重合的问题，将其左飘改为四分之一
                    $html .= $this->setImgWidthAndHeight($imageData);
                    $html .= '</div>';
                }
            }
        }

        // Return
        return $html;
    }

    /**
     * Generate chart tag in cell
     *
     * @param  PHPExcel_Worksheet $pSheet      PHPExcel_Worksheet
     * @param  string             $coordinates Cell coordinates
     *
     * @return string
     * @throws PHPExcel_Writer_Exception
     */
    private function _writeChartInCell(\PHPExcel_Worksheet $pSheet, $coordinates)
    {
        // Construct HTML
        $html = '';
        // Write charts
        foreach ($pSheet->getChartCollection() as $chart) {
            if ($chart instanceof \PHPExcel_Chart) {
                $chartCoordinates = $chart->getTopLeftPosition();
                if ($chartCoordinates['cell'] == $coordinates) {
                    $chartFileName = \PHPExcel_Shared_File::sys_get_temp_dir() . '/' . uniqid() . '.png';
                    if (!$chart->render($chartFileName)) {
                        return;
                    }
                    $html .= PHP_EOL;
                    $imageDetails = getimagesize($chartFileName);
                    if ($fp = fopen($chartFileName, "rb", 0)) {
                        $picture = fread($fp, filesize($chartFileName));
                        fclose($fp);
                        // base64 encode the binary data, then break it
                        // into chunks according to RFC 2045 semantics
                        $base64    = chunk_split(base64_encode($picture));
                        $imageData = 'data:' . $imageDetails['mime'] . ';base64,' . $base64;
                        $html .= '<div style="position: relative;">';
                        $html .= '<img style="position: absolute; z-index: 1; left: ' . $chartCoordinates['xOffset'] . 'px; top: ' . $chartCoordinates['yOffset'] . 'px; width: ' . $imageDetails[0] . 'px; height: ' . $imageDetails[1] . 'px;" src="' . $imageData . '" border="0" />' . PHP_EOL;
                        $html .= '</div>';
                        unlink($chartFileName);
                    }
                }
            }
        }

        // Return
        return $html;
    }

    /**
     * Generate CSS styles
     *
     * @param  boolean $generateSurroundingHTML Generate surrounding HTML tags? (&lt;style&gt; and &lt;/style&gt;)
     *
     * @return string
     * @throws PHPExcel_Writer_Exception
     */
    public function generateStyles($generateSurroundingHTML = true)
    {
        // PHPExcel object known?
        if (is_null($this->_phpExcel)) {
            throw new \PHPExcel_Writer_Exception('Internal PHPExcel object not set to an instance of an object.');
        }
        // Build CSS
        $css = $this->buildCSS($generateSurroundingHTML);
        // Construct HTML
        $html = '';
        // Start styles
        if ($generateSurroundingHTML) {
            $html .= '	<style type="text/css">' . PHP_EOL;
            $html .= '	  html { ' . $this->_assembleCSS($css['html']) . ' }' . PHP_EOL;
        }
        // Write all other styles
        foreach ($css as $styleName => $styleDefinition) {
            if ($styleName != 'html') {
                $html .= '	  ' . $styleName . ' { ' . $this->_assembleCSS($styleDefinition) . ' }' . PHP_EOL;
            }
        }
        // End styles
        if ($generateSurroundingHTML) {
            $html .= '	</style>' . PHP_EOL;
        }

        // Return
        return $html;
    }

    /**
     * Create CSS style
     *
     * @param  PHPExcel_Style $pStyle PHPExcel_Style
     *
     * @return array
     */
    private function _createCSSStyle(\PHPExcel_Style $pStyle)
    {
        // Construct CSS
        $css = '';
        // Create CSS
        $css = array_merge($this->_createCSSStyleAlignment($pStyle->getAlignment()),
            $this->_createCSSStyleBorders($pStyle->getBorders()), $this->_createCSSStyleFont($pStyle->getFont()),
            $this->_createCSSStyleFill($pStyle->getFill()));
        //print_r($css);
        // Return
        return $css;
    }

    /**
     * Create CSS style (PHPExcel_Style_Alignment)
     *
     * @param  PHPExcel_Style_Alignment $pStyle PHPExcel_Style_Alignment
     *
     * @return array
     */
    private function _createCSSStyleAlignment(\PHPExcel_Style_Alignment $pStyle)
    {
        // Construct CSS
        $css = [];
        // Create CSS
        $css['vertical-align'] = $this->_mapVAlign($pStyle->getVertical());
        if ($textAlign = $this->_mapHAlign($pStyle->getHorizontal())) {
            $css['text-align'] = $textAlign;
            if (in_array($textAlign, ['left', 'right'])) {
                $css['padding-' . $textAlign] = (string)((int)$pStyle->getIndent() * 11) . 'px';
            }
        }

        // Return
        return $css;
    }

    /**
     * Create CSS style (PHPExcel_Style_Font)
     *
     * @param  PHPExcel_Style_Font $pStyle PHPExcel_Style_Font
     *
     * @return array
     */
    private function _createCSSStyleFont(\PHPExcel_Style_Font $pStyle)
    {
        // Construct CSS
        $css = [];
        // Create CSS
        if ($pStyle->getBold()) {
            $css['font-weight'] = 'bold';
        }
        if ($pStyle->getUnderline() != \PHPExcel_Style_Font::UNDERLINE_NONE && $pStyle->getStrikethrough()) {
            $css['text-decoration'] = 'underline line-through';
        } elseif ($pStyle->getUnderline() != \PHPExcel_Style_Font::UNDERLINE_NONE) {
            $css['text-decoration'] = 'underline';
        } elseif ($pStyle->getStrikethrough()) {
            $css['text-decoration'] = 'line-through';
        }
        if ($pStyle->getItalic()) {
            $css['font-style'] = 'italic';
        }
        //@TODO 个性化定制颜色
        $css['color'] = '#' . ($pStyle->getColor()->getRGB() == '000000' ? '373737' : $pStyle->getColor()->getRGB());
        //$css['color']		= '#' . $pStyle->getColor()->getRGB();
        $css['font-family'] = '\'' . $pStyle->getName() . '\'';
        $css['font-size']   = $pStyle->getSize() . 'pt';

        // Return
        return $css;
    }

    /**
     * Create CSS style (PHPExcel_Style_Borders)
     *
     * @param  PHPExcel_Style_Borders $pStyle PHPExcel_Style_Borders
     *
     * @return array
     */
    private function _createCSSStyleBorders(\PHPExcel_Style_Borders $pStyle)
    {
        // Construct CSS
        $css = [];
        // Create CSS
        $css['border-bottom'] = $this->_createCSSStyleBorder($pStyle->getBottom());
        $css['border-top']    = $this->_createCSSStyleBorder($pStyle->getTop());
        $css['border-left']   = $this->_createCSSStyleBorder($pStyle->getLeft());
        $css['border-right']  = $this->_createCSSStyleBorder($pStyle->getRight());

        // Return
        return $css;
    }

    /**
     * Create CSS style (PHPExcel_Style_Border)
     *
     * @param  PHPExcel_Style_Border $pStyle PHPExcel_Style_Border
     *
     * @return string
     */
    private function _createCSSStyleBorder(\PHPExcel_Style_Border $pStyle)
    {
        // Create CSS
        //      $css = $this->_mapBorderStyle($pStyle->getBorderStyle()) . ' #' . $pStyle->getColor()->getRGB();
        //	Create CSS - add !important to non-none border styles for merged cells
        $borderStyle = $this->_mapBorderStyle($pStyle->getBorderStyle());
        //$css = $borderStyle . ' #' . $pStyle->getColor()->getRGB() . (($borderStyle == 'none') ? '' : ' !important');
        //@TODO 此处强制限定了 html table 的表格颜色
        if ($pStyle->getColor()->getRGB() == '000000') {
            //如果是默认颜色,则按照重置的样式进行颜色改写
            $css = $borderStyle . ' #e7e7e7' . (($borderStyle == 'none') ? '' : ' !important');
        } else {
            $css = $borderStyle . ' #' . $pStyle->getColor()->getRGB() . (($borderStyle == 'none') ? '' : ' !important');
        }

        // Return
        return $css;
    }

    /**
     * Create CSS style (PHPExcel_Style_Fill)
     *
     * @param  PHPExcel_Style_Fill $pStyle PHPExcel_Style_Fill
     *
     * @return array
     */
    private function _createCSSStyleFill(\PHPExcel_Style_Fill $pStyle)
    {
        // Construct HTML
        $css = [];
        // Create CSS
        $value                   = $pStyle->getFillType() == \PHPExcel_Style_Fill::FILL_NONE ? 'white' : '#' . $pStyle->getStartColor()->getRGB();
        $css['background-color'] = $value;

        // Return
        return $css;
    }

    /**
     * Generate HTML footer
     */
    public function generateHTMLFooter()
    {
        // Construct HTML
        $html = '';
        $html .= '  </body>' . PHP_EOL;
        //加入自定义的 javascript 代码
        $html .= "
                    <script type='text/javascript'>
                      	window.onload = function(){
                      		window.loadImg&&window.loadImg()
                        	var tagets = document.querySelectorAll('ul.navigation li span')
                        	var tables = document.querySelectorAll('table')
                        	tagets.forEach(function(v,i){
                          		v.setAttribute('data-i',i)
                          		v.addEventListener('click',function(){
                            		tables.forEach(function(t){
                              			t.style.display='none'
                            		})
                            		document.querySelectorAll('table')[this.getAttribute('data-i')].style.display='block'
                            		tagets.forEach(function(val){
                              			val.style.color='#3e3e3e'
                              			val.style.borderBottom = '2px solid #f5f5f5'
                            		})
                            		v.style.color='#80b7e7'
                            		v.style.borderBottom = '2px solid #80b7e7'
                          		})
                          	})
                        }
                    </script>
            	 " . PHP_EOL;
        if ($this->_type == 'report') {
            $html .= "<script type='text/javascript'>
						window.loadImg = loadImg
						function loadImg(){
							// 设置图片logo的位置
                          	var column_0 = document.getElementsByClassName('column0')[0].clientWidth;
                          	var column_1 = document.getElementsByClassName('column1')[0].clientWidth;
                          	var column_2 = document.getElementsByClassName('column2')[0].clientWidth;
                          	if(document.getElementById('img')){
                          		var img_w = document.getElementById('img').getAttribute('imgW');
                          		var img_h = document.getElementById('img').getAttribute('imgH');
                          		var cell_w = column_0 + column_1 + column_2 + 3;
                          		var cell_h =  49.33 * 3;
								var img_width = 0;
								var img_height = 0;
								img_w = parseInt(img_w);
								img_h = parseInt(img_h);
								//判断图片是否高度宽度为零
								if (img_h > 0 && img_w > 0) {
									if (img_w > img_h) {
										img_width  = (column_0 + 1) / 2 + (column_1 + 1) + (column_2 + 1) / 2;
										img_height = Math.round((img_width * img_h) / img_w, 2);
									} else {
										img_width  = Math.round((img_w * cell_h) / img_h, 2);
										img_height = cell_h;
									}
									var offSetX = (cell_w - img_width) / 2 ;
									var offSetY = (cell_h - img_height) / 2 - 24;
									document.getElementById('img').style.width = img_width + 'px';
									document.getElementById('img').style.height = img_height + 'px';
									document.getElementById('img').style.left = offSetX + 'px';
									document.getElementById('img').style.top = offSetY + 'px';
								}
                          	}
                        }
                   </script>" . PHP_EOL;
        } elseif ($this->_type == 'graph') {
            $html .= "<script type='text/javascript'>
						window.loadImg = loadImg
						function loadImg(){
							// 设置图片logo的位置
                          	var column_0 = document.getElementsByClassName('column0')[0].clientWidth;
                          	var column_1 = document.getElementsByClassName('column1')[0].clientWidth;
                          	var column_2 = document.getElementsByClassName('column2')[0].clientWidth;
                          	if(document.getElementById('img')){
                          		var cell_w = column_0 + column_1 + column_2 + 3;
                          		var cell_h =  49.33 * 3;
								var img_width = document.getElementById('img').style.width;
                          		var img_height = document.getElementById('img').style.height;
								var offSetX = (cell_w - parseFloat(img_width)) / 2 ;
								var offSetY = (cell_h - parseFloat(img_height)) / 2 - 24;
								document.getElementById('img').style.left = offSetX + 'px';
								document.getElementById('img').style.top = offSetY + 'px';
                          	}
                        }
                   </script>" . PHP_EOL;
        } else {
            $html .= "<script type='text/javascript'>
                        window.loadImg = loadImg
						function loadImg(){
							// 设置图片logo的位置
                          	document.getElementsByClassName('style2')[0].style.minWidth = '73px' ;
                          	document.getElementsByClassName('style2')[1].style.minWidth = '73px' ;
                          	document.getElementsByClassName('style2')[2].style.minWidth = '73px' ;
                          	if(document.getElementById('img')){
                          		var img_width = document.getElementById('img').style.width;
                          		var img_height = document.getElementById('img').style.height;
                          		var cell_w = 84 *3;
                          		var cell_h =  49.33 * 3;
                          		var offSetX = (cell_w - parseFloat(img_width)) / 2 ;
								var offSetY = (cell_h - parseFloat(img_height)) / 2 - 24;
								document.getElementById('img').style.left = offSetX + 'px';
								document.getElementById('img').style.top = offSetY + 'px';
                          		
                          	}
                        }
                   </script>" . PHP_EOL;
        }
        //$this->_type
        $html .= '</html>' . PHP_EOL;

        // Return
        return $html;
    }

    /**
     * Generate table header
     *
     * @param  PHPExcel_Worksheet $pSheet The worksheet for the table we are writing
     *
     * @return string
     * @throws PHPExcel_Writer_Exception
     */
    private function _generateTableHeader($pSheet)
    {
        $sheetIndex = $pSheet->getParent()->getIndex($pSheet);
        // Construct HTML
        $html = '';
        $html .= $this->_setMargins($pSheet);
        if (!$this->_useInlineCss) {
            $gridlines = $pSheet->getShowGridlines() ? ' gridlines' : '';
            $html .= '	<table border="0" cellpadding="0" cellspacing="0" id="sheet' . $sheetIndex . ' table" class="sheet' . $sheetIndex . $gridlines . '">' . PHP_EOL;
        } else {
            $style = isset($this->_cssStyles['table']) ? $this->_assembleCSS($this->_cssStyles['table']) : '';
            if ($this->_isPdf && $pSheet->getShowGridlines()) {
                $html .= '	<table border="1" cellpadding="1" id="sheet' . $sheetIndex . '" cellspacing="1" style="' . $style . '">' . PHP_EOL;
            } else {
                $html .= '	<table border="0" cellpadding="1" id="sheet' . $sheetIndex . '" cellspacing="0" style="' . $style . '">' . PHP_EOL;
            }
        }
        // Write <col> elements
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($pSheet->getHighestColumn()) - 1;
        $i                  = -1;
        while ($i++ < $highestColumnIndex) {
            if (!$this->_isPdf) {
                if (!$this->_useInlineCss) {
                    $html .= '		<col class="col' . $i . '">' . PHP_EOL;
                } else {
                    $style = isset($this->_cssStyles['table.sheet' . $sheetIndex . ' col.col' . $i]) ? $this->_assembleCSS($this->_cssStyles['table.sheet' . $sheetIndex . ' col.col' . $i]) : '';
                    $html .= '		<col style="' . $style . '">' . PHP_EOL;
                }
            }
        }

        // Return
        return $html;
    }

    /**
     * Generate table footer
     *
     * @throws PHPExcel_Writer_Exception
     */
    private function _generateTableFooter()
    {
        // Construct HTML
        $html = '';
        $html .= '	</table>' . PHP_EOL;

        // Return
        return $html;
    }

    /**
     * Generate row
     *
     * @param  PHPExcel_Worksheet $pSheet  PHPExcel_Worksheet
     * @param  array              $pValues Array containing cells in a row
     * @param  int                $pRow    Row number (0-based)
     *
     * @return string
     * @throws PHPExcel_Writer_Exception
     */
    private function _generateRow(\PHPExcel_Worksheet $pSheet, $pValues = null, $pRow = 0, $cellType = 'td')
    {
        if (is_array($pValues)) {
            // Construct HTML
            $html = '';
            // Sheet index
            $sheetIndex = $pSheet->getParent()->getIndex($pSheet);
            // DomPDF and breaks
            if ($this->_isPdf && count($pSheet->getBreaks()) > 0) {
                $breaks = $pSheet->getBreaks();
                // check if a break is needed before this row
                if (isset($breaks['A' . $pRow])) {
                    // close table: </table>
                    $html .= $this->_generateTableFooter();
                    // insert page break
                    $html .= '<div style="page-break-before:always" />';
                    // open table again: <table> + <col> etc.
                    $html .= $this->_generateTableHeader($pSheet);
                }
            }
            // Write row start
            if (!$this->_useInlineCss) {
                $html .= '		  <tr class="row' . $pRow . '">' . PHP_EOL;
            } else {
                $style = isset($this->_cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $pRow]) ? $this->_assembleCSS($this->_cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $pRow]) : '';
                $html .= '		  <tr style="' . $style . '">' . PHP_EOL;
            }
            // Write cells
            $colNum = 0;
            foreach ($pValues as $cellAddress) {
                $cell       = ($cellAddress > '') ? $pSheet->getCell($cellAddress) : '';
                $coordinate = \PHPExcel_Cell::stringFromColumnIndex($colNum) . ($pRow + 1);
                if (!$this->_useInlineCss) {
                    $cssClass = '';
                    $cssClass = 'column' . $colNum;
                } else {
                    $cssClass = [];
                    if ($cellType == 'th') {
                        if (isset($this->_cssStyles['table.sheet' . $sheetIndex . ' th.column' . $colNum])) {
                            $this->_cssStyles['table.sheet' . $sheetIndex . ' th.column' . $colNum];
                        }
                    } else {
                        if (isset($this->_cssStyles['table.sheet' . $sheetIndex . ' td.column' . $colNum])) {
                            $this->_cssStyles['table.sheet' . $sheetIndex . ' td.column' . $colNum];
                        }
                    }
                }
                $colSpan = 1;
                $rowSpan = 1;
                // initialize
                $cellData = '&nbsp;';
                // PHPExcel_Cell
                if ($cell instanceof \PHPExcel_Cell) {
                    $cellData = '';
                    if (is_null($cell->getParent())) {
                        $cell->attach($pSheet);
                    }
                    // Value
                    if ($cell->getValue() instanceof \PHPExcel_RichText) {
                        // Loop through rich text elements
                        $elements = $cell->getValue()->getRichTextElements();
                        foreach ($elements as $element) {
                            // Rich text start?
                            if ($element instanceof \PHPExcel_RichText_Run) {
                                $cellData .= '<span style="' . $this->_assembleCSS($this->_createCSSStyleFont($element->getFont())) . '">';
                                if ($element->getFont()->getSuperScript()) {
                                    $cellData .= '<sup>';
                                } elseif ($element->getFont()->getSubScript()) {
                                    $cellData .= '<sub>';
                                }
                            }
                            // Convert UTF8 data to PCDATA
                            $cellText = $element->getText();
                            $cellData .= htmlspecialchars($cellText);
                            if ($element instanceof \PHPExcel_RichText_Run) {
                                if ($element->getFont()->getSuperScript()) {
                                    $cellData .= '</sup>';
                                } elseif ($element->getFont()->getSubScript()) {
                                    $cellData .= '</sub>';
                                }
                                $cellData .= '</span>';
                            }
                        }
                    } else {
                        if ($this->_preCalculateFormulas) {
                            $cellData = \PHPExcel_Style_NumberFormat::toFormattedString($cell->getCalculatedValue(),
                                $pSheet->getParent()->getCellXfByIndex($cell->getXfIndex())->getNumberFormat()->getFormatCode(),
                                [
                                    $this,
                                    'formatColor',
                                ]);
                        } else {
                            $cellData = \PHPExcel_Style_NumberFormat::toFormattedString($cell->getValue(),
                                $pSheet->getParent()->getCellXfByIndex($cell->getXfIndex())->getNumberFormat()->getFormatCode(),
                                [
                                    $this,
                                    'formatColor',
                                ]);
                        }
                        $cellData = htmlspecialchars($cellData);
                        if ($pSheet->getParent()->getCellXfByIndex($cell->getXfIndex())->getFont()->getSuperScript()) {
                            $cellData = '<sup>' . $cellData . '</sup>';
                        } elseif ($pSheet->getParent()->getCellXfByIndex($cell->getXfIndex())->getFont()->getSubScript()) {
                            $cellData = '<sub>' . $cellData . '</sub>';
                        }
                    }
                    // Converts the cell content so that spaces occuring at beginning of each new line are replaced by &nbsp;
                    // Example: "  Hello\n to the world" is converted to "&nbsp;&nbsp;Hello\n&nbsp;to the world"
                    $cellData = preg_replace("/(?m)(?:^|\\G) /", '&nbsp;', $cellData);
                    // convert newline "\n" to '<br>'
                    $cellData = nl2br($cellData);
                    // Extend CSS class?
                    if (!$this->_useInlineCss) {
                        $cssClass .= ' style' . $cell->getXfIndex();
                        $cssClass .= ' ' . $cell->getDataType();
                    } else {
                        if ($cellType == 'th') {
                            if (isset($this->_cssStyles['th.style' . $cell->getXfIndex()])) {
                                $cssClass = array_merge($cssClass, $this->_cssStyles['th.style' . $cell->getXfIndex()]);
                            }
                        } else {
                            if (isset($this->_cssStyles['td.style' . $cell->getXfIndex()])) {
                                $cssClass = array_merge($cssClass, $this->_cssStyles['td.style' . $cell->getXfIndex()]);
                            }
                        }
                        // General horizontal alignment: Actual horizontal alignment depends on dataType
                        $sharedStyle = $pSheet->getParent()->getCellXfByIndex($cell->getXfIndex());
                        if ($sharedStyle->getAlignment()->getHorizontal() == PHPExcel_Style_Alignment::HORIZONTAL_GENERAL && isset($this->_cssStyles['.' . $cell->getDataType()]['text-align'])) {
                            $cssClass['text-align'] = $this->_cssStyles['.' . $cell->getDataType()]['text-align'];
                        }
                    }
                }
                // Hyperlink?
                if ($pSheet->hyperlinkExists($coordinate) && !$pSheet->getHyperlink($coordinate)->isInternal()) {
                    $cellData = '<a href="' . htmlspecialchars($pSheet->getHyperlink($coordinate)->getUrl()) . '" title="' . htmlspecialchars($pSheet->getHyperlink($coordinate)->getTooltip()) . '">' . $cellData . '</a>';
                }
                // Should the cell be written or is it swallowed by a rowspan or colspan?
                $writeCell = !(isset($this->_isSpannedCell[$pSheet->getParent()->getIndex($pSheet)][$pRow + 1][$colNum]) && $this->_isSpannedCell[$pSheet->getParent()->getIndex($pSheet)][$pRow + 1][$colNum]);
                // Colspan and Rowspan
                $colspan = 1;
                $rowspan = 1;
                if (isset($this->_isBaseCell[$pSheet->getParent()->getIndex($pSheet)][$pRow + 1][$colNum])) {
                    $spans   = $this->_isBaseCell[$pSheet->getParent()->getIndex($pSheet)][$pRow + 1][$colNum];
                    $rowSpan = $spans['rowspan'];
                    $colSpan = $spans['colspan'];
                    //	Also apply style from last cell in merge to fix borders -
                    //		relies on !important for non-none border declarations in _createCSSStyleBorder
                    $endCellCoord = PHPExcel_Cell::stringFromColumnIndex($colNum + $colSpan - 1) . ($pRow + $rowSpan);
                    if (!$this->_useInlineCss) {
                        $cssClass .= ' style' . $pSheet->getCell($endCellCoord)->getXfIndex();
                    }
                }
                // Write
                if ($writeCell) {
                    // Column start
                    $html .= '			<' . $cellType;
                    if (!$this->_useInlineCss) {
                        $html .= ' class="' . $cssClass . '"';
                    } else {
                        //** Necessary redundant code for the sake of PHPExcel_Writer_PDF **
                        // We must explicitly write the width of the <td> element because TCPDF
                        // does not recognize e.g. <col style="width:42pt">
                        $width = 0;
                        $i     = $colNum - 1;
                        $e     = $colNum + $colSpan - 1;
                        while ($i++ < $e) {
                            if (isset($this->_columnWidths[$sheetIndex][$i])) {
                                $width += $this->_columnWidths[$sheetIndex][$i];
                            }
                        }
                        $cssClass['width'] = $width . 'pt';
                        // We must also explicitly write the height of the <td> element because TCPDF
                        // does not recognize e.g. <tr style="height:50pt">
                        if (isset($this->_cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $pRow]['height'])) {
                            $height             = $this->_cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $pRow]['height'];
                            $cssClass['height'] = $height;
                        }
                        //** end of redundant code **
                        $html .= ' style="' . $this->_assembleCSS($cssClass) . '"';
                    }
                    if ($colSpan > 1) {
                        $html .= ' colspan="' . $colSpan . '"';
                    }
                    if ($rowSpan > 1) {
                        $html .= ' rowspan="' . $rowSpan . '"';
                    }
                    $html .= '>';
                    // Image?
                    $html .= $this->_writeImageInCell($pSheet, $coordinate);
                    // Chart?
                    if ($this->_includeCharts) {
                        $html .= $this->_writeChartInCell($pSheet, $coordinate);
                    }
                    // Cell data
                    $html .= $cellData;
                    // Column end
                    $html .= '</' . $cellType . '>' . PHP_EOL;
                }
                // Next column
                ++$colNum;
            }
            // Write row end
            $html .= '		  </tr>' . PHP_EOL;

            // Return
            return $html;
        } else {
            throw new \PHPExcel_Writer_Exception("Invalid parameters passed.");
        }
    }

    /**
     * Takes array where of CSS properties / values and converts to CSS string
     *
     * @param  array
     *
     * @return string
     */
    private function _assembleCSS($pValue = [])
    {
        $pairs = [];
        foreach ($pValue as $property => $value) {
            $pairs[] = $property . ':' . $value;
        }
        $string = implode('; ', $pairs);

        return $string;
    }

    /**
     * Get images root
     *
     * @return string
     */
    public function getImagesRoot()
    {
        return $this->_imagesRoot;
    }

    /**
     * Set images root
     *
     * @param  string $pValue
     *
     * @return PHPExcel_Writer_HTML
     */
    public function setImagesRoot($pValue = '.')
    {
        $this->_imagesRoot = $pValue;

        return $this;
    }

    /**
     * Get embed images
     *
     * @return boolean
     */
    public function getEmbedImages()
    {
        return $this->_embedImages;
    }

    /**
     * Set embed images
     *
     * @param string $pValue
     *
     * @return $this
     */
    public function setEmbedImages($pValue = '.')
    {
        $this->_embedImages = $pValue;

        return $this;
    }

    /**
     * Get use inline CSS?
     *
     * @return boolean
     */
    public function getUseInlineCss()
    {
        return $this->_useInlineCss;
    }

    /**
     * Set use inline CSS?
     *
     * @param  boolean $pValue
     *
     * @return PHPExcel_Writer_HTML
     */
    public function setUseInlineCss($pValue = false)
    {
        $this->_useInlineCss = $pValue;

        return $this;
    }

    /**
     * Add color to formatted string as inline style
     *
     * @param  string $pValue  Plain formatted value without color
     * @param  string $pFormat Format code
     *
     * @return string
     */
    public function formatColor($pValue, $pFormat)
    {
        // Color information, e.g. [Red] is always at the beginning
        $color   = null; // initialize
        $matches = [];
        $color_regex = '/^\\[[a-zA-Z]+\\]/';
        if (preg_match($color_regex, $pFormat, $matches)) {
            $color = str_replace('[', '', $matches[0]);
            $color = str_replace(']', '', $color);
            $color = strtolower($color);
        }
        // convert to PCDATA
        $value = htmlspecialchars($pValue);
        // color span tag
        if ($color !== null) {
            $value = '<span style="color:' . $color . '">' . $value . '</span>';
        }

        return $value;
    }

    /**
     * Calculate information about HTML colspan and rowspan which is not always the same as Excel's
     */
    private function _calculateSpans()
    {
        // Identify all cells that should be omitted in HTML due to cell merge.
        // In HTML only the upper-left cell should be written and it should have
        //   appropriate rowspan / colspan attribute
        $sheetIndexes = $this->_sheetIndex !== null ? [$this->_sheetIndex] : range(0,
            $this->_phpExcel->getSheetCount() - 1);
        foreach ($sheetIndexes as $sheetIndex) {
            $sheet = $this->_phpExcel->getSheet($sheetIndex);
            $candidateSpannedRow = [];
            // loop through all Excel merged cells
            foreach ($sheet->getMergeCells() as $cells) {
                list($cells,) = \PHPExcel_Cell::splitRange($cells);
                $first = $cells[0];
                $last  = $cells[1];
                list($fc, $fr) = \PHPExcel_Cell::coordinateFromString($first);
                $fc = \PHPExcel_Cell::columnIndexFromString($fc) - 1;
                list($lc, $lr) = PHPExcel_Cell::coordinateFromString($last);
                $lc = \PHPExcel_Cell::columnIndexFromString($lc) - 1;
                // loop through the individual cells in the individual merge
                $r = $fr - 1;
                while ($r++ < $lr) {
                    // also, flag this row as a HTML row that is candidate to be omitted
                    $candidateSpannedRow[$r] = $r;
                    $c = $fc - 1;
                    while ($c++ < $lc) {
                        if (!($c == $fc && $r == $fr)) {
                            // not the upper-left cell (should not be written in HTML)
                            $this->_isSpannedCell[$sheetIndex][$r][$c] = [
                                'baseCell' => [
                                    $fr,
                                    $fc,
                                ],
                            ];
                        } else {
                            // upper-left is the base cell that should hold the colspan/rowspan attribute
                            $this->_isBaseCell[$sheetIndex][$r][$c] = [
                                'xlrowspan' => $lr - $fr + 1, // Excel rowspan
                                'rowspan'   => $lr - $fr + 1, // HTML rowspan, value may change
                                'xlcolspan' => $lc - $fc + 1, // Excel colspan
                                'colspan'   => $lc - $fc + 1, // HTML colspan, value may change
                            ];
                        }
                    }
                }
            }
            // Identify which rows should be omitted in HTML. These are the rows where all the cells
            //   participate in a merge and the where base cells are somewhere above.
            $countColumns = \PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
            foreach ($candidateSpannedRow as $rowIndex) {
                if (isset($this->_isSpannedCell[$sheetIndex][$rowIndex])) {
                    if (count($this->_isSpannedCell[$sheetIndex][$rowIndex]) == $countColumns) {
                        $this->_isSpannedRow[$sheetIndex][$rowIndex] = $rowIndex;
                    };
                }
            }
            // For each of the omitted rows we found above, the affected rowspans should be subtracted by 1
            if (isset($this->_isSpannedRow[$sheetIndex])) {
                foreach ($this->_isSpannedRow[$sheetIndex] as $rowIndex) {
                    $adjustedBaseCells = [];
                    $c                 = -1;
                    $e                 = $countColumns - 1;
                    while ($c++ < $e) {
                        $baseCell = $this->_isSpannedCell[$sheetIndex][$rowIndex][$c]['baseCell'];
                        if (!in_array($baseCell, $adjustedBaseCells)) {
                            // subtract rowspan by 1
                            --$this->_isBaseCell[$sheetIndex][$baseCell[0]][$baseCell[1]]['rowspan'];
                            $adjustedBaseCells[] = $baseCell;
                        }
                    }
                }
            }

            // TODO: Same for columns
        }
        // We have calculated the spans
        $this->_spansAreCalculated = true;
    }

    private function _setMargins(PHPExcel_Worksheet $pSheet)
    {
        $htmlPage = '@page { ';
        $htmlBody = 'body { ';
        $left = \PHPExcel_Shared_String::FormatNumber($pSheet->getPageMargins()->getLeft()) . 'in; ';
        $htmlPage .= 'left-margin: ' . $left;
        $htmlBody .= 'left-margin: ' . $left;
        $right = \PHPExcel_Shared_String::FormatNumber($pSheet->getPageMargins()->getRight()) . 'in; ';
        $htmlPage .= 'right-margin: ' . $right;
        $htmlBody .= 'right-margin: ' . $right;
        $top = \PHPExcel_Shared_String::FormatNumber($pSheet->getPageMargins()->getTop()) . 'in; ';
        $htmlPage .= 'top-margin: ' . $top;
        $htmlBody .= 'top-margin: ' . $top;
        $bottom = \PHPExcel_Shared_String::FormatNumber($pSheet->getPageMargins()->getBottom()) . 'in; ';
        $htmlPage .= 'bottom-margin: ' . $bottom;
        $htmlBody .= 'bottom-margin: ' . $bottom;
        $htmlPage .= "}\n";
        $htmlBody .= "}\n";

        return "<style>\n" . $htmlPage . $htmlBody . "</style>\n";
    }

    //public function setPath($path){
    //	$fileName = new PHPExcel_Worksheet_Drawing;
    //	$fileName->setPath($path);
    //
    //}
    /**
     * 设置图片的宽高
     *
     * @param $imageData
     *
     * @return string|void
     */
    public function setImgWidthAndHeight($imageData)
    {
        $imgData = str_replace('.' . APPLICATION_ROOT . 'public/', APPLICATION_ROOT . 'public/', $imageData);
        // 获得图片的宽高
        //计算出上传图片宽高
        $img     = getimagesize($imgData);
        $image_w = $img['0'];
        $image_h = $img['1'];
        //判断图片是否高度宽度为零
        if ($image_h <= 0 || $image_w <= 0) {
            return;
        }
        if ($image_w > $image_h) {
            $width  = 157;
            $height = round((157 * $image_h) / $image_w, 2);
        } else {
            $width  = round(($image_w * 147) / $image_h, 2);
            $height = 147;
        }
        $html = '<img id="img" style="position: absolute; z-index: 1;  width: ' . $width . 'px; height: ' . $height . 'px;"  imgW="' . $image_w . '" imgH="' . $image_h . '"  src="' . $imageData . '" border="0" />';

        return $html;
    }

}