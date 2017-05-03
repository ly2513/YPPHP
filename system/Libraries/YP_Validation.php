<?php
/**
 * User: yongli
 * Date: 17/4/21
 * Time: 10:58
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Libraries;

use YP\Core\YP_IncomingRequest as IncomingRequest;

class YP_Validation
{
    /**
     * 加载带有验证功能的文件。
     *
     * @var array
     */
    protected $ruleSetFiles;

    /**
     * 存放验证实例
     *
     * @var array
     */
    protected $ruleSetInstances = [];

    /**
     * 存储校验$data的规则
     *
     * @var array
     */
    protected $rules = [];

    /**
     * 应验证的数据，其中“键”是别名，具有值。
     *
     * @var array
     */
    protected $data = [];

    /**
     * 验证期间产生的任何错误。关键是别名，“值”是信息。
     *
     * @var array
     */
    protected $errors = [];

    /**
     * 在验证期间存储自定义错误消息
     *
     * @var array
     */
    protected $customErrors = [];

    /**
     * @var \Config\Validation
     */
    protected $config;

    /**
     * 保存视图数据
     *
     * @var
     */
    protected $saveData;

    /**
     * 视图数据
     *
     * @var
     */
    protected $viewData;

    /**
     * YP_Validation constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->ruleSetFiles = $config->ruleSets;
        $this->config       = $config;
    }

    /**
     * 运行验证过程，返回TRUE / false确定是否成功验证。
     *
     * @param array|null  $data  校验的数组
     * @param string|null $group 规则组
     *
     * @return bool
     */
    public function run(array $data = null, string $group = null): bool
    {
        $data = $data ?? $this->data;
        $this->loadRuleSets();
        $this->loadRuleGroup($group);
        // 如果有些字段设置的验证规则,需要通过运行每一个规则进行相关校验
        foreach ($this->rules as $rField => $ruleString) {
            // 将规则是字符串,将进行拆分数组
            $rules = $ruleString;
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }
            $this->processRules($rField, $data[$rField] ?? null, $rules, $data);
        }

        return count($this->errors) > 0 ? false : true;
    }

    /**
     * 运行验证某个值，返回TRUE或false确定是否成功验证。
     *
     * @param        $value  验证的值
     * @param string $rule   验证规则
     * @param array  $errors 错误
     *
     * @return bool
     */
    public function check($value, string $rule, array $errors = []): bool
    {
        $this->reset();
        $this->setRule('check', $rule, $errors);

        return $this->run([
            'check' => $value
        ]);
    }

    /**
     * 运行所有的校验规则及验证字段,或所有的都被处理,如果有错误将存放到$this->error中,
     * 并检查下一个校验参数,这样就可以收集到所有的错误
     *
     * @param string $field
     * @param        $value
     * @param null   $rules
     * @param array  $data
     *
     * @return bool
     */
    protected function processRules(string $field, $value, $rules = null, array $data)
    {
        foreach ($rules as $rule) {
            // 检测参数是否为合法的可调用结构
            $callable = is_callable($rule);
            $passed   = false;
            // 规则可以包含最大长度为5的参数,即最多存放5个参数
            $param = false;
            if (!$callable && preg_match('/(.*?)\[(.*)\]/', $rule, $match)) {
                $rule  = $match[1];
                $param = $match[2];
            }
            // 规则中自定义错误的占位符
            $error = null;
            // 如果存在调用函数,将在这进行调用
            if ($callable) {
                $passed = $param === false ? $rule($value) : $rule($value, $param, $data);
            } else {
                $found = false;
                // 检查我们的规则集
                foreach ($this->ruleSetInstances as $set) {
                    if (!method_exists($set, $rule)) {
                        continue;
                    }
                    $found  = true;
                    $passed = $param === false ? $set->$rule($value, $error) : $set->$rule($value, $param, $data, $error);
                    break;
                }
                // 如果规则在任何地方没有找到，我们应该抛出一个异常，使开发人员可以找到它
                if (!$found) {
                    throw new \InvalidArgumentException(lang('Validation.ruleNotFound'));
                }
            }
            // 如果错误信息不存在,就设置错误信息
            if ($passed === false) {
                $this->errors[$field] = is_null($error) ? $this->getErrorMessage($rule, $field) : $error;

                return false;
            }
        }

        return true;
    }

    /**
     * 接收请求对象中的数组的POST参数
     *
     * @param IncomingRequest $request
     *
     * @return YP_Validation
     */
    public function withRequest(IncomingRequest $request): self
    {
        $this->data = $request->getPost() ?? [];

        return $this;
    }

    /**
     * 设置校验规则
     * 例如:
     *      [
     *        'rule' => 'message',
     *        'rule' => 'message'
     *      ]
     *
     * @param string $field
     * @param string $rule
     * @param array  $errors
     *
     * @return $this
     */
    public function setRule(string $field, string $rule, array $errors = [])
    {
        $this->rules[$field] = $rule;
        $this->customErrors  = array_merge($this->customErrors, [
            $field => $errors
        ]);

        return $this;
    }

    /**
     * 存储用于校验用的规则。
     * 例如规则:
     *      [
     *        'field' => 'rule1|rule2'
     *      ]
     * 例如错误提示信息:
     *      [
     *        'field' => [
     *            'rule' => 'message',
     *            'rule' => 'message
     *        ],
     *     ]
     *
     * @param array $rules
     * @param array $errors
     *
     * @return YP_Validation
     */
    public function setRules(array $rules, array $errors = []): self
    {
        $this->rules = $rules;
        if (!empty($errors)) {
            $this->customErrors = $errors;
        }

        return $this;
    }

    /**
     * 返回当前定义的所有规则
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * 检查键字段的规则是否已设置或不设置。
     *
     * @param string $field
     *
     * @return bool
     */
    public function hasRule(string $field): bool
    {
        return array_key_exists($field, $this->rules);
    }

    /**
     * 获得规则组
     *
     * @param string $group
     *
     * @return array
     */
    public function getRuleGroup(string $group): array
    {
        if (!isset($this->config->$group)) {
            throw new \InvalidArgumentException(sprintf(lang('Validation.groupNotFound'), $group));
        }
        if (!is_array($this->config->$group)) {
            throw new \InvalidArgumentException(sprintf(lang('Validation.groupNotArray'), $group));
        }

        return $this->config->$group;
    }

    /**
     * 设置规则组
     *
     * @param string $group
     */
    public function setRuleGroup(string $group)
    {
        $rules       = $this->getRuleGroup($group);
        $this->rules = $rules;
        $errorName   = $group . '_errors';
        if (isset($this->config->$errorName)) {
            $this->customErrors = $this->config->$errorName;
        }
    }

    /**
     * 将错误信息渲染到定义的模板中
     *
     * @param string $template
     *
     * @return string
     */
    public function listErrors(string $template = 'list'): string
    {
        if (!array_key_exists($template, $this->config->templates)) {
            throw new \InvalidArgumentException($template . ' is not a valid Validation template.');
        }

        return $this->setVar('errors', $this->getErrors())->render($this->config->templates[$template]);
    }

    /**
     * 显示在模板视图中定义的格式化html中的单个错误。
     *
     * @param string $field
     * @param string $template
     *
     * @return string
     */
    public function showError(string $field, string $template = 'single'): string
    {
        if (!array_key_exists($field, $this->errors)) {
            return '';
        }
        if (!array_key_exists($template, $this->config->templates)) {
            throw new \InvalidArgumentException($template . ' is not a valid Validation template.');
        }

        return $this->setVar('error', $this->getError($field))->render($this->config->templates[$template]);
    }

    /**
     * 将所有的规则类，已在配置、验证和存储在本地，我们可以使用它们的定义
     */
    protected function loadRuleSets()
    {
        if (empty($this->ruleSetFiles)) {
            throw new \RuntimeException(lang('Validation.noRuleSets'));
        }
        foreach ($this->ruleSetFiles as $file) {
            $this->ruleSetInstances[] = new $file();
        }
    }

    /**
     * 将自定义规则组（如果设置）加载到当前规则中
     *
     * 规则可以预先定义的配置和验证，可以是任何名字，但必须是用setrules()相同格式的数组
     * 此外，检查{{group}_errors为一个数组的自定义错误消息
     *
     * @param string|null $group
     */
    protected function loadRuleGroup(string $group = null)
    {
        if (empty($group)) {
            return;
        }
        if (!isset($this->config->$group)) {
            throw new \InvalidArgumentException(sprintf(lang('Validation.groupNotFound'), $group));
        }
        if (!is_array($this->config->$group)) {
            throw new \InvalidArgumentException(sprintf(lang('Validation.groupNotArray'), $group));
        }
        $this->rules = $this->config->$group;
        // 如果在配置文件中存在{group}_errors,将覆盖自定义的错误信息
        $errorName = $group . '_errors';
        if (isset($this->config->$errorName)) {
            $this->customErrors = $this->config->$errorName;
        }
    }

    /**
     * 检查给定字段是否存在错误信息
     *
     * @param string $field
     *
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return array_key_exists($field, $this->errors);
    }

    /**
     * 返回指定的$字段的错误（如果没有设置空字符串）
     *
     * @param string|null $field
     *
     * @return string
     */
    public function getError(string $field = null): string
    {
        if ($field === null && count($this->rules) === 1) {
            reset($this->rules);
            $field = key($this->rules);
        }

        return array_key_exists($field, $this->errors) ? $this->errors[$field] : '';
    }

    /**
     * 返回期间遇到的错误数组
     * 例如:
     *      [
     *        'field1' => 'error message',
     *        'field2' => 'error message',
     *      ]
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors ?? [];
    }

    /**
     * 设置特定字段的错误,使用自定义验证方法。
     *
     * @param string $field
     * @param string $error
     *
     * @return YP_Validation
     */
    public function setError(string $field, string $error): self
    {
        $this->errors[$field] = $error;

        return $this;
    }

    /**
     * 找到适当的错误信息
     *
     * @param string      $rule
     * @param string      $field
     * @param string|null $param
     *
     * @return string
     */
    protected function getErrorMessage(string $rule, string $field, string $param = null): string
    {
        // 检查自定义消息是否已被用户定义
        if (isset($this->customErrors[$field][$rule])) {
            $message = $this->customErrors[$field][$rule];
        } else {
            // 尝试抓取本地化版本的消息…lang()将规则名称后如果没有发现，所以总是会有一个字符串返回。
            $message = lang('Validation.' . $rule);
        }
        $message = str_replace('{field}', $field, $message);
        $message = str_replace('{param}', $param, $message);

        return $message;
    }

    /**
     * 将类重置为空白。每当需要处理多个数组时，都应调用。
     *
     * @return YP_Validation
     */
    public function reset(): self
    {
        $this->data         = [];
        $this->rules        = [];
        $this->errors       = [];
        $this->customErrors = [];

        return $this;
    }

    /**
     * 根据文件名和已设置的数据生成输出
     *
     * @param string $view
     * @param null   $saveData
     *
     * @return string
     */
    public function render(string $view, $saveData = null): string
    {
        $start = microtime(true);
        // 将结果存储在这里，即使在视图中调用多个视图，它也不会清除，除非我们自己来清除
        if ($saveData !== null) {
            $this->saveData = $saveData;
        }
        $view = str_replace('.html', '', $view) . '.html';
        if (!file_exists($view)) {
            // 视图文件不存在
            throw new \InvalidArgumentException('View file not found: ' . $view);
        }
        extract($this->viewData);
        if (!$this->saveData) {
            $this->viewData = [];
        }
        ob_start();
        include($view);
        $output = ob_get_contents();
        @ob_end_clean();
        $this->setVar('start_time', $start);
        $this->setVar('end_time', microtime(true));

        return $output;
    }

    /**
     * 设置数据值
     *
     * @param string      $name
     * @param null        $value
     * @param string|null $context 有效值为:html, css, js, url, null
     *
     * @return YP_Validation
     */
    public function setVar(string $name, $value = null, string $context = null): self
    {
        if (!empty($context)) {
            $value = esc($value, $context);
        }
        $this->viewData[$name] = $value;

        return $this;
    }

}