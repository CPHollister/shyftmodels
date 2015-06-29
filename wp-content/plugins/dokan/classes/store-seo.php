<?php
/**
 * Dokan SEO class
 *
 * Integrates Dokan SEO template in front-end Settings menu and provides
 * SEO settings for single stores
 *
 * @since 2.3
 */
class Dokan_Store_Seo {

    public $feedback    = false;
    private $store_info = false;

    public function __construct() {

        $this->init_hooks();
    }

    public static function init() {
        static $instance = false;

        if ( !$instance ) {
            $instance = new Dokan_Store_Seo();
        }

        return $instance;
    }

    /**
     * Init hooks and filters
     *
     * @return void
     */
    function init_hooks() {

        add_action( 'wp_ajax_dokan_seo_form_handler', array( $this, 'dokan_seo_form_handler' ) );
        add_action( 'template_redirect', array( $this, 'output_meta_tags' ) );
    }

    /**
     * Adds proper hooks for output of meta tags
     *
     * @return void
     */
    function output_meta_tags() {
        if ( !dokan_is_store_page() ) {
            return;
        }

        if ( dokan_get_option( 'store_seo', 'dokan_general' ) === 'off' ) {
            return;
        }

        $this->store_info = dokan_get_store_info( get_query_var( 'author' ) );

        if ( class_exists( 'All_in_One_SEO_Pack' ) ) {

            add_filter( 'aioseop_title', array( $this, 'replace_title' ), 500 );
            add_filter( 'aioseop_keywords', array( $this, 'replace_keywords' ), 100 );
            add_filter( 'aioseop_description', array( $this, 'replace_desc' ), 100 );
            add_action( 'wp_head', array( $this, 'print_social_tags' ), 1 );
        } elseif ( class_exists( 'WPSEO_Frontend' ) ) {

            add_filter( 'wp_title', array( $this, 'replace_title' ), 500 );
            add_filter( 'wpseo_metakeywords', array( $this, 'replace_keywords' ) );
            add_filter( 'wpseo_metadesc', array( $this, 'replace_desc' ) );

            add_filter( 'wpseo_opengraph_title', array( $this, 'replace_og_title' ) );
            add_filter( 'wpseo_opengraph_desc', array( $this, 'replace_og_desc' ) );
            add_filter( 'wpseo_opengraph_image', array( $this, 'replace_og_img' ) );
            add_action( 'wpseo_opengraph', array( $this, 'print_og_img' ), 20 );

            add_filter( 'wpseo_twitter_title', array( $this, 'replace_twitter_title' ) );
            add_filter( 'wpseo_twitter_description', array( $this, 'replace_twitter_desc' ) );
            add_filter( 'wpseo_twitter_image', array( $this, 'replace_twitter_img' ) );
            add_action( 'wpseo_twitter', array( $this, 'print_twitter_img' ), 20 );
        } else {

            add_filter( 'wp_title', array( $this, 'replace_title' ), 500 );
            add_action( 'wp_head', array( $this, 'print_tags' ), 1 );
            add_action( 'wp_head', array( $this, 'print_social_tags' ), 1 );
        }
    }

    /*
     * prints out default meta tags from user meta
     *
     * @since 1.0.0
     * @param none
     *
     * @return void
     */

    function print_tags() {
        //get values of title,desc and keywords
        $meta_values = $this->store_info;

        if ( !isset( $meta_values['store_seo'] ) || $meta_values == false ) {
            return;
        }

        $desc     = $meta_values['store_seo']['dokan-seo-meta-desc'];
        $keywords = $meta_values['store_seo']['dokan-seo-meta-keywords'];



        if ( $desc ) {
            echo PHP_EOL . '<meta name="description" content="' . $this->print_saved_meta( $desc ) . '"/>';
        }
        if ( $keywords ) {
            echo PHP_EOL . '<meta name="keywords" content="' . $this->print_saved_meta( $keywords ) . '"/>';
        }
    }

