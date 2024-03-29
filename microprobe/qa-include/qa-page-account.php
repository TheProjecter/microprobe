<?php
	
/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-account.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Controller for user account page


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
	
	require_once QA_INCLUDE_DIR.'qa-db-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';
	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-util-image.php';
	
//	Check we're not using single-sign on integration, that we're logged in, and we're not blocked
	
	if (QA_FINAL_EXTERNAL_USERS)
		qa_fatal_error('User accounts are handled by external code');
		
	if (!isset($qa_login_userid))
		qa_redirect('login');
		
	if (qa_user_permit_error()) {
		$qa_content=qa_content_prepare();
		$qa_content['error']=qa_lang_html('users/no_permission');
		return $qa_content;
	}

	
//	Get current information on user

	list($useraccount, $userprofile, $userpoints, $userfields)=qa_db_select_with_pending(
		qa_db_user_account_selectspec($qa_login_userid, true),
		qa_db_user_profile_selectspec($qa_login_userid, true),
		qa_db_user_points_selectspec($qa_login_userid, true),
		qa_db_userfields_selectspec()
	);
	
	$changehandle=qa_opt('allow_change_usernames') || ((!$userpoints['qposts']) && (!$userpoints['aposts']) && (!$userpoints['cposts']));
	$doconfirms=qa_opt('confirm_user_emails') && ($useraccount['level']<QA_USER_LEVEL_EXPERT);
	$isconfirmed=($useraccount['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED) ? true : false;
	$haspassword=isset($useraccount['passsalt']) && isset($useraccount['passcheck']);

	
//	Process profile if saved

	if (qa_clicked('dosaveprofile')) {
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		$inhandle=$changehandle ? qa_post_text('handle') : $useraccount['handle'];
		$inemail=qa_post_text('email');
		$inmessages=qa_post_text('messages');
		$inavatar=qa_post_text('avatar');
		$innotifyan=qa_post_text('notify_an');
		$innotifyq=qa_post_text('notify_q');
		$innotifya=qa_post_text('notify_a');
		
		
		$errors=qa_handle_email_validate($inhandle, $inemail, $qa_login_userid);

		if (!isset($errors['handle']))
			qa_db_user_set($qa_login_userid, 'handle', $inhandle);

		if (!isset($errors['email']))
			if ($inemail != $useraccount['email']) {
				qa_db_user_set($qa_login_userid, 'email', $inemail);
				qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_EMAIL_CONFIRMED, false);
				$isconfirmed=false;
				
				if ($doconfirms)
					qa_send_new_confirm($qa_login_userid);
			}
			
		qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_NO_MESSAGES, !$inmessages);
		qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_SHOW_AVATAR, ($inavatar=='uploaded'));
		qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_SHOW_GRAVATAR, ($inavatar=='gravatar'));
		qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_NOTIFY_ANNOUNCEMENTS, !$innotifyan);
		qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_NOTIFY_QUESTIONS, !$innotifyq);
		qa_db_user_set_flag($qa_login_userid, QA_USER_FLAGS_NOTIFY_ANSWERS, !$innotifya);

		if (is_array(@$_FILES['file']) && $_FILES['file']['size']) {
			require_once QA_INCLUDE_DIR.'qa-app-limits.php';
			
			switch (qa_user_permit_error(null, 'U'))
			{
				case 'limit':
					$errors['avatar']=qa_lang('main/upload_limit');
					break;
				
				default:
					$errors['avatar']=qa_lang('users/no_permission');
					break;
					
				case false:
					qa_limits_increment($qa_login_userid, 'U');
					
					$toobig=qa_image_file_too_big($_FILES['file']['tmp_name'], qa_opt('avatar_store_size'));
					
					if ($toobig)
						$errors['avatar']=qa_lang_sub('main/image_too_big_x_pc', (int)($toobig*100));
					elseif (!qa_set_user_avatar($qa_login_userid, file_get_contents($_FILES['file']['tmp_name']), $useraccount['avatarblobid']))
						$errors['avatar']=qa_lang_sub('main/image_not_read', implode(', ', qa_gd_image_formats()));
					break;
			}
		}

		$infield=array();
		foreach ($userfields as $userfield) {
			$fieldname='field_'.$userfield['fieldid'];
			$fieldvalue=qa_post_text($fieldname);

			$infield[$fieldname]=$fieldvalue;
			qa_profile_field_validate($fieldname, $fieldvalue, $errors);

			if (!isset($errors[$fieldname]))
				qa_db_user_profile_set($qa_login_userid, $userfield['title'], $fieldvalue);
		}
		
		list($useraccount, $userprofile)=qa_db_select_with_pending(
			qa_db_user_account_selectspec($qa_login_userid, true),
			qa_db_user_profile_selectspec($qa_login_userid, true)
		);

		qa_report_event('u_save', $qa_login_userid, $useraccount['handle'], $qa_cookieid);
		
		if (empty($errors))
			qa_redirect('account', array('state' => 'profile-saved'));

		qa_logged_in_user_flush();
	}


