<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-db-install.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Database-level functions for installation and upgrading


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


	define('QA_DB_VERSION_CURRENT', 30);


	function qa_db_user_column_type_verify()
/*
	Return the column type for user ids after verifying it is one of the legal options
*/
	{
		$coltype=strtoupper(qa_get_mysql_user_column_type());

		switch ($coltype) {
			case 'SMALLINT':
			case 'MEDIUMINT':
			case 'INT':
			case 'BIGINT':
			case 'SMALLINT UNSIGNED':
			case 'MEDIUMINT UNSIGNED':
			case 'INT UNSIGNED':
			case 'BIGINT UNSIGNED':
				// these are all OK
				break;
			
			default:
				if (!preg_match('/VARCHAR\([0-9]+\)/', $coltype))
					qa_fatal_error('Specified user column type is not one of allowed values - please read documentation');
				break;
		}
		
		return $coltype;
	}

	
	function qa_db_table_definitions()
/*
	Return an array of table definitions. For each element of the array, the key is the table name (without prefix)
	and the value is an array of column definitions, [column name] => [definition]. The column name is omitted for indexes.
*/
	{
		require_once QA_INCLUDE_DIR.'qa-db-maxima.php';
		require_once QA_INCLUDE_DIR.'qa-app-users.php';

	/*
		Important note on character encoding in database and PHP connection to MySQL

		All of our TEXT-style columns get UTF-8 character sets and collation from the default defined on the tables.
		However, when connecting to MySQL from PHP, we don't issue a SET NAMES 'utf8' query or call mysql_set_charset('utf8').
		This saves latency, since it could incur an extra query that needs to go from PHP to MySQL and back.
		But as a result, MySQL assumes by default that our connection from PHP is using LATIN-1 encoding.
		It would therefore convert the character set of both incoming and outgoing text, which would be a disaster.
		We prevent this by retrieving columns using BINARY (e.g. SELECT BINARY name AS name) since binary content is left as is.
		And also we precede any text in our queries with the _utf8 introducer, so that MySQL understands it's already UTF-8.
	*/
	
	/*
		Other notes on the definitions below
		
		* In MySQL versions prior to 5.0.3, VARCHAR(x) columns will be silently converted to TEXT where x>255
		
		* See box at top of qa-app-recalc.php for a list of redundant (non-normal) information in the database
		
		* Starting in version 1.2, we explicitly name keys and foreign key constraints, instead of allowing MySQL
		  to name these by default. Our chosen names match the default names that MySQL would have assigned, and
		  indeed *did* assign for people who installed an earlier version of QA. By naming them explicitly, we're
		  on more solid ground for possible future changes to indexes and foreign keys in the schema.
		  
		* There are other foreign key constraints that it would be valid to add, but that would not serve much
		  purpose in terms of preventing inconsistent data being retrieved, and would just slow down some queries.
		  
		* We name some columns here in a not entirely intuitive way. The reason is to match the names of columns in
		  other tables which are of a similar nature. This will save time and space when combining several SELECT
		  queries together via a UNION in qa_db_multi_select() - see comments in qa-db.php for more information.
	*/
	
		$useridcoltype=qa_db_user_column_type_verify();

		$tables=array(
			'users' => array(
				'userid' => $useridcoltype.' NOT NULL AUTO_INCREMENT',
				'created' => 'DATETIME NOT NULL',
				'createip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address when created
				'email' => 'VARCHAR('.QA_DB_MAX_EMAIL_LENGTH.') NOT NULL',
				'handle' => 'VARCHAR('.QA_DB_MAX_HANDLE_LENGTH.') NOT NULL', // username
				'avatarblobid' => 'BIGINT UNSIGNED', // blobid of stored avatar
				'avatarwidth' => 'SMALLINT UNSIGNED', // pixel width of stored avatar
				'avatarheight' => 'SMALLINT UNSIGNED', // pixel height of stored avatar
				'passsalt' => 'BINARY(16)', // salt used to calculate passcheck - null if no passowrd set for direct login
				'passcheck' => 'BINARY(20)', // checksum from password and passsalt - null if no passowrd set for direct login
				'level' => 'TINYINT UNSIGNED NOT NULL', // basic, editor, admin, etc...
				'loggedin' => 'DATETIME NOT NULL', // time of last login
				'loginip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address of last login
				'written' => 'DATETIME', // time of last write action done by user
				'writeip' => 'INT UNSIGNED', // INET_ATON of IP address of last write action done by user
				'emailcode' => 'CHAR(8) CHARACTER SET ascii NOT NULL DEFAULT \'\'', // for email confirmation or password reset
				'sessioncode' => 'CHAR(8) CHARACTER SET ascii NOT NULL DEFAULT \'\'', // for comparing against session cookie in browser
				'sessionsource' => 'VARCHAR (16) CHARACTER SET ascii DEFAULT \'\'', // e.g. facebook, openid, etc...
				'flags' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0', // email confirmed, user blocked, show avatar/gravatar, accept direct messages
				'PRIMARY KEY (userid)',
				'KEY email (email)',
				'KEY handle (handle)',
				'KEY level (level)',
			),
			
			'userlogins' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'source' => 'VARCHAR (16) CHARACTER SET ascii NOT NULL', // e.g. facebook, openid, etc...
				'identifier' => 'VARBINARY (1024) NOT NULL', // depends on source, e.g. Facebook uid or OpenID url
				'identifiermd5' => 'BINARY (16) NOT NULL', // used to reduce size of index on identifier
				'KEY source (source, identifiermd5)',
				'KEY userid (userid)',
			),
			
			'userprofile' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'title' => 'VARCHAR('.QA_DB_MAX_PROFILE_TITLE_LENGTH.') NOT NULL', // profile field name
				'content' => 'VARCHAR('.QA_DB_MAX_PROFILE_CONTENT_LENGTH.') NOT NULL', // profile field value
				'UNIQUE userid (userid,title)',
			),
			
			'userfields' => array(
				'fieldid' => 'SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT',
				'title' => 'VARCHAR('.QA_DB_MAX_PROFILE_TITLE_LENGTH.') NOT NULL', // to match title column in userprofile table
				'content' => 'VARCHAR('.QA_DB_MAX_PROFILE_TITLE_LENGTH.')', // for display on user profile pages - NULL means use default
				'position' => 'SMALLINT UNSIGNED NOT NULL',
				'flags' => 'TINYINT UNSIGNED NOT NULL',
				'PRIMARY KEY (fieldid)',
			),

			'cookies' => array(
				'cookieid' => 'BIGINT UNSIGNED NOT NULL',
				'created' => 'DATETIME NOT NULL',
				'createip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address when cookie created
				'written' => 'DATETIME', // time of last write action done by anon user with cookie
				'writeip' => 'INT UNSIGNED', // INET_ATON of IP address of last write action done by anon user with cookie
				'PRIMARY KEY (cookieid)',
			),
			
			'categories' => array(
				'categoryid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
				'parentid' => 'INT UNSIGNED',
				'title' => 'VARCHAR('.QA_DB_MAX_CAT_PAGE_TITLE_LENGTH.') NOT NULL', // category name
				'tags' => 'VARCHAR('.QA_DB_MAX_CAT_PAGE_TAGS_LENGTH.') NOT NULL', // slug (url fragment) used to identify category
				'content' => 'VARCHAR('.QA_DB_MAX_CAT_CONTENT_LENGTH.') NOT NULL DEFAULT \'\'', // description of category
				'qcount' => 'INT UNSIGNED NOT NULL DEFAULT 0',
				'position' => 'SMALLINT UNSIGNED NOT NULL',
				'backpath' => 'VARCHAR('.(QA_CATEGORY_DEPTH*(QA_DB_MAX_CAT_PAGE_TAGS_LENGTH+1)).') NOT NULL DEFAULT \'\'',
					// full slug path for category, with forward slash separators, in reverse order to make index from effective
				'PRIMARY KEY (categoryid)',
				'UNIQUE parentid (parentid, tags)',
				'UNIQUE parentid_2 (parentid, position)',
				'KEY backpath (backpath('.QA_DB_MAX_CAT_PAGE_TAGS_LENGTH.'))',
			),
			
			'pages' => array(
				'pageid' => 'SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT',
				'title' => 'VARCHAR('.QA_DB_MAX_CAT_PAGE_TITLE_LENGTH.') NOT NULL', // title for navigation
				'nav' => 'CHAR(1) CHARACTER SET ascii NOT NULL', // which navigation does it go in (M=main, F=footer, B=before main, O=opposite main, other=none)
				'position' => 'SMALLINT UNSIGNED NOT NULL', // global ordering, which allows links to be ordered within each nav area
				'flags' => 'TINYINT UNSIGNED NOT NULL', // local or external, open in new window?
				'tags' => 'VARCHAR('.QA_DB_MAX_CAT_PAGE_TAGS_LENGTH.') NOT NULL', // slug (url fragment) for page, or url for external pages
				'heading' => 'VARCHAR('.QA_DB_MAX_TITLE_LENGTH.')', // for display within <H1> tags
				'content' => 'MEDIUMTEXT', // remainder of page HTML
				'PRIMARY KEY (pageid)',
				'UNIQUE tags (tags)',
				'UNIQUE position (position)',
			),
			
			'widgets' => array(
				'widgetid' => 'SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT',
				'place' => 'CHAR(2) CHARACTER SET ascii NOT NULL',
					// full region: FT=very top of page, FH=below nav area, FL=above footer, FB = very bottom of page
					// side region: ST=top of side, SH=below sidebar, SL=below categories, SB=very bottom of side
					// main region: MT=top of main, MH=below page title, ML=above links, MB=very bottom of main region
				'position' => 'SMALLINT UNSIGNED NOT NULL', // global ordering, which allows widgets to be ordered within each place
				'tags' => 'VARCHAR('.QA_DB_MAX_WIDGET_TAGS_LENGTH.') CHARACTER SET ascii NOT NULL', // comma-separated list of templates to display on
				'title' => 'VARCHAR('.QA_DB_MAX_WIDGET_TITLE_LENGTH.') NOT NULL', // name of widget module that should be displayed
				'PRIMARY KEY (widgetid)',
				'UNIQUE position (position)',
			),
			
			'posts' => array(
				'postid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
				'type' => "ENUM('Q', 'A', 'C', 'Q_HIDDEN', 'A_HIDDEN', 'C_HIDDEN') NOT NULL",
				'parentid' => 'INT UNSIGNED', // for follow on questions, all answers and comments
				'categoryid' => 'INT UNSIGNED', // this is the canonical final category id
				'catidpath1' => 'INT UNSIGNED', // the catidpath* columns are calculated from categoryid, for the full hierarchy of that category
				'catidpath2' => 'INT UNSIGNED', // note that QA_CATEGORY_DEPTH=4
				'catidpath3' => 'INT UNSIGNED',
				'acount' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0', // number of answers (for questions)
				'selchildid' => 'INT UNSIGNED', // selected answer (for questions)
				'userid' => $useridcoltype, // which user wrote it
				'cookieid' => 'BIGINT UNSIGNED', // which cookie wrote it, if an anonymous post
				'createip' => 'INT UNSIGNED', // INET_ATON of IP address used to create the post
				'lastuserid' => $useridcoltype, // which user last modified it
				'lastip' => 'INT UNSIGNED', // INET_ATON of IP address which last modified the post
				'upvotes' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
				'downvotes' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 0',
				'netvotes' => 'SMALLINT NOT NULL DEFAULT 0',
				'lastviewip' => 'INT UNSIGNED', // INET_ATON of IP address which last viewed the post
				'views' => 'INT UNSIGNED NOT NULL DEFAULT 0',
				'hotness' => 'FLOAT',
				'flagcount' => 'TINYINT UNSIGNED NOT NULL DEFAULT 0',
				'format' => 'VARCHAR('.QA_DB_MAX_FORMAT_LENGTH.') CHARACTER SET ascii NOT NULL DEFAULT \'\'', // format of content, e.g. 'html'
				'created' => 'DATETIME NOT NULL',
				'updated' => 'DATETIME', // time of last update
				'title' => 'VARCHAR('.QA_DB_MAX_TITLE_LENGTH.')',
				'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.')',
				'tags' => 'VARCHAR('.QA_DB_MAX_TAGS_LENGTH.')', // string of tags separated by commas
				'notify' => 'VARCHAR('.QA_DB_MAX_EMAIL_LENGTH.')', // email address, or @ to get from user, or NULL for none
				'PRIMARY KEY (postid)',
				'KEY type (type, created)', // for getting recent questions, answers, comments
				'KEY type_2 (type, acount, created)', // for getting unanswered questions
				'KEY type_3 (type, flagcount, created)', // for getting posts with the most flags
				'KEY type_4 (type, netvotes, created)', // for getting posts with the most votes
				'KEY type_5 (type, views, created)', // for getting questions with the most views
				'KEY type_6 (type, hotness)', // for getting 'hot' questions
				'KEY parentid (parentid, type)', // for getting a question's answers, any post's comments and follow-on questions
				'KEY userid (userid, type, created)', // for recent questions, answers or comments by a user
				'KEY selchildid (selchildid)', // for counting how many of a user's answers have been selected
				'KEY catidpath1 (catidpath1, type, created)', // for getting question, answers or comments in a specific level category
				'KEY catidpath2 (catidpath2, type, created)', // note that QA_CATEGORY_DEPTH=4
				'KEY catidpath3 (catidpath3, type, created)',
				'KEY categoryid (categoryid, type, created)', // this can also be used for searching the equivalent of catidpath4
				'KEY createip (createip, created)', // for getting posts created by a specific IP address
				'KEY updated (updated, type)', // for getting recent edits across all categories
				'KEY catidpath1_2 (catidpath1, updated, type)', // for getting recent edits in a specific level category
				'KEY catidpath2_2 (catidpath2, updated, type)', // note that QA_CATEGORY_DEPTH=4
				'KEY catidpath3_2 (catidpath3, updated, type)',
				'KEY categoryid_2 (categoryid, updated, type)',
				'KEY lastip (lastip, updated, type)', // for getting posts edited by a specific IP address
				'CONSTRAINT ^posts_ibfk_2 FOREIGN KEY (parentid) REFERENCES ^posts(postid)', // ^posts_ibfk_1 is set later on userid
				'CONSTRAINT ^posts_ibfk_3 FOREIGN KEY (categoryid) REFERENCES ^categories(categoryid) ON DELETE SET NULL',
			),
			
			'blobs' => array(
				'blobid' => 'BIGINT UNSIGNED NOT NULL',
				'format' => 'VARCHAR('.QA_DB_MAX_FORMAT_LENGTH.') CHARACTER SET ascii NOT NULL', // format e.g. 'jpeg', 'gif', 'png'
				'content' => 'MEDIUMBLOB NOT NULL',
				'filename' => 'VARCHAR('.QA_DB_MAX_BLOB_FILE_NAME_LENGTH.')', // name of source file (if appropriate)
				'userid' => $useridcoltype, // which user created it
				'cookieid' => 'BIGINT UNSIGNED', // which cookie created it
				'createip' => 'INT UNSIGNED', // INET_ATON of IP address that created it
				'created' => 'DATETIME', // when it was created
				'PRIMARY KEY (blobid)',
			),
			
			'words' => array(
				'wordid' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
				'word' => 'VARCHAR('.QA_DB_MAX_WORD_LENGTH.') NOT NULL',
				'titlecount' => 'INT UNSIGNED NOT NULL DEFAULT 0', // only counts one per post
				'contentcount' => 'INT UNSIGNED NOT NULL DEFAULT 0', // only counts one per post
				'tagwordcount' => 'INT UNSIGNED NOT NULL DEFAULT 0', // for words in tags - only counts one per post
				'tagcount' => 'INT UNSIGNED NOT NULL DEFAULT 0', // for tags as a whole - only counts one per post (though no duplicate tags anyway)
				'PRIMARY KEY (wordid)',
				'KEY word (word)',
				'KEY tagcount (tagcount)', // for sorting by most popular tags
			),
			
			'titlewords' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'wordid' => 'INT UNSIGNED NOT NULL',
				'KEY postid (postid)',
				'KEY wordid (wordid)',
				'CONSTRAINT ^titlewords_ibfk_1 FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'CONSTRAINT ^titlewords_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
				
			'contentwords' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'wordid' => 'INT UNSIGNED NOT NULL',
				'count' => 'TINYINT UNSIGNED NOT NULL', // how many times word apperas in the post - anything over 255 can be ignored
				'type' => "ENUM('Q', 'A', 'C') NOT NULL", // the post's type (copied here for quick searching)
				'questionid' => 'INT UNSIGNED NOT NULL', // the id of the post's antecedent parent (here for quick searching)
				'KEY postid (postid)',
				'KEY wordid (wordid)',
				'CONSTRAINT ^contentwords_ibfk_1 FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'CONSTRAINT ^contentwords_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
			
			'tagwords' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'wordid' => 'INT UNSIGNED NOT NULL',
				'KEY postid (postid)',
				'KEY wordid (wordid)',
				'CONSTRAINT ^tagwords_ibfk_1 FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'CONSTRAINT ^tagwords_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
				
			'posttags' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'wordid' => 'INT UNSIGNED NOT NULL',
				'postcreated' => 'DATETIME NOT NULL', // created time of post (copied here for tag page's list of recent questions)
				'KEY postid (postid)',
				'KEY wordid (wordid,postcreated)',
				'CONSTRAINT ^posttags_ibfk_1 FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
				'CONSTRAINT ^posttags_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
			),
			
			'uservotes' => array(
				'postid' => 'INT UNSIGNED NOT NULL',
				'userid' => $useridcoltype.' NOT NULL',
				'vote' => 'TINYINT NOT NULL', // -1, 0 or 1
				'flag' => 'TINYINT NOT NULL', // 0 or 1
				'UNIQUE userid (userid, postid)',
				'KEY postid (postid)',
				'CONSTRAINT ^uservotes_ibfk_1 FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
			),
			
			// many userpoints columns could be unsigned but MySQL appears to mess up points calculations that go negative as a result
			
			'userpoints' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'points' => 'INT NOT NULL DEFAULT 0', // user's points as displayed, after final multiple
				'qposts' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of questions by user (excluding hidden)
				'aposts' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of answers by user (excluding hidden)
				'cposts' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of comments by user (excluding hidden)
				'aselects' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of questions by user where they've selected an answer
				'aselecteds' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of answers by user that have been selected as the best
				'qupvotes' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of questions the user has voted up
				'qdownvotes' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of questions the user has voted down
				'aupvotes' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of answers the user has voted up
				'adownvotes' => 'MEDIUMINT NOT NULL DEFAULT 0', // number of answers the user has voted down
				'qvoteds' => 'INT NOT NULL DEFAULT 0', // points from votes on this user's questions (applying per-question limits), before final multiple
				'avoteds' => 'INT NOT NULL DEFAULT 0', // points from votes on this user's answers (applying per-answer limits), before final multiple
				'upvoteds' => 'INT NOT NULL DEFAULT 0', // number of up votes received on this user's questions or answers
				'downvoteds' => 'INT NOT NULL DEFAULT 0', // number of down votes received on this user's questions or answers
				'PRIMARY KEY (userid)',
				'KEY points (points)',
			),
				
			'userlimits' => array(
				'userid' => $useridcoltype.' NOT NULL',
				'action' => 'CHAR(1) CHARACTER SET ascii NOT NULL', // Q/A/C = post question/answer/comment, V=vote, L=login, U=upload, F=flag, M=private message
				'period' => 'INT UNSIGNED NOT NULL', // integer representing hour of last action
				'count' => 'SMALLINT UNSIGNED NOT NULL', // how many of this action has been performed within that hour
				'UNIQUE userid (userid, action)',
			),
			
			// most columns in iplimits have the same meaning as those in userlimits
			
			'iplimits' => array(
				'ip' => 'INT UNSIGNED NOT NULL', // INET_ATON of IP address
				'action' => 'CHAR(1) CHARACTER SET ascii NOT NULL',
				'period' => 'INT UNSIGNED NOT NULL',
				'count' => 'SMALLINT UNSIGNED NOT NULL',
				'UNIQUE ip (ip, action)',
			),
				
			'options' => array(
				'title' => 'VARCHAR('.QA_DB_MAX_OPTION_TITLE_LENGTH.') NOT NULL', // name of option
				'content' => 'VARCHAR('.QA_DB_MAX_CONTENT_LENGTH.') NOT NULL', // value of option
				'PRIMARY KEY (title)',
			),
			
			'cache' => array(
				'type' => 'CHAR(8) CHARACTER SET ascii NOT NULL', // e.g. 'avXXX' for avatar sized to XXX pixels square
				'cacheid' => 'BIGINT UNSIGNED DEFAULT 0', // optional further identifier, e.g. blobid on which cache entry is based
				'content' => 'MEDIUMBLOB NOT NULL',
				'created' => 'DATETIME NOT NULL',
				'lastread' => 'DATETIME NOT NULL',
				'PRIMARY KEY (type,cacheid)',
				'KEY (lastread)',
			),
		);
		
		if (QA_FINAL_EXTERNAL_USERS) {
			unset($tables['users']);
			unset($tables['userlogins']);
			unset($tables['userprofile']);

		} else {
			$userforeignkey='FOREIGN KEY (userid) REFERENCES ^users(userid)';
			
			$tables['userlogins'][]='CONSTRAINT ^userlogins_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
			$tables['userprofile'][]='CONSTRAINT ^userprofile_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
			$tables['posts'][]='CONSTRAINT ^posts_ibfk_1 '.$userforeignkey.' ON DELETE SET NULL';
			$tables['uservotes'][]='CONSTRAINT ^uservotes_ibfk_2 '.$userforeignkey.' ON DELETE CASCADE';
			$tables['userlimits'][]='CONSTRAINT ^userlimits_ibfk_1 '.$userforeignkey.' ON DELETE CASCADE';
		}

		return $tables;
	}

	
	function qa_array_to_lower_keys($array)
