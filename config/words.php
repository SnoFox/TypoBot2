<?PHP
	$correctionsi = Array
		(
			/* Case insensitive fixes; static string */

			'im'		=> 'I\'m',
			'ill'		=> 'I\'ll',
			'u'		=> 'you',
			'ur'		=> 'your',
			'youre'		=> 'you\'re',
			'wat'		=> 'what',
			'r'		=> 'are',
//			'gonna'		=> 'going to',
//			'gotta'		=> 'got to',
//			'sorta'		=> 'sort of',
//			'sup'		=> '\'sup',
			'calcs'		=> 'calculations',
			'wont'		=> 'won\'t',
//			'id'		=> 'I\'d',
			'ud'		=> 'you\'d (or usted)',
			'y'		=> 'why',
//			'its'		=> 'it\'s',
//			'teh'		=> 'the',
			'wut'		=> 'what',
//			'justive'	=> 'justice',
//			'anoying'	=> 'annoying',
			'prolly'	=> 'probably',
//			'luser'		=> 'loser',
//			'wanna'		=> 'want to',
			'whats'		=> 'what\'s'
		);

	$corrections = Array
		(
			/* Case sensitive corrections; static string */

			'i'		=> 'I',
			'cobi'		=> 'Cobi',
			'i\'m'		=> 'I\'m',
//			'tcp'		=> 'TCP',
			'google'	=> 'Google'
		);

	$acorrections = Array
		(
			/* Regex corrections */

//			'/^[a-z].*$/'		=> 'capitalization',
			'/^(?!Cobi)[Cc]+[oO]+[bB]+[IYiy]+$/'	=> 'Cobi'
		);

	$ecorrections = Array
		(
			/* Eval'd corrections
			 * set $z as the correction if existant
			*/

		);
	
	$exceptions = Array
		(
			/* Regex exceptions */

			'/^[^:]+\:.*$/'
		);
	
	$spexceptions = Array
		(
			/* Regex exceptions */

			'/oper/i',
			'/ok/i',
			'/hmm/i',
            '/h?(ah){1,}/i',
            '/h(eh){1,}/i',
            '/ah-(ha){1,}/i',
			'/\bTCP\b/',
			'/erm/i',
			'/pspell/i',
			'/snofox/i',
			'/bleh/i',
			'/(chan|nick|memo|note|jupe|oper|root|fox|blue|php|host|bot|sasl|auth|help)serv/i',
			'/clueirc/i',
			'/Google/',
			'/yay/i',
			'/cluebot/i',
			'/ClueNet/',
			'/irssi/i',

			// Smilies
			'/[oO]\.[oO]/',
			
			// Acronyms
			'/GBP/',
			'/USD/',
			'/SVN/',
			'/CVS',			

			// Chatspeak 4 lazy ppl
			'/lol/i',
			'/lmf?ao/i',
			'/omf?g/i',
			'/rot?fl/i',
			'/wt(f|h)/i'
		);

	$ignores = Array
		(
			'davinci'	=> 1
		);
?>
