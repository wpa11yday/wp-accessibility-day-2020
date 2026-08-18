<?php

use WP_Accessibility_Day\Theme;

/**
 * Autoloader.
 *
 * @param string $class The fully-qualified class name.
 *
 * @return void
 */
spl_autoload_register(
	function( $class ) {
		$prefix   = 'WP_Accessibility_Day\\';
		$len      = strlen( $prefix );
		$base_dir = __DIR__ . '/classes/';

		if ( 0 !== strncmp( $prefix, $class, $len ) ) {
			return;
		}

		$relative_class = strtolower( substr( $class, $len ) );
		$file           = wp_normalize_path( $base_dir . 'class-' . str_replace( '\\', '/', $relative_class ) . '.php' );

		if ( file_exists( $file ) ) {
			require  $file;
		}
	}
);

/**
 * Declare 'wpaccessibilityday' textdomain for this theme.
 * Translations can be added to the /languages/ directory.
 */
function wp_accessibility_day_text_domain() {
	load_child_theme_textdomain( 'wpaccessibilityday', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'wp_accessibility_day_text_domain' );

// Instantiate theme.
Theme::get_instance();

// Add subtitle to home page
function wpad_home_subtitle() {
	global $post;
	if ( function_exists( 'get_field' ) ) {
		//echo '<div class="subtitle">' . get_field( 'subtitle_content', $post->ID ) . '</div>';
	} else {
		//echo '<div class="subtitle"><p class="event-date">October 2nd, 2020</p><p><a href="https://www.timeanddate.com/worldclock/fixedtime.html?hour=18&amp;min=00&amp;sec=0" class="customize-unpreviewable">From 18:00 UTC</a></p></div>';
	}
}
add_action( 'wpad_entry_header', 'wpad_home_subtitle' );

add_action( 'wp', 'wpad_custom_canonical' );
/**
 * Filter in a custom canonical URL for Yoast. Because I can't get the advanced panel to show up.
 */
function wpad_custom_canonical() {
	if ( is_single( 542 ) ) {
		add_action( 'wp_head', 'wpad_canonical' );
		remove_action( 'wp_head', 'rel_canonical' );
		add_filter( 'wpseo_canonical', 'wpad_disable_yoast_canonical' );
	}
}

function wpad_canonical() {
	$link = 'https://yoast.com/image-seo-alt-tag-and-title-tag-optimization/';

	echo "<link rel='canonical' href='$link' />\n";
}

/**
 * Return HTML from a WordPress profile via shortcode.
 *
 * @param array $atts Shortcode attributes with one parameter, user ID.
 *
 * @return string
 */
function wpad_shortcode_people( $atts ) {
	$atts = shortcode_atts( array(
		'id' => '',
	), $atts );

	$content = ( '' !== $atts['id'] ) ? wpad_get_people_data( $atts['id'] ) : '';

	return $content;
}
add_shortcode( 'people', 'wpad_shortcode_people' );

/**
 * Return HTML from a WordPress profile.
 *
 * @param string $id user slug.
 *
 * @return string
 */
function wpad_get_people_data( $id ) {
	$image    = '';
	$name     = '';
	$username = '';
	$bio      = '';
	$employer = '';
	$job      = '';
	$country  = '';
	$website  = '';

	// Remove transient & manually refresh.
	if ( current_user_can( 'manage_options' ) && isset( $_GET['wpad_transients'] ) ) {
		delete_option( 'wpad_existing_data_for_member_' . esc_url( $id ) );
	}
	$existing_people = get_option( 'wpad_existing_data_for_member_' . esc_url( $id ) );

	if ( ! empty( $existing_people ) ) {
		$image    = $existing_people['image'];
		$name     = $existing_people['name'];
		$username = $existing_people['username'];
		$bio      = $existing_people['bio'];
		$employer = $existing_people['employer'];
		$job      = $existing_people['job'];
		$country  = $existing_people['country'];
		$website  = $existing_people['website'];
		$url      = $existing_people['url'];
	} else {
		require_once( get_stylesheet_directory() . '/simplehtmldom/simple_html_dom.php' );
	
		$url  = 'https://profiles.wordpress.org/' . trim( $id );
		$html = file_get_html( $url );
		if ( ! $html ) {
			return $id;
		}

		$image_element = $html->find( '.photo' );
		$image         = $image_element[0]->outertext;
	
		$name_element = $html->find( '.site-header h2' );
		$name         = $name_element[0]->plaintext;
		$name         = ( ! $name ) ? 'Antonio Trifirò' : $name;
	
		$username_element = $html->find( '#slack-username' );
		$username         = $username_element[0]->innertext;

		$bio_element = $html->find( '.item-meta-about' );
		$bio         = $bio_element[0]->innertext;

		$employer_element = $html->find( '#user-company strong' );
		$employer         = $employer_element[0]->innertext;

		$job_element = $html->find( '#user-job strong' );
		$job         = $job_element[0]->innertext;

		$country_element = $html->find( '#user-location strong' );
		$country         = $country_element[0]->innertext;

		$website_element = $html->find( '#user-website strong' );
		$website         = $website_element[0]->innertext;

		$new_people = array(
			'image'    => $image,
			'name'     => $name,
			'username' => $username,
			'url'      => $url,
			'bio'      => $bio,
			'employer' => $employer,
			'job'      => $job,
			'country'  => $country,
			'website'  => $website,
		);

		update_option( 'wpad_existing_data_for_member_' . esc_url( $id ), $new_people );
	}

	$content  = '<div class="people-profile">';
	$content .= '<div class="reorder">';
	$website  = '//' . strip_tags( $website );
	$content .= '<h3><a href="' . esc_url( $website ) . '">' . $name . '</a></h3>';
	$content .= $image;
	$content .= '</div>';

	$username = explode( ' on', $username );
	$username = '<a href="' . esc_url( $url ) . '">' . trim( $username[0] ) . '</a>';
	
	if ( ! empty( $username ) ) {
		$content .= '<p class="username">' . $username . '</p>';
	}
	if ( ! empty( $employer ) ) {
		//$content .= '<p class="employer"><strong>Company:</strong> ' . $employer . '</p>';
	}
	if ( ! empty( $job ) ) {
		//$content .= '<p class="job"><strong>Job title:</strong> ' . $job . '</p>';
	}
	if ( ! empty( $country ) ) {
		$content .= '<p class="country">' . $country . '</p>';
	}
	if ( ! empty( $website ) ) {
		//$content .= '<p class="website"><strong>Website:</strong> ' . $website . '</p>';
	}
	if ( ! empty( $bio ) ) {
		//$content .= '<div class="bio">' . $bio . '</div>';
	}
	$content .= '</div>';

	return $content;
}

/**
 * Parse format for time.
 */
function wpaccessibilityday_time() {
	// https://www.timeanddate.com/worldclock/fixedtime.html?iso=20201003T1400 - Format.
}

add_shortcode( 'schedule', 'wpaccessibilityday_schedule' );
function wpaccessibilityday_schedule( $atts, $content ) {
  $current_talk = '';
	$args = shortcode_atts( array(
		'start'     => '18',
	), $atts, 'wpaccessibilityday_schedule' );

	$query = array(
		'post_type'      => 'mcm_talk',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => '_time-in-utc',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => '_time-in-utc',
				'value'   => '',
				'compare' => '!=',
			),
		),
	);
	$posts    = get_posts( $query );
	$schedule = array();
	foreach( $posts as $post_ID ) {
		$time = str_replace( ':00', '', get_post_meta( $post_ID, '_time-in-utc', true ) );
		$schedule[ $time ] = $post_ID;
	}
	$start = $args['start'] - 24;
	for( $i = $start; $i < $args['start']; $i++ ) {
		if ( absint( $i ) != $i ) {
			$base = 24 - absint( $i );
		} else {
			$base = $i;
		}

		$time = str_pad( $base, 2, '0', STR_PAD_LEFT );
		$is_current = false;

		if ( date( 'H' ) == $time && (int) date( 'i' ) < 50 || date( 'G' ) == (int) $time - 1 && (int) date( 'i' ) > 50 ) {
			$is_current = true;
		}
		if ( (int) date( 'i' ) < 50 ) {
			$text = "Now speaking: ";
		} else {
			$text = "Up next: ";
		}
		$datatime  = date( 'Y-m-d\TH:i:s\Z', strtotime( $time . ':00 UTC' ) );
		$time_html = '<h2 class="talk-time" data-time="' . $datatime . '">' . $time . ':00 UTC' . '</h2>';
		$talk_ID   = $schedule[ $time ];
		if ( ! $talk_ID ) {
			$output[] = $time_html . '<p>Not yet scheduled</p>';
		} else {
			$auth    = get_post_meta( $talk_ID, '_speaker', true );
			$slides  = esc_url( get_post_meta( $talk_ID, '_slides-url', true ) );
			if ( $auth ) {
				$author = get_post( $auth );
			}
			$talk         = get_post( $talk_ID );
			$time_output  = $time_html . get_the_post_thumbnail( $auth, 'medium' ) . '<p class="speaker"><a href="' . esc_url( get_the_permalink( $auth ) ) . '">' . $author->post_title . '</a></p>';
			$time_output .= ( $slides ) ? '<p class="slides"><a href="' . $slides . '">View Slides for presentation by ' . $author->post_title . '</a></p>' : '';
			if ( current_user_can( 'manage_options' ) ) {
				$time_output .= ( $slides ) ? '' : '<p class="slides">Slides not yet provided.</p>';
			}
			$permalink    = get_the_permalink( $talk_ID );
			$talk_output  = '<h3><a href="' . esc_url( $permalink ) . '">' . $talk->post_title . '</a></h3><div class="talk-description">' . get_the_excerpt( $talk ) . '</div>';
			$speaker_id   = sanitize_title( $author->post_title );
			if ( $is_current ) {
				// Don't display current talk now that event is over.
				// $current_talk = "<p class='current-talk alignwide'><strong>$text</strong> <a href='#$speaker_id'>$time:00 UTC - $author->post_title / $talk->post_title</a></p>";
			}

			$output[] = "<div class='wp-block-group alignwide trapezoid schedule' id='$speaker_id'>
				<div class='wp-block-group__inner-container'>
					<div class='wp-block-columns'>
						<div class='wp-block-column'>
							$time_output
						</div>
						<div class='wp-block-column'>
							<div class='pearl-box'>
							$talk_output
							</div>
						</div>
					</div>
				</div>
			</div>";
		}
	}

	$opening_remarks = "<div class='wp-block-group alignwide trapezoid schedule'>
				<div class='wp-block-group__inner-container'>
					<div class='wp-block-columns'>
						<div class='wp-block-column'>
							<h2 class='talk-time' data-time='2020-10-02T17:45:00Z'>17:45 UTC</h2>
							<p class='speaker'>Joe Dolson</p>
						</div>
						<div class='wp-block-column'>
							<div class='pearl-box'>
								<h3>Opening Remarks</h3>
								<div class='talk-description'>
									<p>Our lead organizer will welcome you all, and kick off WP Accessibility Day with a few brief opening comments.</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>";
	$closing_remarks = "<div class='wp-block-group alignwide trapezoid schedule'>
				<div class='wp-block-group__inner-container'>
					<div class='wp-block-columns'>
						<div class='wp-block-column'>
							<h2 class='talk-time' data-time='2020-10-03T17:45:00Z'>~17:45 UTC</h2>
							<p class='speaker'>Stefano Minoia</p>
						</div>
						<div class='wp-block-column'>
							<div class='pearl-box'>
								<h3>Closing Remarks</h3>
								<div class='talk-description'>
									<p>Stefano, our incoming lead organizer for WP Accessibility Day 2021 will deliver closing remarks immediately following the end of the last talk's Q and A session.</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>";
	$links = wpad_youtube_links();

	return $links . $current_talk . $opening_remarks . implode( PHP_EOL, $output ) . $closing_remarks;
}

