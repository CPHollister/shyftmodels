<?php

/*
 * The Logger Class
 *
 * This is a helper class to write to the log.
 *
 */

class Zendesk_Wordpress_Logger {
  /*
   * Log information for debugging
   * @param string $msg the message to be logged
   */
  public static function log( $msg = null, $backtrace = false ) {
    if ( defined( 'WP_DEBUG' ) && false === WP_DEBUG ) {
      return;
    }

    $file = dirname( ZENDESK_BASE_FILE ) . '/~zendesk_log.txt';
    $fh   = @fopen( $file, 'a+' );
    if ( false !== $fh ) {
      if ( null === $msg ) {
        fwrite( $fh, date( "\r\nY-m-d H:i:s:\r\n" ) );
      } else {
        fwrite( $fh, date( 'Y-m-d H:i:s - ' ) . $msg . PHP_EOL );
      }

      if ( $backtrace ) {
        $callers = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
        array_shift( $callers );
        $path = dirname( dirname( dirname( plugin_dir_path( __FILE__ ) ) ) ) . DIRECTORY_SEPARATOR;

        $n = 1;
        foreach ( $callers as $caller ) {
          $func = $caller['function'] . '()';
          if ( isset( $caller['class'] ) && ! empty( $caller['class'] ) ) {
            $type = '->';
            if ( isset( $caller['type'] ) && ! empty( $caller['type'] ) ) {
              $type = $caller['type'];
            }
            $func = $caller['class'] . $type . $func;
          }
          $file = isset( $caller['file'] ) ? $caller['file'] : '';
          $file = str_replace( $path, '', $file );
          if ( isset( $caller['line'] ) && ! empty( $caller['line'] ) ) {
            $file .= ':' . $caller['line'];
          }
          $frame = $func . ' - ' . $file;
          fwrite( $fh, '    #' . ( $n ++ ) . ': ' . $frame . PHP_EOL );
        }
      }

      fclose( $fh );
    }
  }
}
