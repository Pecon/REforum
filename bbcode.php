<?php
	// Convert bbcode formatted text into html
	// Taking the java-ish approach to this
	// Not bothering with regex because it's basically unreadable that way
	function parseTag($tagText, $level)
	{
		if(!isSet($level))
			$level = 0;

		if($level > 25)
		{
			throw new Exception("Reached max nested tag parsing level.");
			return false;
		}

		
		$cursor = -1;
		$length = strlen($tagText);
		$char = "";
		$searchCursor = 0;
		$searchChar = "";

		$errors = Array();
		$newText = "";

		while($cursor <= $length)
		{
			$cursor++;
			$newText = $newText . $char;
			$char = charAt($tagText, $cursor);

			if($char == "[") // Found the beginning of a tag?
			{
				// Try to find the end of the tag, if any
				$searchCursor = $cursor + 1;
				$found = false;

				while($searchCursor <= $length)
				{
					$searchChar = charAt($tagText, $searchCursor);

					if($searchChar == "[")
					{
						break;
					}
					else if($searchChar == "]") // Probably the end of the tag.
					{
						$found = true;
						break;
					}

					$searchCursor++;
				}

				if(!$found)
				{
					continue;
				}

				$startTag = subStr($tagText, $cursor + 1, $searchCursor - $cursor - 1);
				$startTagEndPos = $searchCursor;

				$startTagSearch = 0;
				$startTagLength = strlen($startTag);

				$tagName = "";
				$found = false;

				while($startTagSearch <= $startTagLength)
				{
					$searchChar = charAt($startTag, $startTagSearch);

					if(($searchChar == " " && strlen($tagName) < 1) || $searchChar == "[" || $searchChar == "]")
					{
						// Ignore.
					}
					else if(($searchChar == "\r" || $searchChar == "\n") || ($searchChar == "\\" && $startTagSearch == 1))
					{
						// Character invalidates this tag.
						$found = true;
						break;
					}
					else if($searchChar == "=" || $searchChar == " ")
					{
						// Reached the end of the tag name.
						break;
					}
					else
					{
						$tagName = $tagName . $searchChar;	
					}
					

					$startTagSearch++;
				}

				if($found) // Found invalid character that invalidates the tag.
				{
					continue;
				}

				if(strlen($tagName) < 1) // No tag name.
				{
					continue;
				}

				$tagType = tagType($tagName);

				if($tagType === false)
				{
					array_push($errors, "Found a [$tagName] tag, but it's not a recognized tag name.");	
					continue;
				}

				$tagArgument = "";

				while($startTagSearch <= $startTagLength)
				{
					$searchChar = $searchChar = charAt($startTag, $startTagSearch);

					if($searchChar == "=" || $searchChar == "]")
					{
						// Ignore.
					}
					else
					{
						$tagArgument = $tagArgument . $searchChar;
					}

					$startTagSearch++;
				}

				$tagArgument = htmlentities($tagArgument);

				if($tagType > -1)
				{
					// We have to find the end tag in order to finish validating this tag.

					$searchCursor = $startTagEndPos + 1;
					$found = false;
					$nesting = 0;

					while($searchCursor <= $length)
					{
						$searchChar = charAt($tagText, $searchCursor);
						$tagNameLength = strlen($tagName);
						if($searchChar == "[") // Beginning of the end tag?
						{
							$test1 = charAt($tagText, $searchCursor + 1);
							if($test1 == "/")
							{
								// Ending tag
								$test2 = substr($tagText, $searchCursor + 2,  $tagNameLength + 1);
								if($test2 == ($tagName . "]"))
								{
									// End tag!!!!
									if($nesting > 0)
									{
										$nesting--;
									}
									else
									{
										$found = true;
										$endTagPos = $searchCursor;
										$endTagEndPos = $endTagPos + $tagNameLength + 1;
										$endTag = subStr($tagText, $endTagPos, $tagNameLength + 3);
										break;
									}
								}
							}
							else if(substr($tagText, $searchCursor + 1, $tagNameLength) == $tagName)
							{
								$close = strpos($tagText, "]", $searchCursor + $tagNameLength + 1);

								if($close === false)
									continue;

								$nesting++;
								$searchCursor = $searchCursor + $tagNameLength + 1;
							}
						}

						$searchCursor++;
					}

					if(!$found) // Didn't find a valid end tag.
					{
						array_push($errors, "Found an open [$tagName], but didn't find a matching close tag.");
						continue;
					}

					if($tagType == 1)
					{
						$processed = tagStartHTML($tagName, $tagArgument) . parseTag(substr($tagText, $startTagEndPos + 1, $endTagPos - $startTagEndPos - 1), $level + 1) . tagEndHTML($tagName);
						$newText = $newText . $processed;
						$cursor = $endTagEndPos + 1;
					}
					else if($tagType == 2)
					{
						$processed = subStr($tagText, $startTagEndPos + 1, $endTagPos - $startTagEndPos - 1);
						$newText = $newText . $processed;
						$cursor = $endTagEndPos + 1;
					}
					else
					{
						$processed = tagStartHTML($tagName, subStr($tagText, $startTagEndPos + 1, $endTagPos - $startTagEndPos - 1));
						$newText = $newText . $processed;
						$cursor = $endTagEndPos + 1;
					}

					$char = '';
				}
				else
				{
					$newText = $newText . tagStartHTML($tagName, $tagArgument);
					$cursor = $startTagEndPos;
					$char = '';
				}
			}
		}

		for($i = 0; $i < count($errors); $i++)
		{
			warn("BBcode warning: ${errors[$i]}");
		}

		return $newText;
	}

	// 2 = Escape tag (Text inside won't be parsed by bbcode)
	// 1 = Normal tag (contains text that will be further parsed e.g. [i]text[/i])
	// 0 = Text argument tag (The text contained in the tag is the argument for the tag e.g. [img]url[/img])
	// -1 = Self-closing tag (no end tag, creates a single element e.g. [hr])
	// Returns false if the tag doesn't exist.
	function tagType($tagName)
	{
		switch($tagName)
		{
			case "i": // Italics
				return 1;

			case "u": //Underline
				return 1;

			case "b": //Bold
				return 1;

			case "s": //Strikethrough
				return 1;

			case "color":
				return 1;

			case "size":
				return 1;

			case "font":
				return 1;

			case "url":
				return 1;

			case "abbr":
				return 1;

			case "center":
				return 1;
				
			case "left":
				return 1;

			case "right":
				return 1;

			case "just":
				return 1;

			case "quote":
				return 1;



			case "img":
				return 0;

			case "video":
				return 0;

			case "youtube":
				return 0;

			case "vimeo":
				return 0;



			case "hr":
				return -1;



			case "nobbc":
				return 2;

			case "noparse":
				return 2;

			default:
				return false;
		}
	}

	function tagStartHTML($tagName, $argument)
	{
		$argument = strip_tags($argument);
		$argument = str_replace(Array("<", ">", "{", "}", ";"), "", $argument);

		switch($tagName)
		{
			case "i":
				return '<i>';

			case "u":
				return '<span style="text-decoration: underline;">';

			case "b":
				return '<b>';

			case "s":
				return '<span style="text-decoration: line-through;">';

			case "color":
				return '<span style="color: ' . htmlentities($argument) . ';">';

			case "size":
				return '<span style="font-size: ' . htmlentities($argument) . ';">';

			case "font":
				return '<span style="font-face: \'' . htmlentities($argument) . '\';">';

			case "url":
				return '<a href="' . htmlentities($argument) . '" target="_BLANK">';

			case "abbr":
				return '<span title="' . htmlentities($argument) . '">';

			case "center":
				return '<div style="display: inline-block; width: 100%; text-align: center;">';

			case "left":
				return '<div style="display: inline-block; width: 100%; text-align: left;">';

			case "right":
				return '<div style="display: inline-block; width: 100%; text-align: right;">';

			case "just":
				return '<div style="display: inline-block; width: 100%; text-align: justify;">';

			case "quote":
				$author = (strlen($argument) > 0 ? '<span class="finetext">Quote from: ' . htmlentities($argument) . '</span>' : "");
				return '<div class="blockquoteHead">' . $author . '<blockquote>';



			case "img":
				return '<img class="postImage" src="' . htmlentities($argument) . '">';

			case "video":
				return '<video preload="false" controls><source src="' . htmlentities($argument) . '"></source></video>';

			case "youtube":
				$videoUrl = parse_url($argument);
				parse_str($videoUrl['query'], $videoQuery);
				return '<iframe width="500" height="281" src="https://www.youtube.com/embed/' . htmlentities($videoQuery['v']) . '" frameborder="0" allowfullscreen></iframe>';

			case "vimeo":
				$videoUrl = parse_url($argument);
				return '<iframe width="500" height="281" src="https://player.vimeo.com/video' . htmlentities($videoUrl['path']) . '" frameborder="0" allowfullscreen></iframe>';



			case "hr":
				return "<hr>";

			default:
				return false;
		}

	}

	function tagEndHTML($tagName)
	{
		switch($tagName)
		{
			case "i":
				return "</i>";

			case "u":
				return "</span>";

			case "b":
				return "</b>";

			case "s":
				return "</span>";

			case "color":
				return "</span>";

			case "size":
				return "</span>";

			case "font":
				return "</span>";

			case "url":
				return "</a>";

			case "abbr":
				return "</span>";

			case "center":
				return '</div>';

			case "left":
				return '</div>';

			case "right":
				return '</div>';

			case "just":
				return '</div>';

			case "quote":
				return '</blockquote></div>';


			default:
				return false;
		}
	}


	function charAt($string, $index)
	{
		if($index >= strlen($string) || $index < 0)
			return false;

		return substr($string, $index, 1);
	}
?>