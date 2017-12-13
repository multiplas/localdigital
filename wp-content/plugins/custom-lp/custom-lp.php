<?php
/*
 * Plugin Name: Custom Landing Page
 * Description: Plugin que permite importar contenido de forma masiva mediante la plantilla pre-establecida.
 * Author: Javier
 * Version: 0.0.1
 */

require dirname( __FILE__ ) . '/importar-csv/rs-csv-importer.php'; //Importar contenido mediante CSV

// init plugin
if (! function_exists ( 'crearCustomPost' )) {
	function crearCustomPost() {
		
		// set up labels
		$labels = array (
				"name" => __( 'Entradas personalizadas', 'customlp' ),
				"singular_name" => __( 'Entrada', 'customlp' ),
				"menu_name" => __( 'Entrada personalizada', 'customlp' ),
				"all_items" => __( 'Todas mis entradas', 'customlp' ),
				"add_new" => __( 'Nueva entrada personalizada', 'customlp' ),
				"add_new_item" => __( 'Agrega una nueva entrada personalizada', 'customlp' ),
				"edit_item" => __( 'Edita tu entrada personalizada', 'customlp' ),
				"view_item" => __( 'Vista de la entrada personalizada', 'customlp' ),
				"search_items" => __( 'Búsqueda de la entrada', 'customlp' ),
				"not_found" => __( 'Entrada no encontrada', 'customlp' ),
				"not_found_in_trash" => __( 'No hay entradas en la Papelera', 'customlp' ),
				"set_featured_image" => __( 'Nueva foto para tu entrada', 'customlp' ),
				"archives" => __( 'Archivos de la entrada', 'customlp' ) 
		);
		
		$argumentos = array (
				'labels' => $labels,
				'description' => __( 'Este post es un custom post type para las Landing pages', 'customlp' ),
				'taxonomies' => array (
						'category' 
				),
				'public'  => true,
				'capability_type' => 'post',
				'publicly_queryable' => false,
				'map_meta_cap' => true,
				'menu_position' => 5,
				'hierarchical' => false,
				'rewrite' => array (
						'slug' => '/',
						'with_front' => false
				),
				'query_var' => false,
				'delete_with_user' => true,
				'menu_icon' => 'https://goo.gl/YRM2Jz',
				'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'comments', 'revisions', 'author', 'page-attributes', 'post-formats' )
		);
		
		// https://codex.wordpress.org/Function_Reference/register_post_type
		register_post_type ( 'customlp', $argumentos );
	}
	add_action ( 'init', 'crearCustomPost', 1 );
}

