<?php
/**
 * User: yongli
 * Date: 17/4/27
 * Time: 12:05
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Doctrine;

use Doctrine\ORM\Tools\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateEntitiesCommand extends \Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand
{
    /**
     * 命令名称
     *
     * @var string
     */
    protected $name = 'doctrine:orm:generate-entities';

    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('从映射信息生成实体类和方法.')
            ->setDefinition(array(
                new InputOption(
                    'filter', NULL, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                    'A string pattern used to match entities that should be processed.'
                ),
                new InputArgument(
                    'dest-path', InputArgument::REQUIRED, 'The path to generate your entity classes.'
                ),
                new InputOption(
                    'generate-annotations', NULL, InputOption::VALUE_OPTIONAL,
                    'Flag to define if generator should generate annotation metadata on entities.', FALSE
                ),
                new InputOption(
                    'generate-methods', NULL, InputOption::VALUE_OPTIONAL,
                    'Flag to define if generator should generate stub methods on entities.', TRUE
                ),
                new InputOption(
                    'regenerate-entities', NULL, InputOption::VALUE_OPTIONAL,
                    'Flag to define if generator should regenerate entity if it exists.', FALSE
                ),
                new InputOption(
                    'update-entities', NULL, InputOption::VALUE_OPTIONAL,
                    'Flag to define if generator should only update entity if it exists.', TRUE
                ),
                new InputOption(
                    'extend', NULL, InputOption::VALUE_REQUIRED,
                    'Defines a base class to be extended by generated entity classes.'
                ),
                new InputOption(
                    'num-spaces', NULL, InputOption::VALUE_REQUIRED,
                    'Defines the number of indentation spaces', 4
                ),
                new InputOption(
                    'no-backup', NULL, InputOption::VALUE_NONE,
                    'Flag to define if generator should avoid backuping existing entity file if it exists.'
                ),
            ))
            ->setHelp(<<<EOT
Generate entity classes and method stubs from your mapping information.

If you use the <comment>--update-entities</comment> or <comment>--regenerate-entities</comment> flags your existing
code gets overwritten. The EntityGenerator will only append new code to your
file and will not delete the old code. However this approach may still be prone
to error and we suggest you use code repositories such as GIT or SVN to make
backups of your code.

It makes sense to generate the entity code if you are using entities as Data
Access Objects only and don't put much additional logic on them. If you are
however putting much more logic on the entities you should refrain from using
the entity-generator and code your entities manually.

<error>Important:</error> Even if you specified Inheritance options in your
XML or YAML Mapping files the generator cannot generate the base and
child classes for you correctly, because it doesn't know which
class is supposed to extend which. You have to adjust the entity
code manually for inheritance to work!
EOT
            );
    }
}
