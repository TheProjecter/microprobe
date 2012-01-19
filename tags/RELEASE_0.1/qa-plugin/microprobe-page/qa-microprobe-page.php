<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/example-page/qa-example-page.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Page module class for example page plugin


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
	require_once 'userinfuser/ui_api.php';

	class qa_microprobe_page {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		function suggest_requests() // for display in admin interface
		{	
			return array(
				array(
					'title' => 'Microprobe',
					'request' => 'microprobe-plugin-page',
					'nav' => 'B', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		function match_request($request)
		{
			if ($request=='microprobe-plugin-page')
				return true;

			return false;
		}
		
		function process_request($request)
		{
			// perform userinfuser registration
			$ui = new UserInfuser("ezegarra@yahoo.com", "0658a511-d890-4e51-ba9e-126d6c0a12f2");
			$ui->update_user(qa_get_logged_in_email(), qa_get_logged_in_userid(), "", "");
			$ui->award_points(qa_get_logged_in_email(), 1000);
			$pw = $ui->get_widget(qa_get_logged_in_email(),"points", 100, 100);
			$lw = $ui->get_widget(qa_get_logged_in_email(), "leaderboard", 600, 300);
		
			
			$qa_content=qa_content_prepare();

			$qa_content['title']='Example plugin page';
			$qa_content['error']='An example error';
			$qa_content['custom']='Some <B>custom html</B>';

			$qa_content['form']=array(
				'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
				
				'style' => 'wide',
				
				'ok' => qa_post_text('okthen') ? 'You clicked OK then!' : null,
				
				'title' => 'Form title',
				
				'fields' => array(
					'request' => array(
						'label' => 'The request'.qa_get_logged_in_userid(),
						'tags' => 'NAME="request"',
						'value' => qa_html($request),
						'error' => qa_html('Another error'),
					),
					
				),
				
				'buttons' => array(
					'ok' => array(
						'tags' => 'NAME="okthen"',
						'label' => 'OK then',
						'value' => '1',
					),
				),
				
				'hidden' => array(
					'hiddenfield' => '1',
				),
			);

			$qa_content['custom_2']='<P><BR>More <I>custom html</I></P>';
			
			$qa_content['custom_3']=$pw . $lw;
			
			return $qa_content;
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/