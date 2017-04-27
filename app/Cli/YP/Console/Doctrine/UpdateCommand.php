<?php
/**
 * User: yongli
 * Date: 17/4/27
 * Time: 12:08
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Console\Doctrine;

use Symfony\Component\Console\Input\InputOption;

class UpdateCommand extends \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand
{
    /**
     * 命令名称
     *
     * @var string
     */
    protected $name = 'doctrine:orm:schema-tool:update';

    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription(
                '通过匹配当前的映射元数据,去执行(或转储)SQL需要更新数据库的结构.'
            )
            ->setDefinition(array(
                new InputOption(
                    'complete', NULL, InputOption::VALUE_NONE,
                    'If defined, all assets of the database which are not relevant to the current metadata will be dropped.'
                ),

                new InputOption(
                    'dump-sql', NULL, InputOption::VALUE_NONE,
                    'Dumps the generated SQL statements to the screen (does not execute them).'
                ),
                new InputOption(
                    'force', NULL, InputOption::VALUE_NONE,
                    'Causes the generated SQL statements to be physically executed against your database.'
                ),
            ));

        $this->setHelp(<<<EOT
The <info>%command.name%</info> command generates the SQL needed to
synchronize the database schema with the current mapping metadata of the
default entity manager.

For example, if you add metadata for a new column to an entity, this command
would generate and output the SQL needed to add the new column to the database:

<info>%command.name% --dump-sql</info>

Alternatively, you can execute the generated queries:

<info>%command.name% --force</info>

If both options are specified, the queries are output and then executed:

<info>%command.name% --dump-sql --force</info>

Finally, be aware that if the <info>--complete</info> option is passed, this
task will drop all database assets (e.g. tables, etc) that are *not* described
by the current metadata. In other words, without this option, this task leaves
untouched any "extra" tables that exist in the database, but which aren't
described by any metadata.

<comment>Hint:</comment> If you have a database with tables that should not be managed
by the ORM, you can use a DBAL functionality to filter the tables and sequences down
on a global level:

    \$config->setFilterSchemaAssetsExpression(\$regexp);
EOT
        );
    }
}
