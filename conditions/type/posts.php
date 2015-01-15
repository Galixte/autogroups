<?php
/**
*
* Auto Groups extension for the phpBB Forum Software package.
*
* @copyright (c) 2014 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace phpbb\autogroups\conditions\type;

/**
 * Auto Groups Posts class
 */
class posts extends \phpbb\autogroups\conditions\type\base
{
	/**
	 * Get condition type
	 *
	 * @return string Condition type
	 * @access public
	 */
	public function get_condition_type()
	{
		return 'phpbb.autogroups.type.posts';
	}

	/**
	 * Get condition field (this is the field to check)
	 *
	 * @return string Condition field name
	 * @access public
	 */
	public function get_condition_field()
	{
		return 'user_posts';
	}

	/**
	 * Get condition type name
	 *
	 * @return string Condition type name
	 * @access public
	 */
	public function get_condition_type_name()
	{
		return $this->user->lang('AUTOGROUPS_TYPE_POSTS');
	}

	/**
	 * Get users to apply to this condition
	 *
	 * @param array $options Array of optional data
	 * @return array Array of users ids as keys and their condition data as values
	 * @access public
	 */
	public function get_users_for_condition($options = array())
	{
		// The user data this condition needs to check
		$condition_data = array(
			$this->get_condition_field(),
		);

		// Merge default options, use the active user as the default
		$options = array_merge(array(
			'users'		=> $this->user->data['user_id'],
		), $options);

		$user_ids = $options['users'];

		// Clean up array of ids
		if (is_array($user_ids))
		{
			$user_ids = array_map('intval', $user_ids);
		}
		else
		{
			$user_ids = array((int) $user_ids);
		}

		// Get data for the users to be checked (exclude bots and guests)
		$sql = 'SELECT user_id, ' . implode(', ', $condition_data) . '
			FROM ' . USERS_TABLE . '
			WHERE ' . $this->db->sql_in_set('user_id', $user_ids, false, true) . '
				AND user_type <> ' . USER_IGNORE;
		$result = $this->db->sql_query($sql);

		$user_data = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$user_data[$row['user_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		return $user_data;
	}

	/**
	 * Check condition
	 *
	 * @param array $user_row Array of user data to perform checks on
	 * @param array $options  Array of optional data
	 * @return null
	 * @access public
	 */
	public function check($user_row, $options = array())
	{
		// Merge default options
		$options = array_merge(array(
			'action'	=> '',
		), $options);

		// We need to decrease the user's post count during post deletion
		// because the database does not yet have updated post counts.
		if ($options['action'] == 'delete')
		{
			foreach ($user_row as $user_id => &$user_data)
			{
				$user_data['user_posts']--;
			}

			// Always unset a variable passed by reference in a foreach loop
			unset($user_data);
		}

		parent::check($user_row, $options);
	}
}
