<?php
/**
 * User: yongli
 * Date: 17/4/26
 * Time: 14:37
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
use YP_PHPExclHtml as PHPExclHtml;

/**
 *
 * Class YP_PHPExcel excel封装类
 *
 */
class YP_PHPExcel
{

    /**
     *  A object of phpExcel
     *
     * @var PHPExcel
     */
    private $excelObj;

    /**
     * 默认下载名称
     *
     * @var string
     */
    private $fileName = 'download.xlsx';

    /**
     * 表小标
     *
     * @var int
     */
    private $sheet = 0;

    /**
     * 列
     *
     * @var int
     */
    private $column = 0;

    /**
     * 头部标题开始行
     *
     * @var int
     */
    private $headRow = 6;

    /**
     * 数据开始行
     *
     * @var int
     */
    private $dataRow = 0;

    /**
     * 宽度
     *
     * @var int
     */
    private $width = 20;

    /**
     * 行高
     *
     * @var int
     */
    private $height = 20;

    /**
     * 图片插入单元格
     *
     * @var string
     */
    private $imgCell = 'B2';

    /**
     * 图片相对 x 位置
     *
     * @var int
     */
    private $imgPositionX = 0;

    /**
     * 图片相对 y 位置
     *
     * @var int
     */
    private $imgPositionY = 0;

    /**
     * YP_PHPExcel constructor.
     */
    public function __construct()
    {
        $this->excelObj = new \PHPExcel();
        $this->excelObj->setActiveSheetIndex($this->sheet)->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $this->excelObj->setActiveSheetIndex($this->sheet)->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $this->excelObj->getDefaultStyle()->getFont()->setName('微软雅黑');
        $this->excelObj->getDefaultStyle()->getFont()->setSize(9);
    }

    /**
     * 获取主体数据
     *
     * @param        $excelUrl 读取的excel的位置
     * @param int|定位 $row      定位 从第几行开始读取
     *
     * @return mixed
     */
    public function getBodyData($excelUrl, $row = 1)
    {
        $inputFileType = \PHPExcel_IOFactory::identify($excelUrl);
        $reader        = \PHPExcel_IOFactory::createReader($inputFileType);
        $PHPExcel      = $reader->load($excelUrl); // 载入excel文件
        $sheet         = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow    = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数字母
        $cols          = \PHPExcel_Cell::columnIndexFromString($highestColumm); //获取总列数数字
        //循环读取每个单元格的数据
        $data = [];
        for ($row; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumm . $row, null, true, false);
            $data[]  = $rowData[0];
        }
        $result = $this->unsetNull($this->unsetNull($data));
        if ($result) {
            $returnData = [
                'rowNum' => $cols,
                'data'   => $result,
            ];

            return $returnData;
        }

