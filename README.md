# YPPHP
 A Frame Of PHP

 以下是框架的目录结构

 ```
 app
  |____bootstrap
  |       |____autoLoad.php
  |              |---- 自动加载文件
  |____Config
  |       |---- 各种的用户的配置目录
  |____Controllers
  |       |---- 各种控制器
  |____Core
  |       |---- 框架核心文件复写目录
  |____Functions
  |       |---- 公共函数库
  |____Libraries
  |       |---- 各种工具类库目录
  |____Models
  |       |---- 各种用户模型目录
  |____ThirdParty
  |       |---- 第三方组件目录
  |____Views
          |---- 视图目录
  public
  |____index.php
  |      |---- 框架入口
  |____static
         |---- 前端静态资源目录
  artisan
         |---- 框架的命令工具
  system
  |   |----框架目录
  |____Core
  |       |____Functions.php
  |       |       |---- 框架自用函数文件
  |       |____Controller.php
  |       |       |---- 基类控制器
  |       |____Exceptions.php
  |       |       |---- 异常处理类
  |       |____Hooks.php
  |       |       |---- 各种钩子处理类
  |       |____Log.php
  |       |       |---- 日志类
  |       |____Model.php
  |       |       |---- 基类model
  |       |____Router.php
  |       |       |---- 路由处理类
  |       |____Url.php
  |       |       |---- URL处理类
  |       |____Utf8.php
  |       |       |---- 编码处理类
  |       |       |---- URL处理类
  |        |       |____Utf8.php
          |               |---- 编码处理类
  |____Libraries
          |____FormValidation.php
          |       |---- 表单验证类
          |____Page.php
          |       |---- 分页类
          |____Upload.php
                  |---- 上传类

 ```
 框架采用以下第三方组件

 - [ x ] ORM数据库工具 Eloquent
 - [ X ] Doctrine2
 - [ X ] Symfony2 Console
 - [ X ] Json-schema Json 请求字符串验证
 - [ x ] Twig 模板工具
 - [ X ] PHPMailer 邮件工具
 - [ X ] PHPExcel Excel工具

## 命令行工具

 ```
 php artisan list
 ```

### Doctrine

```
# 数据表转 yml 映射文件(已支持字段注释直接转化为yml文件)
php artisan doctrine:orm:convert-mapping --from-database yml app/ThirdParty/Doctrine/Mappings
# 生成 Entity 文件
php artisan doctrine:orm:generate-entities app/ThirdParty/Doctrine/Entity
# 查看 更新SQL
php artisan doctrine:orm:schema-tool:update --dump-sql
# 执行更新
php artisan doctrine:orm:schema-tool:update --force
```
### Thrift

```
# 生成Thrift文件
php artisan thrift:create -f  Thrift文件名称
# 编译Thrift文件
php artisan thrift:gen -p  Thrift文件目录
```