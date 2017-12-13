<?php

if (!defined('WP_LOAD_IMPORTERS'))
    return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if (!class_exists('WP_Importer')) {
    $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
    if (file_exists($class_wp_importer))
        require_once $class_wp_importer;
}

// Load Helpers
require dirname(__FILE__) . '/class-CAT_CSV_Helper.php';
/**
 * CSV Importer
 *
 * @package WordPress
 * @subpackage Importer
 */
if (class_exists('WP_Importer')) {
    class CAT_CSV_Importer extends WP_Importer
    {
        
        /** Sheet columns
         * @value array
         */
        public $column_indexes = array();
        public $column_keys = array();
        
        // User interface wrapper start
        function header()
        {
            echo '<div class="wrap">';
            echo '<h2>' . __('Import CSV', 'really-simple-csv-importer') . '</h2>';
        }
        
        // User interface wrapper end
        function footer()
        {
            echo '</div>';
        }
        
        // Step 1
        function greet()
        {
            echo '<p>' . __('Choose a CSV (.csv) file to upload, then click Upload file and import.', 'really-simple-csv-importer') . '</p>';
            echo '<p>' . __('Excel-style CSV file is unconventional and not recommended. LibreOffice has enough export options and recommended for most users.', 'really-simple-csv-importer') . '</p>';
            echo '<p>' . __('Requirements:', 'really-simple-csv-importer') . '</p>';
            echo '<ol>';
            echo '<li>' . __('Select UTF-8 as charset.', 'really-simple-csv-importer') . '</li>';
            echo '<li>' . sprintf(__('You must use field delimiter as "%s"', 'really-simple-csv-importer'), CAT_CSV_Helper::DELIMITER) . '</li>';
            echo '<li>' . __('You must quote all text cells.', 'really-simple-csv-importer') . '</li>';
            echo '</ol>';
            echo '</p>';
            wp_import_upload_form(add_query_arg('step', 1));
        }
        
        // Step 2
        function import()
        {
            $file = wp_import_handle_upload();
            
            if (isset($file['error'])) {
                echo '<p><strong>' . __('Sorry, there has been an error.', 'really-simple-csv-importer') . '</strong><br />';
                echo esc_html($file['error']) . '</p>';
                return false;
            } else if (!file_exists($file['file'])) {
                echo '<p><strong>' . __('Sorry, there has been an error.', 'really-simple-csv-importer') . '</strong><br />';
                printf(__('The export file could not be found at <code>%s</code>. It is likely that this was caused by a permissions problem.', 'really-simple-csv-importer'), esc_html($file['file']));
                echo '</p>';
                return false;
            }
            
            $this->id   = (int) $file['id'];
            $this->file = get_attached_file($this->id);
            $result     = $this->process_posts();
            if (is_wp_error($result))
                return $result;
        }
        function obtener_url($url)
        {
            $url = str_replace("servantrip.com/es", "servantrip.com", $url);
            $url = str_replace("servantrip.com/fr", "servantrip.com", $url);
            $url = str_replace("servantrip.com/it", "servantrip.com", $url);
            $url = str_replace("servantrip.com/ar", "servantrip.com", $url);
            $url = str_replace("servantrip.com/mx", "servantrip.com", $url);
            $url = str_replace("servantrip.com/cn", "servantrip.com", $url);
            $url = str_replace("servantrip.com/ru", "servantrip.com", $url);
            $url = str_replace("servantrip.com/pt", "servantrip.com", $url);
            $url = rtrim($url, "/");
            return $url;
        }
        
        function insert_post($post)
        {
            $post_id = wp_insert_post( $post );
			return $post_id;
        }
        function update_post($post_id)
        {
			get_metadata($meta_type, $object_id, $meta_key, $single);
            $post_id = wp_insert_post( $post );
        }
        function update_postmeta($post_id)
        {
			get_metadata($meta_type, $object_id, $meta_key, $single);
            $post_id = wp_insert_post( $post );
        }
        function delete_postmeta($post_id)
        {
			get_metadata($meta_type, $object_id, $meta_key, $single);
            $post_id = wp_insert_post( $post );
        }
        function add_postmeta($post_id, $meta)
        {
			foreach($meta_data as $key => $value){
				add_post_meta($post_id, $key, $value);
			}
        }
        
        // process parse csv ind insert posts
        function process_posts()
        {
            $h = new CAT_CSV_Helper;
			$meta = array();
            
            $handle = $h->fopen($this->file, 'r');
            if ($handle == false) {
                echo '<p><strong>' . __('Failed to open file.', 'really-simple-csv-importer') . '</strong></p>';
                wp_import_cleanup($this->id);
                return false;
            }
            
            $is_first      = true;
            $post_statuses = get_post_stati();
            echo '<ol>';
            while (($data = $h->fgetcsv($handle)) !== FALSE) {
                if ($is_first) {
                    $h->parse_columns($this, $data);
                    $is_first = false;
                } else {
                    echo '<li>';
                    $error = new WP_Error();
                    // Extraer los datos del csv
					$post_id =  $h->get_data($this, $data, 'post_id');
					
					$post = array(
						'post_title' => $h->get_data($this, $data, 'post_title'),
						'post_content' => $h->get_data($this, $data, 'post_content'),
						'post_category' => $h->get_data($this, $data, 'post_category'),
						'post_name' => $h->get_data($this, $data, 'post_name'),
						'post_status' => 'draft',
						'post_type' => 'customlp'
					);   

					foreach ($data as $key => $value) {
						if ($value !== false && isset($this->column_keys[$key])) {						
							$meta[$this->column_keys[$key]] = $value;
						}
					}      
                    
                    //Creamos los request para que me sanitice la url
                    //$permalink = sanitize_title($permalink);
                   	if ( FALSE === get_post_status( $post_id ) ) {// The post does not exist
						$post_id = $this->insert_post($post);
						$this->add_postmeta($post_id, $meta);
					} else {
						// The post exists
					}
                    
                    if (!$error->get_error_codes() && $dry_run == false) {
                        
                        /**
                         * Get Alternative Importer Class name.
                         *
                         * @since 0.6
                         *
                         * @param string Class name to override Importer class. Default to null (do not override).
                         */
                        $class = apply_filters('simple_category_importer_class', null);
                    }
                    
                    // show error messages
                    foreach ($error->get_error_messages() as $message) {
                        echo esc_html($message) . '<br>';
                    }
                    
                    echo '</li>';
                }
            }
            
            echo '</ol>';
            
            $h->fclose($handle);
            
            wp_import_cleanup($this->id);
            echo '<h3 style="color:green;">' . __('All Done.', 'really-simple-csv-importer') . '</h3>';
        }
        
        // dispatcher
        function dispatch()
        {
            $this->header();
            if (empty($_GET['step']))
                $step = 0;
            else
                $step = (int) $_GET['step'];
            
            switch ($step) {
                case 0:
                    $this->greet();
                    break;
                case 1:
                    check_admin_referer('import-upload');
                    set_time_limit(0);
                    $result = $this->import();
                    if (is_wp_error($result))
                        echo $result->get_error_message();
                    break;
            }
            $this->footer();
        }
    }
    
    // Initialize
    function simple_category_importer()
    {
        $CAT_CSV_Importer = new CAT_CSV_Importer();
        register_importer('csv_category', __('Importar CSV', 'really-simple-csv-importer'), __('Import categories from simple csv file.', 'really-simple-csv-importer'), array(
            $CAT_CSV_Importer,
            'dispatch'
        ));
    }
    
    add_action('plugins_loaded', 'simple_category_importer');
    
} // class_exists( 'WP_Importer' )