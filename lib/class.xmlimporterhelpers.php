<?php

	class XMLImporterHelpers {
		static function markdownify($string) {
			require_once(EXTENSIONS . '/xmlimporter/lib/markdownify/markdownify_extra.php');
			$markdownify = new Markdownify(true, MDFY_BODYWIDTH, true);

			$markdown = $markdownify->parseString($string);
			//$markdown = htmlspecialchars($markdown, ENT_NOQUOTES, 'UTF-8');
			return $markdown;
		}

		static function dateFlip($string){
			$value = implode('/', array_reverse(explode('/', strtok($string, ' '))));
			return $value;
		}

		static function trim($string) {
			return trim($string);
		}

		static function filterTitle($title) {
			$title = trim($title);
			$title = preg_replace("/\([^\)]+\)$/iU", '', $title);
			return $title;
		}

		static function saveBookCover($img) {
			// Check if the image is from Amazon or Goodreads and manipulate the URL
			// accordingly to get the large version.
			if(strpos($img,'images-amazon.com') !== false) {
				$img = preg_replace('#_[a-z0-9]+_\.#i', '', $img);
			}
			else if(strpos($img, 'goodreads.com') !== false) {
				$img = preg_replace('#([0-9])m/#', '$1l/', $img);
			} elseif(strpos($img, 'nocover') !== false) {
				$img = 'http://www.goodreads.com/assets/nocover/111x148.png';
			}

			$name = explode('/', $img);
			$name = '/images/book_covers/' . array_pop($name);
			file_put_contents(WORKSPACE . $name, file_get_contents($img));

			return $name;
		}

		static function filterTags($tags) {
			$remove = array(
				'currently-reading',
				'read',
				'to-read',
				'to-reread',
				'borrowed',
				'ebook',
				'first-reads',
				'from-library',
				'own',
				'owned',
				'to-borrow',
				'to-buy',
			);

			$tags_array = explode(',', $tags);
			$tags_array = array_diff($tags_array, $remove);

			$tags_array = array_filter($tags_array, array(self, 'tag_filter'));
			$tags_array = str_replace('-', ' ', $tags_array);
			return implode(',', $tags_array);
		}

		static function tag_filter($tag) {
			// Remove shelves of the form '201xx-yy'
			if(preg_match('/^2[0-9]{3}-/', $tag) || strpos($tag, 'via-') === 0) {
				return false;
			}
			return true;
		}

		static function convertReview($text) {
			if(empty($text)) {
				return "No review";
			}
			$str_replacement = array(
				'class="escapedImg"' => 'class="inline"', // Change class on image tags
				' rel="nofollow"' => '',
				' target="_blank"' => '',
			);

			$preg_replacement = array(
				"#<br /><strong>My reviews(.*?)</strong><br />(.*)<br /><br />#i" => '', // Remove series navigation, if present
				"#<em>([A-Z0-9]{1}\S+?[A-Za-z][^<]+)</em>#" => "<cite>$1</cite>", // Try to convert book titles to <cite> tags
				"#<i>([A-Z]{1}\S+?[A-Za-z][^<]+)</i>#" => "<cite>$1</cite>",
				'#<a href="http://creativecommons.org(.*)</a>#isU' => '',
			);

			$text = str_replace(array_keys($str_replacement), array_values($str_replacement), $text);
			$text = preg_replace(array_keys($preg_replacement), array_values($preg_replacement), $text);

			return self::markdownify($text);
		}
	}

