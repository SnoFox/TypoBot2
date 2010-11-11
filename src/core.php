<?php
/* Core for TypoBot 2
 * Read the LICENSE file for license info
 */

function coreJoin( $nick, $ident, $host, $chan ) {
    // Maintain userlist
    global $userList;
    $userList[ $chan ][] = $nick;
}
function corePart( $nick, $ident, $host, $chan, $reason ) {
    // Maintain userlist
    global $userList;
    foreach( $userList[$chan] as $key => $user ) {
        if (strtolower($user) == strtolower($nick)) {
            unset($userList[$chan][$key]);
            break;
        }
    } // foreach
}
function coreQuit( $nick, $ident, $host, $reason ) {
    // Maintain userlist
    global $userList;
    foreach( $userList as $chanKey => $channel ) {
        foreach( $channel as $key => $user ) {
            if( strtolower($user) == strtolower($nick) ) {
                unset($userList[$chanKey][$key]);
                break;
            } // if
        } // foreach user in channel
    } // foreach channel
}
function coreNick( $nick, $ident, $host, $newNick ) {
    // Maintain userlist
    global $userList;
    foreach( $userList as $chanKey => $channel ) {
        foreach( $channel as $key => $user ) {
            if( strtolower($user) == strtolower($nick) ) {
                $userList[$chanKey][$key] = $newNick;
                break;
            } // if
        } // Foreach user in channel
    } // foreach channel
}
function coreKick( $nick, $ident, $host, $chan, $victim, $reason ) {
    // Maintain userlist
    global $userList;
    foreach( $userList[$chan] as $key => $user ) {
        if (strtolower($user) == strtolower($victim)) {
            unset($userList[$chan][$key]);
            break;
        }
    } // foreach
}
function corePrivmsg( $nick, $ident, $host, $chan, $msg ) {
   /* global $ignores;
    if ( isset($ignores[strtolower($nick)]) ) {
        return 1;
    } */
    checkSpelling( $nick, $chan, $msg );
}
function coreConnected() {
    global $config;
    foreach( $config['channels'] as $channel ) {
       ircWrite( 'JOIN ' . $channel );
    }
}
