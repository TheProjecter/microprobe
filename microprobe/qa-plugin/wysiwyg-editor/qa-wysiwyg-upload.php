<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/wysiwyg-editor/qa-wysiwyg-upload.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Page module class for WYSIWYG editor (CKEditor) file upload receiver


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


	class qa_wysiwyg_upload {
	
		function match_request($request)
		{
			return ($request=='wysiwyg-editor-upload');
		}
		
		function process_request($request)
		{
			$message='';
			$url='';
			
			if (is_array($_FILES) && count($_FILES)) {
			
			//	Check that we're allowed to upload images (if not, no other uploads are allowed either)
			
				if (!qa_opt('wysiwyg_editor_upload_images'))
					$message=qa_lang('users/no_permission');
			
			//	Check that we haven't reached the upload limit and are not blocked
			
				if (empty($message)) {
					require_once QA_INCLUDE_DIR.'qa-app-users.php';
					require_once QA_INCLUDE_DIR.'qa-app-limits.php';
			
					switch (qa_user_permit_error(null, 'U'))
					{
						case 'limit':
							$message=qa_lang('main/upload_limit');
							break;
						
						case false:
							qa_limits_increment(qa_get_logged_in_userid(), 'U');
							break;

						default:
							$message=qa_lang('users/no_permission');
							break;
					}
				}
				
			//	Find out some information about the uploaded file and check it's not too large

				if (empty($message)) {
					require_once QA_INCLUDE_DIR.'qa-app-blobs.php';

					$file=reset($_FILES);
					$pathinfo=pathinfo($file['name']);
					$extension=strtolower(@$pathinfo['extension']);
					$filesize=$file['size'];

					$maxsize=min(qa_opt('wysiwyg_editor_upload_max_size'), qa_get_max_upload_size());
					
					if ( ($filesize<=0) || ($filesize>$maxsize) ) // if file was too big for PHP, $filesize will be zero
						$message='Maximum upload size is '.number_format($maxsize/1048576, 1).'MB';
				}
				
			//	If it's only allowed to be an image, check it's an image

				if (empty($message))
					if (qa_get('qa_only_image') || !qa_opt('wysiwyg_editor_upload_all')) // check if we need to confirm it's an image
						switch ($extension) {
							case 'png':
							case 'gif':
							case 'jpeg':
							case 'jpg':
								break; // these are allowed image extensions
								
							default:
								$message=qa_lang_sub('main/image_not_read', 'GIF, JPG, PNG');
								break;
						}
			
			//	If there have been no errors, looks like we're all set...
						
				if (empty($message)) {
					require_once QA_INCLUDE_DIR.'qa-db-blobs.php';

					$userid=qa_get_logged_in_userid();
					$cookieid=isset($userid) ? qa_cookie_get() : qa_cookie_get_create();
					
					$blobid=qa_db_blob_create(file_get_contents($file['tmp_name']), $extension, @$file['name'], $userid, $cookieid, qa_remote_ip_address());
					
					if (isset($blobid))
						$url=qa_get_blob_url($blobid, true);
					else
						$message='Failed to create object in database - please try again';
				}
			}
			
			echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction(".qa_js(qa_get('CKEditorFuncNum')).
				", ".qa_js($url).", ".qa_js($message).");</script>";
			
			return null;
		}
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/