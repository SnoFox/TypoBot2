<?php
/* Core for TypoBot 2
 * Read the LICENSE file for license info
 */

function coreJoin( $nick, $ident, $host, $chan ) {
    // Maintain userlist
    global $userList;
    $userList[ $channel ][] = $nick;
}
function corePart( $nick, $ident, $host, $chan, $reason ) {
    // Maintain userlist
    global $userList;
    foreach( $userList[$channel] as $key => $user ) {
        if (strtolower($user) == strtolower($nick)) {
            unset($userList[$channel][$key]);
            break;
        }
    } // foreach
    debug('(PART) Userlist for '.$chan.': '.implode(', ', $userList[$chan]) );
}
function coreQuit( $nick, $ident, $host, $reason ) {
    // Maintain userlist
    global $userList;
    foreach( $userList as $channel ) {
        foreach( $userList[$channel] as $key => $user ) {
            if( strtolower($user) == strtolower($nick) ) {
                unset($userList[$channel][$key]);
                break;
            } // if
        } // foreach user in channel
    } // foreach channel
    debug('(QUIT) Userlist for '.$chan.': '.implode(', ', $userList[$chan]) );
}
function coreNick( $nick, $ident, $host, $newNick ) {
    // Maintain userlist
    global $userList;
    foreach( $userList as $channel ) {
        foreach( $userList[$channel] as $key => $user ) {
            if( strtolower($user) == strtolower($nick) ) {
                $userList[$channel][$key] = $newNick;
                break;
            } // if
        } // Foreach user in channel
    } // foreach channel
    debug('(NICK) Userlist for '.$chan.': '.implode(', ', $userList[$chan]) );
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
    debug('(KICK) Userlist for '.$chan.': '.implode(', ', $userList[$chan]) );
}
function corePrivmsg( $nick, $ident, $host, $chan, $msg ) {
   /* global $ignores;
    if ( isset($ignores[strtolower($nick)]) ) {
        return 1;
    } */
    checkSpelling( $nick, $chan, $msg );
}
