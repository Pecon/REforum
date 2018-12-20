<?php
	global $_post;

	$post = fetchSinglePost(intval($_post));

	if($post['changeID'] == false)
	{
		error("There are no changes to view on this post.");
		return;
	}

	$postID = intval($_post);
	$user = findUserByID($post['userID']);
	$username = $user['username'];

	$changeID = $post['changeID'];

	$sql = "SELECT * FROM changes WHERE id=${changeID}";
	$result = querySQL($sql);

	if($result === false)
	{
		error("There are no edits to display.");
		return;
	}

	$change = $result -> fetch_assoc();
	$changeID = $change['lastChange'];
	$date = date("F d, Y H:i:s", $change['changeTime']);

	print('<div class="topicContainer">');
	print("<div class=\"topicHeader\"><span>&nbsp;&rarr;&nbsp;</span><h3>Post edits</h3></div>");


	print('<div class="post originalPost postBackgroundA">');
	print("\n<div class=\"postUser\"><a class=\"userLink\" name=\"${user['id']}\"></a><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${user['id']}\">${username}</a>");

	// Display the user's tagline
	if($user['banned'])
		print("<div class=\"userTagline taglineBanned finetext\">${user['tagline']}</div>");
	else if($user['administrator'])
		print("<div class=\"userTagline taglineAdmin finetext\">${user['tagline']}</div>");
	else
		print("<div class=\"userTagline tagline finetext\">${user['tagline']}</div>");

	print("<img class=\"avatar\" src=\"./avatar.php?user=${user['id']}&amp;cb=${user['avatarUpdated']}\" /><div class=\"postDate finetext\">${date}<br />(Current version)</div><div class=\"userPostSeperator\"></div></div>");

	print("\n<div class=\"postBody\"><div class=\"postText\">${post['postPreparsed']}</div></div></div>");


	
	$iterations = 2;

	while($changeID > 0)
	{
		if($iterations > 50)
		{
			warn("Too many edits to fully display.");
			break;
		}

		$changeText = $change['postData'];
		$sql = "SELECT * FROM changes WHERE id=${changeID}";
		$result = querySQL($sql);

		if($result == false)
		{
			error("Could not get edit.");
			break;
		}

		$change = $result -> fetch_assoc();
		$changeID = $change['lastChange'];
		$date = date("F d, Y H:i:s", $change['changeTime']);

		print('<div class="post originalPost ' . ($iterations % 2 ? "postBackgroundB" : "postBackgroundA") . '">');
		print("\n<div class=\"postUser\"><a class=\"userLink\" name=\"${user['id']}\"></a><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${user['id']}\">${username}</a>");

		// Display the user's tagline
		if($user['banned'])
			print("<div class=\"userTagline taglineBanned finetext\">${user['tagline']}</div>");
		else if($user['administrator'])
			print("<div class=\"userTagline taglineAdmin finetext\">${user['tagline']}</div>");
		else
			print("<div class=\"userTagline tagline finetext\">${user['tagline']}</div>");

		print("<img class=\"avatar\" src=\"./avatar.php?user=${user['id']}&amp;cb=${user['avatarUpdated']}\" /><div class=\"postDate finetext\">${date}</div><div class=\"userPostSeperator\"></div></div>");

		print("\n<div class=\"postBody\"><div class=\"postText\">${changeText}</div></div></div>");

		$iterations++;
	}

	$date = date("F d, Y H:i:s", $post['postDate']);
	print('<div class="post originalPost ' . ($iterations % 2 ? "postBackgroundB" : "postBackgroundA") . '">');
	print("\n<div class=\"postUser\"><a class=\"userLink\" name=\"${user['id']}\"></a><a class=\"userLink\" href=\"./?action=viewProfile&amp;user=${user['id']}\">${username}</a>");

	// Display the user's tagline
	if($user['banned'])
		print("<div class=\"userTagline taglineBanned finetext\">${user['tagline']}</div>");
	else if($user['administrator'])
		print("<div class=\"userTagline taglineAdmin finetext\">${user['tagline']}</div>");
	else
		print("<div class=\"userTagline tagline finetext\">${user['tagline']}</div>");

	print("<img class=\"avatar\" src=\"./avatar.php?user=${user['id']}&amp;cb=${user['avatarUpdated']}\" /><div class=\"postDate finetext\">${date}<br />(Original)</div><div class=\"userPostSeperator\"></div></div>");

	print("\n<div class=\"postBody\"><div class=\"postText\">${change['postData']}</div></div></div>");
	print('<div class="topicFooter"></div></div>');
?>