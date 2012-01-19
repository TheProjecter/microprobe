<?php

	function mp_register_user($userid, $categoryid) 
	{
		/* 
		 * registers $userid as a student in course $categoryid
		*/
		require_once QA_INCLUDE_DIR.'mp-db-users.php';
		
		// call the function to insert user to category
		mp_db_insert_userid_to_course($userid, $categoryid);
		
	}
?>