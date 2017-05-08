<?php
/**
 * User: yongli
 * Date: 17/4/22
 * Time: 00:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace CodeIgniter\Debug\Toolbar\Collectors;

use CodeIgniter\Database\Query;
use CodeIgniter\Services;

/**
 * Collector for the Database tab of the Debug Toolbar.
 */
class Database extends BaseCollector
{
	/**
	 * Whether this collector has timeline data.
	 *
	 * @var boolean
	 */
	protected $hasTimeline = true;

	/**
	 * Whether this collector should display its own tab.
	 *
	 * @var boolean
	 */
	protected $hasTabContent = true;

	/**
	 * Whether this collector has data for the Vars tab.
	 *
	 * @var boolean
	 */
	protected $hasVarData = false;

	/**
	 * The name used to reference this collector in the toolbar.
	 *
	 * @var string
	 */
	protected $title = 'Database';

	/**
	 * Array of database connections.
	 *
	 * @var array
	 */
	protected $connections;

	/**
	 * The query instances that have been collected
	 * through the DBQuery Hook.
	 *
	 * @var array
	 */
	protected static $queries = [];


	//--------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->connections = \Config\Database::getConnections();
	}

	//--------------------------------------------------------------------

	/**
	 * The static method used during Hooks to collect
	 * data.
	 *
	 * @param \CodeIgniter\Database\Query $query
	 *
	 * @internal param $ array \CodeIgniter\Database\Query
	 */
	public static function collect(Query $query)
	{
		static::$queries[] = $query;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns timeline data formatted for the toolbar.
	 *
	 * @return array The formatted data or an empty array.
	 */
	protected function formatTimelineData(): array
	{
		$data = [];

		foreach ($this->connections as $alias => $connection)
		{
			// Connection Time
			$data[] = [
				'name' => 'Connecting to Database: "'.$alias.'"',
				'component' => 'Database',
				'start' => $connection->getConnectStart(),
				'duration' => $connection->getConnectDuration()
			];
		}

		foreach (static::$queries as $query)
		{
			$data[] = [
				'name' => 'Query',
				'component' => 'Database',
				'start' => $query->getStartTime(true),
				'duration' => $query->getDuration()
			];
		}

		return $data;
	}

	//--------------------------------------------------------------------

	/**
	 * Returns the HTML to fill the Database tab in the toolbar.
	 *
	 * @return string The data formatted for the toolbar.
	 */
	public function display(): string
	{
		// Key words we want bolded
		$highlight = ['SELECT', 'DISTINCT', 'FROM', 'WHERE', 'AND', 'LEFT&nbsp;JOIN', 'ORDER&nbsp;BY', 'GROUP&nbsp;BY',
			'LIMIT', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'OR&nbsp;', 'HAVING', 'OFFSET', 'NOT&nbsp;IN',
			'IN', 'LIKE', 'NOT&nbsp;LIKE', 'COUNT', 'MAX', 'MIN', 'ON', 'AS', 'AVG', 'SUM', '(', ')'
		];

		$parser = \Config\Services::parser(BASEPATH.'Debug/Toolbar/Views/');

		$data = [
			'queries' => []
		];

		foreach (static::$queries as $query)
		{
			$sql = $query->getQuery();

			foreach ($highlight as $term)
			{
				$sql = str_replace($term, "<strong>{$term}</strong>", $sql);
			}

			$data['queries'][] = [
				'duration' => $query->getDuration(5) * 1000,
				'sql' => $sql
			];
		}

		$output = $parser->setData($data)
			->render('_database.tpl');

		return $output;
	}

	//--------------------------------------------------------------------

	/**
	 * Information to be displayed next to the title.
	 *
	 * @return string The number of queries (in parentheses) or an empty string.
	 */
	public function getTitleDetails(): string
	{
		return '('.count(static::$queries).' Queries across '.count($this->connections).' Connection'.
			(count($this->connections) > 1 ? 's' : '').')';
	}

	//--------------------------------------------------------------------

}
