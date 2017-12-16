<?php
/*
Plugin Name: Importar desde CSV
Description: Importar posts, categorias, tags, custom fields from simple csv file.
Author: Javier
*/

if ( !defined('WP_LOAD_IMPORTERS') )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

// Load Helpers
require dirname( __FILE__ ) . '/class-rs_csv_helper.php';
require dirname( __FILE__ ) . '/class-rscsv_import_post_helper.php';

/**
 * CSV Importer
 *
 * @package WordPress
 * @subpackage Importer
 */
if ( class_exists( 'WP_Importer' ) ) {
class RS_CSV_Importer extends WP_Importer {
	
	/** Sheet columns
	* @value array
	*/
	public $column_indexes = array();
	public $column_keys = array();

 	// User interface wrapper start
	function header() {
		echo '<div class="wrap">';
		echo '<h2>'.__('Importación mediante CSV', 'really-simple-csv-importer').'</h2>';
	}

	// User interface wrapper end
	function footer() {
		echo '</div>';
	}
	
	// Step 1
	function greet() {
		echo '<p>'.__( 'Abre el archivo CSV a importar y hacer click en "Subir el archivo e importar".', 'really-simple-csv-importer' ).'</p>';
		echo '<p>Es necesario que el archivo a importar se haya generado mediante la plantilla proporcionada.</p>';
		echo '<p>Si el archivo importado no existía previamente, copiar el numero proporcionado al finalizar la importación en el excel para actualizarlo en una futura importación.</p>';
		wp_import_upload_form( add_query_arg('step', 1) );
	}

	// Step 2
	function import() {
		$file = wp_import_handle_upload();

		if ( isset( $file['error'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'really-simple-csv-importer' ) . '</strong><br />';
			echo esc_html( $file['error'] ) . '</p>';
			return false;
		} else if ( ! file_exists( $file['file'] ) ) {
			echo '<p><strong>' . __( 'Sorry, there has been an error.', 'really-simple-csv-importer' ) . '</strong><br />';
			printf( __( 'The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'really-simple-csv-importer' ), esc_html( $file['file'] ) );
			echo '</p>';
			return false;
		}
		
		$this->id = (int) $file['id'];
		$this->file = get_attached_file($this->id);
		$result = $this->process_posts();
		if ( is_wp_error( $result ) )
			return $result;
	}
	
	/**
	* Insert post and postmeta using `RSCSV_Import_Post_Helper` class.
	*
	* @param array $post
	* @param array $meta
	* @param array $terms
	* @param string $thumbnail The uri or path of thumbnail image.
	* @param bool $is_update
	* @return RSCSV_Import_Post_Helper
	*/
	public function save_post($post,$meta,$terms,$thumbnail,$is_update) {
		
		// Separate the post tags from $post array
		if (isset($post['post_tags']) && !empty($post['post_tags'])) {
			$post_tags = $post['post_tags'];
			unset($post['post_tags']);
		}

		// Special handling of attachments
		if (!empty($thumbnail) && $post['post_type'] == 'attachment') {
			$post['media_file'] = $thumbnail;
			$thumbnail = null;
		}

		// Add or update the post
		if ($is_update) {
			$h = RSCSV_Import_Post_Helper::getByID($post['ID']);
			$h->update($post);
		} else {
			$h = RSCSV_Import_Post_Helper::add($post);
		}
		
		// Set post tags
		if (isset($post_tags)) {
			$h->setPostTags($post_tags);
		}
		
		// Set meta data
		$h->setMeta($meta);
		
		// Set terms
		foreach ($terms as $key => $value) {
			$h->setObjectTerms($key, $value);
		}
		
		// Add thumbnail
		if ($thumbnail) {
			$h->addThumbnail($thumbnail);
		}
		
		return $h;
	}

	// process parse csv ind insert posts
	function process_posts() {
		$h = new RS_CSV_Helper;

		$handle = $h->fopen($this->file, 'r');
		if ( $handle == false ) {
			echo '<p><strong>'.__( 'Failed to open file.', 'really-simple-csv-importer' ).'</strong></p>';
			wp_import_cleanup($this->id);
			return false;
		}
		
		$is_first = true;
		$post_statuses = get_post_stati();
		
		echo '<ol>';
		
		while (($data = $h->fgetcsv($handle)) !== FALSE) {
			if ($is_first) {
				$h->parse_columns( $this, $data );
				$is_first = false;
			} else {
				echo '<li>';
				
				$post = array();
				$is_update = false;
				$error = new WP_Error();
				
				// (string) (required) post type
				$post_type = "customlp";
				if ($post_type) {
					if (post_type_exists($post_type)) {
						$post['post_type'] = $post_type;
					} else {
						$error->add( 'post_type_exists', sprintf(__('Invalid post type "%s".', 'really-simple-csv-importer'), $post_type) );
					}
				} else {
					echo __('Note: Please include post_type value if that is possible.', 'really-simple-csv-importer').'<br>';
				}
				
				// (int) post id
				$post_id = $h->get_data($this,$data,'ID');
				$post_id = ($post_id) ? $post_id : $h->get_data($this,$data,'post_id');
				if ($post_id) {
					$post_exist = get_post($post_id);
					if ( is_null( $post_exist ) ) { // if the post id is not exists
						$is_update = false;
					} else {
						if ( !$post_type  || $post_exist->post_type == $post_type ) {
							$post['ID'] = $post_id;
							$is_update = true;
						} else {	
							$error->add( 'post_type_check', sprintf(__('The post type value from your csv file does not match the existing data in your database. post_id: %d, post_type(csv): %s, post_type(db): %s', 'really-simple-csv-importer'), $post_id, $post_type, $post_exist->post_type) );
							echo "\n"; 
							echo "Tipo de post del que se quiere añadir:" . $post_type . "VS Existente:" . $post_exist->post_type . "\n";
							echo "Estado del post que se quiere añadir:" . $post_type . "VS Existente:" . $post_exist->post_status . "\n";
						}
					}
				}
				
				// (string) post slug
				$post_name = $h->get_data($this,$data,'post_name');
				if ($post_name) {
					$post['post_name'] = $post_name;
				}
				
				
				// (login or ID) post_author
				$post_author = $h->get_data($this,$data,'post_author');
				if ($post_author) {
					if (is_numeric($post_author)) {
						$user = get_user_by('id',$post_author);
					} else {
						$user = get_user_by('login',$post_author);
					}
					if (isset($user) && is_object($user)) {
						$post['post_author'] = $user->ID;
						unset($user);
					}
				}

				// user_login to post_author
				$user_login = $h->get_data($this,$data,'post_author_login');
				if ($user_login) {
					$user = get_user_by('login',$user_login);
					if (isset($user) && is_object($user)) {
						$post['post_author'] = $user->ID;
						unset($user);
					}
				}
				
				// (string) publish date
				$post_date = $h->get_data($this,$data,'post_date');
				if ($post_date) {
					$post['post_date'] = date("Y-m-d H:i:s", strtotime($post_date));
				}
				$post_date_gmt = $h->get_data($this,$data,'post_date_gmt');
				if ($post_date_gmt) {
					$post['post_date_gmt'] = date("Y-m-d H:i:s", strtotime($post_date_gmt));
				}
				
				// (string) post status
				$post_status = $h->get_data($this,$data,'post_status');
				if ($post_status) {
    				if (in_array($post_status, $post_statuses)) {
    					$post['post_status'] = $post_status;
    				}
				}
				else{
					$post['post_status'] = 'publish';//por defecto el estado es publish
				}
				
				// (string) post password
				$post_password = $h->get_data($this,$data,'post_password');
				if ($post_password) {
    				$post['post_password'] = $post_password;
				}
				
				// (string) post title
				$post_title = $h->get_data($this,$data,'post_title');
				if(!empty($post_title)){
					$post['post_title'] = $post_title;
				}
				else{
					exit ("El post con id $post_id no tiene titulo, revisalo, por favor. Importación detenida.");
				}
				
				// (string) post content
				$post_content = $h->get_data($this,$data,'post_content');
				if ($post_content) {
					$post['post_content'] = $post_content;
				} 
				else{
					$post['post_content'] = '';
				}
				
				// (string) post excerpt
				$post_excerpt = $h->get_data($this,$data,'post_excerpt');
				if ($post_excerpt) {
					$post['post_excerpt'] = $post_excerpt;
				}
				
				// (int) post parent
				$post_parent = $h->get_data($this,$data,'post_parent');
				if ($post_parent) {
					$post['post_parent'] = $post_parent;
				}
				
				// (int) menu order
				$menu_order = $h->get_data($this,$data,'menu_order');
				if ($menu_order) {
					$post['menu_order'] = $menu_order;
				}
				
				// (string) comment status
				$comment_status = $h->get_data($this,$data,'comment_status');
				if ($comment_status) {
					$post['comment_status'] = $comment_status;
				}
				
				// (string, comma separated) slug of post categories
				$post_category = $h->get_data($this,$data,'post_category');
				if ($post_category) {
					$categories = preg_split("/,+/", $post_category);
					$cat_slugs = "";
					$cat_ids = array();
					if ($categories) {
						foreach($categories as $cat){
							$current_cat = get_term_by('name', trim($cat), 'category');//Intento extraerla por nombre
							if(empty($current_cat)){
								$current_cat = get_term_by('slug', strtolower(str_replace(" ","-",trim($cat))) , 'category');//Intento extraerla por slug
							}
							if(empty($current_cat))
								echo "Error al importar, la categoria $cat no existe. Post ID: $post_id. ";
							else{//La encontre
								$cat_slugs .= $current_cat->slug.",";
								$category_parents = explode('|',get_category_parents($current_cat->term_id,false,'|',true));
								foreach($category_parents as $current_parent){
									if(!empty($current_parent)){
										$single_parent = get_term_by('slug', trim($current_parent), 'category');
										if(!in_array($single_parent->term_id, $cat_ids)){
											$cat_ids[] = $single_parent->term_id;
										}
									}
								}								
								if(!in_array($current_cat->term_id, $cat_ids))
									$cat_ids[] = $current_cat->term_id;
							}
						}
						$cat_slugs = trim($cat_slugs, ',');
												
						$post['post_category'] = wp_create_categories($cat_ids);
					}
				}
				
				// (string, comma separated) name of post tags
				$post_tags = $h->get_data($this,$data,'post_tags');
				if ($post_tags) {
					$post['post_tags'] = $post_tags;
				}
				
				// (string) post thumbnail image uri
				$post_thumbnail = $h->get_data($this,$data,'post_thumbnail');
				
				// Se guardan las distintas URLs de las imágenes que queremos incorporar.
				$post_imagenes = $h->get_data($this,$data,'post_imagenes');
				
				$meta = array();
				$tax = array(); //No utilizamos tax personalizadas
				
				$ImagesArray = array();
				$TotalImages = 0;
				
				if (!empty($post_imagenes)) {
					$arrtmp = explode ( "\n", $post_imagenes ); // Separa por saltos de línea.
					if ($arrtmp){
						$TotalImages = count($arrtmp);
						for ($i = 0; $i < $TotalImages; $i++){
							if(!empty($arrtmp[$i])) {
								$tokens = explode ( "|", $arrtmp[$i]); // Separa por pipes | 
								if($tokens) {
									$ImagesArray[] = array(
										'nlp_image_url' => $tokens[0],
										'nlp_image_alt' => $tokens[1],
									);
								}
							}
						}
					}
					$meta['nlp_photo_details'] = base64_encode(serialize($ImagesArray));
					$meta['nlp_total_photos'] = $TotalImages;
				} else {
					$TotalImages = 0;
					$meta['nlp_total_photos'] = $TotalImages;
					
					$ImagesArray = array();
					$meta['nlp_photo_details'] = base64_encode(serialize($ImagesArray));
				}
						
				
				// Añade cualquier otro campo del excel como metacampo
				foreach ($data as $key => $value) {
					if ($value !== false && isset($this->column_keys[$key])) {						
						$meta[$this->column_keys[$key]] = $value;
					}
				}
				
				//Nuestro custom post no tiene contenido pero se necesita contenido, 
				//así que le añado el contenido de nlp_descripcion
				$urlImagen = $ImagesArray[0]['nlp_image_url'];
				//Creo la información de postmeta para la APi
				// $ar_to_nacho = $ImagesArray;
				$meta['nlp_category'] = $cat_slugs;

				/*if (!$post_content) {
					$content = "<p>";
					if ($TotalImages != 0) {
						$content .= "<img class=\"alignnone size-medium\" src=\"{$urlImagen}\" alt=\"{$post_title}\" width=\"300\" height=\"200\"/>
						<br/> 
						<br/>{$nuestraDescripcion}</p>
						<p><!--more--></p>";
					} else {
						$content .= "{$nuestraDescripcion}</p><p><!--more--></p>";
					}
				}				
				$post['post_content'] = $content;*/
				// array_shift($ar_to_nacho);
				// $meta['extraImgsUrl'] = $ar_to_nacho;
				/**
				 * Filter post data.
				 *
				 * @param array $post (required)
				 * @param bool $is_update
				 */
				$post = apply_filters( 'really_simple_csv_importer_save_post', $post, $is_update );
				/**
				 * Filter meta data.
				 *
				 * @param array $meta (required)
				 * @param array $post
				 * @param bool $is_update
				 */
				$meta = apply_filters( 'really_simple_csv_importer_save_meta', $meta, $post, $is_update );
				/**
				 * Filter taxonomy data.
				 *
				 * @param array $tax (required)
				 * @param array $post
				 * @param bool $is_update
				 */
				$tax = apply_filters( 'really_simple_csv_importer_save_tax', $tax, $post, $is_update );
				/**
				 * Filter thumbnail URL or path.
				 *
				 * @since 1.3
				 *
				 * @param string $post_thumbnail (required)
				 * @param array $post
				 * @param bool $is_update
				 */
				$post_thumbnail = apply_filters( 'really_simple_csv_importer_save_thumbnail', $post_thumbnail, $post, $is_update );

				/**
				 * Option for dry run testing
				 *
				 * @since 0.5.7
				 *
				 * @param bool false
				 */
				$dry_run = apply_filters( 'really_simple_csv_importer_dry_run', false );
				
				if (!$error->get_error_codes() && $dry_run == false) {
					
					/**
					 * Get Alternative Importer Class name.
					 *
					 * @since 0.6
					 *
					 * @param string Class name to override Importer class. Default to null (do not override).
					 */
					$class = apply_filters( 'really_simple_csv_importer_class', null );
					
					// save post data
					if ($class && class_exists($class,false)) {
						$importer = new $class;
						$result = $importer->save_post($post,$meta,$tax,$post_thumbnail,$is_update);
					} else {
						$result = $this->save_post($post,$meta,$tax,$post_thumbnail,$is_update);
					}
					
					if ($result->isError()) {
						$error = $result->getError();
					} else {
						$post_object = $result->getPost();
						
						if (is_object($post_object)) {
							/**
							 * Fires adter the post imported.
							 *
							 * @since 1.0
							 *
							 * @param WP_Post $post_object
							 */
							do_action( 'really_simple_csv_importer_post_saved', $post_object );
						}
					}
				}
				
				// show error messages
				foreach ($error->get_error_messages() as $message) {
					echo esc_html($message).'<br>';
				}
				
				echo '</li>';
			}
		}
		
		echo '</ol>';

		$h->fclose($handle);
		
		wp_import_cleanup($this->id);
		
		echo '<h3>'.__('Importación completada con éxito.', 'really-simple-csv-importer').'</h3>';
	}

	// dispatcher
	function dispatch() {
		$this->header();
		
		if (empty ($_GET['step']))
			$step = 0;
		else
			$step = (int) $_GET['step'];

		switch ($step) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				check_admin_referer('import-upload');
				set_time_limit(0);
				$result = $this->import();
				if ( is_wp_error( $result ) )
					echo $result->get_error_message();
				break;
		}
		
		$this->footer();
	}
	
}

// Initialize
function really_simple_csv_importer() {
    $rs_csv_importer = new RS_CSV_Importer();
    register_importer('csv', __('Importar CSV', 'really-simple-csv-importer'), __('Importar posts mediante un archivo CSV.', 'really-simple-csv-importer'), array ($rs_csv_importer, 'dispatch'));
}
add_action( 'plugins_loaded', 'really_simple_csv_importer' );

} // class_exists( 'WP_Importer' )