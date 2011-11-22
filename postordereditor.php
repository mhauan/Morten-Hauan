<?php
/*
Plugin Name: Post Order Editor
Plugin URI: http://www.hauan.me
Description: Sort the order that posts are sorted on archive pages
Version: 1.0dev
Author: Morten Hauan
*/

add_action( 'admin_print_styles', 'VisSkjulteCustomFields' );
 
function VisSkjulteCustomFields() {
    echo "<style type='text/css'>#postcustom .hidden { display: table-row; }</style>\n";
}

add_action('admin_init', array('PageOrderEditorAdmin', 'admin_init'));
add_action("publish_post", array('PageOrderEditorAdmin', 'SavePost'));
add_action("publish_bildeannonser", array('PageOrderEditorAdmin', 'SavePost'));
add_action("create_category", array('PageOrderEditorAdmin', 'AddCategory'));
add_action("delete_category", array('PageOrderEditorAdmin', 'RemoveCategory'));
add_action('admin_menu', array('PageOrderEditorAdmin', 'create_menus'));
register_activation_hook( __FILE__, array('PageOrderEditor', 'pluggin_activate') );
register_deactivation_hook( __FILE__, array('PageOrderEditor', 'pluggin_deactivate') );


class PageOrderEditorAdmin {
	public static function admin_init() {
		wp_register_style( 'PageOrderEditorAdminStyleSheat', WP_PLUGIN_URL . '/pageordereditor/admin/admin.css' );
		wp_register_script( 'PageOrderEditorCore', WP_PLUGIN_URL . '/pageordereditor/admin/admin.js', array("jquery", "jquery-ui-draggable", "jquery-ui-droppable", "jquery-ui-sortable"), '0.1' );
		wp_register_script( 'PageOrderEditorSidemeta', WP_PLUGIN_URL . '/pageordereditor/admin/sidemeta.js', array("jquery"), '0.1' );
		add_meta_box('pageorder_side_meta', 'Rekkefølge', array('PageOrderEditorAdmin', 'admin_meta_box_side'), 'post', 'side');
		wp_enqueue_script('PageOrderEditorSidemeta');
	}
	public static function create_menus() {
		$page = add_posts_page('Rekkefølge', 'Rekkefølge', 'edit_others_pages', __FILE__, array('PageOrderEditorAdmin', 'admin_page'));
		add_action('admin_print_styles-' . $page, array('PageOrderEditorAdmin', 'admin_page_print_style'));
		add_action('admin_print_scripts-' . $page, array('PageOrderEditorAdmin', 'admin_page_print_script'));
	}
	public static function admin_page() {
		require_once(dirname(__FILE__) . '/admin/admin.php');
	}
	
	public static function admin_meta_box_side() {
		require_once(dirname(__FILE__) . '/admin/sidemeta.php');
	}
	public static function admin_page_print_script() {
		wp_enqueue_script('PageOrderEditorCore');
	}
	public static function admin_page_print_style() {
		wp_enqueue_style('PageOrderEditorAdminStyleSheat');
	}
	public static function SavePost($id) {
		$edit = new PageOrderEditor;
		$edit->save_post($id);
		return $id;
	}
	public static function AddCategory($id) {
		$edit = new PageOrderEditor;
		$edit->add_new_category_meta($id);
		return $id;
	}
	public static function RemoveCategory($id) {
		$edit = new PageOrderEditor;
		$edit->remove_category_meta($id);
		return $id;
	}
}

class PageOrderEditor { // Core class
	public $post_id;
	public $category;

