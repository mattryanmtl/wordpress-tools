<?php

function wp_write_log() {
    if ( true === WP_DEBUG ) {
        $args = func_get_args();
        foreach ( $args as $index => $log ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}
?>
