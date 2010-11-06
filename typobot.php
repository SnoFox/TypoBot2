<?PHP
/* Includes */

include 'functions.php';

include 'tb_config.php';
include 'tb_data.php';

/* Dirty, uncleaned code */

$users = unserialize(file_get_contents('tb_db.dat'));

$pspell = pspell_new('en','','','',PSPELL_BAD_SPELLERS);

$socket = stream_socket_client('tcp://'.$config['server'].':'.$config['port'],$errno,$errstr,30);

if (!$socket) {
    echo "$errstr ($errno)\n";
} else {
    fwrite($socket,'USER '.$config['user'].' "1" "1" :'.$config['gecos']."\n");
    fwrite($socket,'NICK '.$config['nick']."\n");

    while (!feof($socket)) {
        $line = str_replace(array("\n","\r"),'',fgets($socket,512));
        $tmp = explode(' ',$line);
        $cmd = $tmp[0];

        if (strtolower($cmd) == 'ping') {
            fwrite($socket,'PONG '.$tmp[1]."\n");
        } else {
            switch (strtolower($tmp[1])) {
            case '005':
                // RPL_ISUPPORT

                if( !isset($isupport) ) {
                    $isupport = array();
                }
                //:delta.cluenet.org 005 SnoFox CMDS=KNOCK,MAP,DCCALLOW,USERIP UHNAMES NAMESX SAFELIST HCN MAXCHANNELS=60 CHANLIMIT=#:60 MAXLIST=b:60,e:60,I:60 NICKLEN=30 CHANNELLEN=32 TOPICLEN=307 KICKLEN=307 AWAYLEN=307 :are supported by this server
                $rplisupport = implode(' ',$tmp);
                $rplisupport = explode(':',$rplisupport);
                //						debug('$tmp[1] = '.$tmp[1]);
                $rplisupport = explode(' ',$rplisupport[1]);
                //						$rplisupport = array_slice($rplisupport,
                foreach( $rplisupport as $feature ) {
                    $split = explode('=',$feature);
                    $isupport[ strtolower($split[0]) ] = $split[1];

                    // maul PREFIX for the purpose of this bot
                    // XXX: this is a hack; clean this when I clean the code
                    if ( strtolower($split[0]) == 'prefix' ) {
                        $hack = explode(')',$isupport['prefix']);
                        $isupport['prefix'] = $hack[1];
                    }

                    //							debug('Feature: '.$feature);
                }
                //						debug('Prefixes = '.$isupport['prefix']);
                break;
            case '422':
                // ERR_NOMOTD
            case '376':
                // RPL_ENDOFMOTD
                fwrite($socket,'JOIN '.implode(',',$config['channels'])."\n");
                break;
            case '353':
                // RPL_NAMREPLY
                // :delta.cluenet.org 353 SnoFox = #clueirc :Daisy BarryCarlyon hawken MindstormsKid Typo res inntranet thatguy nanobot Ashfire908 Damian pickle sort_-R artemis2 tonyb Hamlin ixfrit Crazytales Rich FastLizard4|zZzZ pop sonicbot-dev dbristow komik TDJACR neoark notdan clueless SnoFox Filefragg Supermeman nickzxcv osxdude danther LuminolBlue [FF]FoxBot MJ94 Somebody sommopfle AmazingCarter Lil`C joannac Suspect[L] Gerdesas jdstroy davenull Sthebig Hellow 
                // :delta.cluenet.org 353 SnoFox = #clueirc :Dan fahadsadah niekie theron Nick hrmlgon2 Cobi &Rembrandt &Bash &AccountBot &PHP Katelin QuoteBot &DaVinci CobiBot Crispy nathan Cat lietk12 Deepy TwitterBot Dvyjones tuntis InvisiblePinkUnicorn mirash chaos95 jercos PieSpy 
                // :delta.cluenet.org 366 SnoFox #clueirc :End of /NAMES list.
                $channel = $tmp[4];

                if( !isset($endOfNamesList[$channel]) ) {
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

                debug('(NAMES) New userlist for '.$channel.': '.implode(', ', $userList[$channel]));

                break;
            case '366':
                if( !isset($endOfNamesList) ) {
                    $endOfNamesList = array();
                }
                $channel = $tmp[3];

                $endOfNamesList[$channel] = TRUE;
                break;
            case 'join':
                $nick = explode('!',substr($tmp[0],1));
                $nick = $nick[0];

                $channel = substr($tmp[2],1);
                //						debug('Joined '.$channel.': '.$nick);

                $userList[ $channel ][] = $nick;
                //						debug('(JOIN) New userlist for '.$channel.': '.implode(', ', $userList[$channel]));
                break;
            case 'part':
                $nick = explode('!',substr($tmp[0],1));
                $nick = $nick[0];

                $channel = $tmp[2];

                //						debug('Parted '.$channel.': '.$user);

                foreach( $userList[$channel] as $key => &$user ) {
                    if (strtolower($user) == strtolower($nick)) {
                        unset($userList[$channel][$key]);
                        break;
                    }
                } // foreach
                unset($user);
                //						debug('(PART) New userlist for '.$channel.': '.implode(', ', $userList[$channel]));
                break;
            case 'quit':
                $nick = explode('!',substr($tmp[0],1));
                $nick = $nick[0];

                //						debug('Quit: '.$nick);

                foreach( $userList as &$channel ) {
                    foreach( $channel as $key => &$user ) {
                        if( strtolower($user) == strtolower($nick) ) {
                            unset($channel[$key]);
                            break;
                        } // if
                    } // foreach user in channel
                } // foreach channel
                unset($channel,$user);
                foreach( $userList as $channel ) {
                    //							debug('(QUIT) New userlist for '.$channel.': '.implode(', ', $channel));
                }
                break;
            case 'nick':
                $nick = explode('!',substr($tmp[0],1));
                $nick = $nick[0];
                $newNick = substr($tmp[2],1);

                //						debug('Nick change: '.$nick.' --> '.$newNick);

                foreach( $userList as &$channel ) {
                    foreach( $channel as $key => &$user ) {
                        if( strtolower($user) == strtolower($nick) ) {
                            $channel[$key] = $newNick;
                            break;
                        } // if
                    } // Foreach user in channel
                } // foreach channel
                unset($channel, $user);
                foreach( $userList as $channel ) {
                    //                                                        debug('(NICK) New userlist for '.$channel.': '.implode(', ', $channel));
                }

                break;
            case 'kick':
                foreach( $userList[$tmp[2]] as $key => &$user ) {
                    if (strtolower($user) == strtolower($tmp[3])) {
                        unset($userList[$tmp[2]][$key]);
                        break;
                    }
                } // foreach
                unset($user);
                //						debug('(KICK) New userlist for '.$tmp[2].': '.implode(', ', $userList[$tmp[2]]));
                break;
            case 'privmsg':
                $nick = explode('!',substr($tmp[0],1));
                $nick = $nick[0];
                if (!isset($ignores[strtolower($nick)])) {
                    $target = $tmp[2];
                    $message = explode(' ',$line,4);
                    $message = substr($message[3],1);
                    $breakChars = array(
                        '/',
                        '\'',
                        '_'
                    );
                    $message = str_replace($breakChars,' ',$message);
                    //							debug('msg: '.$message);

                    if (substr($target,0,1) == '#') {
                        $good = 1;
                        $privchan = 0;
                        $evenifcorrect = 0;
                        foreach ($exceptions as $exception) {
                            if (preg_match($exception,$message)) {
                                $good = 0;
                            }
                        }
                        if ($users[strtolower($nick)]['spell'] != 1) {
                            $good = 0;
                        }
                        if ($users[strtolower($nick)]['public'] == 1) {
                            $privchan = 1;
                        }
                        if (strtolower(substr($message,0,7)) == '!spell ') { $message = substr($message,7); $privchan = 1; $good = 1; $evenifcorrect = 1; }
                            if ($good == 1) {
                                $stuff = '';
                                foreach ($correctionsi as $search => $correction) {
                                    if (preg_match('/(^|\W)'.preg_quote($search,'/').'(\W|\.|\?|\!|$)/i',$message)) {
                                        $stuff .= '*'.$correction.' ';
                                    }
                                }
                                foreach ($corrections as $search => $correction) {
                                    if (preg_match('/(^|\W)'.preg_quote($search,'/').'(\W|\.|\?|\!|$)/',$message)) {
                                        $stuff .= '*'.$correction.' ';
                                    }
                                }
                                foreach ($acorrections as $search => $correction) {
                                    if ($y = preg_replace($search,$correction,$message,-1,$c)) {
                                        if ($c > 0) {
                                            $stuff .= '*'.$y.' ';
                                            $y = '';
                                        }
                                    }
                                }
                                foreach ($ecorrections as $eval) {
                                    $z = '';
                                    eval($eval);
                                    if ($z) {
                                        $stuff .= '*'.$z.' ';
                                        $z = '';
                                    }
                                }
                                $tmp = '';
                                for ($i=0;$i<strlen($message);$i++) {
                                    $c = ord($message{$i});
                                    if (
                                        (($c >= 65) and ($c <= 90))
                                        or (($c >= 97) and ($c <= 122))
                                        or (($c >= 48) and ($c <= 57))
                                        or ($c == 32) or ($c == 39)
                                    ) $tmp .= chr($c);
                                    if ($c == 45) $tmp .= ' ';
                                }
                                $words = explode(' ',$tmp);
                                foreach ($words as $word) {
                                    if (strlen($word) < 3) { continue; }

                                        foreach( $userList[$target] as $user ) {
                                            if( strtolower($word) == strtolower($user) ) {
                                                continue 2;
                                            }
                                        }

                                    if (pspell_check($pspell,$word)) { $w = ''; }
                                    else { ($w = pspell_suggest($pspell,$word)) or ($w = 'Incorrect.'); $w = $w[0]; }
                                        if ($w == '') continue; 
                                    unset($info);
                                    $x = 0;
                                    foreach ($correctionsi as $k=>$v) if (preg_match('/(^|\W)'.preg_quote($k,'/').'(\W|\.|\?|\!|$)/i',$word)) $x = 1;
                                    foreach ($corrections as $k=>$v) if (preg_match('/(^|\W)'.preg_quote($k,'/').'(\W|\.|\?|\!|$)/',$word)) $x = 1;
                                    foreach ($spexceptions as $k=>$v) if (preg_match($v,$word)) $x = 1;
                                    if ($x == 0) {
                                        $stuff .= '*'.$w.' (pspell) ';
                                    }
                                }
                                if (($evenifcorrect == 1) and (!$stuff)) { $stuff = 'Correct.'; }
                                    if ($stuff) {
                                        if ($privchan == 0) {
                                            fwrite($socket,'NOTICE '.$nick.' :'.$stuff."\n");
                                        } else {
                                            fwrite($socket,'PRIVMSG '.$target.' :'.$nick.': '.$stuff."\n");
                                        }
                                    }
                            }
                    } else {
                        if (substr($message,0,6) == 'reload') {
                            include 'tb_data.php';
                            fwrite($socket,'NOTICE '.$nick.' :Okay...'."\n");
                        } elseif (substr($message,0,5) == 'spell') {
                            if ($users[strtolower($nick)]['spell'] == 1) {
                                $users[strtolower($nick)]['spell'] = 0;
                                fwrite($socket,'NOTICE '.$nick.' :Spelling corrections now off.'."\n");
                            } else {
                                $users[strtolower($nick)]['spell'] = 1;
                                fwrite($socket,'NOTICE '.$nick.' :Spelling corrections now on.'."\n");
                            }
                            file_put_contents('tb_db.dat',serialize($users));
                        } elseif (substr($message,0,6) == 'public') {
                            if ($users[strtolower($nick)]['public'] == 1) {
                                $users[strtolower($nick)]['public'] = 0;
                                fwrite($socket,'NOTICE '.$nick.' :Public spelling corrections now off.'."\n");
                            } else {
                                $users[strtolower($nick)]['public'] = 1;
                                fwrite($socket,'NOTICE '.$nick.' :Public spelling corrections now on.'."\n");
                            }
                            file_put_contents('tb_db.dat',serialize($users));
                        }
                    }
                }
                break;
            }
        }
    }
}
?>
