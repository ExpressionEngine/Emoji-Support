<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */


/**
 * Emoji Support update class
 */
class Emoji_support_upd {

	public function install()
	{
		ee('Model')->make('Module', [
			'module_name' => 'Emoji_support',
			'module_version' => '1.0.0',
			'has_cp_backend' => TRUE,
			'has_publish_fields' => FALSE
		])->save();
		return TRUE;
	}

	public function uninstall()
	{
		ee('Model')->get('Moudle')
			->filter('module_name', 'Emoji_support')
			->delete();
		return TRUE;
	}

	public function update($current = '')
	{
		return TRUE;
	}

}

// EOF
