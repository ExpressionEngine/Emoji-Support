<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */


/**
 * Emoji Support control panel
 */
class Emoji_support_mcp {

	protected $msyql_server_version;
	protected $utf8mb4_supported;

	public function __construct() {
		$this->msyql_server_version = ee('Database')->getConnection()->getNative()->getAttribute(PDO::ATTR_SERVER_VERSION);
		$this->utf8mb4_supported = version_compare($this->msyql_server_version, '5.5.3', '>=');
	}

	public function index()
	{
		if ( ! $this->utf8mb4_supported)
		{
			ee('CP/Alert')->makeInline('error')
				->asIssue()
				->cannotClose()
				->withTitle(lang('unsupported'))
				->addToBody(sprintf(lang('unsupported_desc'), $this->msyql_server_version))
				->now();

			return ee('View')->make('emoji_support:alert')->render();
		}

		$indicies = $this->getAffectedIndicies();
		if ( ! empty($indicies))
		{
			$tables = [];
			foreach ($indicies as $row)
			{
				$tables[] = $row['TABLE_NAME'];
			}

			ee('CP/Alert')->makeInline('error')
				->asIssue()
				->cannotClose()
				->withTitle(lang('incompatible'))
				->addToBody(sprintf(lang('incompatible_desc'), $this->msyql_server_version))
				->addToBody($tables)
				->now();

			return ee('View')->make('emoji_support:alert')->render();
		}

		ee('CP/Alert')->makeInline('backup')
			->asWarning()
			->cannotClose()
			->withTitle(lang('backup'))
			->addToBody(sprintf(lang('backup_desc'), ee('CP/URL', 'utilities/db-backup')))
			->now();

		$sql = $this->prepareSQLStatements();

		ee()->cp->load_package_js('convert');
		ee()->javascript->set_global([
			'emoji_support' => [
				'endpoint'         => ee('CP/URL')->make('addons/settings/emoji_support/convert')->compile(),
				'total_commands'   => count($sql),
				'base_url'         => ee('CP/URL')->make('addons/settings/emoji_support/success')->compile(),
				'ajax_fail_banner' => ee('CP/Alert')->makeInline('backup-ajax-fail')
					->asIssue()
					->withTitle(lang('convert_error'))
					->addToBody('%body%')
					->render()
			]
		]);

		return ee('View')->make('emoji_support:index')->render(['sql' => $sql]);
	}

	public function convert()
	{
		// Only accept POST requests
		if (is_null(ee('Request')->post('offset')))
		{
			show_404();
		}

		$offset = ee('Request')->post('offset');

		if ($offset == 0)
		{
			$this->updateDBConfig();
		}

		$sql = $this->prepareSQLStatements();

		$stop = $offset + 5;

		for ($offset; $offset < $stop; $offset++)
		{
			ee('db')->query($sql[$offset]);
		}

		if ($offset == (count($sql) - 1))
		{
			ee('CP/Alert')->makeInline('convert_success')
				->asSuccess()
				->withTitle(lang('convert_success'))
				->addToBody(lang('convert_success_desc'))
				->defer();

			ee()->output->send_ajax_response(['status' => 'finished']);
		}

		ee()->output->send_ajax_response([
			'status' => 'in_progress',
			'offset' => $offset
		]);
	}

	public function success()
	{
		return ee('View')->make('emoji_support:alert')->render();
	}

	protected function getAffectedIndicies()
	{
		$return = [];

		$indicies = ee()->db->query("SELECT `TABLE_NAME`, `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = '" . ee()->db->database . "' AND `DATA_TYPE` REGEXP 'char|text' AND `CHARACTER_MAXIMUM_LENGTH` > 191 AND `COLUMN_KEY` <> '' AND `COLUMN_NAME` NOT IN ('url_title', 'cat_group');");
		if ($indicies->num_rows())
		{
			return $indicies->result_array();
		}

		return $return;
	}

	protected function getAffectedTables()
	{
		$tables = [];

		foreach (ee()->db->list_tables(TRUE) as $table)
		{
			$status = ee()->db->query("SHOW TABLE STATUS LIKE '$table'");

			if ($status->num_rows() != 1 || $status->row('Collation') == 'utf8mb4_unicode_ci')
			{
				continue;
			}

			$tables[] = $table;
		}

		return $tables;
	}

	protected function getAlterDatabaseStatement()
	{
		return "ALTER DATABASE `" . ee()->db->database . "` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;";
	}

	protected function getNewUrlTitleIndexStatements()
	{
		return [
			"DROP INDEX `url_title` ON `exp_channel_titles`;",
			"CREATE INDEX `url_title` ON `exp_channel_titles` (`url_title`(191));"
		];
	}

	protected function prepareSQLStatements()
	{
		$sql = [];

		$sql[] = $this->getAlterDatabaseStatement();
		$sql = array_merge($sql, $this->getNewUrlTitleIndexStatements());

		foreach ($this->getAffectedTables() as $table)
		{
			$sql[] = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
		}

		return $sql;
	}

	protected function updateDBConfig()
	{
		$db_config = ee('Database')->getConfig();

		// We may not need to do anything, so don't muck about with the config!
		if ($db_config->get('char_set') == 'utf8mb4'
			&& $db_config->get('dbcollat') == 'utf8mb4_unicode_ci')
		{
			return;
		}

		$db_config->set('char_set', 'utf8mb4');
		$db_config->set('dbcollat', 'utf8mb4_unicode_ci');

		$group_config = $db_config->getGroupConfig();

		// Remove default properties
		foreach ($db_config->getDefaults() as $property => $value)
		{
			if (isset($group_config[$property]) && $group_config[$property] == $value)
			{
				unset($group_config[$property]);
			}
		}

		ee()->config->_update_config(array(
			'database' => array(
				$db_config->getActiveGroup() => $group_config
			)
		));
	}
}

// EOF
