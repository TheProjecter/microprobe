<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-login.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Controller for login page


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
	qa_report_event('page_enter', qa_get_logged_in_userid(), qa_get_logged_in_handle(), qa_cookie_get(), array('params'=>$_SERVER['QUERY_STRING']));
	

//	Check we're not using Q2A's single-sign on integration and that we're not logged in
	
	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User login is handled by external code');
		
	if (isset($qa_login_userid))
		qa_redirect('');
		

//	Process submitted form after checking we haven't reached rate limit
	
	require_once QA_INCLUDE_DIR.'qa-app-limits.php';

	$passwordsent=qa_get('ps');

	if (qa_limits_remaining(null, 'L') || 1) {
		if (qa_clicked('dologin')) {
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'mp-db-users.php';
				
			$inemailhandle=qa_post_text('emailhandle');
			$inpassword=qa_post_text('password');
			$inremember=qa_post_text('remember');
			// MICROPROBE
			$incategory=qa_post_text('category_2');
			
			$errors=array();
			
			// verify category provided
			if ( strlen($incategory) <= 0 ) {
				$errors['category']=qa_lang('question/category_required');
			}

		
			if ( empty($errors) ) { // if no validation errors found, proceed to login
			
				if (strpos($inemailhandle, '@')===false) // handles can't contain @ symbols
					$matchusers=qa_db_user_find_by_handle($inemailhandle);
				else
					$matchusers=qa_db_user_find_by_email($inemailhandle);
		
				if (count($matchusers)==1) { // if matches more than one (should be impossible), don't log in
					$inuserid=$matchusers[0];
					$userinfo=qa_db_select_with_pending(qa_db_user_account_selectspec($inuserid, true));
					
					// verify user is registered for the category / course
					if ( mp_db_users_verify_permission($userinfo['userid'], $incategory ) != 0)
					{
						// user is allowed to access the category, now check password
						if (strtolower(qa_db_calc_passcheck($inpassword, $userinfo['passsalt'])) == strtolower($userinfo['passcheck'])) { // login and redirect
							require_once QA_INCLUDE_DIR.'qa-app-users.php';
			
							qa_set_logged_in_user($inuserid, $userinfo['handle'], $inremember ? true : false, null, $incategory);
							
							$topath=qa_get('to');
							
							if (isset($topath))
								qa_redirect_raw($qa_root_url_relative.$topath); // path already provided as URL fragment
							elseif ($passwordsent)
								qa_redirect('account');
							else
								qa_redirect('');
			
						} else
							$errors['password']=qa_lang('users/password_wrong');
					}
					else
						$errors['category'] = 'Your userid is not registered for this category';
		
				} else
					$errors['emailhandle']=qa_lang('users/user_not_found');
			
			}
				
			qa_limits_increment(null, 'L'); // only get here if we didn't log in successfully

		} else
			$inemailhandle=qa_get('e');
		
	} else
		$pageerror=qa_lang('users/login_limit');

	
//	Prepare content for theme
	
	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('users/login_title');
	
	$qa_content['error']=@$pageerror;

	if (empty($inemailhandle) || isset($errors['emailhandle']))
		$forgotpath=qa_path('forgot');
	else
		$forgotpath=qa_path('forgot', array('e' => $inemailhandle));
	
	$forgothtml='<A HREF="'.qa_html($forgotpath).'">'.qa_lang_html('users/forgot_link').'</A>';
	
	$qa_content['form']=array(
		'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
		
		'style' => 'tall',
		
		'ok' => $passwordsent ? qa_lang_html('users/password_sent') : null,
		
		'fields' => array(
			'email_handle' => array(
				'label' => qa_lang_html('users/email_handle_label'),
				'tags' => 'NAME="emailhandle" ID="emailhandle"',
				'value' => qa_html(@$inemailhandle),
				'error' => qa_html(@$errors['emailhandle']),
			),
			
			'password' => array(
				'type' => 'password',
				'label' => qa_lang_html('users/password_label'),
				'tags' => 'NAME="password" ID="password"',
				'value' => qa_html(@$inpassword),
				'error' => empty($errors['password']) ? '' : (qa_html(@$errors['password']).' - '.$forgothtml.' ->'.qa_html(@$errors['category']).'<-'),
				'note' => $passwordsent ? qa_lang_html('users/password_sent') : $forgothtml,
			),
			
			'category' => array(
				'label' => qa_lang_html('question/q_category_label'),
				'tags' => 'NAME="category" ID="category"',
				'value' => qa_html(@$incategory),
				'error' => qa_html(@$errors['category']),
			),
			
			'remember' => array(
				'type' => 'checkbox',
				'label' => qa_lang_html('users/remember_label'),
				'tags' => 'NAME="remember"',
				'value' => @$inremember ? true : false,
			),
		),
		
		'buttons' => array(
			'login' => array(
				'label' => qa_lang_html('users/login_button'),
			),
		),
		
		'hidden' => array(
			'dologin' => '1',
		),
	);
	
	$modulenames=qa_list_modules('login');
	
	foreach ($modulenames as $tryname) {
		$module=qa_load_module('login', $tryname);
		
		if (method_exists($module, 'login_html')) {
			ob_start();
			$module->login_html(qa_opt('site_url').qa_get('to'), 'login');
			$html=ob_get_clean();
			
			if (strlen($html))
				@$qa_content['custom'].='<BR>'.$html.'<BR>';
		}
	}

	$qa_content['focusid']=(isset($inemailhandle) && !isset($errors['emailhandle'])) ? 'password' : 'emailhandle';
	
	// add handling of sub categories
	//if (qa_using_categories() && count($categories)) {
	//	$incategoryid=qa_get_category_field_value('category');
	//	if (!isset($incategoryid))
	//		$incategoryid=qa_get('cat');		
		
	$incategoryid=qa_get_category_field_value('category');
	$categories = qa_db_select_with_pending(qa_db_category_nav_selectspec($incategoryid, true));
	qa_set_up_category_field($qa_content, $qa_content['form']['fields']['category'], 'category', $categories, $incategoryid, false, qa_opt('allow_no_sub_category'));
	
	//	
	//	if (!qa_opt('allow_no_category')) // don't auto-select a category even though one is required
	//		$qa_content['form']['fields']['category']['options']['']='';

	//} else
		//unset($qa_content['form']['fields']['category']);	

	return $qa_content;


/*
	Omit PHP closing tag to help avoid accidental output
*/