/* Choose a template for category */
if (!class_exists('Custom_Category_Template')){
	/**
	 *  @author Javier Díaz
	 *  @access public
	 *  @version 0.1
	 *  
	 */
	class Custom_Category_Template{
		
		private $cache_hash;
		private static $cache_expiration = 1800;
		
		/**
		 *  class constructor
		 *  
		 *  @return void
		 */
		public function __construct()
		{
			$this->cache_hash = md5( $this->get_template_directory() );
			
			//do the template selection
			add_filter( 'category_template', array( $this, 'get_custom_category_template' ));
			add_filter( 'single_template', array( $this, 'get_custom_single_category_template' ));
			//add extra fields to category NEW/EDIT form hook
			add_action ( 'edit_category_form_fields', array( $this, 'category_template_meta_box_table' ));
			add_action( 'category_add_form_fields', array( &$this, 'category_template_meta_box' ));
						
			// save extra category extra fields hook
			add_action( 'created_category', array( &$this, 'save_category_template' ));
			add_action( 'edited_category', array( $this, 'save_category_template' ));
			//extra action on constructor
			do_action('Custom_Category_Template_constructor',$this);
		}

		
		/**
		 * category_template_meta_box add extra fields to category edit form callback function
		 *  
		 *  @param  (object) $tag  
		 *  
		 *  @return void
		 * 
		 */
		public function category_template_meta_box( $tag ) {
		    $t_id = $tag->term_id;
		    $cat_meta = get_option( "category_templates");
			$cat_s_meta = get_option( "single_category_templates");
		    $template = isset($cat_meta[$t_id]) ? $cat_meta[$t_id] : false;
			$template_s = isset($cat_s_meta[$t_id]) ? $cat_s_meta[$t_id] : false;
			?>
			<div class="form-field term-template-wrap">
				<label for="cat_template"><?php _e('Category Template'); ?></label>
				<select name="cat_template" id="cat_template" class="postform">
					<option value='default'><?php _e('Default Template'); ?></option>
					<?php $this->category_template_dropdown($template); ?>
				</select>
				<p><?php _e('Select a specific template for this category'); ?></p>
			</div>
			<div class="form-field term-single-template-wrap">
				<label for="cat_s_template"><?php _e('Single Category Template'); ?></label>
				<select name="cat_s_template" id="cat_s_template" class="postform">
					<option value='default'><?php _e('Default Single Template'); ?></option>
					<?php $this->category_template_dropdown($template_s, true); ?>
				</select>
				<p><?php _e('Select a specific template for this single of this category'); ?></p>
			</div>
			<?php
			do_action('Custom_Category_Template_ADD_FIELDS',$tag);
		}
		
		public function category_template_meta_box_table( $tag ) {
		    $t_id = $tag->term_id;
		    $cat_meta = get_option( "category_templates");
			$cat_s_meta = get_option( "single_category_templates");
		    $template = isset($cat_meta[$t_id]) ? $cat_meta[$t_id] : false;
			$template_s = isset($cat_s_meta[$t_id]) ? $cat_s_meta[$t_id] : false;
			?>
			<tr class="form-field term-template-wrap">
				<th scope="row"><label for="cat_template"><?php _e('Category Template'); ?></label></th>
				<td><select name="cat_template" id="cat_template" class="postform">
					<option value='default'><?php _e('Default Template'); ?></option>
					<?php $this->category_template_dropdown($template); ?>
				</select>
				<p><?php _e('Selecciona una plantilla específica para esta categoría.'); ?></p></td>
			</tr>
			<tr class="form-field term-single-template-wrap">
				<th scope="row"><label for="cat_s_template"><?php _e('Single Category Template'); ?></label></th>
				<td><select name="cat_s_template" id="cat_s_template" class="postform">
					<option value='default'><?php _e('Default Single Template'); ?></option>
					<?php $this->category_template_dropdown($template_s, true); ?>
				</select>
				<p><?php _e('Selecciona una plantilla específica para los posts que pertenecen a esta categoría.'); ?></p></td>
			</tr>
			<?php
			do_action('Custom_Category_Template_ADD_FIELDS',$tag);
		}


		/**
		 * save_category_template save extra category extra fields callback function
		 *  
		 *  @param  int $term_id 
		 *  
		 *  @return void
		 */
		public function save_category_template( $term_id ) {
		    if ( isset( $_POST['cat_template'] )) {
		        $cat_meta = get_option( "category_templates");
		        $cat_meta[$term_id] = $_POST['cat_template'];
		        update_option( "category_templates", $cat_meta );
		        do_action('Custom_Category_Template_SAVE_FIELDS',$term_id);
		    }
			if ( isset( $_POST['cat_s_template'] )) {
		        $cat_s_meta = get_option( "single_category_templates");
		        $cat_s_meta[$term_id] = $_POST['cat_s_template'];
		        update_option( "single_category_templates", $cat_s_meta );
		        do_action('Custom_Category_Template_SAVE_FIELDS',$term_id);
		    }
		}

		/**
		 * get_custom_category_template handle category template picking
		 *  
		 *  @param  string $category_template 
		 *  
		 *  @return string category template
		 */
		function get_custom_category_template( $category_template ) {
			$cat_ID = absint( get_query_var('cat') );
			$cat_meta = get_option('category_templates');
			if (isset($cat_meta[$cat_ID]) && $cat_meta[$cat_ID] != 'default' ){
				$filepath = $this->get_template_directory().'/'.$cat_meta[$cat_ID];
				if ( file_exists($filepath)){
					return $filepath;
				}
			}
		    return $category_template;
		}
		function get_custom_single_category_template( $category_template ) {
			$cat_meta = get_option('single_category_templates');			
			foreach( (array) get_the_category() as $cat ) {
				$cat_ID = $cat->cat_ID;
				if (isset($cat_meta[$cat_ID]) && $cat_meta[$cat_ID] != 'default' ){
					$filepath = $this->get_template_directory().'/'.$cat_meta[$cat_ID];					
					if ( file_exists($filepath)){
						return $filepath;
					}
				}
			}			
			//call filters
			$category_template = apply_filters('custom_category_template', $category_template);
			
			return $category_template;
		}
		
		function category_template_dropdown( $default = '', $single = false ) {
			$templates = $this->get_category_templates( $single );
			ksort( $templates );
			foreach ( array_keys( $templates ) as $template ) {
				$selected = selected( $default, $templates[ $template ], false );
				echo "\n\t<option value='" . $templates[ $template ] . "' $selected>$template</option>";
			}
		}
		
		private function get_category_templates( $single = false ) {
			
			if ($single)
				$category_templates = $this->cache_get( 'single_category_templates' );
			else 
				$category_templates = $this->cache_get( 'category_templates' );			
			
			if ( ! is_array( $category_templates ) ) {
				$category_templates = array();

				$files = (array) $this->get_files( 'php', 1 );

				foreach ( $files as $file => $full_path ) {
					if ($single && !preg_match( '|Single Template Name:(.*)$|mi', file_get_contents( $full_path ), $header )) 
						continue;
					else if (!$single && !preg_match( '|Category Template Name:(.*)$|mi', file_get_contents( $full_path ), $header ))
						continue;
					$category_templates[ _cleanup_header_comment( $header[1] ) ] = $file;
				}

				if ($single)
					$this->cache_add( 'single_category_templates', $category_templates );
				else
					$this->cache_add( 'category_templates', $category_templates );
			}
			
			if ($single)
				return $category_templates;
				
			$return = apply_filters( 'theme_category_templates', $category_templates, $this, null );
			return array_intersect_assoc( $return, $category_templates );
		}
		
		private function get_files( $type = null, $depth = 0, $search_parent = false ) {
			$files = (array) $this->scandir( $this->get_template_directory(), $type, $depth );

			return $files;
		}
		
		private function scandir( $path, $extensions = null, $depth = 0, $relative_path = '' ) {
			if ( ! is_dir( $path ) )
				return false;

			if ( $extensions ) {
				$extensions = (array) $extensions;
				$_extensions = implode( '|', $extensions );
			}

			$relative_path = trailingslashit( $relative_path );
			if ( '/' == $relative_path )
				$relative_path = '';

			$results = scandir( $path );
			$files = array();

			foreach ( $results as $result ) {
				if ( '.' == $result[0] )
					continue;
				if ( is_dir( $path . '/' . $result ) ) {
					if ( ! $depth || 'CVS' == $result )
						continue;
					$found = scandir( $path . '/' . $result, $extensions, $depth - 1 , $relative_path . $result );
					$files = array_merge_recursive( $files, $found );
				} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
					$files[ $relative_path . $result ] = $path . '/' . $result;
				}
			}

			return $files;
		}
		
		private function get_template_directory() {
			return dirname( __FILE__ ) . '/category-templates';
		}
		
		private function cache_add( $key, $data ) {			
			return wp_cache_add( $key . '-' . $this->cache_hash, $data, 'themes', self::$cache_expiration );
		}
		
		private function cache_get( $key ) {
			return wp_cache_get( $key . '-' . $this->cache_hash, 'themes' );
		}
		
	}//end class
}//end if

$cat_template = new Custom_Category_Template();