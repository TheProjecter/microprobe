<?php
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	
	function mp_db_users_verify_permission($userid, $categoryid)
	{
		/*
		*	Validates if userid is assosciated to categoryid
		*/
		return qa_db_read_one_value(qa_db_query_sub(
			'SELECT COUNT(*) as A1 FROM mp_user_category_map WHERE userid=# AND categoryid=#',
			$userid, $categoryid
		));	
	}
	
	function mp_set_categoryid($categoryid)
	{
	/*
	*	set the categoryid
	*/
		qa_start_session();

		$suffix=qa_session_var_suffix();
		
		// lets check if we have a cookie defined already
		if (!empty($_COOKIE['qa_session'])) {
			@list($handle, $sessioncode, $remember, $oldcategoryid)=explode('/', $_COOKIE['qa_session']);
			qa_set_session_cookie($handle, $sessioncode, $remember, $categoryid);
		}
		
		$_SESSION['mp_session_category_id_'.$suffix] = $categoryid;
	}
	
	function mp_get_categoryid()
	{
	/*
	* get the categoryid
	*/
		$suffix=qa_session_var_suffix();
		
		// lets check if we have a cookie defined already
		if (!empty($_COOKIE['qa_session'])) {
			@list($handle, $sessioncode, $remember, $categoryid)=explode('/', $_COOKIE['qa_session']);
			
			$_SESSION['mp_session_category_id_'.$suffix] = $categoryid;

		}
		
		return @$_SESSION['mp_session_category_id_'.$suffix];
	}
	
	function mp_get_categoryslug()
	{
	
		$i = qa_db_read_all_values(qa_db_query_sub(
			'select backpath from ^categories where categoryid=#',
			mp_get_categoryid()));
		if ( count( $i ) > 0 ) 
			return array_reverse(explode('/',$i[0]));
		else
			return "";
	}
	
	function mp_get_categories_for_user($userid)
	{
		/* 
		 * Returns all the categories associated with a given user id
		 * in the format: (categoryid, categorytitle, parentid, parenttitle)
		 */
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT c1.categoryid, c1.title, c2.categoryid as parentid, c2.title as parenttitle '
			.'FROM ^categories as c1, ^categories as c2, mp_user_category_map as m '
			.'WHERE c1.parentid = c2.categoryid AND c1.categoryid = m.categoryid AND m.userid = #',
			$userid
		), 'categoryid');	
	}
	
	function mp_get_categoryinfo($categoryid)
	{
		/*
		 * Returns all the information for $categoryid
		 *
		 */
		 return qa_db_read_one_assoc(qa_db_query_sub(
			'SELECT * FROM ^categories WHERE categoryid = #', $categoryid), true);
	}
	
	function mp_get_category_userids($categoryid )
	{
		/* 
		 * Returns all the userids associated with category $categoryid
		 *
		 */
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT * FROM mp_user_category_map WHERE categoryid = #', $categoryid), 'userid');
	}	
	
	
	function mp_db_user_find_by_userid($userid)
/*
	Return the userinformation of all users in the database which match $userid (=userid), should be one or none
*/
	{
		return qa_db_read_all_assoc(qa_db_query_sub(
			'SELECT * FROM ^users WHERE userid=#',
			$userid
		));
	}
	
	function mp_db_user_find_dayssincelastlogin_by_userid($userid)
	{
		return qa_db_read_one_value(qa_db_query_sub(
			//'SELECT DATEDIFF(NOW(), loggedin) DAYS FROM ^users WHERE userid=#',
			'SELECT DATEDIFF(NOW(), COALESCE(MAX(A.loggedin),"2011-09-15 00:00:00")) DAYS FROM '
			.'(SELECT max(award_date) as loggedin FROM `mp_userpoints` WHERE userid = # '
			.'UNION '
			.'SELECT loggedin FROM qa_users WHERE userid = #) A',
			$userid, $userid), true);
	}