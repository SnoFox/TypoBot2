<?php
function checkSpelling( $nick, $target, $message ) {
    include 'config/words.php';

    $breakChars = array(
        '/',
        '\'',
        '_'
    );
    $message = str_replace($breakChars,' ',$message);

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
                            ircWrite('NOTICE '.$nick.' :'.$stuff);
                        } else {
                            ircWrite('PRIVMSG '.$target.' :'.$nick.': '.$stuff);
                        }
                    }
            }
    } else {
/* // No longer required as of now
if (substr($message,0,6) == 'reload') {
include 'config/words.php';
fwrite($socket,'NOTICE '.$nick.' :Okay...'."\n");
} 
 */
        if (substr($message,0,5) == 'spell') {
            if ($users[strtolower($nick)]['spell'] == 1) {
                $users[strtolower($nick)]['spell'] = 0;
                ircWrite('NOTICE '.$nick.' :Spelling corrections now off.');
            } else {
                $users[strtolower($nick)]['spell'] = 1;
                ircWrite('NOTICE '.$nick.' :Spelling corrections now on.');
            }
            file_put_contents('data/tb_db.dat',serialize($users));
        } elseif (substr($message,0,6) == 'public') {
            if ($users[strtolower($nick)]['public'] == 1) {
                $users[strtolower($nick)]['public'] = 0;
                ircWrite('NOTICE '.$nick.' :Public spelling corrections now off.');
            } else {
                $users[strtolower($nick)]['public'] = 1;
                ircWrite('NOTICE '.$nick.' :Public spelling corrections now on.');
            }
            file_put_contents('data/tb_db.dat',serialize($users));
        }
    }
}
?>
