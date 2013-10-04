<?php
/*
Plugin Name: WPML comments merging
Plugin URI: https://github.com/jgalea/wpml-comments-merging
Description: This plugin merges comments from all translations of the posts and pages, so that they all are displayed on each other. Comments are internally still attached to the post or page they were made on.
Version: 2.1
Author: Jean Galea
Author URI: http://www.jeangalea.com
License: GPL
Contributor: Simon Wheatley (@simonwheatley), juliobox (@boiteaweb)

This is a fixed version of the no longer maintained WPML Comment Merging plugin:
http://wordpress.org/extend/plugins/wpml-comment-merging/
Thanks to Simon Wheatley & Julio Potier for contributing the fix.
*/

function sort_merged_comments($a, $b) { 
	return $a->comment_ID - $b->comment_ID;
}

function merge_comments($comments, $post_ID) {
	global $sitepress;
	// get all the languages for which this post exists
	$languages = $sitepress->get_active_languages();
	$post = get_post( $post_ID );
	$type = $post->post_type;
	foreach($languages as $l) {
		// in $comments are already the comments from the current language
		if(!$l['active']) {
			$otherID = icl_object_id($post_ID, $type, false, $l['code']);
			$othercomments = get_comments( array('post_id' => $otherID, 'status' => 'approve', 'order' => get_option('comment_order') ) );
			$comments = array_merge($comments, $othercomments);
		}
	}
	if ($languages) {
		// if we merged some comments in we need to reestablish an order
		usort($comments, 'sort_merged_comments');
	}


	return $comments;
}
function merge_comment_count($count, $post_ID) {
	// get all the languages for which this post exists
	$languages = $sitepress->get_active_languages();
	$post = get_post( $post_ID );
	$type = $post->post_type;

	foreach($languages as $l) {
		// in $count is already the count from the current language
		if(!$l['active']) {
			$otherID = icl_object_id($post_ID, $type, false, $l['code']);
			if($otherID) {
				// cannot use call_user_func due to php regressions
				$otherpost = get_post($otherID);
				
				if ($otherpost) {
					// increment comment count using translation post comment count.
					$count += $otherpost->comment_count;
				}
			}
		}
	}
	return $count;
}

add_filter('comments_array', 'merge_comments', 11, 2);
add_filter('get_comments_number', 'merge_comment_count', 11, 2);
