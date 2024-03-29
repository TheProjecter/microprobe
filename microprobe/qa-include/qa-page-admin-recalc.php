<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-recalc.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Handles admin-triggered recalculations if JavaScript disabled


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	// report that we entered this page
	qa_report_event('page_enter', qa_get_logged_in_userid(), qa_get_logged_in_handle(), qa_cookie_get(), array('params'=>$_SERVER['QUERY_STRING'],'path'=>$_SERVER['SCRIPT_NAME']));
	
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-app-recalc.php';

	
//	Check we have administrative privileges

	if (!qa_admin_check_privileges($qa_content))
		return $qa_content;

	
//	Find out the operation

	$allowstates=array(
		'dorecountposts',
		'doreindexposts',
		'dorecalcpoints',
		'dorecalccategories',
		'dodeletehidden',
	);
	
	foreach ($allowstates as $allowstate)
		if (qa_post_text($allowstate) || qa_get($allowstate))
			$state=$allowstate;
			
	if (isset($state)) {
?>

<HTML>
	<HEAD>
		<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8">
	</HEAD>
	<BODY>
		<TT>

<?php

		while ($state) {
			set_time_limit(60);
			
			$stoptime=time()+2; // run in lumps of two seconds...
			
			while ( qa_recalc_perform_step($state) && (time()<$stoptime) )
				;
			
			echo qa_html(qa_recalc_get_message($state)).str_repeat('    ', 1024)."<BR>\n";

			flush();
			sleep(1); // ... then rest for one
		}

?>
		</TT>
		
		<A HREF="<?php echo qa_path_html('admin/stats')?>"><?php echo qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/stats_title')?></A>
	</BODY>
</HTML>

<?php
		exit;
	
	} else {
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		$qa_content=qa_content_prepare();

		$qa_content['title']=qa_lang_html('admin/admin_title');
		$qa_content['error']=qa_lang_html('main/page_not_found');
		
		return $qa_content;
	}
			

/*
	Omit PHP closing tag to help avoid accidental output
*/