function wpad_youtube_links() {
	$time = time();
    $output = '';
	if ( $time < strtotime( '2020-10-02 11:50 UTC' ) ) {
		if ( $time < strtotime( '2020-10-02 18:00 UTC' ) ) {
			$until = human_time_diff( $time, strtotime( '2020-10-02 18:00 UTC' ) );
			$append = "Starts in only <strong>$until</strong>!";
		}
		$output = "<a href='https://youtu.be/X0BcKR2Go1E'>Watch the stream at YouTube!</a> $append";
	}
	if ( $time > strtotime( '2020-10-02 11:50 UTC' ) && $time < strtotime( '2020-10-03 5:50 UTC' ) ) {
		$output = "<a href='https://youtu.be/-fKkptFna9E'>Watch the stream at YouTube (for Sessions 7-12!)</a>";
	}
	if ( $time > strtotime( '2020-10-03 5:50 UTC' ) && $time < strtotime( '2020-10-03 11:50 UTC' ) ) {
		$output = "<a href='https://youtu.be/V0yJ_qJBvoc'>Watch the stream at YouTube (for Sessions 13-18!)!</a>";
	}
	if ( $time > strtotime( '2020-10-03 11:50 UTC' ) && $time < strtotime( '2020-10-03 18:00 UTC' ) ) {
		$output = "<a href='https://youtu.be/9J7JlYjahMU'>Watch the stream at YouTube (for Sessions 19-24!)!</a>";
	}
	$output = '<p class="youtube-link"><span class="dashicons dashicons-youtube" aria-hidden="true"></span>' . $output . '</p>';

	$output = '<p>WP Accessibility Day is over! Watch our YouTube channel for the posting of the edited and captioned videos!</p>';

	return $output;
}