//	Process change password if clicked

	if (qa_clicked('dochangepassword')) {
		require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
		
		$inoldpassword=qa_post_text('oldpassword');
		$innewpassword1=qa_post_text('newpassword1');
		$innewpassword2=qa_post_text('newpassword2');
		
		$errors=array();
		
		if ($haspassword && (strtolower(qa_db_calc_passcheck($inoldpassword, $useraccount['passsalt'])) != strtolower($useraccount['passcheck'])))
			$errors['oldpassword']=qa_lang_html('users/password_wrong');

		$errors=array_merge($errors, qa_password_validate($innewpassword1));

		if ($innewpassword1 != $innewpassword2)
			$errors['newpassword2']=qa_lang_html('users/password_mismatch');
			
		if (empty($errors)) {
			qa_db_user_set_password($qa_login_userid, $innewpassword1);
			qa_db_user_set($qa_login_userid, 'sessioncode', ''); // stop old 'Remember me' style logins from still working
			qa_set_logged_in_user($qa_login_userid, $useraccount['handle'], false, $useraccount['sessionsource']); // reinstate this specific session

			qa_report_event('u_password', $qa_login_userid, $useraccount['handle'], $qa_cookieid);
		
			qa_redirect('account', array('state' => 'password-changed'));
		}
	}


//	Prepare content for theme

	$qa_content=qa_content_prepare();

	$qa_content['title']=qa_lang_html('profile/my_account_title');
	
	$qa_content['form_profile']=array(
		'tags' => 'ENCTYPE="multipart/form-data" METHOD="POST" ACTION="'.qa_self_html().'"',
		
		'style' => 'wide',
		
		'fields' => array(
			'duration' => array(
				'type' => 'static',
				'label' => qa_lang_html('users/member_for'),
				'value' => qa_time_to_string(qa_opt('db_time')-$useraccount['created']),
			),
			
			'type' => array(
				'type' => 'static',
				'label' => qa_lang_html('users/member_type'),
				'value' => qa_html(qa_user_level_string($useraccount['level'])),
			),
			
			'handle' => array(
				'label' => qa_lang_html('users/handle_label'),
				'tags' => 'NAME="handle"',
				'value' => qa_html(isset($inhandle) ? $inhandle : $useraccount['handle']),
				'error' => qa_html(@$errors['handle']),
				'type' => $changehandle ? 'text' : 'static',
			),
			
			'email' => array(
				'label' => qa_lang_html('users/email_label'),
				'tags' => 'NAME="email"',
				'value' => qa_html(isset($inemail) ? $inemail : $useraccount['email']),
				'error' => isset($errors['email']) ? qa_html($errors['email']) :
					(($doconfirms && !$isconfirmed) ? qa_insert_login_links(qa_lang_html('users/email_please_confirm')) : null),
			),
			
			'notify_annoucements' => array(
				'type'  => 'checkbox',
				'label' => qa_lang_html('users/notify_announcements_label'),
				'tags' => 'NAME="notify_an"',
				'value' => !($useraccount['flags'] & QA_USER_FLAGS_NOTIFY_ANNOUNCEMENTS),
				'note'  => qa_lang_html('users/notify_annoucements_explanation'),
			),
			
			'notify_questions' => array(
				'type'  => 'checkbox',
				'label' => qa_lang_html('users/notify_questions_label'),
				'tags' => 'NAME="notify_q"',
				'value' => !($useraccount['flags'] & QA_USER_FLAGS_NOTIFY_QUESTIONS),
				'note'  => qa_lang_html('users/notify_questions_explanation'),
			),			
			
			'notify_answers' => array(
				'type'  => 'checkbox',
				'label' => qa_lang_html('users/notify_answers_label'),
				'tags' => 'NAME="notify_a"',
				'value' => !($useraccount['flags'] & QA_USER_FLAGS_NOTIFY_ANSWERS),
				'note'  => qa_lang_html('users/notify_answers_explanation'),
			),				
			'messages' => array(
				'label' => qa_lang_html('users/private_messages'),
				'tags' => 'NAME="messages"',
				'type' => 'checkbox',
				'value' => !($useraccount['flags'] & QA_USER_FLAGS_NO_MESSAGES),
				'note' => qa_lang_html('users/private_messages_explanation'),
			),
			
			'avatar' => null, // for positioning
		),
		
		'buttons' => array(
			'save' => array(
				'label' => qa_lang_html('users/save_profile'),
			),
		),
		
		'hidden' => array(
			'dosaveprofile' => '1'
		),
	);
	
	if ($qa_state=='profile-saved')
		$qa_content['form_profile']['ok']=qa_lang_html('users/profile_saved');
	
	if (!qa_opt('allow_private_messages'))
		unset($qa_content['form_profile']['fields']['messages']);
		

