<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/tag-cloud-widget/qa-tag-cloud.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Widget module class for tag cloud plugin


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

	class mp_profile_progress_widget {
		
		function option_default($option)
		{
			if ($option=='tag_cloud_count_tags')
				return 100;
			elseif ($option=='tag_cloud_font_size')
				return 24;
			elseif ($option=='tag_cloud_size_popular')
				return true;
		}
		
		function admin_form()
		{
			$saved=false;
			
			if (qa_clicked('tag_cloud_save_button')) {
				qa_opt('tag_cloud_count_tags', (int)qa_post_text('tag_cloud_count_tags_field'));
				qa_opt('tag_cloud_font_size', (int)qa_post_text('tag_cloud_font_size_field'));
				qa_opt('tag_cloud_size_popular', (int)qa_post_text('tag_cloud_size_popular_field'));
				$saved=true;
			}
			
			return array(
				'ok' => $saved ? 'Profile settings saved' : null,
				
				'fields' => array(
					array(
						'label' => 'Number of tags to show:',
						'type' => 'number',
						'value' => (int)qa_opt('tag_cloud_count_tags'),
						'tags' => 'NAME="tag_cloud_count_tags_field"',
					),

					array(
						'label' => 'Starting font size (in pixels):',
						'type' => 'number',
						'value' => (int)qa_opt('tag_cloud_font_size'),
						'tags' => 'NAME="tag_cloud_font_size_field"',
					),
					
					array(
						'label' => 'Font size represents tag popularity',
						'type' => 'checkbox',
						'value' => qa_opt('tag_cloud_size_popular'),
						'tags' => 'NAME="tag_cloud_size_popular_field"',
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="tag_cloud_save_button"',
					),
				),
			);
		}
		
		function allow_template($template)
		{
			$allow=true;
			
			switch ($template)
			{
				case 'activity':
				case 'qa':
				case 'questions':
				case 'hot':
				case 'ask':
				case 'categories':
				case 'question':
				case 'tag':
				case 'tags':
				case 'unanswered':
				case 'user':
				case 'users':
				case 'search':
				case 'admin':
				case 'plugin':
					$allow=true;
					break;
			}
			
			return $allow;
		}
		
		function allow_region($region)
		{
			return ($region=='side');
		}
		
		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
			// only show for logged in users
			if (qa_get_logged_in_handle() == null){
                return;
            }
			require_once QA_INCLUDE_DIR.'qa-db.php';
			require_once QA_INCLUDE_DIR.'qa-db.php';
			require_once QA_INCLUDE_DIR.'mp-db-users.php';
			
			$userid 	= qa_get_logged_in_userid();
			$categoryid = mp_get_categoryid();
			
			// check each property
			$nameSQL = qa_db_read_one_value(
						qa_db_query_sub( 'select count(content) from ^userprofile p where p.userid =# and content="" and title=#', $userid, 'name'));

			$aboutSQL = qa_db_read_one_value(
						qa_db_query_sub( 'select count(content) from ^userprofile p where p.userid =# and content="" and title=#', $userid, 'about'));
						
			$websiteSQL = qa_db_read_one_value(
						qa_db_query_sub( 'select count(content) from ^userprofile p where p.userid =# and content="" and title=#', $userid, 'website'));						
			
			$questionSQL = qa_db_read_one_value(
						qa_db_query_sub( 'select count(userid) from ^posts p where p.userid =# and categoryid=# and type=#', $userid, $categoryid, 'Q'));	

			$answerSQL = qa_db_read_one_value(
						qa_db_query_sub( 'select count(userid) from ^posts p where p.userid =# and categoryid=# and type=#', $userid, $categoryid, 'A'));	
			
			$perc = 0;
			
			if ( $nameSQL == 0 ) $perc++;
			if ( $aboutSQL == 0 ) $perc++;
			if ( $websiteSQL == 0 ) $perc++;
			if ( $questionSQL > 0 ) $perc++;
			if ( $answerSQL > 0 ) $perc++;
			
			$themeobject->output(
				'<DIV CLASS="mp-widget-profile-view"><DIV CLASS="mp-widget-profile-title">Profile Progress - '.($perc*100/5).'%</DIV>');

			$data = '<DIV CLASS="mp-widget-profile-list-item"><SPAN CLASS="mp-widget-profile-list-title">Name</SPAN>'.($nameSQL?'<SPAN CLASS="mp-widget-profile-list-bad"></SPAN>':'<SPAN CLASS="mp-widget-profile-list-good"></SPAN>').'</DIV>'; // value of 0 is complete
			$data .= '<DIV CLASS="mp-widget-profile-list-item"><SPAN CLASS="mp-widget-profile-list-title">About</SPAN>'.($aboutSQL?'<SPAN CLASS="mp-widget-profile-list-bad"></SPAN>':'<SPAN CLASS="mp-widget-profile-list-good"></SPAN>').'</DIV>'; // value of 0 is complete
			$data .= '<DIV CLASS="mp-widget-profile-list-item"><SPAN CLASS="mp-widget-profile-list-title">Website</SPAN>'.($websiteSQL?'<SPAN CLASS="mp-widget-profile-list-bad"></SPAN>':'<SPAN CLASS="mp-widget-profile-list-good"></SPAN>').'</DIV>'; // value of 0 is complete
			$data .= '<DIV CLASS="mp-widget-profile-list-item"><SPAN CLASS="mp-widget-profile-list-title">Posted a question</SPAN>'.($questionSQL <= 0?'<SPAN CLASS="mp-widget-profile-list-bad"></SPAN>':'<SPAN CLASS="mp-widget-profile-list-good"></SPAN>').'</DIV>'; // value > 0 is complete
			$data .= '<DIV CLASS="mp-widget-profile-list-item"><SPAN CLASS="mp-widget-profile-list-title">Posted an answer</SPAN>'.($answerSQL <= 0?'<SPAN CLASS="mp-widget-profile-list-bad"></SPAN>':'<SPAN CLASS="mp-widget-profile-list-good"></SPAN>').'</DIV>'; // value > 0 is complete
			
			$data .= '<br /><center><a CLASS="qa-page-link" href="'.qa_path_html('account').'">Edit Profile</a></center><br />';
			
			$themeobject->output($data);
			
			$themeobject->output('</DIV>');
			
		}
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/