    /**
     * Prints out social tags
     *
     * @since 1.0.0
     */
    function print_social_tags() {
        $meta_values = $this->store_info;

        if ( !isset( $meta_values['store_seo'] ) || $meta_values == false ) {
            return;
        }

        $og_title      = $meta_values['store_seo']['dokan-seo-og-title'];
        $og_desc       = $meta_values['store_seo']['dokan-seo-og-desc'];
        $og_img        = $meta_values['store_seo']['dokan-seo-og-image'];
        $twitter_title = $meta_values['store_seo']['dokan-seo-twitter-title'];
        $twitter_desc  = $meta_values['store_seo']['dokan-seo-twitter-desc'];
        $twitter_img   = $meta_values['store_seo']['dokan-seo-twitter-image'];

        if ( $og_title ) {
            echo PHP_EOL . '<meta property="og:title" content="' . $this->print_saved_meta( $og_title ) . '"/>';
        }

        if ( $og_desc ) {
            echo PHP_EOL . '<meta property="og:description" content="' . $this->print_saved_meta( $og_desc ) . '"/>';
        }

        if ( $og_img ) {
            echo PHP_EOL . '<meta property="og:image" content="' . wp_get_attachment_url( $og_img ) . '"/>';
        }

        if ( $twitter_title ) {
            echo PHP_EOL . '<meta name="twitter:title" content="' . $this->print_saved_meta( $twitter_title ) . '"/>';
        }

        if ( $twitter_desc ) {
            echo PHP_EOL . '<meta name="twitter:description" content="' . $this->print_saved_meta( $twitter_desc ) . '"/>';
        }

        if ( $twitter_img ) {
            echo PHP_EOL . '<meta name="twitter:image" content="' . wp_get_attachment_url( $twitter_img ) . '"/>';
        }
    }

    /**
     * Generic meta replacer for meta tags
     *
     * @since 1.0.0
     *
     * @param string val, string meta_name, string meta_type
     *
     * @return string meta
     */
    function replace_meta( $val_default, $meta, $type = '' ) {

        $meta_values = $this->store_info;

        if ( !isset( $meta_values['store_seo'] ) || $meta_values == false ) {
            return $val_default;
        }

        $key = 'dokan-seo-' . $type . '-' . $meta;
        $val = $meta_values['store_seo'][$key];

        if ( $val ) {
            return $val;
        }

        return $val_default;
    }

    /**
     * Replace title meta of other SEO plugin
     *
     * @param title
     * @since 1.0.0
     *
     * @return string title
     */
    function replace_title( $title ) {
        return $this->replace_meta( $title, 'title', 'meta' );
    }

    /**
     * Replace keywords meta of other SEO plugin
     *
     * @param keywords
     *
     * @since 1.0.0
     *
     * @return keywords
     */
    function replace_keywords( $keywords ) {
        return $this->replace_meta( $keywords, 'keywords', 'meta' );
    }

    /**
     * Replace description meta of other SEO plugin
     *
     * @param desc
     *
     * @since 1.0.0
     *
     * @return desc
     */
    function replace_desc( $desc ) {
        return $this->replace_meta( $desc, 'desc', 'meta' );
    }

    /**
     * Replace OG tag title for WP_SEO
     *
     * @since 1.0.0
     */
    function replace_og_title( $title ) {
        return $this->replace_meta( $title, 'title', 'og' );
    }

    /**
     * Replace OG tag description for WP_SEO
     *
     * @since 1.0.0
     */
    function replace_og_desc( $desc ) {
        return $this->replace_meta( $desc, 'desc', 'og' );
    }

    /**
     * Replace OG tag Image for WP_SEO
     *
     * @since 1.0.0
     */
    function replace_og_img( $img ) {
        $img_default = $img;

        $meta_values = $this->store_info;

        if ( !isset( $meta_values['store_seo'] ) || $meta_values == false ) {
            return $img_default;
        }

        $img = $meta_values['store_seo']['dokan-seo-og-image'];

        if ( $img )
            return wp_get_attachment_url( $img );
        else
            return $img_default;
    }

    function print_og_img() {
        $meta_values = $this->store_info;

        if ( !isset( $meta_values['store_seo'] ) || $meta_values == false ) {
            return;
        }

        $og_img = $meta_values['store_seo']['dokan-seo-og-image'];

        if ( $og_img ) {
            echo '<meta property="og:image" content="' . wp_get_attachment_url( $og_img ) . '"/>';
        }
    }