        return $result;
    }

    /**
     * 设置头部标题开始行
     *
     * @param $headRow
     */
    public function setHeadRow($headRow)
    {
        $this->headRow = $headRow;
    }

    /**
     * 设置下载时文件名
     *
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName . '.xlsx';
    }

    /**
     * 设置sheet和标题
     *
     * @param int    $num
     * @param string $title
     *
     * @throws PHPExcel_Exception
     */
    public function setSheet($num = 0, $title = 'sheet')
    {
        if ($num > 0) {
            $objWorksheet1 = $this->excelObj->createSheet();
            $objWorksheet1->setTitle($title);
            $this->excelObj->setActiveSheetIndex($num);
        } else {
            $this->excelObj->getActiveSheet()->setTitle($title);
        }
        $this->sheet = $num;
    }

    /**
     * 设置行高
     *
     * @param $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    public function getActiveSheet()
    {
        return $this->excelObj->getActiveSheet();
    }

    /**
     * 添加图片logo
     *
     * @param        $url
     * @param string $imageName
     *
     * @throws PHPExcel_Exception
     */
    public function setImage($url, $imageName = 'Logo')
    {
        //baozhiying     add new
        // 1英寸   约为96.52像素
        // 1磅   约为1.34039像素
        // 经过多次测试，在excel中图片的大小是更具图片的高度suo来自适应的，所以我们需要动态的更具图片自身的高度来计算出，
        // 应该给出图片的高度
        // 经过计算 图片应给出宽高250*105
        $column       = [
            'A',
            'B',
            'C',
        ];
        $columnLength = [];
        $defaultFont  = $this->excelObj->getDefaultStyle()->getFont();
        $cl_width     = '';
        if ($this->excelObj->getActiveSheet()->getColumnDimensions()) {
            foreach ($this->excelObj->getActiveSheet()->getColumnDimensions() as $key => $columnDimension) {
                if (($width = \PHPExcel_Shared_Drawing::cellDimensionToPixels($columnDimension->getWidth(),
                        $defaultFont)) >= 0 && in_array($key, $column)
                ) {
                    $cl_width           = $cl_width + ($width);
                    $columnLength[$key] = $width;

                }
            }
        } else {
            $column = [
                'A',
                'B',
                'C',
                'D',
                'E',
                'F',
            ];
            $sheet  = $this->excelObj->setActiveSheetIndex($this->sheet);
            foreach ($column as $col) {
                $sheet->getColumnDimension($col)->setWidth(20);
            }
            foreach ($this->excelObj->getActiveSheet()->getColumnDimensions() as $key => $columnDimension) {
                if (($width = \PHPExcel_Shared_Drawing::cellDimensionToPixels($columnDimension->getWidth(),
                        $defaultFont)) >= 0 && in_array($key, $column)
                ) {
                    $cl_width           = $cl_width + ($width);
                    $columnLength[$key] = $width;
                }
            }
        }
        //判断文件是否存在
        if (!file_exists($url)) {
            return;
        }
        //计算出上传图片宽高
        $img     = getimagesize($url);
        $image_w = $img['0'];
        $image_h = $img['1'];
        //判断图片是否高度宽度为零
        if ($image_h <= 0 || $image_w <= 0) {
            return;
        }
        if ($image_w > $image_h) {
            $width  = 250;
            $height = round((250 * $image_h) / $image_w, 2);
        } else {
            $width  = round(($image_w * 105) / $image_h, 2);
            $height = 105;
        }
        /**
         * 注意 Excl 在设置图片的时候, Excl 所设定的 浮动 X,Y轴是不能超过 setCoordinates 所设置的表格的宽高
         */
        //开始动态计算 setCoordinates
        $formWidth  = $columnLength['B'];
        $formHeight = \PHPExcel_Shared_Drawing::pointsToPixels(37);
        //校对 X 轴
        if ($width < $formWidth) {
            $xFix               = 'B';
            $this->imgPositionX = ($columnLength['B'] - $width) / 2;
        } else {
            $xFix               = 'A';
            $this->imgPositionX = ($cl_width - ($width) + 4) / 2;
        }
        //校对 Y 轴
        if ($height < $formHeight) {
            $yFix               = '3';
            $this->imgPositionY = (\PHPExcel_Shared_Drawing::pointsToPixels(37) - ($height)) / 2;
        } else {
            $yFix               = '2';
            $this->imgPositionY = (\PHPExcel_Shared_Drawing::pointsToPixels(37 * 3) - ($height) + 2) / 2;
        }
        $this->imgPositionX = \PHPExcel_Shared_Drawing::pointsToPixels($this->imgPositionX);
        $this->imgCell      = $xFix . $yFix;
        $this->imgPositionY = (\PHPExcel_Shared_Drawing::pointsToPixels(37) * 3 - ($height) + 2) / 2;
        $objDrawing         = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName($imageName);
        $objDrawing->setPath($url);
        $objDrawing->setWidth($width);
        $objDrawing->setHeight($height);
        $objDrawing->setCoordinates($this->imgCell);
        $objDrawing->setOffsetX($this->imgPositionX);
        $objDrawing->setOffsetY($this->imgPositionY);
        $objDrawing->setWorksheet($this->excelObj->getActiveSheet());
        $this->imgPositionX = 0;
        $this->imgPositionY = 0;
    }

    /**
     * 获取excel列的下标
     *
     * @param array $data
     * @param array $headerData
     * @param int   $reportType
     *
     * @return array ['A','B']
     */
    public function getExcelCol($data, $headerData = [], $reportType = 1)
    {
        $keys = $this->getCharByNumber($data, $headerData, $reportType);

        return $keys;
    }

    /**
     * 写入Excel数据
     *
     * @param array $data       二维数组
     * @param array $headerData 需要替换的数组
     * @param int   $reportType 报表类型
     *
     * @throws PHPExcel_Exception
     */
    public function dataExcel($data, $headerData = [], $reportType = 1)
    {
        // 获得列号(A,B,C,D....)
        $keys = $this->getCharByNumber($data, $headerData, $reportType);
        // 1:普通报表头部处理 其他:订阅报表表头部处理
        $reportType == 1 ? $this->createHeader($headerData, $keys) : $this->createReportHeader($headerData, $keys);
        if (!empty($data)) {
            $dataRow = $this->headRow + 1;
            // 将数据写入单元格
            foreach ($data as $i => $vo) {
                //$j 控制列
                $j = 0;
                foreach ($vo as $key => $item) {
                    // 设置数据格式
                    // $this->excelObj->setActiveSheetIndex($this->sheet)->setCellValue($keys[$j] . $this->dataRow, $item);
                    $this->excelObj->setActiveSheetIndex($this->sheet)->setCellValueExplicit($keys[$j] . $this->dataRow,
                        $item, \PHPExcel_Cell_DataType::TYPE_STRING);
                    $j++;
                }
                $this->dataRow++;
            }
            $this->setDataCellStyle($keys, $dataRow, $this->dataRow);
        }
        // 设置头部区域格式
        $this->createBlankCell($keys, $this->headRow);
        $this->dataRow = 0;
    }

    /**
     * 输出报表到浏览器
     *
     * @param $name
     *
     * @throws PHPExcel_Reader_Exception
     */
    public function outPutExcel($name)
    {
        $this->fileName = $name ? $name : $this->fileName;
        $objWriter      = \PHPExcel_IOFactory::createWriter($this->excelObj, 'Excel2007');
        //输出到临时缓冲区  提供下载
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $this->fileName . '"');
        header("Content-Transfer-Encoding: binary");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $objWriter->save('php://output');

        return;
    }

    /**
     * 将excel保存到本地
     *
     * @param $path  保存文件的路径
     *
     * @throws PHPExcel_Reader_Exception
     */
    public function saveExcelFile($path)
    {
        $objWriter = \PHPExcel_IOFactory::createWriter($this->excelObj, 'Excel2007');
        $objWriter->save($path);
    }

    /**
     * 预览excel
     *
     * @throws PHPExcel_Writer_Exception
     */
    public function outPutHtmlExcel()
    {
        $exclHtml = new PHPExclHtml($this->excelObj, 'exclHtml');
        $html     = $exclHtml->saveHtml();

        return $html;
    }

    /**
     * 创建头部(普通报表)
     *
     * @param $header
     * @param $keys
     *
     * @return array
     * @throws PHPExcel_Exception
     */
    private function createHeader($header, $keys)
    {
        if (empty($header)) {
            return [];
        }
        //组合数据
        $arr = [];
        foreach ($header as $key => $value) {
            $arr[$keys[$key]] = $value;
        }
        // 获得数据开始行
        $this->dataRow = $this->headRow + 1;
        $sheet         = $this->excelObj->setActiveSheetIndex($this->sheet);
        foreach ($arr as $vKey => $vValue) {
            // 设置标题区域格式
            $sheet->setCellValue($vKey . $this->headRow, $vValue);
        }
        $this->setHeadCellStyle(array_keys($arr), $this->headRow, $this->headRow);
        unset($arr, $header, $keys);
    }

    /**
     * 创建头部(普通报表)
     *
     * @param $header
     * @param $keys
     *
     * @return array
     * @throws PHPExcel_Exception
     */
    private function createReportHeader($header, $keys)
    {
        $status = (empty($header['row']) & isset($header['row'])) && (empty($header['col']) & isset($header['col']));
        if ($status) {
            return [];
        }
        // 获得纵向标题的数量
        $portraitTitleNum = count($header['col'][0]);
        // 获得横向数据需要的列名
        $rowColNames  = array_slice($keys, $portraitTitleNum);
        $this->column = count($rowColNames);
        //组合数据
        $arr     = [];
        $headers = array_values($header['row']);
        $oldRow  = 1;
        $bb      = 1;
        foreach ($headers as $key => $vo) {
            $voNum = count($vo);
            $oldRow *= $voNum;
            $arr[] = $this->CombinedHeader($vo, $rowColNames, $key + 1, $oldRow, $bb);
            $bb *= $voNum;
        }
        $count = count($arr);
        // 获得数据开始行
        $this->dataRow = $count + $this->headRow;
        // 获得纵向数据需要的列名
        $portraitColNames = array_slice($keys, 0, $portraitTitleNum);
        $colData          = $this->getColData($portraitColNames, $count, $header['col'][0]);
        $cellData         = [];
        $row              = 0;
        $tArr             = [];
        foreach ($arr as $k => $v) {
            $row = $k + $this->headRow;
            foreach ($v as $vKey => $vValue) {
                if ($k == 0) {
                    $tArr[] = $vKey;
                }
                $cellData[$vKey . $row] = $vValue;
            }
        }
        $this->setHeadCellStyle($tArr, $this->headRow, $row);
        // 合并头部数据
        $cellData = array_merge($cellData, $colData);
        $sheet    = $this->excelObj->setActiveSheetIndex($this->sheet);
        // 将头部数据写入单元格中
        foreach ($cellData as $col => $value) {
            $sheet->setCellValue($col, $value);
        }
        unset($reportType, $arr, $header, $oldRow, $bb, $keys);
    }

    /**
     * 设置数据区域格式
     *
     * @param $celName
     * @param $startRow
     * @param $endRow
     *
     * @internal param $row
     */
    private function setDataCellStyle($celName, $startRow, $endRow)
    {
        $range      = reset($celName) . $startRow . ':' . end($celName) . ($endRow - 1);
        $styleArray = [
            'borders' => [
                'allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => ['argb' => '00000000'],
                ],
            ],
        ];
        $sheet      = $this->excelObj->setActiveSheetIndex($this->sheet);
        foreach ($celName as $value) {
            $sheet->getColumnDimension($value)->setWidth($this->width);
        }
        for ($i = $startRow; $i < $endRow; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(24);
        }
        $objStyle = $sheet->getStyle($range);
        $objStyle->applyFromArray($styleArray);
    }

    /**
     * 设置数据区域格式
     *
     * @param $celName  列名
     * @param $startRow
     * @param $endRow
     *
     * @internal param 行号 $row
     */
    private function setHeadCellStyle($celName, $startRow, $endRow)
    {
        $range      = reset($celName) . $startRow . ':' . end($celName) . $endRow;
        $styleArray = [
            'font'    => [
                'name'  => '微软雅黑',
                'bold'  => true,
                'size'  => '9',
                'color' => ['rgb' => 'ffffff'],
            ],
            'borders' => [
                'allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'fill'    => [
                'type'       => \PHPExcel_Style_Fill::FILL_SOLID,
                'startcolor' => ['rgb' => '4196CB'],
                'endcolor'   => ['rgb' => '4196CB'],
            ],
        ];
        $objStyle   = $this->excelObj->setActiveSheetIndex($this->sheet)->getStyle($range);
        $sheet      = $this->excelObj->setActiveSheetIndex($this->sheet);
        foreach ($celName as $value) {
            $sheet->getColumnDimension($value)->setWidth($this->width);
        }
        for ($i = $startRow; $i <= $endRow; $i++) {
            $sheet->getRowDimension($i)->setRowHeight($this->height);
        }
        $objStyle->applyFromArray($styleArray);
    }

    /**
     * 设置头部空白区域格式
     *
     * @param $celName 列名
     * @param $row     行号
     *
     * @throws PHPExcel_Exception
     */
    private function createBlankCell($celName, $row)
    {
        $range      = reset($celName) . '1:' . end($celName) . ($row - 1);
        $styleArray = [
            'font'      => [
                'name'  => '微软雅黑',
                'bold'  => true,
                'size'  => '9',
                'color' => ['rgb' => '373737'],
            ],
            'alignment' => [
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                'vertical'   => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ],
            'borders'   => [
                'allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => ['rgb' => 'ffffff'],
                ],
            ],
        ];
        $this->excelObj->setActiveSheetIndex($this->sheet)->getStyle($range)->applyFromArray($styleArray);
        $sheet = $this->excelObj->setActiveSheetIndex($this->sheet);
        $sheet->getRowDimension(1)->setRowHeight(12);
        $sheet->getRowDimension(5)->setRowHeight(12);
        $row = $row - 1;
        // 设置行高
        for ($i = 2; $i < $row; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(37);
        }
        //foreach ($celName as $value) {
        //    $sheet->getColumnDimension($value)->setWidth($this->width);
        //}
        unset($cellNameStatus, $cellNameStatus1, $cellNameStatus2);
    }

    /**
     * 合并excel头部
     *
     * @param $vo        标题数据
     * @param $arrColumn 所有列名称
     * @param $nowRow    当前行号
     * @param $oldRow
     * @param $bb
     *
     * @return array
     */
    private function CombinedHeader($vo, $arrColumn, $nowRow, $oldRow, $bb)
    {
        $num    = $this->column / $oldRow;
        $result = [];
        $data   = $this->CombinedHeaderChild($vo, $num);
        if ($nowRow == 1) {
            $result = array_merge($result, $data);
        } else {
            for ($i = 1; $i <= $bb; $i++) {
                $result = array_merge($result, $data);
            }
        }
        $quest = array_combine($arrColumn, $result);
        unset($num, $result, $data);

        return $quest;
    }

    /**
     * 组合头部数据--最小级处理
     *
     * @param $vo
     * @param $num
     *
     * @return array
     */
    private function CombinedHeaderChild($vo, $num)
    {
        $result = [];
        foreach ($vo as $key => $value) {
            for ($f = 0; $f < $num; $f++) {
                $result[] = $vo[$key];
            }
        }

        return $result;
    }

    /**
     * 合并单元格
     *
     * @param $startCell
     * @param $endCell
     *
     * @throws PHPExcel_Exception
     */
    public function mergeCells($startCell, $endCell)
    {
        $this->excelObj->setActiveSheetIndex($this->sheet)->mergeCells($startCell . ':' . $endCell);
    }

    /**
     * 获取总列数
     *
     * @param $header
     * @param $reportType 报表类型
     */
    private function rowNum($header, $reportType)
    {
        if (empty($header)) {
            $this->column = 0;

            return;
        }
        $num = 1;
        if ($reportType) {
            $this->column = count($header);
        } else {
            if (!empty($header['row'])) {
                foreach ($header['row'] as $key => $value) {
                    $valueNum = count($value);
                    $num *= $valueNum;
                }
            }
            $colNumber    = isset($header['col'][0]) ? count($header['col'][0]) : 0;
            $this->column = $colNumber + $num;
        }
        unset($valueNum, $num, $colNum);
    }

    /**
     * 根据总数,返回列数组
     *
     * @param $data
     * @param $header
     * @param $reportType 报表类型
     *
     * @return array
     */
    private function getCharByNumber($data, $header, $reportType)
    {
        $this->rowNum($header, $reportType);
        $number = isset($data[0]) ? count($data[0]) : 0;
        $count  = empty($this->column) ? $number : $this->column;

        return $this->getChar($count);
    }

    /**
     * 更加数据获得字符
     *
     * @param $colNumber 列数
     *
     * @return array
     */
    private function getChar($colNumber)
    {
        $keys = [];
        $ch = [
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
            'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ',
            'CA','CB','CC','CD','CE','CF','CG','CH','CI','CJ','CK','CL','CM','CN','CO','CP','CQ','CR','CS','CT','CU','CV','CW','CX','CY','CZ',
            'DA','DB','DC','DD','DE','DF','DG','DH','DI','DJ','DK','DL','DM','DN','DO','DP','DQ','DR','DS','DT','DU','DV','DW','DX','DY','DZ',
            'EA','EB','EC','ED','EE','EF','EG','EH','EI','EJ','EK','EL','EM','EN','EO','EP','EQ','ER','ES','ET','EU','EV','EW','EX','EY','EZ',
        ];
        for ($number    = 1; $number <= $colNumber; $number++) {
            $divisor    = intval($number / 26.01);
            $char       = chr(64 + $number % 26);
            $charNum    = ($char == '@') ? 'Z' : $char;
            if($divisor < 27){
                $charNumber = chr(64 + $divisor);
                $char       = $divisor == 0 ? $charNum : $charNumber . $charNum;
            } else {
                $charNumber = $divisor - 27;
                $char       = $ch[$charNumber] . $charNum;
            }
            $keys[]     = $char;
        }

        return $keys;
    }

    /**
     * 多维数组null替换为 ''
     *
     * @param $result
     *
     * @return mixed
     */
    private function unsetNull($result)
    {
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $status      = is_array($value) && !empty($value);
                $statusValue = is_null($value) || empty($value);
                if ($status) {
                    $result[$key] = $this->unsetNull($value);
                }
                if ($statusValue) {
                    unset($result[$key]);
                }
            }
            unset($status, $statusValue);
        }

        return $result;
    }

    /**
     * 设置某种特殊的单元格值
     *
     * @param $cellName  单元格名称
     * @param $value     值
     */
    public function setCellValue($cellName, $value)
    {
        $this->excelObj->setActiveSheetIndex($this->sheet)->setCellValue($cellName, $value);
    }

    /**
     * 获得纵向数据
     *
     * @param $celName
     * @param $Num
     * @param $data
     *
     * @return array
     */
    private function getColData($celName, $Num, $data)
    {
        $celNameArray = [];
        $endNum       = $this->headRow + $Num - 1;
        $number       = 0;
        foreach ($data as $key => $value) {
            for ($i = 0; $i < $Num; $i++) {
                $number = $this->headRow + $i;
            }
            // 合并单元格
            $this->mergeCells($celName[$key] . $this->headRow, $celName[$key] . $endNum);
            // 设置数据
            $celNameArray[$celName[$key] . $this->headRow] = $value;
        }
        $this->setHeadCellStyle($celName, $this->headRow, $number);
        unset($number, $celName);

        return $celNameArray;
    }

    /**
     * 获取excel对象
     * @return PHPExcel
     */
    public function getObject()
    {
        return $this->excelObj;
    }
}