/*
	Return $array with all keys converted to lower case
*/
	{
		$keyarray=array();

		foreach ($array as $value)
			$keyarray[strtolower($value)]=true;
			
		return $keyarray;
	}

	
	function qa_db_missing_tables($definitions)
/*
	Return a list of tables missing from the database, [table name] => [column/index definitions]
*/
	{
		$keydbtables=qa_array_to_lower_keys(qa_db_read_all_values(qa_db_query_raw('SHOW TABLES')));
		
		$missing=array();
		
		foreach ($definitions as $rawname => $definition)
			if (!isset($keydbtables[strtolower(qa_db_add_table_prefix($rawname))]))
				$missing[$rawname]=$definition;
		
		return $missing;
	}

	
	function qa_db_missing_columns($table, $definition)
/*
	Return a list of columns missing from $table in the database, given the full definition set in $definition
*/
	{
		$keycolumns=qa_array_to_lower_keys(qa_db_read_all_values(qa_db_query_sub('SHOW COLUMNS FROM ^'.$table)));
		
		$missing=array();
		
		foreach ($definition as $colname => $coldefn)
			if ( (!is_int($colname)) && !isset($keycolumns[strtolower($colname)]) )
				$missing[$colname]=$coldefn;
				
		return $missing;
	}

	
	function qa_db_get_db_version()