	// Fuctions for activation and deactivation of plugin
	public static function pluggin_activate() {
		global $post;
		$categories = get_all_category_ids();
		foreach($categories as $cat_id) {
			$meta_name = "_PageOrder" . $cat_id;
			$args = array(
				'posts_per_page' => 150,//get_option('posts_per_page'),
				'post_status' => 'publish',
				'cat' => $cat_id,
				'post_type' => array('post', 'bildeannonser'),
				'orderby' => 'date',
				'order' => 'DESC'
				);
			$activate_plugginn = new WP_Query($args);
			$count = 1;
			while ($activate_plugginn->have_posts()) : $activate_plugginn->the_post();
				delete_post_meta($post->ID, $meta_name);
				add_post_meta($post->ID, $meta_name, $count);
				$count++;
			endwhile;
		}
		$args = array(
				'posts_per_page' => 150,//get_option('posts_per_page'),
				'post_status' => 'publish',
				'post_type' => array('post', 'bildeannonser'),
				'orderby' => 'date',
				'order' => 'DESC'
				);
		$activate_plugginn = new WP_Query($args);
		$count = 1;
		while ($activate_plugginn->have_posts()) : $activate_plugginn->the_post();
			delete_post_meta($post->ID, "_PageOrder0");
			add_post_meta($post->ID, "_PageOrder0", $count);
			$count++;
		endwhile;
		
		
		// Add database
		global $pageorder_db_version;
		$pageorder_db_version = "0.1";
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . "pageorder";
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			
			$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			catid mediumint(9) NOT NULL,
			numberpost mediumint(9) NOT NULL,
			autosave tinyint(1),
			UNIQUE KEY id (id)
			);";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			
			
		    $rows_affected = $wpdb->insert( $table_name, array( 'catid' => 0, 'numberpost' => get_option('posts_per_page ') ) );

