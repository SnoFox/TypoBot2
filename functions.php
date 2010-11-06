<?PHP
/* Functions for Typo 2
 * Read the LICENSE file for license info
 */

function debug( $msg ) {
    print $msg . "\n";
}

function ircWrite( $str ) {
    fwrite( $socket, $str . "\n" );
}
?>
