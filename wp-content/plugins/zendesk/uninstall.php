<?php
//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
  exit();
}

$zendeskSettings   = 'zendesk-settings';
$zendeskRemoteAuth = 'zendesk-settings-remote-auth';
delete_option( $zendeskSettings );
delete_option( $zendeskRemoteAuth );
?>