/*
	Return the current version of the database, to determine need for DB upgrades
*/
	{
		$definitions=qa_db_table_definitions();
		
		if (count(qa_db_missing_columns('options', $definitions['options']))==0) {
			$version=(int)qa_db_read_one_value(qa_db_query_sub("SELECT content FROM ^options WHERE title='db_version'"), true);
			
			if ($version>0)
				return $version;
		}
			
		return null;
	}

	
	function qa_db_set_db_version($version)
/*
	Set the current version in the database
*/
	{
		qa_db_query_sub("REPLACE ^options (title,content) VALUES ('db_version', #)", $version);
	}

	
	function qa_db_check_tables()
/*
	Return a string describing what is wrong with the database, or false if everything is just fine
*/
	{
		qa_db_query_raw('UNLOCK TABLES'); // we could be inside a lock tables block
		
		$version=qa_db_read_one_value(qa_db_query_raw('SELECT VERSION()'));
		
		if (((float)$version)<4.1)
			qa_fatal_error('MySQL version 4.1 or later is required - you appear to be running MySQL '.$version);
		
		$definitions=qa_db_table_definitions();
		$missing=qa_db_missing_tables($definitions);
		
		if (count($missing) == count($definitions))
			return 'none';
		
		else {
			if (!isset($missing['options'])) {
				$version=qa_db_get_db_version();
				
				if (isset($version) && ($version<QA_DB_VERSION_CURRENT))
					return 'old-version';
			}
		
			if (count($missing)) {
				if (defined('QA_MYSQL_USERS_PREFIX')) { // special case if two installations sharing users
					$datacount=0;
					$datamissing=0;
					
					foreach ($definitions as $rawname => $definition)
						if (qa_db_add_table_prefix($rawname)==(QA_MYSQL_TABLE_PREFIX.$rawname)) {
							$datacount++;
							
							if (isset($missing[$rawname]))
								$datamissing++;
						}
						
					if ( ($datacount==$datamissing) && ($datamissing==count($missing)) )
						return 'non-users-missing';
				}
							
				return 'table-missing';
				
			} else
				foreach ($definitions as $table => $definition)
					if (count(qa_db_missing_columns($table, $definition)))
						return 'column-missing';
		}
				
		return false;
	}

	
	function qa_db_install_tables()
