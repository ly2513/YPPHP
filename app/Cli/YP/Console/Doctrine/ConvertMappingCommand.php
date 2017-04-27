<?php
/**
 * User: yongli
 * Date: 17/4/27
 * Time: 12:01
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Doctrine;

use Doctrine\ORM\Tools\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ConvertMappingCommand extends \Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand
{
    /**
     * 命令名称
     *
     * @var string
     */
    protected $name = 'doctrine:orm:convert-mapping';

    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('在支持格式之间转换映射信息.')
            ->setDefinition(array(
                new InputOption(
                    'filter', NULL, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                    'A string pattern used to match entities that should be processed.'
                ),
                new InputArgument(
                    'to-type', InputArgument::REQUIRED, 'The mapping type to be converted.'
                ),
                new InputArgument(
                    'dest-path', InputArgument::REQUIRED,
                    'The path to generate your entities classes.'
                ),
                new InputOption(
                    'force', NULL, InputOption::VALUE_NONE,
                    'Force to overwrite existing mapping files.'
                ),
                new InputOption(
                    'from-database', NULL, NULL, 'Whether or not to convert mapping information from existing database.'
                ),
                new InputOption(
                    'extend', NULL, InputOption::VALUE_OPTIONAL,
                    'Defines a base class to be extended by generated entity classes.'
                ),
                new InputOption(
                    'num-spaces', NULL, InputOption::VALUE_OPTIONAL,
                    'Defines the number of indentation spaces', 4
                ),
                new InputOption(
                    'namespace', NULL, InputOption::VALUE_OPTIONAL,
                    'Defines a namespace for the generated entity classes, if converted from database.'
                ),
            ))
            ->setHelp(<<<EOT
Convert mapping information between supported formats.

This is an execute <info>one-time</info> command. It should not be necessary for
you to call this method multiple times, especially when using the <comment>--from-database</comment>
flag.

Converting an existing database schema into mapping files only solves about 70-80%
of the necessary mapping information. Additionally the detection from an existing
database cannot detect inverse associations, inheritance types,
entities with foreign keys as primary keys and many of the
semantical operations on associations such as cascade.

<comment>Hint:</comment> There is no need to convert YAML or XML mapping files to annotations
every time you make changes. All mapping drivers are first class citizens
in Doctrine 2 and can be used as runtime mapping for the ORM.

<comment>Hint:</comment> If you have a database with tables that should not be managed
by the ORM, you can use a DBAL functionality to filter the tables and sequences down
on a global level:

    \$config->setFilterSchemaAssetsExpression(\$regexp);
EOT
            );
    }
}