    /**
     * Replace twitter tag title for WP_SEO
     *
     * @param  string
     *
     * @return string
     */
    function replace_twitter_title( $val_default ) {
        return $this->replace_meta( $val_default, 'title', 'twitter' );
    }

    /**
     * replace twitter tag description for WP_SEO
     *
     * @param  string
     *
     * @return string
     */
    function replace_twitter_desc( $val_default ) {
        return $this->replace_meta( $val_default, 'desc', 'twitter' );
    }

    /**
     * Replace twitter image tag for WP_SEO
     *
     * @param  string
     *
     * @return string
     */
    function replace_twitter_img( $img ) {

        $img_default = $img;

        $meta_values = $this->store_info;

        if ( !isset( $meta_values['store_seo'] ) || $meta_values == false ) {
            return $img_default;
        }

        $img = $meta_values['store_seo']['dokan-seo-twitter-image'];

        if ( $img ) {
            return wp_get_attachment_url( $img );
        }

        return $img_default;
    }

    /**
     * Prints out twitter image tag
     *
     * @return void
     */
    function print_twitter_img() {
        $meta_values = $this->store_info;

        if ( !isset( $meta_values['store_seo'] ) || $meta_values == false ) {
            return;
        }

        $tw_img = $meta_values['store_seo']['dokan-seo-twitter-image'];

        if ( $tw_img ) {
            echo '<meta name="twitter:image" content="' . wp_get_attachment_url( $tw_img ) . '"/>';
        }
    }

    /**
     * Print SEO meta input form on frontend
     *
     * @return void
     */
    function frontend_meta_form() {
        $current_user   = get_current_user_id();
        $seller_profile = dokan_get_store_info( $current_user );
        $seo_meta       = isset( $seller_profile['store_seo'] ) ? $seller_profile['store_seo'] : array();

        $default_store_seo = array(
            'dokan-seo-meta-title'    => false,
            'dokan-seo-meta-desc'     => false,
            'dokan-seo-meta-keywords' => false,
            'dokan-seo-og-title'      => false,
            'dokan-seo-og-desc'       => false,
            'dokan-seo-og-image'      => false,
            'dokan-seo-twitter-title' => false,
            'dokan-seo-twitter-desc'  => false,
            'dokan-seo-twitter-image' => false,
        );

        $seo_meta = wp_parse_args( $seo_meta, $default_store_seo );
        ?>
        <div class="dokan-alert dokan-hide" id="dokan-seo-feedback"></div>
        <form method="post" id="dokan-store-seo-form"  action="" class="dokan-form-horizontal">

            <div class="dokan-form-group">
                <label class="dokan-w3 dokan-control-label" for="dokan-seo-meta-title"><?php _e( 'SEO Title :', 'dokan' ); ?>
                    <span class="dokan-tooltips-help tips" title="" data-original-title="<?php _e( 'SEO Title is shown as the title of your store page', 'dokan' ); ?>">
                        <i class="fa fa-question-circle"></i>
                    </span>
                </label>
                <div class="dokan-w5 dokan-text-left">
                    <input id="dokan-seo-meta-title" value="<?php echo $this->print_saved_meta( $seo_meta['dokan-seo-meta-title'] ) ?>" name="dokan-seo-meta-title" placeholder=" " class="dokan-form-control input-md" type="text">
                </div>
            </div>

            <div class="dokan-form-group">
                <label class="dokan-w3 dokan-control-label" for="dokan-seo-meta-desc"><?php _e( 'Meta Description :', 'dokan' ); ?>
                    <span class="dokan-tooltips-help tips" title="" data-original-title="<?php _e( 'The meta description is often shown as the black text under the title in a search result. For this to work it has to contain the keyword that was searched for and should be less than 156 chars.', 'dokan' ); ?>">
                        <i class="fa fa-question-circle"></i>
                    </span>
                </label>
                <div class="dokan-w5 dokan-text-left">
                    <textarea class="dokan-form-control" rows="3" id="dokan-seo-meta-desc" name="dokan-seo-meta-desc"><?php echo $this->print_saved_meta( $seo_meta['dokan-seo-meta-desc'] ) ?></textarea>
                </div>
            </div>

            <div class="dokan-form-group">
                <label class="dokan-w3 dokan-control-label" for="dokan-seo-meta-keywords"><?php _e( 'Meta Keywords :', 'dokan' ); ?>
                    <span class="dokan-tooltips-help tips" title="" data-original-title="<?php _e( 'Insert some comma separated keywords for better ranking of your store page.', 'dokan' ); ?>">
                        <i class="fa fa-question-circle"></i>
                    </span>
                </label>
                <div class="dokan-w7 dokan-text-left">
                    <input id="dokan-seo-meta-keywords" value="<?php echo $this->print_saved_meta( $seo_meta['dokan-seo-meta-keywords'] ) ?>" name="dokan-seo-meta-keywords" placeholder=" " class="dokan-form-control input-md" type="text">
                </div>
            </div>

            <?php $this->print_fb_meta_form( $seo_meta ); ?>
            <?php $this->print_twitter_meta_form( $seo_meta ); ?>

            <?php wp_nonce_field( 'dokan_store_seo_form_action', 'dokan_store_seo_form_nonce' ); ?>

            <div class="dokan-form-group" style="margin-left: 23%">
                <input type="submit" id='dokan-store-seo-form-submit' class="dokan-left dokan-btn dokan-btn-theme" value="<?php esc_attr_e( 'Save Changes', 'dokan' ); ?>">
            </div>
        </form>
        <?php
    }