			add_option("pageorder_db_version", $pageorder_db_version);
			add_option("PageOrder_numberpost", "150");			
			
		}
	}
	
	public static function pluggin_deactivate() {
		global $post;
		$categories = get_all_category_ids();
		foreach($categories as $cat_id) {
			$meta_name = "_PageOrder" . $cat_id;
			$args = array(
				'meta_key' => $meta_name,
				'post_type' => array('post', 'bildeannonser')
				);
			$deactivate_plugginn = new WP_Query($args);
			while ($deactivate_plugginn->have_posts()) : $deactivate_plugginn->the_post();
				delete_post_meta($post->ID, $meta_name);
			endwhile;
		}
		$args = array(
				'meta_key' => '_PageOrder0',
				'post_type' => array('post', 'bildeannonser')
				);
		$deactivate_plugginn = new WP_Query($args);
		while ($deactivate_plugginn->have_posts()) : $deactivate_plugginn->the_post();
			delete_post_meta($post->ID, "_PageOrder0");
		endwhile;

		global $wpdb;
		$table = $wpdb->prefix."pageorder";
		

		//$wpdb->query("DROP TABLE IF EXISTS $table");
		
		delete_option("pageorder_db_version");
		delete_option("PageOrder_numberpost");
	}
	
	// Add - Delete - Save - Publish functions
	public function save_post($id) {
		$this->post_id = $id;
		if(wp_is_post_revision($id)) $this->post_id = wp_is_post_revision($id);
		if($_POST['post_category']) :
			$this->category = $_POST['post_category'];
		elseif($_POST['kategorier']) :
			$this->category = $_POST['kategorier'];
		else :
			$this->category = $_GET['post_category'];
		endif;
		$this->do_save_post();
		return $id;
	}
	
	public function add_new_category_meta($id) {
		global $post;
		if($id) {
			$meta_name = "_PageOrder" . $id;
			$count = 1;
			$args = array(
				'posts_per_page' => get_option('posts_per_page '),
				'post_status' => 'publish',
				'meta_key' => $meta_name,
				'post_type' => array('post', 'bildeannonser')
				);
			$add_new_category_meta = new WP_Query($args);
			while ($add_new_category_meta->have_posts()) : $add_new_category_meta->the_post();
				if($this->has_meta($meta_name)) delete_post_meta($post->ID, $meta_name);
				add_post_meta($post->ID, $meta_name, $count);
				$count++;
			endwhile;
		}
	}
	
	public function remove_category_meta($id) {
		global $post;
		if($id) {
			$meta_name = "_PageOrder" . $id;
			$args = array(
				'posts_per_page' => 30,
				'meta_key' => $meta_name,
				'post_type' => array('post', 'bildeannonser')
				);
			$add_new_category_meta = new WP_Query($args);
			while ($add_new_category_meta->have_posts()) : $add_new_category_meta->the_post();
				delete_post_meta($post->ID, $meta_name);
			endwhile;
		}
	}
	
	// Core fuctions
	protected function has_meta($meta_name) {
		$has_meta = false;
		if(get_post_meta($this->post_id, $meta_name, true)) :
			$has_meta = true;
		endif;
		return $has_meta;
	}
	
	protected function do_save_post() {
		global $post;
		// Legger til meta for kategorier
		if(is_array($this->category)) {
			foreach ($this->category as $category) {
				if ($this->autosave($category)) {
					$meta_name = "_PageOrder" . $category;
					if(!$this->has_meta($meta_name)) {
						$args = array(
							'posts_per_page' => -1,
							'post_status' => 'publish',
							'meta_key' => $meta_name,
							'post_type' => array('post', 'bildeannonser')
						);
						$activate_plugginn = new WP_Query($args);
						while ($activate_plugginn->have_posts()) : $activate_plugginn->the_post();
							$pre_count = get_post_meta($post->ID, $meta_name, true);
							$count = $pre_count + 1;
							if($count <= $this->get_page($category)->numberpost) {
								update_post_meta($post->ID, $meta_name, $count);
							}
							else {
								delete_post_meta($post->ID, $meta_name);
							}
						endwhile;
						add_post_meta($this->post_id, $meta_name, 1);
					}
				}
			}
		}
		
		// Sletter eventuelle kategorier som har blitt fjernet
		$categories = get_all_category_ids();
		$cat_not_in = array_diff($categories, $this->category);
		foreach ($cat_not_in as $delete) {
			$meta_name = "_PageOrder" . $delete;
			delete_post_meta($this->post_id, $meta_name);
		}
		
		// Fjerne overføldige innlegg
	}
	
	protected function add_page($id, $numpost = 30) {
		if($id) {
			global $wpdb;
			
			$data = array(
				'catid' => $id,
				'numberpost' => $numpost
				);
	
			$table_name = $wpdb->prefix . "pageorder";
			$wpdb->insert($table_name, $data, array( '%d', '%d' ));
			return $wpdb->insert_id;
		}
	}
	
	protected function delete_page($id) {
		if($id) {
			global $wpdb;
			
			$table_name = $wpdb->prefix . "pageorder";
			$wpdb->query("DELETE FROM $table_name WHERE id=$id");
		}
	}
	
	public function update_page($id, $numpost) {
		if(isset($id, $numpost)) {
			global $wpdb;
			
			$this->update_page_number($id, $numpost);

			$data = array(
				'numberpost' => $numpost
				);
	
			$table_name = $wpdb->prefix . "pageorder";
			$wpdb->update($table_name, $data, array('catid' => $id),'%d' , '%d' );
		}
	}

	public function update_page_number($catid, $newnum, $excludeid) {
		global $post;
		if(isset($catid, $newnum)) {
			$meta_name = "_PageOrder" . $catid;
			$args = array(
				'posts_per_page' => -1,
				'order_by' => 'meta_value_num',
				'order' => 'ASC',
				'post_status' => 'publish',
				'meta_key' => $meta_name,
				'post_type' => array('post', 'bildeannonser')
				);
			$old_post = new WP_Query($args);
			$oldnum = $old_post->post_count;
			$old_posts_id = array();
			if($oldnum != $newnum) {
				if(isset($excludeid)) array_push($old_posts_id, $excludeid);
				if($oldnum < $newnum) { // Add posts
					// Find posts id's
					foreach($old_post->posts as $thepost) {
						array_push($old_posts_id, $thepost->ID);
					}
					
					// add new posts
					$diff = $newnum - $oldnum;
					$args = array(
						'cat' => $catid,
						'posts_per_page' => $diff,
						'post__not_in' => $old_posts_id,
						'post_type' => array('post', 'bildeannonser')
					);
					$i = $oldnum + 1;
					
					$add_posts = new WP_Query($args);
					while ($add_posts->have_posts()) : $add_posts->the_post();
						add_post_meta($post->ID, $meta_name, $i);
						$i++;
					endwhile;
					
				}
				else { // Remove posts
					$diff = ($oldnum - $newnum) + 1;
					$args = array(
				'posts_per_page' => $diff,
				'orderby' => 'meta_value_num',
				'order' => 'ASC',
				'offset' => $newnum,
				'post_status' => 'publish',
				'meta_key' => $meta_name,
				'post_type' => array('post', 'bildeannonser')
				);
					$delete_post = new WP_Query($args);
					while ($delete_post->have_posts()) : $delete_post->the_post();
						delete_post_meta($post->ID, $meta_name);
					endwhile;
				}
			}
		}
	}
	
	public $pages;
	public function get_pages() {
		global $wpdb;
		
		$table_name = $wpdb->prefix . "pageorder";
		$this->pages = $wpdb->get_results( "SELECT id, catid, numberpost FROM $table_name ORDER BY id ASC" );
		
		return $this->pages;
	}
	public function get_page($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . "pageorder";
		$page = $wpdb->get_row("SELECT * FROM " . $table_name . " WHERE catid = " . $id);
		return $page;
	}
	public function autosave($id) {
		global $wpdb;
		$table_name = $wpdb->prefix . "pageorder";
		$autosave = $wpdb->get_row("SELECT autosave FROM " . $table_name . " WHERE catid = " . $id);
		return $autosave->autosave;
	}
	
	public function init_pages() {
			$pages = $this->get_pages();
			foreach($pages as $page) {
		?>
            <li class="kategori" id="catid<?php echo $page->id; ?>">
            	<input type="hidden" name="id" class="id" value="<?php echo $page->id; ?>" />
            	<input type="hidden" name="catid" class="catid" value="<?php echo $page->catid; ?>" />
            	<input type="hidden" name="numperpost" class="numperpost" value="<?php echo $page->numberpost; ?>" />
            	<div class="kategori-name closed">
                	<div class="kategori-name-arrow-left" id="sort<?php echo $page->catid; ?>"></div>
                	<div class="kategori-name-arrow"></div>
                    <h3><?php if($page->catid == 0) echo "Fremside"; if($page->catid == 9999) echo "Verdt å lese"; echo get_cat_name($page->catid); ?></h3>
                </div>
                <div class="kategori-settings">
                	<p><label>Antall innlegg i kategorien:</label>
                    <input type="text" size="4" name="numpost" class="numpost<?php echo $page->catid; ?>" value="<?php echo $page->numberpost; ?>"/></p>
                    <p>&nbsp;</p>
                    <p><?php if($page->catid != 0 && 1 == 0) : ?><a href="#" class="delete-category">Slett</a> | <?php endif; ?><a href="#" class="close-category">Lukk</a>
                    <input type="button" class="button-primary postorder-save-button" id="save<?php echo $page->catid; ?>" value="Lagre"/></p>
                </div>
            </li>
    	<?php
			}
	}

	public function load_sortables($catid, $numberpost) {
		$i=1;
		$meta = "_PageOrder" . $catid;
		$args = array(
				'posts_per_page' => -1,
				'cat' => $catid,
				'orderby' => 'meta_value_num',
				'order' => 'ASC',
				'post_status' => 'publish',
				'meta_key' => $meta,
				'post_type' => array('post', 'bildeannonser')
				);
		$fremside = new WP_Query($args);
		global $post;
		if ($fremside->have_posts()) : ?>
        	<script type="text/javascript">
			//<![CDATA[
				CURCATID = "<?php echo $catid; ?>";
				CURCATNAME = "<?php if($catid == 0) echo "Fremside"; echo get_cat_name($catid); ?>";
				CURNUMPOST = "<?php echo $numberpost; ?>";
			//]]>
			</script>
			<ul id="fremside" class="catid_<?php echo $catid; ?>">
				<?php while ($fremside->have_posts()) : $fremside->the_post(); ?>
					<?php $fremside_post_id[] = get_the_ID(); ?>
					<li class="widget-top list item<?php echo $i; ?> <?php echo $post->post_type ?>" id="id<?php echo the_ID(); ?>"><span class="listnumber"><?php echo get_post_meta($post->ID, $meta, true); ?></span><h4><?php the_title(); ?></h4><?php the_modified_time(); ?>, <?php echo get_the_date(); ?> - <?php the_author(); ?><span class="delete" onclick="deletepost(<?php echo the_ID(); ?>);">fjern</span></li>
				<?php
				$i++;
				endwhile; ?>
			</ul>
		<?php else : ?>
        	<script type="text/javascript">
			//<![CDATA[
				CURCATID = "";
				CURCATNAME = "";
				CURNUMPOST = "";
			//]]>
			</script>
			<h3 class="tomt">Det er ingen innlegg i denne kategorien</h3>
		<?php endif;
	}

	public function sort_sortables($catid, $ids) {
		// Delete all metas
		global $post;
		$meta_name = "_PageOrder" . $catid;
		$args = array(
				'meta_key' => $meta_name,
				'post_type' => array('post', 'bildeannonser')
				);
		$remove_meta = new WP_Query($args);
		while ($remove_meta->have_posts()) : $remove_meta->the_post();
			delete_post_meta($post->ID, $meta_name);
		endwhile;
		
		// Add new metas
		$i = 1;
		foreach($ids as $id) {
			delete_post_meta($id, $meta_name);
			add_post_meta($id, $meta_name, $i);
			$i++;
		}
	}
	public function delete_sortable($id, $catid) {
		delete_post_meta($id, "_PageOrder" . $catid);
	}
	
	public function force_sortable($id, $catid) {
		global $post;
		$meta_name = "_PageOrder" . $catid;
		if(!$this->has_meta($meta_name)) {
			$args = array(
				'posts_per_page' => -1,
				'post_status' => 'publish',
				'meta_key' => $meta_name,
				'post_type' => array('post', 'bildeannonser')
				);
			$activate_plugginn = new WP_Query($args);
			while ($activate_plugginn->have_posts()) : $activate_plugginn->the_post();
				$pre_count = get_post_meta($post->ID, $meta_name, true);
				$count = $pre_count + 1;
				if($count <= $this->get_page($catid)->numberpost) {
					update_post_meta($post->ID, $meta_name, $count);
				}
				else {
					delete_post_meta($post->ID, $meta_name);
				}
			endwhile;
			add_post_meta($id, $meta_name, 1);
		}
	}
}

