<?PHP
/* Functions for TypoBot 2
 * Read the LICENSE file for license info
 */

function debug( $msg ) {
    $debug = 1;
    if( $debug == 1 )
        print 'Debug: ' . $msg . "\n";
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
function ircSplit( $message ) {
    $return = Array();
    $i = 0;
    $quotes = false;

    if( $message[ $i ] == ':' ) {
        $return[ 'type' ] = 'relayed';
        $i++;
    } else
        $return[ 'type' ] = 'direct';

    $return[ 'rawpieces' ] = Array();
    $temp = '';
    for( ; $i < strlen( $message ) ; $i++ ) {
        if( $quotes and $message[ $i ] != '"' )
            $temp .= $message[ $i ];
        else 
            switch( $message[ $i ] ) {
            case ' ':
                $return[ 'rawpieces' ][] = $temp;
                $temp = '';
                break;
            case '"':
                if( $quotes or $temp == '' ) {
                    $quotes = !$quotes;
                    break;
                }
            case ':':
                if( $temp == '' ) {
                    $i++;
                    $return[ 'rawpieces' ][] = substr( $message, $i );
                    $i = strlen( $message );
                    break;
                }
            default:
                $temp .= $message[ $i ];
            }
    }
    if( $temp != '' )
        $return[ 'rawpieces' ][] = $temp;

    if( $return[ 'type' ] == 'relayed' ) {
        $return[ 'source' ] = $return[ 'rawpieces' ][ 0 ];
        $return[ 'command' ] = strtolower( $return[ 'rawpieces' ][ 1 ] );
        $return[ 'target' ] = $return[ 'rawpieces' ][ 2 ];
        $return[ 'pieces' ] = array_slice( $return[ 'rawpieces' ], 3 );
    } else {
        $return[ 'source' ] = 'Server';
        $return[ 'command' ] = strtolower( $return[ 'rawpieces' ][ 0 ] );
        $return[ 'target' ] = 'You';
        $return[ 'pieces' ] = array_slice( $return[ 'rawpieces' ], 1 );
    }
    $return[ 'raw' ] = $message;
    return $return;
}
?>
