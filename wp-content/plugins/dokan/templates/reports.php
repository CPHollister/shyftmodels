<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array( 'active_menu' => 'report' ) ); ?>

    <div class="dokan-dashboard-content dokan-reports-content">

        <article class="dokan-reports-area">
            <header class="dokan-dashboard-header">
                <h1 class="entry-title"><?php _e( 'Reports', 'dokan' ) ?></h1>
            </header><!-- .dokan-dashboard-header -->

            <div class="dokan-report-wrap">
                <?php
                global $woocommerce;

                require_once dirname( dirname(__FILE__) ) . '/includes/reports.php';

                $charts = dokan_get_reports_charts();

                $link = dokan_get_navigation_url( 'reports' );
                $current = isset( $_GET['chart'] ) ? $_GET['chart'] : 'overview';
                echo '<ul class="dokan_tabs">';
                foreach ($charts['charts'] as $key => $value) {
                    $class = ( $current == $key ) ? ' class="active"' : '';
                    printf( '<li%s><a href="%s">%s</a></li>', $class, add_query_arg( array( 'chart' => $key ), $link ), $value['title'] );
                }
                echo '</ul>';
                ?>

                <?php if ( isset( $charts['charts'][$current] ) ) { ?>
                    <div id="dokan_tabs_container">
                        <div class="tab-pane active" id="home">
                            <?php
                            $func = $charts['charts'][$current]['function'];
                            if ( $func && ( is_callable( $func ) ) ) {
                                call_user_func( $func );
                            }
                            ?>
                        </div>
                    </div>
                <?php } ?>
        </article>

    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->