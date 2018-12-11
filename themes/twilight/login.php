<?php
	global $site_name, $require_email_verification;
	setPageTitle("$site_name - Login");

	function showLoginForm($fillUsername, $fillPassword)
	{
		$fillUsername = htmlentities($fillUsername);
		$fillPassword = htmlentities($fillPassword);
		?>
<h1>Forum Login</h1>
<br>
<form method="POST">
<table class="loginTable">
	<tr class="loginTable">
		<td class="loginTable">
		Username:
	</td>
	<td class="loginTable">
		<input style="margin: 0px; height: 100%;" type="text" name="username" tabIndex="1" value="<?php print($fillUsername); ?>" autofocus required />
	</td>
	</tr>
	<tr class="loginTable">
		<td class="loginTable">
			Password:
		</td>
		<td class="loginTable">
			<input style="margin: 0px; height: 100%;" type="password" name="password" tabIndex="2" required autocomplete="current-password" value="<?php print($fillPassword); ?>" />
		</td>
	</tr>
	<tr class="loginTable">
		<td class="loginTable">
			<input type="hidden" name="loggingin" value="true" />
			<input style="margin: 0px; height: 100%; width: 100%;" type="submit" value="Log in" tabIndex="3"  />
		</td>
		<td class="loginTable"></td>
	</tr>
</table>
</form>
<br><br>
...or <a href=register.php>register here</a>
<br><br><br><h2>Lost password?</h2><br>
<a href="./index.php?action=resetpassword">Reset your password</a>
<br>
<br>
		<?php
	}


	if(isSet($_SESSION['loggedin']))
	{
		error("You are already logged in.");
		return;
	}

	if(isSet($_POST['loggingin']))
	{
		usleep(5000);

		if(!isSet($_POST['username']) || !isSet($_POST['password']))
		{
			error("Didn't you forget to send some other post variables??? Like, geeze, you're not even trying.");
			showLoginForm("", "");
		}
		$username = $_POST['username'];

		$userData = findUserByName($username);
		if($userData === false)
		{
			error('No user exists by that name.');
			showLoginForm("", "");
			return;
		}

		$username = $userData['username'];
		$passkey = $userData['passkey'];

		if(!password_verify($_POST['password'], $passkey))
		{
			info('Incorrect password.', "Login");
			showLoginForm($_POST['username'], "");
			return;
		}

		if($require_email_verification && $userData['verified'] == 0)
		{
			error("You must verify your email address before logging in.");
			showLoginForm("", "");
			return;
		}

		$_SESSION['loggedin'] = true;
		$_SESSION['name'] = $username;

		switch($userData['usergroup'])
		{
			case "superuser":
				$_SESSION['superuser'] = true;
				$_SESSION['admin'] = true;
				$_SESSION['moderator'] = true;
				$_SESSION['member'] = true;
				break;
			case "admin":
				$_SESSION['superuser'] = false;
				$_SESSION['admin'] = true;
				$_SESSION['moderator'] = true;
				$_SESSION['member'] = true;
				break;
			case "moderator":
				$_SESSION['superuser'] = false;
				$_SESSION['admin'] = false;
				$_SESSION['moderator'] = true;
				$_SESSION['member'] = true;
				break;
			case "member":
				$_SESSION['superuser'] = false;
				$_SESSION['admin'] = false;
				$_SESSION['moderator'] = false;
				$_SESSION['member'] = true;
				break;
			case "unverified":
				$_SESSION['superuser'] = false;
				$_SESSION['admin'] = false;
				$_SESSION['moderator'] = false;
				$_SESSION['member'] = false;
				break;

		}
		$_SESSION['banned'] = $userData['banned'];
		$_SESSION['userid'] = $userData['id'];
		$_SESSION['lastpostdata'] = "";
		$_SESSION['lastpostingtime'] = time();

		if($_SESSION['banned'] == true)
		{
			error("Your account is banned. Goodbye.");
			session_destroy();
		}
		else
		{
			if($_SESSION['admin'] == true)
				info("Logged in as administrator.<br><a href=\"./\">Continue</a>", "Login");
			else
				info("Logged in!<br><a href=\"./\">Continue</a>", "Login");

			addToHead("<meta http-equiv=\"refresh\" content=\"3;URL='./'\" />");
		}
	}
	else
		showLoginForm("", "");
?>