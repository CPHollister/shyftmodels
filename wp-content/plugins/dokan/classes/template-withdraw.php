<?php

/**
 * Dokan Withdraw class
 *
 * @author weDevs
 */
class Dokan_Template_Withdraw {

    /**
     * Initializes the Dokan_Template_Withdraw class
     *
     * Checks for an existing Dokan_Template_Withdraw instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Dokan_Template_Withdraw();
        }

        return $instance;
    }

    /**
     * Bulk action handler
     *
     * @return void
     */
    function bulk_action_handler() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        if ( ! isset( $_POST['dokan_withdraw_bulk'] ) ) {
            return;
        }

        $bulk_action = $_POST['dokan_withdraw_bulk'];

        if ( ! isset( $_POST['id'] )  ) {
            return;
        }

        //if id empty then empty value return
        if ( ! is_array( $_POST['id'] ) && ! count( $_POST['id'] ) ) {
            return;
        }

        $withdraw_ids = implode( "','", $_POST['id'] );
        $status = $_POST['status_page'];

        switch ( $bulk_action ) {
        case 'paypal':
            $this->generate_csv( $withdraw_ids );
            break;

        case 'delete':

            foreach ( $_POST['id'] as $withdraw_id ) {
                $this->delete_withdraw( $withdraw_id );
            }

            wp_redirect( admin_url( 'admin.php?page=dokan-withdraw&message=trashed&status=' . $status ) );
            die();

            break;

        case 'cancel':

            foreach ( $_POST['id'] as $key => $withdraw_id ) {
                $user_id = $_POST['user_id'][$key];
                $amount  = $_POST['amount'][$key];
                $method  = $_POST['method'][$key];
                $note    = $_POST['note'][$key];

                Dokan_Email::init()->withdraw_request_cancel( $user_id, $amount, $method, $note );
                $this->update_status( $withdraw_id, $user_id, 2 );
            }

            wp_redirect( admin_url( 'admin.php?page=dokan-withdraw&message=cancelled&status=' . $status ) );
            die();

            break;

        case 'approve':

            foreach ( $_POST['id'] as $key => $withdraw_id ) {
                $user_id = $_POST['user_id'][$key];
                $amount  = $_POST['amount'][$key];
                $method  = $_POST['method'][$key];

                Dokan_Email::init()->withdraw_request_approve( $user_id, $amount, $method );
                $this->update_status( $withdraw_id, $user_id, 1 );
            }

            wp_redirect( admin_url( 'admin.php?page=dokan-withdraw&message=approved&status=' . $status ) );

            break;

        case 'pending':

            foreach ( $_POST['id'] as $key => $withdraw_id ) {
                $this->update_status( $withdraw_id, $_POST['user_id'][$key], 0 );
            }

            wp_redirect( admin_url( 'admin.php?page=dokan-withdraw&message=pending&status=' . $status ) );

            break;
        }


    }

    /**
     * Export withdraws as CSV format
     *
     * @param string  $withdraw_ids
     *
     * @return void
     */
    function generate_csv( $withdraw_ids ) {
        global $wpdb;

        $result = $wpdb->get_results(
            "SELECT * FROM {$wpdb->dokan_withdraw}
            WHERE id in('$withdraw_ids')"
        );

        if ( ! $result ) {
            return;
        }

        foreach ( $result as $key => $obj ) {

            if ( $obj->method != 'paypal' ) {
                continue;
            }

            $data[] = array(
                'email'    => dokan_get_seller_withdraw_mail( $obj->user_id ),
                'amount'   => $obj->amount,
                'currency' => get_option( 'woocommerce_currency' )
            );

        }

        if ( $data ) {

            header( 'Content-type: html/csv' );
            header( 'Content-Disposition: attachment; filename="withdraw-'.date( 'd-m-y' ).'.csv"' );

            foreach ( $data as $fields ) {
                echo $fields['email']. ',';
                echo $fields['amount']. ',';
                echo $fields['currency'] . "\n";
            }

            die();
        }
    }

    /**
     * Cancel an withdraw request
     *
     * @return void
     */
    function cancel_pending() {

        if ( isset( $_GET['action'] ) && $_GET['action'] == 'dokan_cancel_withdrow' ) {

            if ( !wp_verify_nonce( $_GET['_wpnonce'], 'dokan_cancel_withdrow' ) ) {
                wp_die( __( 'Are you cheating?', 'dokan' ) );
            }

            global $current_user, $wpdb;

            $row_id = absint( $_GET['id'] );

            $this->update_status( $row_id, $current_user->ID, 2 );

            wp_redirect( add_query_arg( array( 'message' => 'request_cancelled' ), dokan_get_navigation_url( 'withdraw' ) ) );
        }
    }

    /**
     * Validate an withdraw request
     *
     * @return void
     */
    function validate() {

        if ( !isset( $_POST['withdraw_submit'] ) ) {
            return false;
        }

        if ( !wp_verify_nonce( $_POST['dokan_withdraw_nonce'], 'dokan_withdraw' ) ) {
            wp_die( __( 'Are you cheating?', 'dokan' ) );
        }

        $error           = new WP_Error();
        $limit           = $this->get_withdraw_limit();
        $balance         = dokan_get_seller_balance( get_current_user_id(), false );
        $withdraw_amount = (float) $_POST['witdraw_amount'];

        if ( empty( $_POST['witdraw_amount'] ) ) {
            $error->add( 'dokan_empty_withdrad', __( 'Withdraw amount required ', 'dokan' ) );
        } elseif ( $withdraw_amount > $balance ) {

            $error->add( 'enough_balance', __( 'You don\'t have enough balance for this request', 'dokan' ) );
        } elseif ( $withdraw_amount < $limit ) {
            $error->add( 'dokan_withdraw_amount', sprintf( __( 'Withdraw amount must be greater than %d', 'dokan' ), $this->get_withdraw_limit() ) );
        }

        if ( empty( $_POST['withdraw_method'] ) ) {
            $error->add( 'dokan_withdraw_method', __( 'withdraw method required', 'dokan' ) );
        }

        if ( $error->get_error_codes() ) {
            return $error;
        }

        return true;
    }

    function update_status( $row_id, $user_id, $status ) {
        global $wpdb;

        // 0 -> pending
        // 1 -> active
        // 2 -> cancelled

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->dokan_withdraw}
            SET status = %d WHERE user_id=%d AND id = %d",
            $status, $user_id, $row_id
        ) );
    }

    /**
     * Insert an withdraw request
     *
     * @param  array   $data
     *
     * @return bool
     */
    function insert_withdraw( $data = array() ) {
        global $wpdb;

        $wpdb->dokan_withdraw = $wpdb->prefix . 'dokan_withdraw';
        $data = array(
            'user_id' => $data['user_id'],
            'amount'  => $data['amount'],
            'date'    => current_time( 'mysql' ),
            'status'  => $data['status'],
            'method'  => $data['method'],
            'note'    => $data['notes'],
            'ip'      => $data['ip']
        );

        $format = array( '%d', '%f', '%s', '%d', '%s', '%s', '%s' );

        return $wpdb->insert( $wpdb->dokan_withdraw, $data, $format );
    }

    /**
     * Insert withdraw info
     *
     * @return void
     */
    function insert_withdraw_info() {

        global $current_user, $wpdb;

        $amount = floatval( $_POST['witdraw_amount'] );
        $method = $_POST['withdraw_method'];

        $data_info = array(
            'user_id' => $current_user->ID,
            'amount'  => $amount,
            'status'  => 0,
            'method'  => $method,
            'ip'      => dokan_get_client_ip(),
            'notes'   => ''
        );

        $update = $this->insert_withdraw( $data_info );
        Dokan_Email::init()->new_withdraw_request( $current_user, $amount, $method );

        wp_redirect( add_query_arg( array( 'message' => 'request_success' ), dokan_get_navigation_url( 'withdraw' ) ) );
    }

    /**
     * Check if a user has already pending withdraw request
     *
     * @param  int   $user_id
     *
     * @return boolean
     */
    function has_pending_request( $user_id ) {
        global $wpdb;

        $wpdb->dokan_withdraw = $wpdb->prefix . 'dokan_withdraw';

        $status = $wpdb->get_results( $wpdb->prepare(
            "SELECT id
             FROM $wpdb->dokan_withdraw
             WHERE user_id = %d AND status = 0", $user_id
        ) );

        if ( $status ) {
            return true;
        }

        return false;
    }

    /**
     * Get withdraw request of a user
     *
     * @param  int   $user_id
     * @param  int   $status
     * @param  int   $limit
     * @param  int   $offset
     *
     * @return array
     */
    function get_withdraw_requests( $user_id = '', $status = 0, $limit = 10, $offset = 0 ) {
        global $wpdb;

        $where  = empty( $user_id ) ? '' : sprintf( "user_id ='%d' &&", $user_id );

        $sql    = $wpdb->prepare( "SELECT * FROM {$wpdb->dokan_withdraw} WHERE $where status = %d LIMIT %d, %d", $status, $offset, $limit );
        $result = $wpdb->get_results( $sql );

        return $result;
    }

    /**
     * Delete an withdraw request
     *
     * @param  int
     *
     * @return void
     */
    function delete_withdraw( $id ) {
        global $wpdb;

        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->dokan_withdraw} WHERE id = %d", $id ) );
    }

    /**
     * Get status code by status type
     *
     * @param  string
     *
     * @return int
     */
    function get_status_code( $status ) {
        switch ( $status ) {
            case 'pending':
                return 0;
                break;

            case 'completed':
                return 1;
                break;

            case 'cancelled':
                return 2;
                break;
        }
    }

    /**
     * Withdraw listing for admin
     *
     * @param  string  $status
     *
     * @return void
     */
    function admin_withdraw_list( $status ) {
        $pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $limit   = 20;
        $offset  = ( $pagenum - 1 ) * $limit;
        $result  = $this->get_withdraw_requests( '', $this->get_status_code( $status ), $limit, $offset );
        ?>

        <?php if ( isset( $_GET['message'] ) ) {
            $message = '';

            switch ( $_GET['message'] ) {
            case 'trashed':
                $message = __( 'Requests deleted!', 'dokan' );
                break;

            case 'cancelled':
                $message = __( 'Requests cancelled!', 'dokan' );
                break;

            case 'approved':
                $message = __( 'Requests approved!', 'dokan' );
                break;
            }

            if ( ! empty( $message ) ) {
                ?>
                <div class="updated">
                    <p><strong><?php echo $message; ?></strong></p>
                </div>
                <?php
            }
        } ?>
        <form method="post" action="" id="dokan-admin-withdraw-action">
            <?php wp_nonce_field( 'dokan_withdraw_admin_bulk_action', 'dokan_withdraw_admin_bulk_action_nonce' ); ?>
            
            <table class="widefat withdraw-table">
                <thead>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" class="dokan-withdraw-allcheck">
                        </th>
                        <th><?php _e( 'User Name', 'dokan' ); ?></th>
                        <th><?php _e( 'Amount', 'dokan' ); ?></th>
                        <th><?php _e( 'Method', 'dokan' ); ?></th>
                        <th><?php _e( 'Method Details', 'dokan' ); ?></th>
                        <th><?php _e( 'Note', 'dokan' ); ?></th>
                        <th><?php _e( 'IP', 'dokan' ); ?></th>
                        <th><?php _e( 'Date', 'dokan' ); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" class="dokan-withdraw-allcheck">
                        </th>
                        <th><?php _e( 'User Name', 'dokan' ); ?></th>
                        <th><?php _e( 'Amount', 'dokan' ); ?></th>
                        <th><?php _e( 'Method', 'dokan' ); ?></th>
                        <th><?php _e( 'Method Details', 'dokan' ); ?></th>
                        <th><?php _e( 'Note', 'dokan' ); ?></th>
                        <th><?php _e( 'IP', 'dokan' ); ?></th>
                        <th><?php _e( 'Date', 'dokan' ); ?></th>
                    </tr>
                </tfoot>

            <?php
        if ( $result ) {
            $count = 0;
            foreach ( $result as $key => $row ) {
                $user_data  = get_userdata( $row->user_id );
                $store_info = dokan_get_store_info( $row->user_id );
                    ?>
                    <tr class="<?php echo ( $count % 2 ) == 0 ? 'alternate': 'odd'; ?>">

                        <th class="check-column">
                            <input type="checkbox" name="id[<?php echo $row->id;?>]" value="<?php echo $row->id;?>">
                            <input type="hidden" name="user_id[<?php echo $row->id;?>]" value="<?php echo $row->user_id; ?>">
                            <input type="hidden" name="method[<?php echo $row->id;?>]" value="<?php echo esc_attr( $row->method ); ?>">
                            <input type="hidden" name="amount[<?php echo $row->id;?>]" value="<?php echo esc_attr( $row->amount ); ?>">
                        </th>
                        <td>
                            <strong><a href="<?php echo admin_url( 'user-edit.php?user_id=' . $user_data->ID ); ?>"><?php echo $user_data->user_login; ?></a></strong>
                            <div class="row-actions">
                                <?php if ( $status == 'pending' ) { ?>

                                    <span class="edit"><a href="#" class="dokan-withdraw-action" data-status="approve" data-withdraw_id = "<?php echo $row->id; ?>"><?php _e( 'Approve', 'dokan' ); ?></a> | </span>
                                    <span class="edit"><a href="#" class="dokan-withdraw-action" data-status="cancel" data-withdraw_id = "<?php echo $row->id; ?>"><?php _e( 'Cancel', 'dokan' ); ?></a></span>
                                
                                <?php } elseif ( $status == 'completed' ) { ?>
                                    
                                    <span class="edit"><a href="#" class="dokan-withdraw-action" data-status="cancel" data-withdraw_id = "<?php echo $row->id; ?>"><?php _e( 'Cancel', 'dokan' ); ?></a> | </span>
                                    <span class="edit"><a href="#" class="dokan-withdraw-action" data-status="pending" data-withdraw_id = "<?php echo $row->id; ?>"><?php _e( 'Pending', 'dokan' ); ?></a></span>

                                <?php } elseif ( $status == 'cancelled' ) { ?>
                                    
                                    <span class="edit"><a href="#" class="dokan-withdraw-action" data-status="approve" data-withdraw_id = "<?php echo $row->id; ?>"><?php _e( 'Approve', 'dokan' ); ?></a> | </span>
                                    <span class="edit"><a href="#" class="dokan-withdraw-action" data-status="pending" data-withdraw_id = "<?php echo $row->id; ?>"><?php _e( 'Pending', 'dokan' ); ?></a></span>

                                <?php } ?>

                                <?php if ( $result ) { ?>
                                    <span class="trash"> | <a href="#" class="dokan-withdraw-action" data-status="delete" data-withdraw_id = "<?php echo $row->id; ?>"><?php _e( 'Delete', 'dokan' ); ?></a></span>

                                <?php } ?>
                            </div>
                        </td>
                        <td><?php echo wc_price( $row->amount ); ?></td>
                        <td><?php echo dokan_withdraw_get_method_title( $row->method ); ?></td>
                        <td>
                            <?php
                            if ( $row->method != 'bank' ) {
                                if ( isset( $store_info['payment'][$row->method] ) ) {
                                    echo $store_info['payment'][$row->method]['email'];
                                }
                            } elseif ( $row->method == 'bank' ) {
                                echo dokan_get_seller_bank_details( $row->user_id );
                            }
                            ?>
                        </td>
                        <td>
                            <div class="dokan-add-note">
                                <div class="note-display">
                                    <p class="ajax_note"><?php echo $row->note; ?></p>

                                    <div class="row-actions">
                                        <a href="#" class="dokan-note-field"><?php _e( 'Add note', 'dokan' ); ?></a>
                                    </div>
                                </div>

                                <div class="note-form" style="display: none;">
                                    <p><input type="text" class="dokan-note-text" name="note[<?php echo $row->id;?>]" value="<?php echo esc_attr( $row->note ); ?>"></p>
                                    <a class="dokan-note-submit button" data-id=<?php echo $row->id; ?> href="#" ><?php _e( 'Save', 'dokan' ); ?></a>
                                    <a href="#" class="dokan-note-cancel"><?php _e( 'cancel', 'dokan' ); ?></a>
                                </div>
                            </div>

                        </td>
                        <td><?php echo $row->ip; ?></td>
                        <td><?php echo date_i18n( 'M j, Y g:ia', strtotime( $row->date ) ); ?></td>
                    </tr>
                    <?php
                $count++;
            }

        } else {
            ?>
                <tr>
                    <td colspan="8">
                        <?php _e( 'No result found', 'dokan' ) ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </table>

            <div class="tablenav bottom">

                <div class="alignleft actions bulkactions">
                    <select name="dokan_withdraw_bulk">
                        <option value="-1" selected="selected"><?php _e( 'Bulk Actions', 'dokan' ); ?></option>

                        <?php if ( $status == 'pending' ) { ?>

                            <option value="approve"><?php _e( 'Approve Requests', 'dokan' ); ?></option>
                            <option value="cancel"><?php _e( 'Mark as Cancelled', 'dokan' ); ?></option>

                        <?php } elseif ( $status == 'completed' ) { ?>

                            <option value="cancel"><?php _e( 'Mark as Cancelled', 'dokan' ); ?></option>
                            <option value="pending"><?php _e( 'Mark Pending', 'dokan' ); ?></option>

                        <?php } elseif ( $status == 'cancelled' ) { ?>

                            <option value="approve"><?php _e( 'Approve Requests', 'dokan' ); ?></option>
                            <option value="pending"><?php _e( 'Mark Pending', 'dokan' ); ?></option>

                        <?php } ?>

                        <?php if ( $result ) { ?>
                            <option value="delete"><?php _e( 'Delete', 'dokan' ); ?></option>
                            <option value="paypal"><?php _e( 'Download PayPal mass payment file', 'dokan' ); ?></option>
                        <?php } ?>
                    </select>

                    <input type="hidden" name="status_page" value="<?php echo $status; ?>">
                    <input type="submit" name="" id="doaction2" class="button button-primary" value="<?php esc_attr_e( 'Apply', 'dokan' ); ?>">
                </div>

                <?php if ( $result ) {
                    $counts = dokan_get_withdraw_count();
                    $num_of_pages = ceil( $counts[$status] / $limit );
                    $page_links = paginate_links( array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '',
                        'prev_text' => __( '&laquo;', 'aag' ),
                        'next_text' => __( '&raquo;', 'aag' ),
                        'total'     => $num_of_pages,
                        'current'   => $pagenum
                    ) );

                    if ( $page_links ) {
                        echo '<div class="tablenav-pages">' . $page_links . '</div>';
                    }
                } ?>
            </div>

        </form>
        <?php $ajax_url = admin_url('admin-ajax.php'); ?>
        <style type="text/css">
            .withdraw-table {
                margin-top: 10px;
            }

            .withdraw-table td, .withdraw-table th {
                vertical-align: top;
            }

            .custom-spinner {
                background: url('images/spinner-2x.gif') no-repeat;
                background-position: 43% 9px;
                background-size: 20px 20px;
                opacity: .4;
                filter: alpha(opacity=40);
                width: 20px;
                height: 20px;
            }
        </style>
        <script>
            (function($){
                $(document).ready(function(){
                    var url = "<?php echo $ajax_url; ?>";

                    $('#dokan-admin-withdraw-action').on('click', 'a.dokan-withdraw-action', function(e) {
                        e.preventDefault();
                        var self = $(this);

                        self.closest( 'tr' ).addClass('custom-spinner');
                        data = {
                            action: 'dokan_withdraw_form_action',
                            formData : $('#dokan-admin-withdraw-action').serialize(),
                            status: self.data('status') ,
                            withdraw_id : self.data( 'withdraw_id' )   
                        }

                        $.post(url, data, function( resp ) {

                            if( resp.success ) {
                                self.closest( 'tr' ).removeClass('custom-spinner');
                                window.location = resp.data.url;
                            } else {
                                self.closest( 'tr' ).removeClass('custom-spinner');    
                                alert( 'Somthig wrong...!!!' );
                            }
                        });

                    }); 
                });
            })(jQuery)
        </script>
        <?php

        $this->add_note_script();
    }

    /**
     * JS codes for adding note on a withdraw requst
     *
     * @return void
     */
    function add_note_script() {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                var dokan_admin = {
                    init: function() {
                        $('div.dokan-add-note').on('click', 'a.dokan-note-field', this.addnote);
                        $('div.dokan-add-note').on('click', 'a.dokan-note-cancel', this.cancel);
                        $('div.dokan-add-note').on('click', 'a.dokan-note-submit', this.updateNote);
                    },

                    updateNote: function(e) {
                        e.preventDefault();

                        var self = $(this),
                            form_wrap = self.closest('.note-form'),
                            row_id = self.data('id'),
                            note = form_wrap.find('input.dokan-note-text').val(),
                            data = {
                                'action': 'note',
                                'row_id': row_id,
                                'note': note,
                            };

                        $.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(resp) {
                            if ( resp.success ) {
                                form_wrap.hide();

                                var display = form_wrap.siblings('.note-display');
                                display.find('p.ajax_note').text(resp.data['note']);
                                display.show();
                            }
                        });
                    },

                    cancel: function(e) {
                        e.preventDefault();

                        var display = $(this).closest('.note-form');
                        display.slideUp();
                        display.siblings('.note-display').slideDown();
                    },

                    addnote: function(e) {
                        e.preventDefault();

                        var display = $(this).closest('.note-display');
                        display.slideUp();
                        display.siblings('.note-form').slideDown();
                    }
                };

                dokan_admin.init();
            });
        </script>

        <?php
    }

    /**
     * Update a note
     *
     * @return void
     */
    function note_update() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_withdraw';
        $update     = $wpdb->update( $table_name, array( 'note' => sanitize_text_field( $_POST['note'] ) ), array( 'id' => $_POST['row_id'] ) );

        if ( $update ) {
            $html = array(
                'note' => wp_kses_post( $_POST['note'] ),
            );

            wp_send_json_success( $html );

        } else {
            wp_send_json_error();
        }
    }

    /**
     * Check if a user has sufficient withdraw balance
     *
     * @param  int   $user_id
     *
     * @return boolean
     */
    function has_withdraw_balance( $user_id ) {

        $balance        = $this->get_user_balance( $user_id );
        $withdraw_limit = $this->get_withdraw_limit();

        if ( $balance < $withdraw_limit ) {
            return false;
        }

        return true;
    }

    /**
     * Get the system withdraw limit
     *
     * @return int
     */
    function get_withdraw_limit() {
        return (int) dokan_get_option( 'withdraw_limit', 'dokan_selling', '50' );
    }

    /**
     * Get a sellers balance
     *
     * @param  int  $user_id
     *
     * @return int
     */
    function get_user_balance( $user_id ) {
        return dokan_get_seller_balance( $user_id, false );
    }

    /**
     * Print status messages
     *
     * @param  string  $status
     *
     * @return void
     */
    function request_status( $status ) {
        switch ( $status ) {
            case 0:
                return '<span class="label label-danger">' . __( 'Pending Reivew', 'dokan' ) . '</span>';
                break;

            case 1:
                return '<span class="label label-warning">' . __( 'Accepted', 'dokan' ) . '</span>';
                break;
        }
    }

    /**
     * List withdraw request for a user
     *
     * @param  int  $user_id
     *
     * @return void
     */
    function withdraw_requests( $user_id ) {
        $withdraw_requests = $this->get_withdraw_requests( $user_id );

        if ( $withdraw_requests ) {
            ?>
            <table class="table table-striped">
                <tr>
                    <th><?php _e( 'Amount', 'dokan' ); ?></th>
                    <th><?php _e( 'Method', 'dokan' ); ?></th>
                    <th><?php _e( 'Date', 'dokan' ); ?></th>
                    <th><?php _e( 'Cancel', 'dokan' ); ?></th>
                    <th><?php _e( 'Status', 'dokan' ); ?></th>
                </tr>

                <?php foreach ( $withdraw_requests as $request ) { ?>

                    <tr>
                        <td><?php echo wc_price( $request->amount ); ?></td>
                        <td><?php echo dokan_withdraw_get_method_title( $request->method ); ?></td>
                        <td><?php echo dokan_format_time( $request->date ); ?></td>
                        <td>
                            <?php
                            $url = add_query_arg( array(
                                'action' => 'dokan_cancel_withdrow',
                                'id'     => $request->id
                            ), dokan_get_navigation_url( 'withdraw' ) );
                            ?>
                            <a href="<?php echo wp_nonce_url( $url, 'dokan_cancel_withdrow' ); ?>">
                                <?php _e( 'Cancel', 'dokan' ); ?>
                            </a>
                        </td>
                        <td><?php echo $this->request_status( $request->status ); ?></td>
                    </tr>

                <?php } ?>

            </table>
            <?php
        }
    }

    /**
     * Get payment methods
     *
     * @return array
     */
    function get_payment_methods() {
        $method = array(
            ''       => __( '- Select Method -', 'dokan' ),
            'paypal' => __( 'Paypal', 'dokan' ),
            'bank'   => __( 'Bank Transfer', 'dokan' ),
        );

        $payment_methods = apply_filters( 'payment_withdraw_option', $method );

        return $payment_methods;
    }

    /**
     * Show alert messages
     *
     * @return void
     */
    function show_alert_messages() {
        $type    = isset( $_GET['message'] ) ? $_GET['message'] : '';
        $message = '';

        switch ( $type ) {
            case 'request_cancelled':
                $message = __( 'Your request has been cancelled successfully!', 'dokan' );
                break;

            case 'request_success':
                $message = __( 'Your request has been received successfully and is under review!', 'dokan' );
                break;

            case 'request_error':
                $message = __( 'Unknown error!', 'dokan' );
                break;
        }

        if ( ! empty( $message ) ) {
            ?>
            <div class="dokan-alert dokan-alert-danger">
                <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
                <strong><?php echo $message; ?></strong>
            </div>
            <?php
        }
    }

    /**
     * Generate withdraw request form
     *
     * @param  string  $validate
     *
     * @return void
     */
    function withdraw_form( $validate = '' ) {
        global $current_user;

        // show alert messages
        $this->show_alert_messages();

        $balance = $this->get_user_balance( $current_user->ID );
        if ( $balance < 0 ) {
            printf( '<div class="dokan-alert dokan-alert-danger">%s</div>', sprintf( __( 'You already withdrawed %s. This amount will deducted from your next balance.', 'dokan' ), wc_price( $balance ) ) );
        }

        if ( $this->has_pending_request( $current_user->ID ) ) {
            ?>
            <div class="dokan-alert dokan-alert-warning">
                <p><strong><?php _e( 'You\'ve already pending withdraw request(s).', 'dokan' ); ?></strong></p>
                <p><?php _e( 'Until it\'s been cancelled or approved, you can\'t submit any new request.', 'dokan' ) ?></p>
            </div>

            <?php
            $this->withdraw_requests( $current_user->ID );
            return;

        } else if ( !$this->has_withdraw_balance( $current_user->ID ) ) {

            printf( '<div class="dokan-alert dokan-alert-danger">%s</div>', __( 'You don\'t have sufficient balance for a withdraw request!', 'dokan' ) );

            return;
        }

        $payment_methods = dokan_withdraw_get_active_methods();

        if ( is_wp_error( $validate ) ) {
            $amount          = $_POST['witdraw_amount'];
            $withdraw_method = $_POST['withdraw_method'];
        } else {
            $amount          = '';
            $withdraw_method = '';
        }
        ?>
        <div class="dokan-alert dokan-alert-danger" style="display: none;">
            <button type="button" class="dokan-close" data-dismiss="alert">&times;</button>
            <strong class="jquery_error_place"></strong>
        </div>

        <span class="ajax_table_shown"></span>
        <form class="dokan-form-horizontal withdraw" role="form" method="post">

            <div class="dokan-form-group">
                <label for="withdraw-amount" class="dokan-w3 dokan-control-label">
                    <?php _e( 'Withdraw Amount', 'dokan' ); ?>
                </label>

                <div class="dokan-w5 dokan-text-left">
                    <div class="dokan-input-group">
                        <span class="dokan-input-group-addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                        <input name="witdraw_amount" required number min="<?php echo esc_attr( dokan_get_option( 'withdraw_limit', 'dokan_selling', 50 ) ); ?>" class="dokan-form-control" id="withdraw-amount" name="price" type="number" placeholder="0.00" value="<?php echo $amount; ?>"  >
                    </div>
                </div>
            </div>

            <div class="dokan-form-group">
                <label for="withdraw-method" class="dokan-w3 dokan-control-label">
                    <?php _e( 'Payment Method', 'dokan' ); ?>
                </label>

                <div class="dokan-w5 dokan-text-left">
                    <select class="dokan-form-control" required name="withdraw_method" id="withdraw-method">
                        <?php foreach ( $payment_methods as $method_name ) { ?>
                            <option <?php selected( $withdraw_method, $method_name );  ?>value="<?php echo esc_attr( $method_name ); ?>"><?php echo dokan_withdraw_get_method_title( $method_name ); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="dokan-form-group">
                <div class="dokan-w3 ajax_prev" style="margin-left:23%; width: 200px;">
                    <?php wp_nonce_field( 'dokan_withdraw', 'dokan_withdraw_nonce' ); ?>
                    <input type="submit" class="dokan-btn dokan-btn-theme" value="<?php esc_attr_e( 'Submit Request', 'dokan' ); ?>" name="withdraw_submit">
                </div>
            </div>
        </form>
        <?php
    }

    /**
     * Print the approved user withdraw requests
     *
     * @param  int  $user_id
     *
     * @return void
     */
    function user_approved_withdraws( $user_id ) {
        $requests = $this->get_withdraw_requests( $user_id, 1, 100 );

        if ( $requests ) {
            ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Amount', 'dokan' ); ?></th>
                        <th><?php _e( 'Method', 'dokan' ); ?></th>
                        <th><?php _e( 'Date', 'dokan' ); ?></th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ( $requests as $row ) { ?>
                    <tr>
                        <td><?php echo wc_price( $row->amount ); ?></td>
                        <td><?php echo dokan_withdraw_get_method_title( $row->method ); ?></td>
                        <td><?php echo date_i18n( 'M j, Y g:ia', strtotime( $row->date ) ); ?></td>
                    </tr>
                <?php } ?>

                </tbody>
            </table>

        <?php } else { ?>
            <div class="dokan-alert dokan-alert-warning">
                <strong><?php _e( 'Err!', 'dokan' ); ?></strong> <?php _e( 'Sorry, no transactions found!', 'dokan' ); ?>
            </div>
            <?php
        }
    }

}
