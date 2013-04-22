<?php
/*
Plugin Name: Custom taxonomy Template
Plugin URI: http://en.bainternet.info
Description: This plugin lets you select a specific template for a taxomony term, just like pages
Version: 0.2
Author: Bainternet
Author URI: http://en.bainternet.info
*/
/*  Copyright 2012 Ohad Raz aKa BaInternet  (email : admin@bainternet.info)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,this
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename (__FILE__)) {
	die('Sorry, but you cannot access this page directly.');
}

if (!class_exists('Custom_Taxonomy_Template')){
	/**
	 *  @author Ohad Raz <admin@bainternet.info>
	 *  @access public
	 *  @version 0.1
	 *  
	 */
	class Custom_Taxonomy_Template{
		
		/**
		 *  class constructor
		 *  
		 *  @since 0.1
		 *  @author Ohad Raz <admin@bainternet.info>
		 *  @access public
		 *  
		 *  @return void
		 */
		public function __construct()
		{
			//do the template selection
			add_filter( 'template_include', array($this,'get_Custom_Taxonomy_Template' ));
			
			add_action('admin_init',array($this,'init'),999);
			//plugin row links
			add_filter( 'plugin_row_meta', array($this,'_my_plugin_links'), 10, 2 );
			//extra action on constructor
			do_action('Custom_Taxonomy_Template_constructor',$this);
		}

		public function init(){
			$args=array(
			  'public'   => true,
			  '_builtin' => false
			  
			); 
			$output = 'names'; // or objects
			$operator = 'and'; // 'and' or 'or'
			$taxonomies=get_taxonomies($args,$output,$operator);
			$exclude = apply_filters('custom_taxonomy_template_tax_exclude',array());
			if  ($taxonomies) {
			 	foreach ($taxonomies  as $taxonomy ) {
					if (!in_array($taxonomy,$exclude)){
	    			  	//add extra fields to taxonomy edit form hook
		    		    add_action ( $taxonomy.'_edit_form_fields', array($this,'taxonomy_template_meta_box'));
			    	    add_action( $taxonomy.'_add_form_fields', array( &$this, 'taxonomy_template_meta_box') );
				        // save extra taxonomy extra fields hook
	    				add_action ( 'edited_'.$taxonomy, array($this,'save_taxonomy_template'));
		    			add_action ( 'created_'.$taxonomy, array($this,'save_taxonomy_template'));
					}
			  	}
			}
		}

		
		/**
		 * taxonomy_template_meta_box add extra fields to taxonomy edit form callback function
		 * 
		 *  @since 0.1
		 *  @author Ohad Raz <admin@bainternet.info>
		 *  @access public
		 *  
		 *  @param  (object) $tag  
		 *  
		 *  @return void
		 * 
		 */
		public function taxonomy_template_meta_box( $tag ) {
		    $t_id = isset($tag->term_id) ? $tag->term_id: 0;
		    $tax_meta = get_option( "taxonomy_templates");
		    $template = isset($tax_meta[$t_id]) ? $tax_meta[$t_id] : false;
			?>
			<tr class="form-field">
				<th scope="row" valign="top"><label for="cat_Image_url"><?php _e('taxonomy Template'); ?></label></th>
				<td>
					<select name="tax_template" id="tax_template">
						<option value='default'><?php _e('Default Template'); ?></option>
						<?php page_template_dropdown($template); ?>
					</select>
					<br />
			            <span class="description"><?php _e('Select a specific template for this taxonomy'); ?></span>
			    </td>
			</tr>
			<?php
			do_action('Custom_Taxonomy_Template_ADD_FIELDS',$tag);
		}


		/**
		 * save_taxonomy_template save extra taxonomy extra fields callback function
		 *  
		 *  @since 0.1
		 *  @author Ohad Raz <admin@bainternet.info>
		 *  @access public
		 *  
		 *  @param  int $term_id 
		 *  
		 *  @return void
		 */
		public function save_taxonomy_template( $term_id ) {
		    if ( isset( $_POST['tax_template'] ) ) {
		        $tax_meta = get_option( "taxonomy_templates");
		        $tax_meta[$term_id] = $_POST['tax_template'];
		        update_option( "taxonomy_templates", $tax_meta );
		        do_action('Custom_Taxonomy_Template_SAVE_FIELDS',$term_id);
		    }
		}

		/**
		 * get_Custom_Taxonomy_Template handle taxonomy template picking
		 * 
		 *  @since 0.1
		 *  @author Ohad Raz <admin@bainternet.info>
		 *  @access public
		 *  
		 *  @param  string $taxonomy_template 
		 *  
		 *  @return string taxonomy template
		 */
		function get_Custom_Taxonomy_Template( $taxonomy_template ) {
			if (!is_tax())
				return $taxonomy_template;
			$term_slug = get_query_var( 'term' );
			$taxonomyName = get_query_var( 'taxonomy' );
			$current_term = get_term_by( 'slug', $term_slug, $taxonomyName );

			$term_ID = $current_term->term_id;
			$tax_meta = get_option('taxonomy_templates');
			if (isset($tax_meta[$term_ID]) && $tax_meta[$term_ID] != 'default'){
				$temp = locate_template($tax_meta[$term_ID]);
				if (!empty($temp))
					return apply_filters("Custom_Taxonomy_Template_found",$temp);
			}
		    return $taxonomy_template;
		}

		/**
		 * _my_plugin_links
		 * @since 0.1
		 * @author Ohad Raz <admin@bainternet.info>
		 * @param  array $links 
		 * @param  File $file  
		 * @return array      
		 */
		public function _my_plugin_links($links, $file) {
		    $plugin = plugin_basename(__FILE__); 
		    if ($file == $plugin) // only for this plugin
		            return array_merge( $links,
		        array( '<a href="http://en.bainternet.info/category/plugins">' . __('Other Plugins by this author' ) . '</a>' ),
		        array( '<a href="http://wordpress.org/support/plugin/custom-taxonomy-template">' . __('Plugin Support') . '</a>' ),
		        array( '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K4MMGF5X3TM5L" target="_blank">' . __('Donate') . '</a>' )
		    );
		    return $links;
		}
	}//end class
}//end if
$tax_template = new Custom_Taxonomy_Template();