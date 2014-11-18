<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-vote.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Server-side response to Ajax voting requests


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

	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-cookies.php';
	

	function qa_ajax_vote_db_fail_handler()
	{
		echo "QA_AJAX_RESPONSE\n0\nA database error occurred.";
		exit;
	}

    $event = qa_post_text('logevent');
    
    // report that we entered this page
    qa_report_event($event, qa_get_logged_in_userid(), qa_get_logged_in_handle(), qa_cookie_get(), array('params'=>$_SERVER['QUERY_STRING'],'path'=>$_SERVER['SCRIPT_NAME']));
    
    echo "QA_AJAX_RESPONSE\n1\n";


	

/*
	Omit PHP closing tag to help avoid accidental output
*/