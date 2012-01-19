<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/event-logger/qa-event-logger.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Event module class for event logger plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/
	// make mp classes requird
	require_once QA_INCLUDE_DIR.'mp-db-users.php';
	
	class mp_event_logger {
			
		function process_event($event, $userid, $handle, $cookieid, $params)
		{
			$activityPoints = array(
				"page_enter" 	=> 1,
				"u_register" 	=> 0,
				"u_confirmed" 	=> 0,
				"q_edit" 		=> 50,
				"a_edit" 		=> 50,
				"a_vote_up" 	=> 30,
				"a_vote_down"	=> 30,
				"a_vote_nil" 	=> 30,
				"q_vote_up" 	=> 30,
				"q_vote_down" 	=> 30,
				"q_vote_nil" 	=> 30,
				"q_post" 		=> 200,
				"a_post" 		=> 300,
				"u_password" 	=> 5,
				"u_reset" 		=> 5,
				"u_edit" 		=> 5,
				"u_save" 		=> 5,
				"a_select" 		=> 30,
				"a_unselect" 	=> 10,
				"u_login"		=> 10,
				"u_logout"		=> 10
				);
			
			if ( array_key_exists($event, $activityPoints)) {

				qa_db_query_sub(
					'INSERT INTO mp_userpoints (userid, categoryid, eventid, points, award_date) '.
					'VALUES (#, #, $, #, NOW())',
					(is_null($userid)?'-1':$userid), (is_null(mp_get_categoryid())?'-1':mp_get_categoryid()), $event, $activityPoints[$event] 
					);

			}//qa_opt('mp_active_category')
			
		}
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/