/*
	Install any missing database tables and/or columns and automatically set version as latest.
	This is not suitable for use if the database needs upgrading.
*/
	{
		$definitions=qa_db_table_definitions();
		
		$missingtables=qa_db_missing_tables($definitions);
		
		foreach ($missingtables as $rawname => $definition) {
			qa_db_query_sub(qa_db_create_table_sql($rawname, $definition));

			if ($rawname=='userfields')
				qa_db_query_sub(qa_db_default_userfields_sql());
		}
		
		foreach ($definitions as $table => $definition) {
			$missingcolumns=qa_db_missing_columns($table, $definition);
			
			foreach ($missingcolumns as $colname => $coldefn)
				qa_db_query_sub('ALTER TABLE ^'.$table.' ADD COLUMN '.$colname.' '.$coldefn);
		}
		
		qa_db_set_db_version(QA_DB_VERSION_CURRENT);
	}

	
	function qa_db_create_table_sql($rawname, $definition)
/*
	Return the SQL command to create a table with $rawname and $definition obtained from qa_db_table_definitions()
*/
	{
		$querycols='';
		foreach ($definition as $colname => $coldef)
			$querycols.=(strlen($querycols) ? ', ' : '').(is_int($colname) ? $coldef : ($colname.' '.$coldef));
			
		return 'CREATE TABLE ^'.$rawname.' ('.$querycols.') ENGINE=InnoDB CHARSET=utf8';
	}
	
	
	function qa_db_default_userfields_sql()
