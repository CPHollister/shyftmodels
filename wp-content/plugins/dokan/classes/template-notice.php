<?php
/**
 * Dokan Notice Class
 *
 * @since  2.1
 *
 * @author weDevs
 */
class Dokan_Template_Notice {

    private $perpage = 10;
    private $total_query_result;

    /**
     * Initializes the Dokan_Template_Notice() class
     *
     * Checks for an existing Dokan_Template_Notice() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( !$instance ) {
            $instance = new Dokan_Template_Notice();
        }

        return $instance;
    }

    /**
     * Get Announcement via teble and announcement post type
     *
     * @since  2.1
     *
     * @param  integer $per_page
     *
     * @return object
     */
    function get_announcement_by_users( $per_page = NULL ) {

        $pagenum      = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;

        $args = array(
            'post_type' => 'dokan_announcement',
            'post_status' => 'publish',
            'posts_per_page' => ($per_page) ? $per_page : $this->perpage,
            'orderby'        => 'post_date',
            'order'          => 'DESC',
            'paged'          => $pagenum
        );

        $this->add_query_filter();

        $query = new WP_Query( $args );

        $this->remove_query_filter();

        return $query;
    }

    /**
     * Show announcement template
     *
     * @since  2.1
     *
     * @return void
     */
    function show_announcement_template() {

        $query = $this->get_announcement_by_users();

        // var_dump($query);
        ?>
        <div class="dokan-announcement-wrapper">
            <?php
            if( $query->posts ) {
                $i = 0;
                foreach ( $query->posts as $notice ) {
                    $notice_url =  trailingslashit( dokan_get_navigation_url( 'single-announcement' ).''.$notice->ID );
                    ?>
                    <div class="dokan-announcement-wrapper-item <?php echo ( $notice->status == 'unread' ) ? 'dokan-announcement-uread' : '' ?>">
                        <div class="announcement-action">
                            <a href="#" class="remove_announcement" data-notice_row = <?php echo $notice->id; ?>><i class="fa fa-times"></i></a>
                        </div>
                        <div class="dokan-annnouncement-date dokan-left">
                            <div class="announcement-day"><?php echo date('d', strtotime( $notice->post_date ) ) ?></div>
                            <div class="announcement-month"><?php echo strtoupper( date('l', strtotime( $notice->post_date ) ) ); ?></div>
                            <div class="announcement-year"><?php echo date('Y', strtotime( $notice->post_date ) ) ?></div>
                        </div>
                        <div class="dokan-announcement-content-wrap dokan-left">
                            <div class="dokan-announcement-heading">
                                <a href="<?php echo $notice_url; ?>">
                                    <h3><?php echo $notice->post_title; ?></h3>
                                </a>
                            </div>

                            <div class="dokan-announcement-content">
                                <?php echo wp_trim_words( $notice->post_content, '15', sprintf('<p><a href="%s">%s</a></p>', $notice_url , __( ' See More', 'dokan' ) ) );  ?>
                            </div>
                        </div>
                        <div class="dokan-clearfix"></div>
                    </div>
                    <?php
                    $i++;
                }
            } else {
                ?>
                <div class="dokan-no-announcement">
                    <div class="annoument-no-wrapper">
                        <i class="fa fa-bell dokan-announcement-icon"></i>
                        <p><?php _e( 'No announcement found', 'dokan' ) ?></p>
                    </div>
                </div>

                <?php
            }
            ?>
        </div>
        <?php

        wp_reset_postdata();

        $this->get_pagination( $query );

    }

    /**
     *  Add Query filter for select, join and where
     *  with dokan_announcement post type
     *
     *  @since  2.1
     */
    function add_query_filter() {
        add_filter( 'posts_fields', array( $this, 'select_dokan_announcement_table' ), 10, 2 );
        add_filter( 'posts_join', array( $this, 'join_dokan_announcement_table' ) );
        add_filter( 'posts_where', array( $this, 'where_dokan_announcement_table' ), 10, 2 );
    }

    /**
     * Remove query filters
     *
     * @since  2.1
     *
     * @return void
     */
    function remove_query_filter() {
        remove_filter( 'posts_fields', array( $this, 'select_dokan_announcement_table' ), 10, 2 );
        remove_filter( 'posts_join', array( $this, 'join_dokan_announcement_table' ) );
        remove_filter( 'posts_where', array( $this, 'where_dokan_announcement_table' ), 10, 2 );
    }

    /**
     * Render pagination for seller announcement
     *
     * @since  2.1
     *
     * @param  object $query
     *
     * @return void
     */
    function get_pagination( $query ) {
        $pagenum  = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
        $base_url = dokan_get_navigation_url('notice');

        if ( $query->max_num_pages > 1 ) {
            echo '<div class="pagination-wrap">';
            $page_links = paginate_links( array(
                'current'   => $pagenum,
                'total'     => $query->max_num_pages,
                'base'      => $base_url. '%_%',
                'format'    => '?pagenum=%#%',
                'add_args'  => false,
                'type'      => 'array',
                'prev_text' => __( '&laquo; Previous', 'dokan' ),
                'next_text' => __( 'Next &raquo;', 'dokan' )
            ) );

            echo '<ul class="pagination"><li>';
            echo join("</li>\n\t<li>", $page_links);
            echo "</li>\n</ul>\n";
            echo '</div>';
        }
    }

    /**
     * Get single announcement
     *
     * @since  2.1
     *
     * @param  integer $notice_id
     *
     * @return object
     */
    function get_single_announcement( $notice_id ) {
        $args = array(
            'p' => $notice_id,
            'post_type' => 'dokan_announcement'
        );

        $this->add_query_filter();

        $query = new WP_Query( $args );
        $notice = (array)$query->posts;

        $this->remove_query_filter();

        return $notice;
    }

    /**
     * Update notice status in dokan_announcement table
     *
     * @since  2.1
     *
     * @param  integer $notice_id
     * @param  string $status
     *
     * @return void
     */
    function update_notice_status( $notice_id, $status ) {
        global $wpdb;
        $table_name = $wpdb->prefix.'dokan_announcement';

        $wpdb->update(
            $table_name,
            array(
                'status' => $status,
            ),
            array( 'post_id' => $notice_id, 'user_id' => get_current_user_id() )
        );
    }

    /**
     * Select query filter
     *
     * @since  2.1
     *
     * @param  string $fields
     * @param  object $query
     *
     * @return string
     */
    function select_dokan_announcement_table( $fields, $query ) {
        global $wpdb;

        $table_name = $wpdb->prefix.'dokan_announcement';
        $fields .= " ,da.id, da.status";

        return $fields;
    }

    /**
     * Join query filter
     *
     * @since  2.1
     *
     * @param  string $join
     *
     * @return string
     */
    function join_dokan_announcement_table( $join ) {
        global $wpdb;

        $table_name = $wpdb->prefix .'dokan_announcement';
        $join .= " LEFT JOIN $table_name AS da ON $wpdb->posts.ID = da.post_id";

        return $join;
    }

    /**
     * Where query filter
     *
     * @since  2.1
     *
     * @param  integer $where
     * @param  object $query
     *
     * @return string
     */
    function where_dokan_announcement_table( $where, $query ) {
        global $wpdb;

        $table_name = $wpdb->prefix .'dokan_announcement';
        $current_user_id = get_current_user_id();

        $where .= " AND da.user_id = $current_user_id AND ( da.status = 'read' OR da.status = 'unread' )";

        return $where;
    }
}