add_action( 'wp_enqueue_scripts', 'wp_talk_time' );
function wp_talk_time() {
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_script( 'wp-talk-time', get_stylesheet_directory_uri() . '/js/talk-time.js', array( 'jquery' ), '1.0.8', true );
}

add_filter( 'wp_dropdown_users_args', 'wpad_add_subscribers_to_dropdown', 10, 2 );
function wpad_add_subscribers_to_dropdown( $query_args, $r ) {
	$query_args['who'] = '';

	return $query_args;

}

class WPad_Walker_Comment extends Walker_Comment {
 
	/**
	 * Outputs a comment in the HTML5 format.
	 *
	 * @see wp_list_comments()
	 *
	 * @param WP_Comment $comment Comment to display.
	 * @param int        $depth   Depth of the current comment.
	 * @param array      $args    An array of arguments.
	 */
	protected function html5_comment( $comment, $depth, $args ) {
		global $post;
		$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
 
		?>
		<<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( $this->has_children ? 'parent' : '', $comment ); ?>>
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<footer class="comment-meta">
					<?php
					// Only display comment author info if speaker posts.
					if ( $comment->user_id === $post->post_author ) {
						?>
					<div class="comment-author vcard">
						<?php
						/*
						 * Eliminate comment author avatar.
						$comment_author_link = get_comment_author_link( $comment );
						$comment_author_url  = get_comment_author_url( $comment );
						$comment_author      = get_comment_author( $comment );
						$avatar              = get_avatar( $comment, $args['avatar_size'] );
						if ( 0 != $args['avatar_size'] ) {
							if ( empty( $comment_author_url ) ) {
								echo $avatar;
							} else {
								printf( '<a href="%s" rel="external nofollow" class="url">', $comment_author_url );
								echo $avatar;
							}
						}
						 */
						/*
						 * Using the `check` icon instead of `check_circle`, since we can't add a
						 * fill color to the inner check shape when in circle form.
						 */
						if ( $comment->user_id === $post->post_author ) {
							print( '<span class="post-author-badge"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> Speaker Response</span>' );
						}
 
						/*
						 * Eliminate comment author link
						 *
						printf(
							wp_kses(
								__( '%s <span class="screen-reader-text says">says:</span>', 'custom' ),
								array(
									'span' => array(
										'class' => array(),
									),
								)
							),
							'<b class="fn">' . get_comment_author_link( $comment ) . '</b>'
						);
 
						if ( ! empty( $comment_author_url ) ) {
							echo '</a>';
						}
						*/
						?>
					</div><!-- .comment-author -->
						<?php
					} else {
						echo '<div></div>';
					}
					?>
					<div class="comment-metadata">
						<a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">Asked on 
							<?php
								/* translators: 1: comment date, 2: comment time */
								$comment_timestamp = sprintf( __( '%1$s at %2$s', 'custom' ), get_comment_date( '', $comment ), get_comment_time() );
							?>
							<time datetime="<?php comment_time( 'c' ); ?>">
								<?php echo $comment_timestamp; ?>
							</time>
						</a> <?php
							edit_comment_link( __( 'Edit', 'custom' ), '<span class="edit-link">', '</span>' );
						?>
					</div><!-- .comment-metadata -->
 
					<?php if ( '0' == $comment->comment_approved ) : ?>
					<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'custom' ); ?></p>
					<?php endif; ?>
				</footer><!-- .comment-meta -->
 