/*
	Return the SQL to create the default entries in the userfields table (before 1.3 these were hard-coded in PHP)
*/
	{
		$oldprofileflags=array(
			'name' => 0,
			'location' => 0,
			'website' => QA_FIELD_FLAGS_LINK_URL,
			'about' => QA_FIELD_FLAGS_MULTI_LINE,
		);
		
		$sql='INSERT INTO ^userfields (title, position, flags) VALUES '; // content column will be NULL, meaning use default from lang files
		
		$index=0;
		foreach ($oldprofileflags as $title => $flags)
			$sql.=($index ? ', ' : '')."('".qa_db_escape_string($title)."', ".(++$index).", ".(int)@$oldprofileflags[$title].")";
				
		return $sql;
	}
	
	
	function qa_db_upgrade_tables()
/*
	Upgrade the database schema to the latest version, outputting progress to the browser
*/
	{
		require_once QA_INCLUDE_DIR.'qa-app-recalc.php';
		
		$definitions=qa_db_table_definitions();
		$keyrecalc=array();
		
	//	Write-lock all QA tables before we start so no one can read or write anything

		$keydbtables=qa_array_to_lower_keys(qa_db_read_all_values(qa_db_query_raw('SHOW TABLES')));

		foreach ($definitions as $rawname => $definition)
			if (isset($keydbtables[strtolower(qa_db_add_table_prefix($rawname))]))
				$locks[]='^'.$rawname.' WRITE';
				
		$locktablesquery='LOCK TABLES '.implode(', ', $locks);
			
		qa_db_upgrade_query($locktablesquery);
		
	//	Upgrade it step-by-step until it's up to date (do LOCK TABLES after ALTER TABLE because the lock can sometimes be lost)

		while (1) {
			$version=qa_db_get_db_version();
			
			if ($version>=QA_DB_VERSION_CURRENT)
				break;
			
			$newversion=$version+1;
			
			qa_db_upgrade_progress(QA_DB_VERSION_CURRENT-$version.' upgrade step/s remaining...');
			
			switch ($newversion) {
			
			//	Up to here: Version 1.0 beta 1
				
				case 2:
					qa_db_upgrade_query('ALTER TABLE ^posts DROP COLUMN votes, ADD COLUMN (upvotes '.$definitions['posts']['upvotes'].
						', downvotes '.$definitions['posts']['downvotes'].')');
					qa_db_upgrade_query($locktablesquery);
					$keyrecalc['dorecountposts']=true;
					break;
					
				case 3:
					qa_db_upgrade_query('ALTER TABLE ^userpoints ADD COLUMN (upvoteds '.$definitions['userpoints']['upvoteds'].
						', downvoteds '.$definitions['userpoints']['downvoteds'].')');
					qa_db_upgrade_query($locktablesquery);
					$keyrecalc['dorecalcpoints']=true;
					break;
					
				case 4:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD COLUMN lastuserid '.$definitions['posts']['lastuserid'].', CHANGE COLUMN updated updated '.$definitions['posts']['updated']);
					qa_db_upgrade_query($locktablesquery);
					qa_db_upgrade_query('UPDATE ^posts SET updated=NULL WHERE updated=0 OR updated=created');
					break;
					
				case 5:
					qa_db_upgrade_query('ALTER TABLE ^contentwords ADD COLUMN (type '.$definitions['contentwords']['type'].', questionid '.$definitions['contentwords']['questionid'].')');
					qa_db_upgrade_query($locktablesquery);
					$keyrecalc['doreindexposts']=true;
					break;
					
			//	Up to here: Version 1.0 beta 2
				
				case 6:
					qa_db_upgrade_query('ALTER TABLE ^userpoints ADD COLUMN cposts '.$definitions['userpoints']['cposts']);
					qa_db_upgrade_query($locktablesquery);
					$keyrecalc['dorecalcpoints']=true;
					break;
					
				case 7:
					if (!QA_FINAL_EXTERNAL_USERS) {
						qa_db_upgrade_query('ALTER TABLE ^users ADD COLUMN sessioncode '.$definitions['users']['sessioncode']);
						qa_db_upgrade_query($locktablesquery);
					}
					break;
					
				case 8:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD KEY (type, acount, created)');
					qa_db_upgrade_query($locktablesquery);
					$keyrecalc['dorecountposts']=true; // for unanswered question count
					break;

			//	Up to here: Version 1.0 beta 3, 1.0, 1.0.1 beta, 1.0.1
			
				case 9:
					if (!QA_FINAL_EXTERNAL_USERS) {
						qa_db_upgrade_query('ALTER TABLE ^users CHANGE COLUMN resetcode emailcode '.$definitions['users']['emailcode'].', ADD COLUMN flags '.$definitions['users']['flags']);
						qa_db_upgrade_query($locktablesquery);
						qa_db_upgrade_query('UPDATE ^users SET flags=1');
					}
					break;
				
				case 10:
					qa_db_upgrade_query(qa_db_create_table_sql('categories', array(
						'categoryid' => $definitions['categories']['categoryid'],
						'title' => $definitions['categories']['title'],
						'tags' => $definitions['categories']['tags'],
						'qcount' => $definitions['categories']['qcount'],
						'position' => $definitions['categories']['position'],
						'PRIMARY KEY (categoryid)',
						'UNIQUE tags (tags)',
						'UNIQUE position (position)',
					))); // hard-code list of columns and indexes to ensure we ignore any added at a later stage

					$locktablesquery.=', ^categories WRITE';
					qa_db_upgrade_query($locktablesquery);
					break;
				
				case 11:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD CONSTRAINT ^posts_ibfk_2 FOREIGN KEY (parentid) REFERENCES ^posts(postid), ADD COLUMN categoryid '.$definitions['posts']['categoryid'].', ADD KEY categoryid (categoryid, type, created), ADD CONSTRAINT ^posts_ibfk_3 FOREIGN KEY (categoryid) REFERENCES ^categories(categoryid) ON DELETE SET NULL');
						// foreign key on parentid important now that deletion is possible
					qa_db_upgrade_query($locktablesquery);
					break;
					
				case 12:
					qa_db_upgrade_query(qa_db_create_table_sql('pages', array(
						'pageid' => $definitions['pages']['pageid'],
						'title' => $definitions['pages']['title'],
						'nav' => $definitions['pages']['nav'],
						'position' => $definitions['pages']['position'],
						'flags' => $definitions['pages']['flags'],
						'tags' => $definitions['pages']['tags'],
						'heading' => $definitions['pages']['heading'],
						'content' => $definitions['pages']['content'],
						'PRIMARY KEY (pageid)',
						'UNIQUE tags (tags)',
						'UNIQUE position (position)',
					))); // hard-code list of columns and indexes to ensure we ignore any added at a later stage
					$locktablesquery.=', ^pages WRITE';
					qa_db_upgrade_query($locktablesquery);
					break;
					
				case 13:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD COLUMN createip '.$definitions['posts']['createip'].', ADD KEY createip (createip, created)');
					qa_db_upgrade_query($locktablesquery);
					break;
					
				case 14:
					qa_db_upgrade_query('ALTER TABLE ^userpoints DROP COLUMN qvotes, DROP COLUMN avotes, ADD COLUMN (qupvotes '.$definitions['userpoints']['qupvotes'].', qdownvotes '.$definitions['userpoints']['qdownvotes'].', aupvotes '.$definitions['userpoints']['aupvotes'].', adownvotes '.$definitions['userpoints']['adownvotes'].')');
					qa_db_upgrade_query($locktablesquery);
					$keyrecalc['dorecalcpoints']=true;
					break;
					
			//	Up to here: Version 1.2 beta 1
			
				case 15:
					if (!QA_FINAL_EXTERNAL_USERS)
						qa_db_upgrade_table_columns($definitions, 'users', array('emailcode', 'sessioncode', 'flags'));
					
					qa_db_upgrade_table_columns($definitions, 'posts', array('acount', 'upvotes', 'downvotes', 'format'));
					qa_db_upgrade_table_columns($definitions, 'categories', array('qcount'));
					qa_db_upgrade_table_columns($definitions, 'words', array('titlecount', 'contentcount', 'tagcount'));
					qa_db_upgrade_table_columns($definitions, 'userpoints', array('points', 'qposts', 'aposts', 'cposts',
						'aselects', 'aselecteds', 'qupvotes', 'qdownvotes', 'aupvotes', 'adownvotes', 'qvoteds', 'avoteds', 'upvoteds', 'downvoteds'));
					qa_db_upgrade_query($locktablesquery);
					break;

			//	Up to here: Version 1.2 (release)
			
				case 16:
					qa_db_upgrade_table_columns($definitions, 'posts', array('format'));
					qa_db_upgrade_query($locktablesquery);
					$keyrecalc['doreindexposts']=true; // because of new treatment of apostrophes in words
					break;
					
				case 17:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD KEY updated (updated, type), ADD KEY categoryid_2 (categoryid, updated, type)');
					qa_db_upgrade_query($locktablesquery);
					break;
					
				case 18:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD COLUMN lastip '.$definitions['posts']['lastip'].', ADD KEY lastip (lastip, updated, type)');
					qa_db_upgrade_query($locktablesquery);
					break;
					
				case 19:
					if (!QA_FINAL_EXTERNAL_USERS)
						qa_db_upgrade_query('ALTER TABLE ^users ADD COLUMN avatarblobid '.$definitions['users']['avatarblobid'].', ADD COLUMN avatarwidth '.$definitions['users']['avatarwidth'].', ADD COLUMN avatarheight '.$definitions['users']['avatarheight']);
					
					// hard-code list of columns and indexes to ensure we ignore any added at a later stage

					qa_db_upgrade_query(qa_db_create_table_sql('blobs', array(
						'blobid' => $definitions['blobs']['blobid'],
						'format' => $definitions['blobs']['format'],
						'content' => $definitions['blobs']['content'],
						'PRIMARY KEY (blobid)'
					)));

					qa_db_upgrade_query(qa_db_create_table_sql('cache', array(
						'type' => $definitions['cache']['type'],
						'cacheid' => $definitions['cache']['cacheid'],
						'content' => $definitions['cache']['content'],
						'created' => $definitions['cache']['created'],
						'lastread' => $definitions['cache']['lastread'],
						'PRIMARY KEY (type,cacheid)',
						'KEY (lastread)',
					))); // hard-code list of columns and indexes to ensure we ignore any added at a later stage

					$locktablesquery.=', ^blobs WRITE, ^cache WRITE';
					qa_db_upgrade_query($locktablesquery);
					break;
					
				case 20:
					if (!QA_FINAL_EXTERNAL_USERS) {
						qa_db_upgrade_query(qa_db_create_table_sql('userlogins', array(
							'userid' => $definitions['userlogins']['userid'],
							'source' => $definitions['userlogins']['source'],
							'identifier' => $definitions['userlogins']['identifier'],
							'identifiermd5' => $definitions['userlogins']['identifiermd5'],
							'KEY source (source, identifiermd5)',
							'KEY userid (userid)',
							'CONSTRAINT ^userlogins_ibfk_1 FOREIGN KEY (userid) REFERENCES ^users(userid) ON DELETE CASCADE',
						)));
						
						qa_db_upgrade_query('ALTER TABLE ^users CHANGE COLUMN passsalt passsalt '.$definitions['users']['passsalt'].', CHANGE COLUMN passcheck passcheck '.$definitions['users']['passcheck']);
						
						$locktablesquery.=', ^userlogins WRITE';
						qa_db_upgrade_query($locktablesquery);
					}
					break;
					
				case 21:
					if (!QA_FINAL_EXTERNAL_USERS) {
						qa_db_upgrade_query(qa_db_create_table_sql('userfields', array(
							'fieldid' => $definitions['userfields']['fieldid'],
							'title' => $definitions['userfields']['title'],
							'content' => $definitions['userfields']['content'],
							'position' => $definitions['userfields']['position'],
							'flags' => $definitions['userfields']['flags'],
							'PRIMARY KEY (fieldid)',
						)));
						
						$locktablesquery.=', ^userfields WRITE';
						qa_db_upgrade_query($locktablesquery);
						
						qa_db_upgrade_query(qa_db_default_userfields_sql());
					}
					break;
					
			//	Up to here: Version 1.3 beta 1
			
				case 22:
					if (!QA_FINAL_EXTERNAL_USERS) {
						qa_db_upgrade_query('ALTER TABLE ^users ADD COLUMN sessionsource '.$definitions['users']['sessionsource']);
						qa_db_upgrade_query($locktablesquery);
					}
					break;
			
			//	Up to here: Version 1.3 beta 2 and release
			
				case 23:
					qa_db_upgrade_query(qa_db_create_table_sql('widgets', array(
						'widgetid' => $definitions['widgets']['widgetid'],
						'place' => $definitions['widgets']['place'],
						'position' => $definitions['widgets']['position'],
						'tags' => $definitions['widgets']['tags'],
						'title' => $definitions['widgets']['title'],
						'PRIMARY KEY (widgetid)',
						'UNIQUE position (position)',
					)));
					
					$locktablesquery.=', ^widgets WRITE';
					qa_db_upgrade_query($locktablesquery);
					break;
					
				case 24:
					qa_db_upgrade_query(qa_db_create_table_sql('tagwords', array(
						'postid' => $definitions['tagwords']['postid'],
						'wordid' => $definitions['tagwords']['wordid'],
						'KEY postid (postid)',
						'KEY wordid (wordid)',
						'CONSTRAINT ^tagwords_ibfk_1 FOREIGN KEY (postid) REFERENCES ^posts(postid) ON DELETE CASCADE',
						'CONSTRAINT ^tagwords_ibfk_2 FOREIGN KEY (wordid) REFERENCES ^words(wordid)',
					)));
					
					$locktablesquery.=', ^tagwords WRITE';
					
					qa_db_upgrade_query('ALTER TABLE ^words ADD COLUMN tagwordcount '.$definitions['words']['tagwordcount']);
					qa_db_upgrade_query($locktablesquery);
					
					$keyrecalc['doreindexposts']=true;
					break;
					
			//	Up to here: Version 1.4 developer preview
			
				case 25:
					$keycolumns=qa_array_to_lower_keys(qa_db_read_all_values(qa_db_query_sub('SHOW COLUMNS FROM ^blobs')));
						// might be using blobs table shared with another installation, so check if we need to upgrade
					
					if (isset($keycolumns['filename']))
						qa_db_upgrade_progress('Skipping upgrading blobs table since it was already upgraded by another QA site sharing it.');

					else {
						qa_db_upgrade_query('ALTER TABLE ^blobs ADD COLUMN filename '.$definitions['blobs']['filename'].', ADD COLUMN userid '.$definitions['blobs']['userid'].', ADD COLUMN cookieid '.$definitions['blobs']['cookieid'].', ADD COLUMN createip '.$definitions['blobs']['createip'].', ADD COLUMN created '.$definitions['blobs']['created']);
						qa_db_upgrade_query($locktablesquery);
					}
					break;
					
				case 26:
					qa_db_upgrade_query('ALTER TABLE ^uservotes ADD COLUMN flag '.$definitions['uservotes']['flag']);
					qa_db_upgrade_query($locktablesquery);
					
					qa_db_upgrade_query('ALTER TABLE ^posts ADD COLUMN flagcount '.$definitions['posts']['flagcount'].', ADD KEY type_3 (type, flagcount, created)');
					qa_db_upgrade_query($locktablesquery);
					
					$keyrecalc['dorecountposts']=true;
					break;
					
				case 27:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD COLUMN netvotes '.$definitions['posts']['netvotes'].', ADD KEY type_4 (type, netvotes, created)');
					qa_db_upgrade_query($locktablesquery);
					
					$keyrecalc['dorecountposts']=true;
					break;
					
				case 28:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD COLUMN views '.$definitions['posts']['views'].', ADD COLUMN hotness '.$definitions['posts']['hotness'].', ADD KEY type_5 (type, views, created), ADD KEY type_6 (type, hotness)');
					qa_db_upgrade_query($locktablesquery);
					
					$keyrecalc['dorecountposts']=true;
					break;
					
				case 29:
					qa_db_upgrade_query('ALTER TABLE ^posts ADD COLUMN lastviewip '.$definitions['posts']['lastviewip']);
					qa_db_upgrade_query($locktablesquery);
					break;
					
				case 30:
					qa_db_upgrade_query('ALTER TABLE ^posts DROP FOREIGN KEY ^posts_ibfk_3'); // to allow category column types to be changed
					qa_db_upgrade_query($locktablesquery);
					
					qa_db_upgrade_query('ALTER TABLE ^posts DROP KEY categoryid, DROP KEY categoryid_2');
					qa_db_upgrade_query($locktablesquery);
					
					qa_db_upgrade_query('ALTER TABLE ^categories CHANGE COLUMN categoryid categoryid '.$definitions['categories']['categoryid'].', ADD COLUMN parentid '.$definitions['categories']['parentid'].', ADD COLUMN backpath '.$definitions['categories']['backpath'].', ADD COLUMN content '.$definitions['categories']['content'].', DROP INDEX tags, DROP INDEX position, ADD UNIQUE parentid (parentid, tags), ADD UNIQUE parentid_2 (parentid, position), ADD KEY backpath (backpath('.QA_DB_MAX_CAT_PAGE_TAGS_LENGTH.'))');
					qa_db_upgrade_query($locktablesquery);
					
					qa_db_upgrade_query('ALTER TABLE ^posts CHANGE COLUMN categoryid categoryid '.$definitions['posts']['categoryid'].', ADD COLUMN catidpath1 '.$definitions['posts']['catidpath1'].', ADD COLUMN catidpath2 '.$definitions['posts']['catidpath2'].', ADD COLUMN catidpath3 '.$definitions['posts']['catidpath3']); // QA_CATEGORY_DEPTH=4
					qa_db_upgrade_query($locktablesquery);
					
					qa_db_upgrade_query('ALTER TABLE ^posts ADD KEY catidpath1 (catidpath1, type, created), ADD KEY catidpath2 (catidpath2, type, created), ADD KEY catidpath3 (catidpath3, type, created), ADD KEY categoryid (categoryid, type, created), ADD KEY catidpath1_2 (catidpath1, updated, type), ADD KEY catidpath2_2 (catidpath2, updated, type), ADD KEY catidpath3_2 (catidpath3, updated, type), ADD KEY categoryid_2 (categoryid, updated, type)');
					qa_db_upgrade_query($locktablesquery);
					
					qa_db_upgrade_query('ALTER TABLE ^posts ADD CONSTRAINT ^posts_ibfk_3 FOREIGN KEY (categoryid) REFERENCES ^categories(categoryid) ON DELETE SET NULL');
					qa_db_upgrade_query($locktablesquery);
					
					$keyrecalc['dorecalccategories']=true;
					break;					
					
			//	Up to here: Version 1.4 beta 1 and 2
					
			}
			
			qa_db_set_db_version($newversion);
			
			if (qa_db_get_db_version()!=$newversion)
				qa_fatal_error('Could not increment database version');
		}
		
		qa_db_upgrade_query('UNLOCK TABLES');

	//	Perform any necessary recalculations, as determined by upgrade steps
		
		foreach ($keyrecalc as $state => $dummy)
			while ($state) {
				set_time_limit(60);
			
				$stoptime=time()+2;
				
				while ( qa_recalc_perform_step($state) && (time()<$stoptime) )
					;
				
				qa_db_upgrade_progress(qa_recalc_get_message($state));
			}
	}
	
	
	function qa_db_upgrade_table_columns($definitions, $table, $columns)
/*
	Reset the definitions of $columns in $table according to the $definitions array
*/
	{
		$sqlchanges=array();
		
		foreach ($columns as $column)
			$sqlchanges[]='CHANGE COLUMN '.$column.' '.$column.' '.$definitions[$table][$column];

		qa_db_upgrade_query('ALTER TABLE ^'.$table.' '.implode(', ', $sqlchanges));
	}

	
	function qa_db_upgrade_query($query)
/*
	Perform upgrade $query and output progress to the browser
*/
	{
		qa_db_upgrade_progress('Running query: '.qa_db_apply_sub($query, array()).' ...');
		qa_db_query_sub($query);
	}

	
	function qa_db_upgrade_progress($text)
/*
	Output $text to the browser (after converting to HTML) and do all we can to get it displayed
*/
	{
		echo qa_html($text).str_repeat('    ', 1024)."<BR><BR>\n";
		flush();
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/