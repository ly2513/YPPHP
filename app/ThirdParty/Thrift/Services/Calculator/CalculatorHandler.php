<?php
/**
 * User: yongli
 * Date: 17/9/27
 * Time: 18:41
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace Services\Calculator;

class CalculatorHandler implements CalculatorIf {

    protected $log = [];

    public function ping()
    {
        error_log("ping()");
    }

    public function add($num1, $num2)
    {
        error_log("add({$num1}, {$num2})");

        return $num1 + $num2;
    }

    public function calculate($logid, Work $w)
    {
        error_log("calculate({$logid}, {{$w->op}, {$w->num1}, {$w->num2}})");
        switch ($w->op) {
            case Operation::ADD:
                $val = $w->num1 + $w->num2;
                break;
            case Operation::SUBTRACT:
                $val = $w->num1 - $w->num2;
                break;
            case Operation::MULTIPLY:
                $val = $w->num1 * $w->num2;
                break;
            case Operation::DIVIDE:
                if ($w->num2 == 0) {
                    $io         = new InvalidOperation();
                    $io->whatOp = $w->op;
                    $io->why    = "Cannot divide by 0";
                    throw $io;
                }
                $val = $w->num1 / $w->num2;
                break;
            default:
                $io         = new InvalidOperation();
                $io->whatOp = $w->op;
                $io->why    = "Invalid Operation";
                throw $io;
        }
        $log               = new SharedStruct();
        $log->key          = $logid;
        $log->value        = (string) $val;
        $this->log[$logid] = $log;

        return $val;
    }

    public function getStruct($key)
    {
        error_log("getStruct({$key})");
        // This actually doesn't work because the PHP interpreter is
        // restarted for every request.
        //return $this->log[$key];
        return new SharedStruct(["key" => $key, "value" => "PHP is stateless!"]);
    }

    public function zip()
    {
        error_log("zip()");
    }
}