				<div class="comment-content">
					<?php comment_text(); ?>
				</div><!-- .comment-content -->
 
			</article><!-- .comment-body -->
 
			<?php
			comment_reply_link(
				array_merge(
					$args,
					array(
						'add_below'     => 'div-comment',
						'depth'         => $depth,
						'max_depth'     => $args['max_depth'],
						'before'        => '<div class="comment-reply">',
						'after'         => '</div>',
						'reply_text'    => 'Answer',
						'reply_to_text' => 'Add an Answer',
					)
				)
			);
			?>
		<?php
	}
}

function wpad_archive_header() {
	echo sprintf( '<aside id="wpad-archive"><p>You are viewing the <strong>%s</strong>. <a href="https://wpaccessibility.day">View the current event site</a>.</p></aside>', get_bloginfo( 'title' ) );
}
add_action( 'wp_body_open', 'wpad_archive_header' );

// Used to parse YouTube links out of embed blocks.
function wpad_generate_able_player( $post_ID ) {
	$content   = get_post_field( 'post_content', $post_ID );
	$processor = new WP_HTML_Tag_Processor( $content );
	$text      = '';

	while( $processor->next_tag( array( 'class_name' => 'wp-block-embed__wrapper' ) ) ) {
		$processor->next_token();
		if ( '#text' === $processor->get_token_type() ) {
			$text = $processor->get_modifiable_text();
			continue;
		}
	}

	if ( current_user_can( 'manage_options' ) ) {
		update_post_meta( $post_ID, '_wpcs_youtube_id', $text );
	}
}

