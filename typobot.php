<?PHP
/* Includes */

include 'includes/functions.php';

include 'config/config.php';
include 'config/words.php';

include 'src/core.php';
include 'src/typo.php';


$userData = unserialize( file_get_contents( 'data/tb_db.dat' ) );

$pspell = pspell_new( $config['lang'], '', '', '', PSPELL_BAD_SPELLERS );

$socket = stream_socket_client( 'tcp://' . $config['server'] . ':' . $config['port'] , $errno , $errstr , 30 );

if ( !$socket ) {
    logit( "IRC error: $errstr ($errno)" );
} else {
    ircWrite( 'USER ' . $config['user'] . ' "1" "1" :' . $config['gecos'] );
    ircWrite( 'NICK '. $config['nick'] );

    while ( !feof( $socket ) ) {
        $ircRawData = str_replace( array("\n","\r"), '', fgets($socket,512) );
        $ircData = explode(' ',$ircRawData);
        debug( 'IRC:I: ' . implode(' ', $ircData) );

        /* Parse the IRC string
         * Put it into a nice format for the rest of the program to use
         */

        if( $ircData[0][0] == ':' ) {
            // If the line starts with a colon, it has a source
            $src = substr( $ircData[0], 1 );
            $cmd = $ircData[1];
            $rawParams = array_slice( $ircData, 2 );
        } else {
            // No source. Probably a PING...
            $src = NULL;
            $cmd = $ircData[0];
            $rawParams = array_slice( $ircData, 1 );
        }
        //        $params = explode( ':', implode( ' ', $params ), 2 );
        //  Use below hack due to above hack getting blank params and extra spaces
        $params = array();
        foreach( $rawParams as $param ) {
            if( $param[0] != ':' ) {
                $params[] = $param;
            } else {
                $magic = explode(':', implode(' ',$rawParams), 2);
                $params[] = $magic[1];
                break;
            }
        }
        
        unset( $ircData, $ircRawData ); // Shouldn't be using these anymore

        /* Done parsing the string into something more usable
         * At this point, our setup is as follows:
         *
         * array $params    - all params to the command.
         * string $src      - the source string
         * string $cmd      - the command
         *
         * Examples:
         * :SnoFox!~SnoFox@SnoFox.net KICK #clueirc MJ94 :Quit being a lamer.
         *      $src = SnoFox!~SnoFox@SnoFox.net
         *      $cmd = KICK
         *      $params[0] = #clueirc
         *      $params[1] = MJ94
         *      $params[2] = Quit being a lamer.
         *
         * PING :03FE8J5
         *      $src = NULL
         *      $cmd = PING
         *      $params[0] = 03FE8J5
         */
        debug('     $src = ' . $src);
        debug('     $cmd = ' . $cmd);
        debug('  $params = ' . implode(', ', $params));

        /* Now lets respond to the IRC event! */

        if ( strtolower($cmd) == 'ping' ) {
            ircWrite( 'PONG :' . $params[0] );
        } else {
            /* Break down the source further */
            $tmpSrc = explode( '!', $src);
            $nick = $tmpSrc[0];
            if( isset($tmpSrc[1]) ) {
                $tmpSrc = explode( '@', $tmpSrc[1] );
                $ident = $tmpSrc[0];
                $address = $tmpSrc[1];
            } else {
                $ident = NULL;
                $address = NULL;
            }

            /* Okay, now we have these:
             * $nick = source nickname
             * $ident = source's identd value
             * $address = source's hostname
             * Note: $ident and $address will be NULL on server-source
             */
            
            switch( strtolower($cmd) ) {
            case '005':
                // RPL_ISUPPORT

                if( !isset($isupport) ) {
                    $isupport = array();
                }
                //:delta.cluenet.org 005 SnoFox CMDS=KNOCK,MAP,DCCALLOW,USERIP UHNAMES NAMESX SAFELIST HCN MAXCHANNELS=60 CHANLIMIT=#:60 MAXLIST=b:60,e:60,I:60 NICKLEN=30 CHANNELLEN=32 TOPICLEN=307 KICKLEN=307 AWAYLEN=307 :are supported by this server
                $rplisupport = implode(' ',$tmp);
                $rplisupport = explode(':',$rplisupport);
                $rplisupport = explode(' ',$rplisupport[1]);
                foreach( $rplisupport as $feature ) {
                    $split = explode('=',$feature);
                    $isupport[ strtolower($split[0]) ] = $split[1];

                    // maul PREFIX for the purpose of this bot
                    // XXX: this is a hack; clean this when I clean the code
                    if ( strtolower($split[0]) == 'prefix' ) {
                        $hack = explode(')',$isupport['prefix']);
                        $isupport['prefix'] = $hack[1];
                    }
                }

                break;
            case '422':
                // ERR_NOMOTD
                break;
            case '376':
                // RPL_ENDOFMOTD
                break;
            case '353':
                // RPL_NAMREPLY
                // :delta.cluenet.org 353 SnoFox = #clueirc :Daisy BarryCarlyon hawken MindstormsKid Typo res inntranet thatguy nanobot Ashfire908 Damian pickle sort_-R artemis2 tonyb Hamlin ixfrit Crazytales Rich FastLizard4|zZzZ pop sonicbot-dev dbristow komik TDJACR neoark notdan clueless SnoFox Filefragg Supermeman nickzxcv osxdude danther LuminolBlue [FF]FoxBot MJ94 Somebody sommopfle AmazingCarter Lil`C joannac Suspect[L] Gerdesas jdstroy davenull Sthebig Hellow 
                // :delta.cluenet.org 353 SnoFox = #clueirc :Dan fahadsadah niekie theron Nick hrmlgon2 Cobi &Rembrandt &Bash &AccountBot &PHP Katelin QuoteBot &DaVinci CobiBot Crispy nathan Cat lietk12 Deepy TwitterBot Dvyjones tuntis InvisiblePinkUnicorn mirash chaos95 jercos PieSpy 
                // :delta.cluenet.org 366 SnoFox #clueirc :End of /NAMES list.
                $channel = $params[2];

                if( !isset( $endOfNamesList[$channel] ) ) {
                    // New channel? :D
                    $endOfNamesList[$channel] = FALSE;
                }

                if( $endOfNamesList[$channel] ) {
                    // Already got names list; reset, as this is new
                    unset($userList[$channel]);
                    $endOfNamesList[$channel] = FALSE;
                }

                $tmpUserList = explode(':',implode(' ',$tmp));
                $tmpUserList = explode(' ',trim($tmpUserList[2]));

                foreach($tmpUserList as $num => $user) {
                    $tmpUserList[$num] = ltrim($user,$isupport['prefix']);
                }

                foreach( $tmpUserList as $user ) {
                    $userList[$channel][] = $user;
                }

                break;
            case '366':
                if( !isset($endOfNamesList) ) {
                    $endOfNamesList = array();
                }
                $channel = $tmp[3];

                $endOfNamesList[$channel] = TRUE;
                break;
            case 'join':
                // :SnoFox!~SnoFox@SnoFox.net JOIN #clueirc
                coreJoin( $nick, $ident, $address, $params[0] );
                break;
            case 'part':
                corePart( $nick, $ident, $address, $params[0], $params[1] );
                break;
            case 'quit':
                coreQuit( $nick, $ident, $address, $params[0] );
                break;
            case 'nick':
                coreNick( $nick, $ident, $address, $params[0] );
                break;
            case 'kick':
                coreKick( $nick, $ident, $address, $params[0], $params[1], $params[2] );
                break;
            case 'privmsg':
                corePrivmsg( $nick, $ident, $address, $params[0] );
                break;
            default:
                break;
            }
        }
    }
    die("Broke out of infinite loop. :D\n");
}
?>
