<?php
/**
 * Emoji Support module for ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://github.com/EllisLab/Emoji-Support
 * @copyright Copyright (c) 2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://opensource.org/licenses/MIT MIT
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
// END CLASS

// EOF
