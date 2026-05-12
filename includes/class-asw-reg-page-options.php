<?php
/**
 * Project pages eligible for form auto-inject (condominium / house, excluding thank-you category).
 *
 * @package asw-register
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ASW_Reg_Page_Options
 */
class ASW_Reg_Page_Options {

	const POST_TYPES = array( 'condominium', 'house' );

	const EXCLUDE_CATEGORY_TAXONOMY = 'category';

	const EXCLUDE_CATEGORY_SLUG = 'thank-you';

	/**
	 * Post titles containing these substrings (case-insensitive) are excluded from the inject list.
	 *
	 * @return string[]
	 */
	private static function banned_title_substrings() {
		return array( 'master', 'thank you', 'thank-you' );
	}

	/**
	 * Whether the raw post title should be excluded from inject targets / dropdown.
	 *
	 * @param string $title Post title.
	 * @return bool
	 */
	public static function is_title_excluded( $title ) {
		$title = (string) $title;
		foreach ( self::banned_title_substrings() as $needle ) {
			if ( function_exists( 'mb_stripos' ) ) {
				if ( mb_stripos( $title, $needle, 0, 'UTF-8' ) !== false ) {
					return true;
				}
			} elseif ( stripos( $title, $needle ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * WP_Query args shared by dropdown and validation.
	 *
	 * @return array
	 */
	public static function eligible_posts_query_args() {
		return array(
			'post_type'              => self::POST_TYPES,
			'post_status'            => 'publish',
			'posts_per_page'       => 500,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
			'tax_query'              => array(
				array(
					'taxonomy' => self::EXCLUDE_CATEGORY_TAXONOMY,
					'field'    => 'slug',
					'terms'    => array( self::EXCLUDE_CATEGORY_SLUG ),
					'operator' => 'NOT IN',
				),
			),
		);
	}

	/**
	 * Posts for admin dropdown: id => label.
	 *
	 * @return array<int,string>
	 */
	public static function get_choice_list() {
		$q = new WP_Query( self::eligible_posts_query_args() );
		$out = array();
		if ( ! $q->have_posts() ) {
			return $out;
		}
		while ( $q->have_posts() ) {
			$q->the_post();
			$title = get_the_title();
			if ( self::is_title_excluded( $title ) ) {
				continue;
			}
			$id    = (int) get_the_ID();
			$type  = get_post_type_object( get_post_type() );
			$label = $type ? $type->labels->singular_name : '';
			$out[ $id ] = trim( sprintf( '%s — %s', $label, $title ) );
		}
		wp_reset_postdata();
		return $out;
	}

	/**
	 * Whether this post ID is allowed as an inject target.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_valid_inject_target( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return false;
		}
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return false;
		}
		if ( ! in_array( $post->post_type, self::POST_TYPES, true ) ) {
			return false;
		}
		if ( has_term( self::EXCLUDE_CATEGORY_SLUG, self::EXCLUDE_CATEGORY_TAXONOMY, $post_id ) ) {
			return false;
		}
		if ( self::is_title_excluded( $post->post_title ) ) {
			return false;
		}
		return true;
	}
}
