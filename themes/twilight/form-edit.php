<?php
	global $_postContentPrefill, $_subjectPrefill, $_postID, $_topicID, $_page, $_script_nonce;

	if(!isSet($_postContentPrefill))
		$_postContentPrefill = "";

	if(!isSet($_postID))
	{
		error("Must provide a valid postID.");
		return;
	}
	else
		$_postID = intval($_postID);

	if(!isSet($_topicID))
	{
		error("Must provide a valid topicID");
		return;
	}
	else
		$_topicID = intval($_topicID);

	if(!isSet($_page))
		$_page = 0;
	else
		$_page = intval($_page);

?>

<noscript>
	<style type="text/css">
		.editor-tray
		{
			display: none;
		}
		.editor-warnings
		{
			display: none;
		}
	</style>
</noscript>

<div class="editor" id="editor">
	<span>&nbsp;&rarr;&nbsp;</span><h3>Edit</h3><hr />
	<form action="./?action=edit&post=<?php print($_postID); ?>&topic=<?php print($_topicID); ?>&page=<?php print($_page); ?>" method="POST">
		<?php 	if(isSet($_subjectPrefill))
				{	?>
		<div>
			Subject
		</div>
		<input class="editor-input" type="text" maxLength="130" minLength="3" name="edittopicsubject" value="<?php print($_subjectPrefill); ?>" tabIndex="1" required>
		<?php 	}	?>
		<div class="editor-tray" id="editor-tray">

		</div>
		<div class="editor-warnings" id="editor-warnings">
			​
		</div>
		<div class="editor-textarea">
			<textarea id="replytext" class="postbox" maxLength="<?php print($_SESSION['admin'] ? 100000 : 30000); ?>" minLength="3" name="editpost" tabindex="2"><?php

			print($_postContentPrefill);

			?></textarea>
		</div>
		<div class="editor-formbuttons">
			<input class="postButtons" type="submit" name="edit" value="Edit" tabindex="4">
			<input class="postButtons" type="submit" name="preview" value="Preview" tabindex="3">
		</div>
	</form>
</div>
<script src="./themes/twilight/js/editor.js" nonce="<?php print($_script_nonce); ?>" async></script>