    /**
     * print social meta input fields
     *
     * @param  array  $seo_meta
     *
     * @return void
     */
    function print_fb_meta_form( $seo_meta ) {
        ?>

        <div class="dokan-form-group">
            <label class="dokan-w3 dokan-control-label" for="dokan-seo-og-title"><?php _e( 'Facebook Title :', 'dokan' ); ?></label>
            <div class="dokan-w5 dokan-text-left">
                <input id="dokan-seo-og-title" value="<?php echo $this->print_saved_meta( $seo_meta['dokan-seo-og-title'] ) ?>" name="dokan-seo-og-title" placeholder=" " class="dokan-form-control input-md" type="text">
            </div>
        </div>

        <div class="dokan-form-group">
            <label class="dokan-w3 dokan-control-label" for="dokan-seo-og-desc"><?php _e( 'Facebook Description :', 'dokan' ); ?></label>
            <div class="dokan-w5 dokan-text-left">
                <textarea class="dokan-form-control" rows="3" id="dokan-seo-og-desc" name="dokan-seo-og-desc"><?php echo $this->print_saved_meta( $seo_meta['dokan-seo-og-desc'] ) ?></textarea>
            </div>
        </div>
        <?php
        $og_image     = $seo_meta['dokan-seo-og-image'] ? $seo_meta['dokan-seo-og-image'] : 0;
        $og_image_url = $og_image ? wp_get_attachment_thumb_url( $og_image ) : '';
        ?>
        <div class="dokan-form-group ">
            <label class="dokan-w3 dokan-control-label" for="dokan-seo-og-image"><?php _e( 'Facebook Image :', 'dokan' ); ?></label>
            <div class="dokan-w5 dokan-gravatar dokan-seo-image">
                <div class="dokan-left gravatar-wrap<?php echo $og_image ? '' : ' dokan-hide'; ?>">
                    <input type="hidden" class="dokan-file-field" value="<?php echo $og_image; ?>" name="dokan-seo-og-image">
                    <img class="dokan-gravatar-img" src="<?php echo esc_url( $og_image_url ); ?>">
                    <a class="dokan-close dokan-remove-gravatar-image">&times;</a>
                </div>

                <div class="gravatar-button-area <?php echo $og_image ? ' dokan-hide' : ''; ?>">
                    <a href="#" class="dokan-gravatar-drag dokan-btn dokan-btn-default dokan-left"><i class="fa fa-cloud-upload"></i> <?php _e( 'Upload Photo', 'dokan' ); ?></a>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Print twitter meta form
     *
     * @param  array  $seo_meta
     *
     * @return void
     */
    function print_twitter_meta_form( $seo_meta ) {
        ?>

        <div class="dokan-form-group">
            <label class="dokan-w3 dokan-control-label" for="dokan-seo-twitter-title"><?php _e( 'Twitter Title :', 'dokan' ); ?></label>
            <div class="dokan-w5 dokan-text-left">
                <input id="dokan-seo-twitter-title" value="<?php echo $this->print_saved_meta( $seo_meta['dokan-seo-twitter-title'] ) ?>" name="dokan-seo-twitter-title" placeholder=" " class="dokan-form-control input-md" type="text">
            </div>
        </div>

        <div class="dokan-form-group">
            <label class="dokan-w3 dokan-control-label" for="dokan-seo-twitter-desc"><?php _e( 'Twitter Description :', 'dokan' ); ?></label>
            <div class="dokan-w5 dokan-text-left">
                <textarea class="dokan-form-control" rows="3" id="dokan-seo-twitter-desc" name="dokan-seo-twitter-desc"><?php echo $this->print_saved_meta( $seo_meta['dokan-seo-twitter-desc'] ) ?></textarea>
            </div>
        </div>
        <?php
        $twitter_image     = $seo_meta['dokan-seo-twitter-image'] ? $seo_meta['dokan-seo-twitter-image'] : 0;
        $twitter_image_url = $twitter_image ? wp_get_attachment_thumb_url( $twitter_image ) : '';
        ?>
        <div class="dokan-form-group ">
            <label class="dokan-w3 dokan-control-label" for="dokan-seo-twitter-image"><?php _e( 'Twitter Image :', 'dokan' ); ?></label>
            <div class="dokan-w5 dokan-gravatar dokan-seo-image">
                <div class="dokan-left gravatar-wrap<?php echo $twitter_image ? '' : ' dokan-hide'; ?>">
                    <input type="hidden" class="dokan-file-field" value="<?php echo $twitter_image; ?>" name="dokan-seo-twitter-image">
                    <img class="dokan-gravatar-img" src="<?php echo esc_url( $twitter_image_url ); ?>">
                    <a class="dokan-close dokan-remove-gravatar-image">&times;</a>
                </div>

                <div class="gravatar-button-area <?php echo $twitter_image ? ' dokan-hide' : ''; ?>">
                    <a href="#" class="dokan-gravatar-drag dokan-btn dokan-btn-default dokan-left"><i class="fa fa-cloud-upload"></i> <?php _e( 'Upload Photo', 'dokan' ); ?></a>
                </div>
            </div>
        </div>

        <?php
    }

    /**
     * Check meta data and print
     *
     * @param  boolean|string  $val
     *
     * @return string
     */
    function print_saved_meta( $val ) {
        if ( $val == false )
            return '';
        else
            return esc_attr( $val );
    }

    /**
     * Submit handler for settings form
     *
     * @return void
     */
    function dokan_seo_form_handler() {
        parse_str( $_POST['data'], $postdata );

        if ( !wp_verify_nonce( $postdata['dokan_store_seo_form_nonce'], 'dokan_store_seo_form_action' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'dokan' ) );
        }

        unset( $postdata['dokan_store_seo_form_nonce'] );
        unset( $postdata['_wp_http_referer'] );

        $default_store_seo = array(
            'dokan-seo-meta-title'    => false,
            'dokan-seo-meta-desc'     => false,
            'dokan-seo-meta-keywords' => false,
            'dokan-seo-og-title'      => false,
            'dokan-seo-og-desc'       => false,
            'dokan-seo-og-image'      => false,
            'dokan-seo-twitter-title' => false,
            'dokan-seo-twitter-desc'  => false,
            'dokan-seo-twitter-image' => false,
        );

        $current_user   = get_current_user_id();
        $seller_profile = dokan_get_store_info( $current_user );

        $seller_profile['store_seo'] = wp_parse_args( $postdata, $default_store_seo );

        //unset( $seller_profile['store_seo'] );

        update_user_meta( $current_user, 'dokan_profile_settings', $seller_profile );

        wp_send_json_success( __( 'Your changes has been updated!', 'dokan' ) );
    }

}

$seo = Dokan_Store_Seo::init();