add_action('wp_ajax_page_order_ajax', array('PageOrderAjax', 'AjaxHandler'));
class PageOrderAjax {
	public function AjaxHandler() {
		$hook = $_POST['hook'];
		$data = $_POST['data'];
		$core = new PageOrderEditor;
		switch ($hook) {
			case "init_pages" :
				$core->init_pages();
			break;
			case "load_sortable":
				$catid = $data['catid'];
				if($catid == "") { if(get_locale() == "en_US") { $catid = "0"; } else { $catid = "77"; }};
				$numberpost = $data['numberpost'];
				if($numberpost == "") $numberpost = get_option("posts_per_page");
				$core->load_sortables($catid, $numberpost);
			break;
			case "update_page":
				$id = $data['id'];
				$numpost = $data['numpost'];
				$core->update_page($id, $numpost);
			break;
			case "sort_sortables":
				$ids = $data['ids'];
				$catid = $data['catid'];
				$core->sort_sortables($catid, $ids);
			break;
			case "delete_sortable":
				$id = $data['id'];
				$ids = $data['ids'];
				$catid = $data['catid'];
				$numberpost = $data['numberpost'];
				$core->sort_sortables($catid, $ids);
				$core->delete_sortable($id, $catid);
				$core->update_page_number($catid, $numberpost, $id);
			break;
			case "force_sortable":
				$id = $data['id'];
				$catid = $data['catid'];
				$core->force_sortable($id, $catid);
			break;
		}
		die();
	}
}

function pageorder_query_posts($numbpost = NULL) {
	global $query_string;
	$core = new PageOrderEditor;
	$cats = $core->get_pages();
	$query = $query_string;
	$query .= "&post_status=publish&orderby=meta_value_num&order=ASC";
	if(!is_null($numbpost))	$query .= "&posts_per_page=" . $numbpost;
	if(is_home()) {
		$cat = 0;
		$query .= "&meta_key=_PageOrder" . $cat;
		query_posts($query);
	}
	else {
		foreach($cats as $cat) {
			if(is_category($cat->catid) && $cat->catid) {
				$query .= "&meta_key=_PageOrder" . $cat->catid;
				parse_str($query, $args);
				$args['post_type'] = array('post', 'bildeannonser');
				query_posts($args);
			}
		}
	}
}