//	Avatar upload stuff

	if (qa_opt('avatar_allow_gravatar') || qa_opt('avatar_allow_upload')) {
		$avataroptions=array();
		
		if (qa_opt('avatar_default_show') && strlen(qa_opt('avatar_default_blobid'))) {
			$avataroptions['']='<SPAN STYLE="margin:2px 0; display:inline-block;">'.
				qa_get_avatar_blob_html(qa_opt('avatar_default_blobid'), qa_opt('avatar_default_width'), qa_opt('avatar_default_height'), 32).
				'</SPAN> '.qa_lang_html('users/avatar_default');
		} else
			$avataroptions['']=qa_lang_html('users/avatar_none');

		$avatarvalue=$avataroptions[''];
	
		if (qa_opt('avatar_allow_gravatar')) {
			$avataroptions['gravatar']='<SPAN STYLE="margin:2px 0; display:inline-block;">'.
				qa_get_gravatar_html($useraccount['email'], 32).' '.strtr(qa_lang_html('users/avatar_gravatar'), array(
					'^1' => '<A HREF="http://www.gravatar.com/" TARGET="_blank">',
					'^2' => '</A>',
				)).'</SPAN>';

			if ($useraccount['flags'] & QA_USER_FLAGS_SHOW_GRAVATAR)
				$avatarvalue=$avataroptions['gravatar'];
		}

		if (qa_has_gd_image() && qa_opt('avatar_allow_upload')) {
			$avataroptions['uploaded']='<INPUT NAME="file" TYPE="file">';

			if (isset($useraccount['avatarblobid']))
				$avataroptions['uploaded']='<SPAN STYLE="margin:2px 0; display:inline-block;">'.
					qa_get_avatar_blob_html($useraccount['avatarblobid'], $useraccount['avatarwidth'], $useraccount['avatarheight'], 32).
					'</SPAN>'.$avataroptions['uploaded'];

			if ($useraccount['flags'] & QA_USER_FLAGS_SHOW_AVATAR)
				$avatarvalue=$avataroptions['uploaded'];
		}
		
		$qa_content['form_profile']['fields']['avatar']=array(
			'type' => 'select-radio',
			'label' => qa_lang_html('users/avatar_label'),
			'tags' => 'NAME="avatar"',
			'options' => $avataroptions,
			'value' => $avatarvalue,
			'error' => qa_html(@$errors['avatar']),
		);
		
	} else
		unset($qa_content['form_profile']['fields']['avatar']);


//	Other profile fields

	foreach ($userfields as $userfield) {
		$fieldname='field_'.$userfield['fieldid'];
		
		$value=@$infield[$fieldname];
		if (!isset($value))
			$value=@$userprofile[$userfield['title']];
			
		$label=trim(qa_user_userfield_label($userfield), ':');
		if (strlen($label))
			$label.=':';
			
		$qa_content['form_profile']['fields'][$userfield['title']]=array(
			'label' => qa_html($label),
			'tags' => 'NAME="'.$fieldname.'"',
			'value' => qa_html($value),
			'error' => qa_html(@$errors[$fieldname]),
			'rows' => ($userfield['flags'] & QA_FIELD_FLAGS_MULTI_LINE) ? 8 : null,
		);
	}
	
//	Change password form

	$qa_content['form_password']=array(
		'tags' => 'METHOD="POST" ACTION="'.qa_self_html().'"',
		
		'style' => 'wide',
		
		'title' => qa_lang_html('users/change_password'),
		
		'fields' => array(
			'old' => array(
				'label' => qa_lang_html('users/old_password'),
				'tags' => 'NAME="oldpassword"',
				'value' => qa_html(@$inoldpassword),
				'type' => 'password',
				'error' => @$errors['oldpassword'],
			),
		
			'new_1' => array(
				'label' => qa_lang_html('users/new_password_1'),
				'tags' => 'NAME="newpassword1"',
				'type' => 'password',
				'error' => @$errors['password'],
			),

			'new_2' => array(
				'label' => qa_lang_html('users/new_password_2'),
				'tags' => 'NAME="newpassword2"',
				'type' => 'password',
				'error' => @$errors['newpassword2'],
			),
		),
		
		'buttons' => array(
			'change' => array(
				'label' => qa_lang_html('users/change_password'),
			),
		),
		
		'hidden' => array(
			'dochangepassword' => '1',
		),
	);
	
	if (!$haspassword) {
		$qa_content['form_password']['fields']['old']['type']='static';
		$qa_content['form_password']['fields']['old']['value']=qa_lang_html('users/password_none');
	}
	
	if ($qa_state=='password-changed')
		$qa_content['form_profile']['ok']=qa_lang_html('users/password_changed');

		
	return $qa_content;
	

/*
	Omit PHP closing tag to help avoid accidental output
*/