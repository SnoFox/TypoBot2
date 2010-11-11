<?PHP
/* Includes */

include 'includes/functions.php';

include 'config/config.php';

include 'src/core.php';
include 'src/typo.php';



$socket = stream_socket_client( 'tcp://' . $config['server'] . ':' . $config['port'] , $errno , $errstr , 30 );

if ( !$socket ) {
    logit( "IRC error: $errstr ($errno)" );
} else {
    ircWrite( 'USER ' . $config['user'] . ' "1" "1" :' . $config['gecos'] );
    ircWrite( 'NICK '. $config['nick'] );

    while ( !feof( $socket ) ) {
        $ircRawData = str_replace( array("\n","\r"), '', fgets($socket,512) );
        $ircRawData = trim($ircRawData);
        if( empty($ircRawData) ) {
            // Getting tired of Unreal's blank lines...
            continue;
        }
        debug( 'IRC:I: ' . $ircRawData );

        $data = ircSplit( $ircRawData );
        unset( $ircRawData ); // We shouldn't be using this now.

        /* Now we're using Cobi's kickass irc splitter, so we look like this:
         * :SnoFox!~SnoFox@SnoFox.net KICK #clueirc MJ94 :Quit being a lamer!
         * $data['type']        = relayed
         * $data['rawpieces']   = array( 'SnoFox!~SnoFox@SnoFox.net', 'KICK', '#clueirc', 'MJ94', 'Quit being a lamer!' )
         * $data['source']      = SnoFox!~SnoFox@SnoFox.net
         * $data['command']     = KICK
         * $data['target']      = #clueirc
         * $data['pieces']      = array( 'MJ94', 'Quit being a lamer!' )
         */


        /* Now lets respond to the IRC event! */
        
        if( $data['type'] == 'direct' ) { 
            if ( $data['command'] == 'ping' ) {
                ircWrite( 'PONG :' . $data['pieces'][0] );
            }
        } else {
            /* Break down the source further */
            $tmpSrc = explode( '!', $data['source'] );
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
            
            switch( $data['command'] ) {
            case '005':
                // RPL_ISUPPORT
                //:delta.cluenet.org 005 SnoFox CMDS=KNOCK,MAP,DCCALLOW,USERIP UHNAMES NAMESX SAFELIST HCN MAXCHANNELS=60 CHANLIMIT=#:60 MAXLIST=b:60,e:60,I:60 NICKLEN=30 CHANNELLEN=32 TOPICLEN=307 KICKLEN=307 AWAYLEN=307 :are supported by this server

                if( !isset($isupport) ) {
                    $isupport = array();
                    $prefixMauled = FALSE;
                }
                $tmpSupport = $data['pieces'];
                // Rid us of "are supported by this server" >.<
                array_pop($tmpSupport);
                $newSupport = array();
                foreach( $tmpSupport as $key => $feature ) {
                    $split = explode( '=', $feature, 2 );
                    $newSupport[strtolower($split[0])] = isset($split[1]) ? $split[1] : NULL;
                }
                $isupport = array_merge($isupport,$newSupport);
                if( isset($isupport['prefix']) && $prefixMauled == FALSE ) {
                    $prefixMauled = TRUE;
                    $isupport['prefix'] = explode(')',$isupport['prefix'],2);
                    $isupport['prefix'] = $isupport['prefix'][1];
                }

                break;
            case '422':
                // ERR_NOMOTD
//                break;
            case '376':
                // RPL_ENDOFMOTD
                coreConnected();
                break;
            case '353':
                // RPL_NAMREPLY
                // :delta.cluenet.org 353 SnoFox = #clueirc :Daisy BarryCarlyon hawken MindstormsKid Typo res inntranet thatguy nanobot Ashfire908 Damian pickle sort_-R artemis2 tonyb Hamlin ixfrit Crazytales Rich FastLizard4|zZzZ pop sonicbot-dev dbristow komik TDJACR neoark notdan clueless SnoFox Filefragg Supermeman nickzxcv osxdude danther LuminolBlue [FF]FoxBot MJ94 Somebody sommopfle AmazingCarter Lil`C joannac Suspect[L] Gerdesas jdstroy davenull Sthebig Hellow 
                // :delta.cluenet.org 353 SnoFox = #clueirc :Dan fahadsadah niekie theron Nick hrmlgon2 Cobi &Rembrandt &Bash &AccountBot &PHP Katelin QuoteBot &DaVinci CobiBot Crispy nathan Cat lietk12 Deepy TwitterBot Dvyjones tuntis InvisiblePinkUnicorn mirash chaos95 jercos PieSpy 
                // :delta.cluenet.org 366 SnoFox #clueirc :End of /NAMES list.
                $channel = $data['pieces'][1];

                if( !isset( $endOfNamesList[$channel] ) || $endOfNamesList[$channel] ) {
                    // New names list; del del del!
                    if( isset( $userList[$channel] ) )
                        unset( $userList[$channel] );
                    $endOfNamesList[$channel] = FALSE;
                }
                $tmpUserList = explode( ' ',$data['pieces'][2]);

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
                $channel = $data['pieces'][0];

                $endOfNamesList[$channel] = TRUE;
                break;
            case 'join':
                // :SnoFox!~SnoFox@SnoFox.net JOIN #clueirc
                coreJoin( $nick, $ident, $address, $data['target'] );
                break;
            case 'part':
                corePart( $nick, $ident, $address, $data['target'], $data['pieces'][0] );
                break;
            case 'quit':
                coreQuit( $nick, $ident, $address, $data['target'] );
                break;
            case 'nick':
                coreNick( $nick, $ident, $address, $data['target'] );
                break;
            case 'kick':
                coreKick( $nick, $ident, $address, $data['target'], $data['pieces'][0], $data['pieces'][1] );
                break;
            case 'privmsg':
                corePrivmsg( $nick, $ident, $address, $data['target'], $data['pieces'][0] );
                break;
            default:
                break;
            }
        }
    }
    die("Broke out of infinite loop. :D\n");
}
?>
