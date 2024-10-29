<?php

/*
Plugin Name: Beautiful permalink
Description: Plugins create custom Beautiful permalink for SEO
Author: Akasa Team
Version: 2.0
Author URI: https://www.facebook.com/akasa.com.vn/
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

Class LC_Beautiful{

    public function __construct(){
       
       
        register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
        register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );
        add_action('admin_enqueue_scripts', array($this,'admin_style'));
        add_action('admin_init',array($this,'permalink_register_fields'));
        add_action('load-options-permalink.php',array($this,'settings_save'));
        add_action( 'init',array($this,'lc_blog_category_args_filter'));
        add_filter( 'term_link', array( $this, 'plugins_remove_category_from_url' ),10,3);
        add_filter('woocommerce_taxonomy_args_product_cat', array($this, 'remove_hierarchical_woocommerce_taxonomy_args_product_cat'));
        add_filter( 'post_type_link',array($this,'lc_beautiful_remove_cpt_slug'), 10, 3 );
        add_action( 'pre_get_posts',array($this,'lc_beautiful_parse_request_trick') );
        add_filter('sanitize_title',array($this,'clear_beautiful_sanitize_title'));
 
        add_action('template_redirect', array($this,'plugins_product_cat_old_term_redirect'));
        add_action('template_redirect', array($this,'plugins_category_old_term_redirect'));
        add_action('template_redirect', array($this,'plugins_single_product_old_term_redirect'));
        add_action( 'registered_taxonomy',array($this,"html_product_category_permastruct_html"), 10, 3 );
 

    }
  
    public function plugins_remove_category_from_url($termlink,$term, $taxonomy){
        if(get_option('beautiful-permalink-taxonomy')==1) {
            global $wp_rewrite;
            $termlink = str_replace('/category/', '/', $termlink);
            $termlink = str_replace('/'._x( 'product-category', 'slug', 'woocommerce' ).'/', '/', $termlink);
		
            return $termlink;
        }
        else{
            return $termlink;
        }
    }

    public function lc_blog_category_args_filter(){

        if(get_option('beautiful-permalink-taxonomy')==1){

            register_taxonomy('category', 'post', array(
                'hierarchical' => true,
                'update_count_callback' => '_update_post_term_count',
                'query_var' => 'category_name',
                'rewrite' => did_action('init') ? array(
                    'hierarchical' => false,
                    'slug' => get_option('category_base') ? get_option('category_base') : 'category',
                    'with_front' => false) : false,
                'public' => true,
                'show_ui' => true,
                '_builtin' => true,
            ));


            if(!is_admin()){

                add_filter('request', array($this,'taxonomy_change_term_request'), 1, 1 );


            }



        }

    }


    public function clear_beautiful_sanitize_title($title){
        if((int) get_option('clear-beautiful-permalink')==1) {
            $title = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/i", 'a', $title);
            $title = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/i", 'e', $title);
            $title = preg_replace("/(ì|í|ị|ỉ|ĩ)/i", 'i', $title);
            $title = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/i", 'o', $title);
            $title = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/i", 'u', $title);
            $title = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/i", 'y', $title);
            $title = preg_replace("/(đ)/i", 'd', $title);
            $title = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $title);
            $title = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $title);
            $title = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $title);
            $title = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $title);
            $title = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $title);
            $title = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $title);
            $title = preg_replace("/(Đ)/", 'D', $title);
            $title = str_replace(" ", "-", str_replace("&*#39;", "", $title));
            $title = str_replace(' ', '', $title);
            $title = preg_replace('/[^A-Za-z0-9\-]/', '', $title);
            $title = trim(strip_tags(addslashes($title)));
            $title = strtolower($title);
            return $title;
        }
        else{
            return $title;
        }

    }

    public function lc_beautiful_remove_cpt_slug( $post_link, $post, $leavename){
  $productbaseplugin=get_option('product-base-plugin');
        if((int) get_option('beautiful-permalink-productwc')==1) {
            if ( class_exists( 'WooCommerce' ) ) {
                if ('product' != $post->post_type || 'publish' != $post->post_status) {
                    return $post_link;
                }
                $post_link = str_replace('/' ._x( 'product', 'slug', 'woocommerce' ). '/', '/', $post_link);
                if(strlen($productbaseplugin)>0){
					
					
					
								if(strpos(get_option("permalink_structure"),".")){
					
					$arg_permalink_structure=explode(".",get_option("permalink_structure"));
					$last_arg=end($arg_permalink_structure);
					if(strlen($last_arg)>0){
					$post_link=trim($post_link,"/").".".$last_arg; 
				
					
					}
				}
				else{
				
$post_link=trim($post_link,"/").".".$productbaseplugin;
				
				}
				
      
                }
				else{
					
						if(strpos(get_option("permalink_structure"),".")){
					
					$arg_permalink_structure=explode(".",get_option("permalink_structure"));
					$last_arg=end($arg_permalink_structure);
					if(strlen($last_arg)>0){
					$post_link=trim($post_link,"/").".".$last_arg; 
				
					
					}
				}
				
				}
			

            }
            return $post_link;

        }
        else{
            return $post_link;
        }
    }


    function lc_beautiful_parse_request_trick( $query ) {

	


            if ((int)get_option('beautiful-permalink-productwc') == 1) {

  $productbaseplugin=get_option('product-base-plugin');
  if(strlen($productbaseplugin)>0){
	  
	if($query->query['name']){

    $term = str_replace(".".$productbaseplugin, '',$query->query['name']);
    $query->query['name']=trim($term);
    $query->query_vars['name']=trim($term);

	}
	  
	if($query->query['product']){

    $term = str_replace(".".$productbaseplugin, '',$query->query['product']);

    $query->query['product']=trim($term);
    $query->query_vars['product']=trim($term);

	  }
	  
     }
            	
				
                if (class_exists('WooCommerce')) {


                    if (!$query->is_main_query())
                        return;

                    if(get_query_var(AMP_QUERY_VAR)){

                        if(!is_search()){
                            $query->set('post_type', array('post', 'page', 'product'));
                        }

                    }
                    else{

                        if (2 != count($query->query) || !isset($query->query['page'])) {
                            return;
                        }


                        if (!empty($query->query['name'])) {
                            if(!is_search()){
                                $query->set('post_type', array('post', 'page', 'product'));
                            }

                        }

                    }






                }
            }


    }

    public function remove_hierarchical_woocommerce_taxonomy_args_product_cat($args){
        if((int) get_option('beautiful-permalink-taxonomy')==1){
            if ( class_exists( 'WooCommerce' ) ) {
                $args['rewrite']['hierarchical'] = false;
                return $args;
            }
            else{
                return $args;
            }

        }
        else{
            return $args;
        }

    }

    public function plugins_product_cat_old_term_redirect() {
        if(get_option('beautiful-permalink-taxonomy')==1) {
            $taxonomy_name = 'product_cat';
            $taxonomy_slug = _x( 'product-category', 'slug', 'woocommerce' );

            // exit the redirect function if taxonomy slug is not in URL
            if (strpos($_SERVER['REQUEST_URI'], $taxonomy_slug) === FALSE){
			
			if(is_tax() && !is_category()){
				$beautifultaxonomyhtml=get_option('beautiful-permalink-taxonomy-html');
			if($beautifultaxonomyhtml==1 && strpos($_SERVER['REQUEST_URI'],".html")===false){
				$siteurl=str_replace($taxonomy_slug, '', $_SERVER['REQUEST_URI'].".html");
				
				$argsiteurl=explode("/",$siteurl);
				
				$lastsiteurl=end($argsiteurl);
				$linksite=$lastsiteurl;
				
				 $linksite=str_replace('.html', '',$linksite);
                  $linksite=str_replace('.', '',$linksite);
					$linksite=$linksite.".html";			
				
					$linksite=site_url($linksite);
					
						wp_redirect($linksite, 301);
				exit;	
			}
			
					
		}
				
                return;
			}

            if (is_tax($taxonomy_name)) :
			 
                wp_redirect(site_url(str_replace($taxonomy_slug, '', $_SERVER['REQUEST_URI'])), 301);
                exit();

            endif;
        }
    }

    public function plugins_single_product_old_term_redirect(){
        if(get_option('beautiful-permalink-productwc')==1) {
$productbaseplugin=get_option('product-base-plugin');

            if ( class_exists( 'WooCommerce' ) ) {
                if (strpos($_SERVER['REQUEST_URI'],_x( 'product', 'slug', 'woocommerce' )) === FALSE){
                    
                    if(is_product()){
                       
                        if(!strpos($_SERVER['REQUEST_URI'],".".$productbaseplugin)){
                              if(strlen($productbaseplugin)>0){
                                 $link=str_replace('.', '',$_SERVER['REQUEST_URI']);
                           $urlredirect=$link.".".$productbaseplugin;
                         wp_redirect($urlredirect, 301);
                           exit();
                           
                                }
                          
 return;    
                           
                    }
                    else{
                        
                         if(strlen($productbaseplugin)>0){
                            
                        
                        if((substr_count($_SERVER['REQUEST_URI'],".")>1)){
                        $link=str_replace('.'.$productbaseplugin, '',$_SERVER['REQUEST_URI']);
                        $link=str_replace('.', '',$link);
                           $urlredirect=$link.".".$productbaseplugin;
                         wp_redirect($urlredirect, 301);
                           exit();                            
                        }
                            
                            }
                            else{
                                
                                if((substr($_SERVER['REQUEST_URI'], -1) == '.')){
                      
                        $link=str_replace('.', '',$_SERVER['REQUEST_URI']);
                           $urlredirect=$link;
                         wp_redirect($urlredirect, 301);
                           exit();  
                                         
                                }
                            }
                            
                        

                    }
                    
                   
                      
                    }
                    else{
                    return;    
                    }

                    
                }
                else{
                    if (is_product()):
                      
  if(strlen($productbaseplugin)>0){
  $urlredirect=str_replace('/'._x( 'product', 'slug', 'woocommerce' ).'/', '/', $_SERVER['REQUEST_URI']);
     $urlredirect=$urlredirect.".".$productbaseplugin;
  
    }
    else{
      $urlredirect=str_replace('/'._x( 'product', 'slug', 'woocommerce' ).'/', '/', $_SERVER['REQUEST_URI']);   
    }                
                          wp_redirect($urlredirect, 301);
                           exit();


                    endif;
                }


            }
        }

    }


    public function plugins_category_old_term_redirect() {
        if(get_option('beautiful-permalink-productwc')==1) {
            $taxonomy_name = 'category_name';
            $taxonomy_slug = 'category';

            // exit the redirect function if taxonomy slug is not in URL
            if (strpos($_SERVER['REQUEST_URI'], $taxonomy_slug) === FALSE)
                return;

            if (is_category()) :
                wp_redirect(site_url(str_replace($taxonomy_slug, '', $_SERVER['REQUEST_URI'])), 301);
                exit();

            endif;
        }
    }

    public function taxonomy_change_term_request($query){
        if(isset($query)){

	
        $beautifultaxonomyhtml=get_option('beautiful-permalink-taxonomy-html');
		
            if(isset($query['name'])):
                $name = $query['name'];
				
				if(strpos($name,".html") && $beautifultaxonomyhtml==1){
				$name=trim($name,".html");	
				}
                $term = get_term_by('slug',$name,'category');

                if (isset($name) && $term && !is_wp_error($term)):
                    unset($query['name']);
                    $query['category_name'] = $name;
                else:

                    if ( class_exists( 'WooCommerce' ) ) {

                        $term = get_term_by('slug',$name,'product_cat');


                        if (isset($name) && $term && !is_wp_error($term)):
                            unset($query['name']);
                            $query['product_cat'] = $name;
                        endif;
                    }

                endif;




                return $query;


            endif;


            if(isset($query['category_name'])):
                $name = $query['category_name'];
                $term = get_term_by('slug',$name,'product_cat');

                if (isset($name) && $term && !is_wp_error($term)):
                    unset($query['category_name']);
                    $query['product_cat'] = $name;

                endif;

                return $query;

            endif;


            return $query;
        }
    }



    public function permalink_register_fields(){
        add_settings_section( 'beautiful-permalink', __( 'Beautiful permalinks SEO', '' ), array( $this, 'permalink_register_field_callback' ), 'permalink' );

    }
    public function permalink_register_field_callback(){
        $taxonomy_permalink=get_option('beautiful-permalink-taxonomy');
        $beautifultaxonomyhtml=get_option('beautiful-permalink-taxonomy-html');
        $product_permalink=get_option('beautiful-permalink-productwc');
        $beautifulclearlink=get_option('clear-beautiful-permalink');
        $productbaseplugin=get_option('product-base-plugin');
		
		
        ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th><label>
                        Permalink Taxonomy
                    </label></th>
                <td>
                    <div class="beautiful-permalink">
                        <div class="onoffswitch">
                            <input type="checkbox" name="beautifultaxonomy" value="on" class="cmn-toggle onoffswitch-checkbox" id="beautifultaxonomy" <?=($taxonomy_permalink==1?'checked':''); ?>>
                            <label class="onoffswitch-label" id="labelbeautifultaxonomy" for="beautifultaxonomy">
                                <span class="onoffswitch-inner"></span>
                                <span class="onoffswitch-switch"></span>
                            </label>
                        </div>
                    </div><code class="default-example"><?php echo esc_html( home_url() ); ?>/taxonomy/category</code> &rarr; <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/category/</code></td>
            </tr>
			
			<?php
if ( class_exists( 'WooCommerce' ) ) {
 ?>
			<tr id="trbeautifultaxonomyhtml">
                <th><label>
                        Add Html to link Product Category
                    </label></th>
                <td>
                    <div class="beautiful-permalink">
                        <div class="onoffswitch">
                            <input type="checkbox" name="beautifultaxonomyhtml" value="on" class="cmn-toggle onoffswitch-checkbox" id="beautifultaxonomyhtml" <?=($beautifultaxonomyhtml==1?'checked':''); ?>>
                            <label class="onoffswitch-label" for="beautifultaxonomyhtml" id="labelbeautifultaxonomyhtml">
                                <span class="onoffswitch-inner"></span>
                                <span class="onoffswitch-switch"></span>
                            </label>
                        </div>
                    </div><code class="default-example"><?php echo esc_html( home_url() ); ?>/taxonomy/category</code> &rarr; <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/category.html</code></td>
            </tr>
			<?php
};
			?>
			
            <tr>
                <th><label>
                        Clear Permalink Slug (UTF8)
                    </label></th>
                <td>
                    <div class="beautiful-permalink">
                        <div class="onoffswitch">
                            <input type="checkbox" name="beautifulclearlink" value="on" class="cmn-toggle onoffswitch-checkbox" id="beautifulclearlink" <?=($beautifulclearlink==1?'checked':''); ?>>
                            <label class="onoffswitch-label" for="beautifulclearlink">
                                <span class="onoffswitch-inner"></span>
                                <span class="onoffswitch-switch"></span>
                            </label>
                        </div>
                    </div><code class="default-example"><?php echo esc_html( home_url() ); ?>/xin-chào</code> &rarr; <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/xin-chao/</code></td>
            </tr>
<?php
if ( class_exists( 'WooCommerce' ) ) {
 ?>
    <tr>
        <th><label>
                Permalink Product (WooCommerce)
            </label></th>
        <td>
            <div class="beautiful-permalink">
                <div class="onoffswitch">
                    <input type="checkbox" name="beautifulproductwc" value="on" class="cmn-toggle onoffswitch-checkbox" id="beautifulproductwc" <?=($product_permalink==1?'checked':''); ?>>
                    <label class="onoffswitch-label" for="beautifulproductwc" id="labelbeautifulproductwc">
                        <span class="onoffswitch-inner"></span>
                        <span class="onoffswitch-switch"></span>
                    </label>
                </div>
            </div><code class="default-example"><?php echo esc_html( home_url() ); ?>/product/post-name</code> &rarr; <code class="non-default-example"><?php echo esc_html( home_url() ); ?>/post-name/</code></td>
    </tr>
	
	
	 <tr id="customlinkproduct">
        <th><label>
              Custom Link Product (WooCommerce)
            </label></th>
        <td>
            <div class="beautiful-permalink">
               <input name="product_base_plugin" id="product_base_plugin" placeholder="html" type="text" value="<?=$productbaseplugin; ?>" class="regular-text code">
            </div>
			</td>
    </tr>
	
    <?php
}
?>

            </tbody>
        </table>
<?php
    }

    public function settings_save(){
        if ( ! is_admin() ) {
            return;
        }
        if(isset($_POST['submit'])){
            $beautifultaxonomy=isset($_POST['beautifultaxonomy'])?1:0;
            update_option('beautiful-permalink-taxonomy',$beautifultaxonomy);
            $beautifulproductwc=isset($_POST['beautifulproductwc'])?1:0;
            update_option('beautiful-permalink-productwc',$beautifulproductwc);
            $beautifulproductwc=isset($_POST['beautifulclearlink'])?1:0;
            update_option('clear-beautiful-permalink',$beautifulproductwc);
			
			            $beautifultaxonomyhtml=isset($_POST['beautifultaxonomyhtml'])?1:0;
            update_option('beautiful-permalink-taxonomy-html',$beautifultaxonomyhtml);
			
		
				            
              $product_base_plugin=isset($_POST['product_base_plugin'])?sanitize_title(addslashes(trim(strip_tags($_POST['product_base_plugin'])))):'';
            update_option('product-base-plugin',$product_base_plugin);
            if($product_permalink==1){
            $this->load_akasa_product_permastruct_html();                
			}
					
			flush_rewrite_rules();
			
			
        }
    }
public function html_product_category_permastruct_html( $taxonomy, $object_type, $args ) {
	if(get_option("beautiful-permalink-taxonomy-html")==1){
    if ( $taxonomy === 'product_cat' ){
        add_permastruct( $taxonomy, "{$args['rewrite']['slug']}/%$taxonomy%.html", $args['rewrite'] );		
		}
	}

}

    public function admin_style() {
        wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__).'/assets/css/admin.css');
            wp_enqueue_script( 'plugins_custom_script', plugin_dir_url(__FILE__) . '/assets/js/plugins.js' );
    }

    public function plugin_activation(){
        add_option('beautiful-permalink-taxonomy',1);
        add_option('beautiful-permalink-productwc',1);
        add_option('clear-beautiful-permalink',1);
        add_option('beautiful-permalink-taxonomy-html',0);
        
          
         add_action('init', function() {
         flush_rewrite_rules();
         
        });
    }
    public function plugin_deactivation(){
        delete_option('beautiful-permalink-taxonomy');
        delete_option('beautiful-permalink-productwc');
        delete_option('clear-beautiful-permalink');
        delete_option('beautiful-permalink-taxonomy-html');
        delete_option('product-base-plugin');
    }
    
    public function akasa_product_permastruct_html( $post_type, $args ) {
          $productbaseplugin=get_option('product-base-plugin');
    if ( $post_type === 'product' ){
        add_permastruct( $post_type, "{$args->rewrite['slug']}.".$productbaseplugin,$args->rewrite);  
        flush_rewrite_rules();
    }

    }
    
     public function load_akasa_product_permastruct_html(){
        
        $productbaseplugin=get_option('product-base-plugin');
        if(strlen($productbaseplugin)>0){
          add_action( 'registered_post_type',array($this,'akasa_product_permastruct_html'), 10, 2 );
               
        }

     }
	 

 

}
new LC_Beautiful();
