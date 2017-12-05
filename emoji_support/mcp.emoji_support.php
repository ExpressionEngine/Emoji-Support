<?php
/**
 * Emoji Support module for ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://github.com/EllisLab/Emoji-Support
 * @copyright Copyright (c) 2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://opensource.org/licenses/MIT MIT
 */

/**
 * Emoji Support control panel
 */
class Emoji_support_mcp {

	protected function isUtf8mb4Supported()
	{
		$alert = ee('CP/Alert')->makeInline('error')
				->asIssue()
				->cannotClose()
				->withTitle(lang('unsupported'));

		$msyql_server_version = ee('Database')->getConnection()->getNative()->getAttribute(PDO::ATTR_SERVER_VERSION);

		$server_is_compatible = version_compare($msyql_server_version, '5.5.3', '>=');

		if ( ! $server_is_compatible)
		{
			$alert->addToBody(sprintf(lang('unsupported_server'), $msyql_server_version));
		}

		$client_info = ee('Database')->getConnection()->getNative()->getAttribute(PDO::ATTR_CLIENT_VERSION);

		if (strpos($client_info, 'mysqlnd') === 0)
		{
			$msyql_client_version = preg_replace('/^mysqlnd ([\d.]+).*/', '$1', $client_info);
			$client_is_compatible = version_compare($msyql_client_version, '5.0.9', '>=');

			if ( ! $client_is_compatible)
			{
				$alert->addToBody(sprintf(lang('unsupported_client'), $msyql_client_version, '5.0.9'));
			}
		}
		else
		{
			$msyql_client_version = $client_info;
			$client_is_compatible = version_compare($msyql_client_version, '5.5.3', '>=');

			if ( ! $client_is_compatible)
			{
				$alert->addToBody(sprintf(lang('unsupported_client'), $msyql_client_version, '5.5.3'));
			}
		}

		$utf8mb4_supported = $server_is_compatible && $client_is_compatible;

		if ( ! $utf8mb4_supported)
		{
			$alert->now();
		}

		return $utf8mb4_supported;
	}

	public function index()
	{
		if ( ! $this->isUtf8mb4Supported())
		{
			return ee('View')->make('emoji_support:alert')->render();
		}

		$indicies = $this->getAffectedIndicies();
		if ( ! empty($indicies))
		{
			$tables = [];
			foreach ($indicies as $row)
			{
				$tables[] = $row['TABLE_NAME'].' '.sprintf(lang('key_too_large'), $row['COLUMN_NAME']);
			}

			ee('CP/Alert')->makeInline('error')
				->asIssue()
				->cannotClose()
				->withTitle(lang('incompatible'))
				->addToBody(lang('incompatible_desc'))
				->addToBody($tables)
				->now();

			return ee('View')->make('emoji_support:alert')->render();
		}

		if (version_compare(APP_VER, '4.0.0-dp.1', '>='))
		{
			$url = ee('CP/URL', 'utilities/db-backup');
		}
		else
		{
			$url = DOC_URL.'operations/database_backup.html';
		}

		ee('CP/Alert')->makeInline('backup')
			->asWarning()
			->cannotClose()
			->withTitle(lang('backup'))
			->addToBody(sprintf(lang('backup_desc'), $url))
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
		$end = count($sql);

		$stop = (5 <= $end) ? 5 : $end;

		for ($i = 0; $i < $stop; $i++)
		{
			ee('db')->query($sql[$i]);
		}

		if ($i >= $end)
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
			'offset' => $offset + $i
		]);
	}

	public function success()
	{
		$sql = $this->prepareSQLStatements();

		if ( ! empty($sql))
		{
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/emoji_support'));
		}

		return ee('View')->make('emoji_support:alert')->render();
	}

	protected function getAffectedIndicies()
	{
		$return = [];

		$indicies = ee()->db->query("SELECT `TABLE_NAME`, `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = '" . ee()->db->database . "' AND `DATA_TYPE` REGEXP 'char|text' AND `CHARACTER_MAXIMUM_LENGTH` > 191 AND `COLUMN_KEY` <> '' AND `COLUMN_NAME` NOT IN ('url_title', 'cat_group');");
		if ($indicies->num_rows())
		{
			// check to see if the associated index is also over 191 characters
			foreach ($indicies->result() as $row)
			{
				$indicies_too_large = ee()->db->query("SHOW INDEX FROM `{$row->TABLE_NAME}` WHERE Key_name = '{$row->COLUMN_NAME}' AND (Sub_part > 191 OR Sub_part IS NULL)");

				if ($indicies_too_large->num_rows())
				{
					$return[] = ['TABLE_NAME' => $row->TABLE_NAME, 'COLUMN_NAME' => $row->COLUMN_NAME];
				}
			}

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
		$sql = "SELECT default_character_set_name FROM information_schema.SCHEMATA  WHERE schema_name = '" . ee()->db->database . "';";
		$status = ee()->db->query($sql);

		if ($status->row('default_character_set_name') == 'utf8mb4')
		{
			return;
		}

		return "ALTER DATABASE `" . ee()->db->database . "` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;";
	}

	protected function getNewUrlTitleIndexStatements()
	{
		$return = [];

		$sql = "SHOW INDEX FROM exp_channel_titles WHERE Key_name = 'url_title';";
		$status = ee()->db->query($sql);

		if ($status->row('Sub_part') > '191')
		{
			$return[] = "DROP INDEX `url_title` ON `exp_channel_titles`;";
			$return[] = "CREATE INDEX `url_title` ON `exp_channel_titles` (`url_title`(191));";
		}

		$sql = "SHOW INDEX FROM exp_channel_entries_autosave WHERE Key_name = 'url_title';";
		$status = ee()->db->query($sql);

		if ($status->row('Sub_part') > '191')
		{
			$return[] = "DROP INDEX `url_title` ON `exp_channel_entries_autosave`;";
			$return[] = "CREATE INDEX `url_title` ON `exp_channel_entries_autosave` (`url_title`(191));";
		}

		return $return;
	}

	protected function getNewCatGroupIndexStatements()
	{
		$sql = "SHOW INDEX FROM exp_channels WHERE Key_name = 'cat_group';";
		$status = ee()->db->query($sql);

		if ($status->row('Sub_part') == '191')
		{
			return [];
		}

		return [
			"DROP INDEX `cat_group` ON `exp_channels`;",
			"CREATE INDEX `cat_group` ON `exp_channels` (`cat_group`(191));"
		];
	}


	protected function prepareSQLStatements()
	{
		$sql = [];

		$alter_db = $this->getAlterDatabaseStatement();
		if ($alter_db)
		{
			$sql[] = $alter_db;
		}

		$sql = array_merge($sql, $this->getNewUrlTitleIndexStatements());

		$sql = array_merge($sql, $this->getNewCatGroupIndexStatements());

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
// END CLASS

// EOF