/**
 * Append video to end of session post if has captions & video.
 *
 * @param string $content The content.
 *
 * @return string
 */
function wpcs_add_video( $content ) {
	if ( get_the_ID() === 384 ) {
		//update_post_meta( get_the_ID(), '_wpcs_youtube_id', 'https://www.youtube.com/watch?v=1iLK1Xgbqko' );
	}
	if ( ! wpcs_get_captions() || ! wpcs_get_youtube() ) {
		return $content;
	} else {
		return $content . wpcs_get_video();
	}
}
add_filter( 'the_content', 'wpcs_add_video' );

/**
 * Get video poster (the post thumbnail URL).
 *
 * @return string
 */
function wpcs_get_poster() {
	$poster = get_the_post_thumbnail_url();

	return $poster;
}

/**
 * Get the ASL version of a video.
 *
 * @return string
 */
function wpcs_get_asl() {
	$asl = get_post_meta( get_the_ID(), '_wpcs_asl_id', true );

	return $asl;
}

/**
 * Get youtube ID from meta.
 *
 * @param string $type 'main' or 'asl'.
 *
 * @return string
 */
function wpcs_get_youtube( $type = 'main' ) {
	$post_id = get_the_ID();
	if ( 'main' === $type ) {
		$session_youtube = get_post_meta( $post_id, '_wpcs_youtube_id', true );
	} else {
		$session_youtube = get_post_meta( $post_id, '_wpcs_asl_id', true );
	}

	return $session_youtube;
}

/**
 * Get captions URLs.
 *
 * @return bool|array
 */
