<?php
$user_id        = get_current_user_id();
$dokan_withdraw = Dokan_Template_Withdraw::init();
?>
<div class="dokan-dashboard-wrap">
    <?php dokan_get_template( 'dashboard-nav.php', array('active_menu' => 'withdraw') ); ?>

    <div class="dokan-dashboard-content dokan-withdraw-content">

        <article class="dokan-withdraw-area">
            <header class="entry-header">
                <h1 class="entry-title"><?php _e( 'Withdraw', 'dokan' ); ?></h1>
            </header><!-- .entry-header -->

            <div class="entry-content">

                <?php if ( is_wp_error(Dokan_Template_Shortcodes::$validate) ) {
                    $messages = Dokan_Template_Shortcodes::$validate->get_error_messages();

                    foreach( $messages as $message ) {
                        ?>
                        <div class="dokan-alert dokan-alert-danger" style="width: 55%; margin-left: 10%;">
                            <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
                            <strong><?php echo $message; ?></strong>
                        </div>

                        <?php
                    }
                } ?>
            </div><!-- .entry-content -->

            <?php $current = isset( $_GET['type'] ) ? $_GET['type'] : 'pending'; ?>
            <ul class="list-inline subsubsub">
                <li<?php echo $current == 'pending' ? ' class="active"' : ''; ?>>
                    <a href="<?php echo dokan_get_navigation_url( 'withdraw' ); ?>"><?php _e( 'Withdraw Request', 'dokan' ); ?></a>
                </li>
                <li<?php echo $current == 'approved' ? ' class="active"' : ''; ?>>
                    <a href="<?php echo add_query_arg( array( 'type' => 'approved' ), dokan_get_navigation_url( 'withdraw' ) ); ?>"><?php _e( 'Approved Requests', 'dokan' ); ?></a>
                </li>
            </ul>

            <div class="dokan-alert dokan-alert-warning">
                <strong><?php printf( __( 'Current Balance: %s', 'dokan' ), dokan_get_seller_balance( $user_id ) ); ?></strong>
            </div>

            <?php if ( $current == 'pending' ) {
                $dokan_withdraw->withdraw_form( Dokan_Template_Shortcodes::$validate );
            } elseif ( $current == 'approved' ) {
                $dokan_withdraw->user_approved_withdraws( $user_id );
            } ?>

        </article>

    </div><!-- .dokan-dashboard-content -->
</div><!-- .dokan-dashboard-wrap -->