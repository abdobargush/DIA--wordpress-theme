<?php

// Theme support
add_theme_support( 'title-tag' );
add_theme_support( 'post-thumbnails' );

// Adding scripts and stylesheets
function dia_scripts() {
	wp_enqueue_style( 'bootstrap', get_template_directory_uri() . '/css/bootstrap.min.css', array(), '3.3.7' );
	wp_enqueue_style( 'style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_script( 'bootstrap', get_template_directory_uri() . '/js/bootstrap.min.js', array( 'jquery' ), '3.3.7', true );
}
add_action( 'wp_enqueue_scripts', 'dia_scripts' );

// Adding Google Fonts
function dia_google_fonts() {
	wp_register_style('fonts', 'https://fonts.googleapis.com/css?family=Arvo:400,700|Raleway:300,400,700', array() , null);
	wp_enqueue_style( 'fonts');
}
add_action('wp_print_styles', 'dia_google_fonts');

// Menus
function dia_custom_menus() {
  register_nav_menus(
    array(
      'top-menu' => __( 'Top Menu' ),
      'social-menu' => __( 'Social Links Menu' )
    )
  );
}
add_action( 'init', 'dia_custom_menus' );

// Sidebar Widgets
function sidebart_widgets() {

	register_sidebar( array(
		'name'          => 'Blog sidebar',
		'id'            => 'blog_sidebar',
		'before_widget' => '<div class="sidebar-widget">',
		'after_widget'  => '</div>',
		'before_title'  => '<h2 class="page-header">',
		'after_title'   => '</h2>',
	) );

}
add_action( 'widgets_init', 'sidebart_widgets' );

// Custom Comments template
function dia_comments($comment, $args, $depth) {  
	$GLOBALS['comment'] = $comment; ?>
	   <li id="comment-<?php comment_ID() ?>" class="comment media">
		   <div class="media-left">
			   <?php echo get_avatar( $comment, 64 ); ?>
		   </div>
		   <div class="media-body">
			   <h4 class="media-hading"><?php echo ( get_comment_author($comment) ); ?></h4>
			   <p class="small text-muted"><?php printf(__('%1$s'), get_comment_date() . ' at ' . get_comment_time()) ?></p>
			   <p><?php echo (get_comment_text()); ?></p>
			   <p><?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'], 'reply_text' => '<svg id="i-reply" viewBox="0 0 32 32" width="16" height="16" fill="none" stroke="currentcolor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5">
				<path d="M10 6 L3 14 10 22 M3 14 L18 14 C26 14 30 18 30 26" />
		    	</svg> Reply'))) ?></p>
		   </div>
		   
   <?php 
}

// Replies
function theme_queue_js(){
if ( (!is_admin()) && is_singular() && comments_open() && get_option('thread_comments') )
  wp_enqueue_script( 'comment-reply' );
}
add_action('wp_print_scripts', 'theme_queue_js');

// Theme options
function theme_options_add_menu() {
  add_menu_page( 'DIA\' Theme Options', 'Theme Options', 'manage_options', 'theme-options', 'theme_options_page', null, 99 );
}
add_action( 'admin_menu', 'theme_options_add_menu' );

function theme_options_page() { ?>
  <div class="wrap">
    <h1>DIA' Theme Options</h1>
    <form method="post" action="options.php">
       <?php
           settings_fields( 'section' );
           do_settings_sections( 'theme-options' );      
           submit_button(); 
       ?>          
    </form>
  </div>
<?php }

function setting_footer_text() { ?>
   <input type="text" name="footer_text" id="footer_text" value="<?php echo str_replace('"','\'',get_option( 'footer_text' )); ?>" />
<?php }

function theme_options_page_setup() {
  add_settings_section( 'section', 'Footer', null, 'theme-options' );
  add_settings_field( 'footer_text', 'Footer Text', 'setting_footer_text', 'theme-options', 'section' );

  register_setting('section', 'footer_text');
}
add_action( 'admin_init', 'theme_options_page_setup' );

// Portfolio
function create_portfolio_post() {
	register_post_type( 'portfolio',
			array(
			'labels' => array(
					'name' => __( 'Portfolio' ),
					'singular_name' => __( 'Portfolio Item' ),
			),
			'public' => true,
			'has_archive' => true,
			'rewrite' => ['slug' => 'portfolio'],
			'menu_icon' => __('dashicons-format-gallery'),
			'supports' => array(
					'title',
					'thumbnail',
			)
	));
}
add_action( 'init', 'create_portfolio_post' );

// portfolio archive
add_action( 'pre_get_posts' ,'portflio_archive_get_posts', 1, 1 );
function portflio_archive_get_posts( $query )
{
    if ( ! is_admin() && is_post_type_archive( 'portfolio' ) && $query->is_main_query() )
    {
        $query->set( 'posts_per_page', 9 ); //set query arg ( key, value )
    }
}

// Portfolio project link Metabox
function project_link_metabox() {
	add_meta_box(
		'project_link_box',
		'Project Link',
		'project_link_box_content',
		'portfolio',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'project_link_metabox' );

function project_link_box_content() {
	global $post; ?>

	<input type="hidden" name="project_link_box_nonce" value="<?php echo wp_create_nonce( basename(__FILE__) ); ?>">

	<p>
		<label for="project_link_fields[url]">Input Text</label>
		<input type="url" name="project_link" id="project_link" class="regular-text" value="<?php echo get_post_meta( $post->ID, 'project_link', true ); ?>">
	</p>

<?php }

function save_project_link_meta( $post_id ) { 
	
	// verify nonce
	if ( !wp_verify_nonce( $_POST['project_link_box_nonce'], basename(__FILE__) ) ) {
		return $post_id; 
	}
	// check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}
	// check permissions
	if ( 'page' === $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}  
	}
	
	$old = get_post_meta( $post_id, 'project_link', true );
	$new = $_POST['project_link'];

	if ( $new && $new !== $old ) {
		update_post_meta( $post_id, 'project_link', $new );
	} elseif ( '' === $new && $old ) {
		delete_post_meta( $post_id, 'project_link', $old );
	}
}
add_action( 'save_post', 'save_project_link_meta' );

?>