function wpcs_get_captions() {
	$cache_break = ( current_user_can( 'manage_options' ) ) ? true : false;
	$post_id     = get_the_ID();
	$languages   = wpcs_get_languages( false );
	$captions    = array();
	$has_caption = false;
	$args        = array(
		'fields' => 'slugs',
	);
	$terms       = wp_get_object_terms( $post_id, 'mcm_category_talk', $args );
	foreach ( $languages as $key => $language ) {
		$filename = get_post_field( 'post_name', $post_id ) . '-' . $key;
		$filepath = '/home/wp/www/www/wp-content/plugins/wpad-conference-schedule/src/assets/captions/2020/' . $filename . '.vtt';
		$file_url = 'https://wpaccessibility.day/2020/wp-content/plugins/wpad-conference-schedule/src/assets/captions/2020/' . $filename . '.vtt';
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		if ( $wp_filesystem->exists( $filepath ) ) {
			if ( ! has_term( $key, 'mcm_category_talk' ) ) {
				$terms[] = $key;
				wp_set_object_terms( $post_id, $terms, 'mcm_category_talk' );
			}
			$has_caption      = ( 'en' === $key ) ? true : $has_caption;
			$captions[ $key ] = ( $cache_break ) ? add_query_arg( 'version', wp_rand( 10000, 99999 ), $file_url ) : $file_url;
		}
	}
	// We only display videos if they have English captions, first. Translations are secondary.
	if ( ! $has_caption ) {
		return false;
	}

	return $captions;
}


/**
 * Register supported caption languages and provide labels. English version of language is used in admin; native is used on front-end.
 *
 * @param string $lang Language code or `false` to return entire array.
 *
 * @return string|array
 */
function wpcs_get_languages( $lang = false ) {
	$labels = array(
		// $langcode => [English version, Native version].
		'de' => array( 'German', 'Deutsch' ),
		'en' => array( 'English', 'English' ),
		'es' => array( 'Spanish', 'Español' ),
		'fr' => array( 'French', 'Français' ),
	);
	if ( ! $lang ) {
		return $labels;
	}

	return ( isset( $labels[ $lang ] ) ) ? $labels[ $lang ] : array( $lang, $lang );
}

/**
 * Get video HTML.
 *
 * @return string
 */
function wpcs_get_video() {
	$captions  = wpcs_get_captions();
	$count     = 0;
	$subtitles = '';
	if ( ! empty( $captions ) ) {
		foreach ( $captions as $lang => $caption ) {
			$label = wpcs_get_languages( $lang )[1];
			if ( $caption ) {
				$subtitles .= '<track kind="captions" src="' . esc_url( $caption ) . '" srclang="' . esc_attr( $lang ) . '" label="' . $label . '">';
			}
		}
		$count = count( $captions );
	}
	if ( $count <= 1 ) {
		// translators: Link to translation interest form.
		$translate = sprintf( __( 'This session is only available in English! Can you <a href="%s">help translate it</a>?', 'wpa-conference' ), 'https://wpaccessibility.day/translate/' );
	} else {
		// translators: Number of translations available; Link to translation interest form.
		$translate = sprintf( __( 'This session is available in %1$d languages! Can you <a href="%2$s">help translate more</a>?', 'wpa-conference' ), $count, 'https://wpaccessibility.day/translate/' );
	}
	$sign_src = wpcs_get_asl();
	if ( $sign_src ) {
		$sign_src = ' data-youtube-sign-src="' . esc_attr( $sign_src ) . '"';
	}
	$holder = $sign_src ? '<div class="holder"><p><em>Space for positioning sign language player</em></p></div>' : '';
	$vts    = ( current_user_can( 'edit_posts' ) && isset( $_GET['editor'] ) && 'vts' === $_GET['editor'] ) ? '<div id="able-vts"></div>' : '';

	return '
	<div class="wp-block-group alignwide wpad-video-player">
		<h2>Session Video</h2>
		<div class="video-wrapper">
			<video id="able-player-' . get_the_ID() . '" data-heading-level="0" data-able-player data-transcript-div="able-player-transcript-' . get_the_ID() . '" preload="auto" poster="' . wpcs_get_poster() . '" data-youtube-id="' . wpcs_get_youtube() . '"' . $sign_src . '>
				' . $subtitles . '
			</video>
			' . $holder . '
		</div>
		<div id="able-player-transcript-' . get_the_ID() . '"></div>
		' . $vts . '
		<div class="please-translate">
			<p>
				' . $translate . '
			</p>
		</div>
	</div>';
}