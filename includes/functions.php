<?PHP
/* Functions for TypoBot 2
 * Read the LICENSE file for license info
 */

function debug( $msg ) {
    $debug = 1;
    if( $debug == 1 )
        print $msg . "\n";
    return $debug;
}

function ircWrite( $str ) {
    global $socket;
    fwrite( $socket, $str . "\n" );
    debug( 'IRC:O: ' . $str );
    return;
}

function logit( $str ) {
    print "$str.\n";
}
?>
