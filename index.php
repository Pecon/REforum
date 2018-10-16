<?php
	require_once 'functions.php';
	require_once 'database.php';
	require_once 'page.php';

	setPageTitle($site_name);
	reauthuser();

	if(isSet($_GET['action']))
	{
		switch(strToLower($_GET['action']))
		{
			case "post":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(!isSet($_POST['postcontent']))
				{
					error("Form error.");
				}

				else if($_SESSION['banned'] == true)
				{
					error("You are banned.");
				}

				else if(isSet($_POST['preview']))
				{
					$postStuff = $_POST['postcontent'];
					$preview = bb_parse($postStuff);

					addToBody('Here is a preview of your post.<br /><table class="forumTable"><tr><td class="postcontent">');
					addToBody($preview);
					addToBody('</td></tr></table><br /><form action="./?action=post&topic=' . "${_GET['topic']}&page=${_GET['page']}" . '" method="POST">
						<textarea name="postcontent" class="postbox" tabIndex="1">' . htmlentities($postStuff) . '</textarea>
						<br />
						<input class="postButtons" type="submit" name="post" value="Post" tabIndex="3">
						<input class="postButtons" type="submit" name="preview" value="Preview" tabIndex="2">
						</form><br />');
				}

				else if($_SESSION['lastpostingtime'] > time() - 20)
				{
					error("Please wait a minute before posting.");
				}

				else if(!isSet($_GET['topic']))
				{
					error("You need to be in a topic to post.");
				}
				else if(strLen(trim($_POST['postcontent'])) < 3)
				{
					error("Please make your post longer.");
				}
				else if(strLen(trim($_POST['postcontent'])) > 10000)
				{
					error("Your post is over the 10000 character limit.");
				}
				else if($_SESSION['lastpostdata'] == $_POST['postcontent'])
				{
					error("Oops! Looks like you already tried to post that message.");
				}
				else if(isSet($_POST['postcontent']))
				{
					$postID = createPost($_SESSION['userid'], intval($_GET['topic']), $_POST['postcontent']);
					addToBody("Post successful!");
					header("Location: ./?topic=${_GET['topic']}&page=${_GET['page']}#${postID}");
					$_SESSION['lastpostdata'] = $_POST['postcontent'];
					$_SESSION['lastpostingtime'] = time();
				}

				break;

			case "edit":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}
				if(!isSet($_GET['post']))
				{
					error("No post specified.");
					break;
				}
				$post = fetchSinglePost(intval($_GET['post']));
				if($post['userID'] !== $_SESSION['userid'] && !$_SESSION['admin'] == true)
				{
					error("You do not have permission to edit this post!");
					break;
				}
				else if(!isSet($_POST['editpost']))
				{
					addToBody("Editing post<br />\n<form method=\"post\" action=\"./?action=edit&post=${_GET['post']}&topic=${_GET['topic']}&page=${_GET['page']}\"><textarea name=\"editpost\" class=\"postbox\">${post['postData']}</textarea><br />\n<input class=\"postButtons\" type=\"submit\" value=\"Edit\"></form>\n");
				}
				else if(strLen(trim($_POST['editpost'])) < 3)
				{
					error("Please make your post longer.");
				}
				else if(strLen(trim($_POST['editpost'])) > 10000)
				{
					error("Your post is over the 10000 character limit.");
				}
				else
				{
					editPost($post['userID'], $post['postID'], $_POST['editpost']);
					header("Location: ./?topic=${_GET['topic']}&page=${_GET['page']}#${post['postID']}");
				}

				break;

			case "recentposts":
				displayRecentPosts(0, 40);
				break;

			case "newtopic":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				else if($_SESSION['banned'] == true)
				{
					error("You are banned.");
					break;
				}

				else if($_SESSION['lastpostingtime'] > time() - 20)
				{
					error("Please wait a minute before posting.");
					break;
				}

				else if(isSet($_POST['newtopicsubject']) && isSet($_POST['newtopicpost']))
				{
					if(strLen(trim($_POST['newtopicsubject'])) < 3)
					{
						error("Please make your topic title longer.");
						break;
					}
					else if(strLen(trim($_POST['newtopicsubject'])) > 130)
					{
						error("Topic title is longer than the 130 character maximum.");
						break;
					}
					else if(strLen(trim($_POST['newtopicpost'])) < 3)
					{
						error("Please make your post longer.");
						break;
					}
					else if(isSet($_POST['preview']))
					{
						$preview = bb_parse($_POST['newtopicpost']);

						addToBody('Here is a preview of your post.<br /><table class="forumTable"><tr><td class="postcontent">');
						addToBody($preview);
						addToBody('</td></tr></table><br />');

						addToBody('<form action="./?action=newtopic" method="POST" >
							Subject: <input type="text" maxLength="130" minLength="3" name="newtopicsubject" value="' . $_POST['newtopicsubject'] . '" tabIndex="1" required><br />
							Original post:<br />
							<textarea class="postbox" maxLength="' . ($_SESSION['admin'] ? 100000 : 30000) . '" minLength="3" name="newtopicpost" tabIndex="2">' . htmlentities($_POST['newtopicpost']) . '</textarea><br />
							<input class="postButtons" type="submit" name="create" value="Create topic" tabIndex="4">
							<input class="postButtons" type="submit" name="preview" value="Preview" tabIndex="3">
						</form>');
						break;
					}
					else if(strLen(trim($_POST['newtopicpost'])) > 30000)
					{
						if(!$_SESSION['admin'])
						{
							error("Your post is over the 30000 character limit. Size: " . strLen(trim($_POST['newtopicpost'])));
							break;
						}
						else if(strLen(trim($_POST['newtopicpost'])) > 100000)
						{
							error("Your post is over the 100000 character hard limit. Size: " . strLen(trim($_POST['newtopicpost'])));
							break;
						}
						else
						{
							$topicID = createThread($_SESSION['userid'], $_POST['newtopicsubject'], $_POST['newtopicpost']);
							header("Location: ./?topic=${topicID}");
							$_SESSION['lastpostdata'] = $_POST['newtopicsubject'];
							$_SESSION['lastpostingtime'] = time();
						}
					}
					else if($_SESSION['lastpostdata'] == $_POST['newtopicsubject'])
					{
						error("Oops! Looks like you already tried to post that message.");
						break;
					}
					else
					{
						$topicID = createThread($_SESSION['userid'], $_POST['newtopicsubject'], $_POST['newtopicpost']);
						header("Location: ./?topic=${topicID}");
						$_SESSION['lastpostdata'] = $_POST['newtopicsubject'];
						$_SESSION['lastpostingtime'] = time();
					}
				}

				else
				{
					addToBody('<form action="./?action=newtopic" method="POST" >
							Subject: <input type="text" maxLength="130" minLength="3" name="newtopicsubject" tabIndex="1" required><br />
							Original post:<br />
							<textarea class="postbox" maxLength="' . ($_SESSION['admin'] ? 100000 : 30000) . '" minLength="3" name="newtopicpost" tabIndex="2"></textarea><br />
							<input class="postButtons" type="submit" name="create" value="Create topic" tabIndex="4">
							<input class="postButtons" type="submit" name="preview" value="Preview" tabIndex="3">
						</form>');
				}
				break;

			case "viewedits":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You don't have permission to do this action.");
					break;
				}
				else if(!isSet($_GET['post']))
				{
					error("No post specified.");
					break;
				}
				else
				{
					displayPostEdits(intval($_GET['post']));
				}
				break;

			case "messaging":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_GET['id']))
					displayMessage($_GET['id']);
				else if(isSet($_GET['page']))
				{
					displayRecentMessages($_GET['page'], false);
				}
				else
					displayRecentMessages(0, false);

				break;

			case "outbox":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_GET['id']))
					displayMessage($_GET['id']);
				else if(isSet($_GET['page']))
				{
					displayRecentMessages($_GET['page'], true);
				}
				else
					displayRecentMessages(0, true);

				break;

			case "composemessage":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_POST['toName']) && isSet($_POST['subject']) && isSet($_POST['postcontent']))
				{
					if(isSet($_POST['preview']))
					{
						// Create preview
						$postStuff = $_POST['postcontent'];
						$preview = bb_parse($postStuff);
						
						addToBody('Here is a preview of your message.<br /><table class="forumTable"><tr><td class="postcontent">');
						addToBody($preview);
						addToBody('</td></tr></table><br />');
					}
					else if($_SESSION['lastpostingtime'] > time() - 20)
					{
						error("Please wait a minute before doing that.");
					}
					else if(isSet($_POST['send']))
					{
						$success = sendMessage($_POST['postcontent'], $_POST['subject'], $_POST['toName'], (isSet($_POST['replyID']) ? $_POST['replyID'] : -1));

						if($success)
						{
							$_SESSION['lastpostingtime'] = time();
							addToBody("Message sent successfully!");
							header('location: ./?action=outbox');
						}
						break;
					}

					addToBody('<div class="topicHeader">&rarr; Composing message</div><br /><form action="./?action=composemessage" method="POST">
					To: <input type="text" name="toName" value="' . htmlentities($_POST['toName']) . '" tabIndex="1" required>
					<br />
					Subject: <input type="text" name="subject" value="' . htmlentities($_POST['subject']) . '" tabIndex="2" required>
					<br />
					<textarea name="postcontent" class="postbox" tabIndex="3">' . htmlentities($_POST['postcontent']) . '</textarea>
					<br />
					<input class="postButtons" type="submit" name="send" value="Send" tabIndex="5">
					<input class="postButtons" type="submit" name="preview" value="Preview" tabIndex="4">
					</form><br />');
					break;
				}

				addToBody('<div class="topicHeader">&rarr; Composing message</div><br /><form action="./?action=composemessage" method="POST">
					To: <input type="text" name="toName" value="" tabIndex="1" required>
					<br />
					Subject: <input type="text" name="subject" value="" tabIndex="2" required>
					<br />
					<textarea name="postcontent" class="postbox" tabIndex="3"></textarea>
					<br />
					<input class="postButtons" type="submit" name="send" value="Send" tabIndex="5">
					<input class="postButtons" type="submit" name="preview" value="Preview" tabIndex="4">
					</form><br />');

				break;

			case "deletemessage":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_POST['id']))
				{
					$result = deleteMessage($_POST['id']);

					if($result)
					{
						header('location: ./?action=messaging');
						addToBody("Successfully deleted message.");
					}
					else
						error("Could not delete message.");
				}
				else
					error("Invalid action.");

				break;

			case "viewprofile":
				if(!isSet($_GET['user']))
				{
					error("No profile was specified.");
					break;
				}

				displayUserProfile(intval($_GET['user']));
				break;

			case "updateprofile":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}
				else if(!isSet($_POST['updateProfileText']))
				{
					error("Did you forget to put something here?");
					break;
				}

				updateUserProfileText($_SESSION['userid'], $_POST['updateProfileText'], $_POST['tagline'], $_POST['website']);
				header("Location: ./?action=viewprofile&user=${_SESSION['userid']}");
				break;

			case "avatarchange":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_FILES['avatar']))
				{
					if($_FILES['avatar']['error'] !== UPLOAD_ERR_OK)
					{
						error("An error occurred while uploading your avatar. Please try again.<br /><a href=\"./?action=avatarchange\">Continue</a>");
						addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=avatarchange'\" />");
					}
					else if($_FILES['avatar']['size'] > 2024000)
					{
						error("Your avatar file is too large. Try to keep it under 2MB.<br /><a href=\"./?action=avatarchange\">Continue</a>");
						addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=avatarchange'\" />");
					}
					else
					{
						$location = "./data/avatartemp_${_SESSION['userid']}.dat";

						$success = updateAvatarByID($_SESSION['userid'], $location);

						if($success)
							header("Location: ./?action=viewprofile&user=${_SESSION['userid']}");
						else
						{
							addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=avatarchange'\" />");
							error("Couldn't update avatar.");
						}
					}
				}
				else
				{
					$form = <<<EOT
					<form enctype="multipart/form-data" method="POST">
						Avatar upload: <input type="file" accept=".jpg,.png,.gif,.bmp" name="avatar" />
						<input class="postButtons" type="submit" value="Upload" />
					</form><br />
					png, jpg, bmp, and gif files supported<br />
					Non-PNG images will be converted to PNG.<br />
					For best results, make your avatar a PNG of 100x100px or smaller.
EOT;
					addToBody($form);
				}
				break;

			case "passwordchange":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_POST['oldpassword']) && isSet($_POST['newpassword']) && isSet($_POST['confirmnewpassword']))
				{
					if(password_verify($_POST['oldpassword'], getPasswordHashByID($_SESSION['userid'])))
					{
						if($_POST['newpassword'] == $_POST['confirmnewpassword'])
						{
							updatePasswordByID($_SESSION['userid'], password_hash($_POST['newpassword'], PASSWORD_BCRYPT));
							addToBody("Your password has been updated.<br /><a href=\"./?action=viewprofile&user=${_SESSION['userid']}\">Continue</a>");
							addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=viewprofile&user=${_SESSION['userid']}'\" />");
						}
						else
							error("The new passwords you entered didn't match.<br /><a href=\"./?action=passwordchange\">Try again</a>");
							addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=passwordchange'\" />");
					}
					else
						error("Incorrect password.<br /><a href=\"./?action=passwordchange\">Try again</a>");
						addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=passwordchange'\" />");
				}
				else
				{
					$form = <<<EOT
					<form action="./?action=passwordchange" method="POST">
						Old password: <input type="password" name="oldpassword" /><br />
						New password: <input type="password" name="newpassword" /><br />
						Confirm new password: <input type="password" name="confirmnewpassword" /><br />
						<input class="postButtons" type="submit" value="Update password" />
					</form>
EOT;
					addToBody($form);
				}
				break;

			case "emailchange":
				if(isSet($_GET['code']) && isSet($_GET['id']))
				{
					if(verifyEmailChange($_GET['id'], $_GET['code']))
					{
						addToBody("Your new email was successfully verified!");
						addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./'\" />");
						break;
					}
					else
					{
						error("Email verification failed.");
						break;
					}
				}
				else if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(isSet($_POST['newemail']))
				{
					if(updateEmailByID($_SESSION['userid'], $_POST['newemail']))
					{
						if($require_email_verification)
							addToBody("A confirmation email has been sent to the new email address. Please click the link in the email to confirm this change.");
						else
						{
							addToBody("Your email has been updated.<br /><a href=\"./?action=viewprofile&user=${_SESSION['userid']}\">Continue</a>");
							addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=viewprofile&user=${_SESSION['userid']}'\" />");
						}
					}
					else
					{
						error("That is not a valid email address.<br /><a href=\"./?action=emailchange\">Try again</a>");
						addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./?action=emailchange'\" />");
					}
				}
				else
				{
					$form = <<<EOT
					<form action="./?action=emailchange" method="POST">
						Enter new email address: <input class="validate" type="email" name="newemail" />
						<input class="postButtons" type="submit" value="Update email" />
					</form>
EOT;
					addToBody($form);
				}
				break;

			case "verify":
				$error = verifyAccount($_GET['code']);

				if($error === false)
				{
					error("Unable to verify account.");
					break;
				}

				addToBody("Account verified!");
				addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./'\" />");
				break;

			case "resetpassword":
				if(isSet($_GET['code']) && isSet($_GET['id']))
				{
					if(getVerificationByID($_GET['id']) !== $_GET['code'])
					{
						error("This verifcation code is invalid.");
						break;
					}
					if(!isSet($_POST['newpassword']))
					{
						$form = <<<'EOD'
						<h1>Complete Password Reset</h1>
						<table border=1 style="align: center; padding: 3px;">
							<form method="POST">
								New password: <input type="password" class="" minLength="${min_password_length}" maxLength="72" name="password" tabIndex="1" autocomplete="new-password" required pattern="(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~ ]{0,70}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\x27\x22,./<>?~]$)" /><br />
								Confirm password: <input type="password" name="confirmpassword" tabIndex="2" /><br />
								<input class="postButtons" type="submit" value="Change password" tabIndex="3" />
							</form>
						</table>
EOD;
						addToBody($form);
						break;
					}
					else
					{
						// Verify password requirements
						// Matches a string between 2-72 characters with only alphanumeric characters, spaces, or most ascii special characters. Spaces are not allowed at the beginning or end of the string (typo protection since it's unlikely a user would want that intentionally).
						// This same expression is used in the form html to let the client self-validate.
						if(!preg_match('(^[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~][A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~ ]{0,70}[A-Za-z0-9!@#$%^&*()\-_=+\[\]{}|\\;:\'",./<>?~]$)', $_POST['newpassword']))
						{
							error('Password is not valid. Passwords can contain alphanumeric characters, spaces, and common special characters. Unicode characters are not allowed and the password cannot begin or end with a space.  <br /><button onclick="goBack()">Try again</button>');
							break;
						}
						else if($_POST['newpassword'] !== $_POST['confirmpassword'])
						{
							error("The passwords you entered did not match.");
							break;
						}
						else if(strlen($_POST['newpassword']) < $min_password_length)
						{
							error("Error: Password is too short. Use at least ${min_password_length} characters. This is the only requirement aside from your password not being 'password'. <br /><button onclick=\"goBack()\">Try again</button>");
							break;
						}
						else if(stripos($_POST['newpassword'], "password") !== false && strlen($_POST['password']) < 16)
						{
							error("You've got to be kidding me. <br /><button onclick=\"goBack()\">Try again</button>");
							break;
						}

						$newPassword = password_hash($_POST['newpassword'], PASSWORD_BCRYPT);
						updatePasswordByID($_GET['id'], $newPassword);
						clearVerificationByID($_GET['id']);

						addToBody("Password reset completed successfully!");
						break;
					}
				}
				if(!isSet($_POST['email']))
				{
					$form = <<<EOT
					<h1>Reset Password</h1>
					<table border=1 style="align: center; padding: 3px;">
						<form method="POST">
							Email address: <input type="text" name="email" class="validate" /><br />
							<input class="postButtons" type="submit" value="Send reset email">
						</form>
					</table>
EOT;
					addToBody($form);
					break;
				}
				$error = sendResetEmail($_POST['email']);

				if($error === false)
				{
					error("Couldn't send reset email. Contact the system administrator.");
					break;
				}

				if($error == 1)
					addToBody("Reset email sent! Please follow the link in the email to reset your password.");
				break;

			case "locktopic":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(!isSet($_GET['topic']))
				{
					error("No topic specified.");
					break;
				}

				$result = lockTopic($_GET['topic']);
				if($result === -1)
					break;

				addToBody(($result ? "Locked" : "Unlocked") . " topic!");
				addToHead("<meta http-equiv=\"refresh\" content=\"1;URL='./?topic=${_GET['topic']}'\" />");
				break;

			case "stickytopic":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in to perform this action.");
					break;
				}

				if(!isSet($_GET['topic']))
				{
					error("No topic specified.");
					break;
				}

				$result = stickyTopic($_GET['topic']);
				if($result === -1)
					break;

				addToBody(($result ? "Sticky'd" : "Unsticky'd") . " topic!");
				addToHead("<meta http-equiv=\"refresh\" content=\"1;URL='./?topic=${_GET['topic']}'\" />");
				break;

			case "deletepost":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in.");
					break;
				}

				if(!$_SESSION['admin'])
				{
					error("You do not have permission to do this action.");
					break;
				}

				if(!isSet($_GET['post']))
				{
					error("No post specified.");
					break;
				}

				$result = deletePost($_GET['post']);

				if(!$result)
				{
					error("Failed to delete post.");
					break;
				}

				error("Post deleted successfully.");
				break;

			case "ban":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in.");
					break;
				}
				if(!$_SESSION['admin'])
				{
					error("You do not have permission to do this action.");
					break;
				}

				if(!isSet($_GET['id']))
				{
					error("No user id specified.");
					break;
				}

				$result = toggleBanUserByID($_GET['id']);
				addToBody(($result ? "Banned" : "Unbanned") . " user!");
				addToHead("<meta http-equiv=\"refresh\" content=\"1;URL='./?action=viewProfile&user=${_GET['id']}'\" />");
				break;

			case "promote":
				if(!isSet($_SESSION['loggedin']))
				{
					error("You must be logged in.");
					break;
				}
				if(!$_SESSION['admin'])
				{
					error("You do not have permission to do this action.");
					break;
				}

				if(!isSet($_GET['id']))
				{
					error("No user id specified.");
					break;
				}

				$result = togglePromoteUserByID($_GET['id']);
				addToBody(($result ? "Promoted" : "Demoted") . " user!");
				addToHead("<meta http-equiv=\"refresh\" content=\"1;URL='./?action=viewProfile&user=${_GET['id']}'\" />");
				break;

			case "search":
				addToBody("");
				break;

			default:
				error("Unknown action.");
				break;
		}
	}
	if(isSet($_GET['topic']))
	{
		if(!isSet($_GET['page']))
		{
			$_page = 0;
		}
		else
			$_page = intval($_GET['page']);

		$_topicID = intval($_GET['topic']);

		loadThemePart("topic");
	}
	else if(!isSet($_GET['action']))
	{
		if(!isSet($_GET['page']))
			$page = 0;
		else
			$page = intval($_GET['page']);

		displayRecentThreads($page);

		if(isSet($_SESSION['loggedin']))
			addToBody("<br /><br /><a href=\"./?action=newtopic\">Post a new topic</a>\n");

		addToBody("<br /><br /><a href=\"./?action=recentPosts\">Show all recent posts</a>\n");

		if(isSet($_SESSION['admin']))
			if($_SESSION['admin'])
				addToBody("<br /><a href=\"./admin.php\">Admin</a>");
	}

	// End of possible actions, close mysql connection.
	disconnectSQL();
	finishPage();
?>