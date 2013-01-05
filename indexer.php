<?php

/*
 * Simple indexer to stick all the Dr Who subtitles into Xapian
 * Oh, how I do like Xapian
 *
 * Matthew Somerville, http://www.dracos.co.uk/
 * Version 1.5, now I have all modern series
 */

$colors = array(
	'#fefe00' => 'yellow',
	'#ffff00' => 'yellow',
	'#00ffff' => 'cyan',
	'#00fffd' => 'cyan',
	'#00fefc' => 'cyan',
	'#ededed' => 'white',
	'#ececec' => 'white',
	'#00ff00' => 'green',
	'#00fe00' => 'green',
	'#ffffff' => 'white',
);

include '/usr/share/php/xapian.php';
include './config.php';

$db = new XapianWritableDatabase(XAPIAN_DIR . 'write', Xapian::DB_CREATE_OR_OPEN);
$indexer = new XapianTermGenerator();
$stemmer = new XapianStem("english");
$indexer->set_flags(128);
$indexer->set_database($db); # For spelling
$indexer->set_stemmer($stemmer);

# Read in files

array_shift($argv); # Script name
foreach ($argv as $file) {
	print "Processing $file...\n";

	preg_match('#/(\d+)-(\d+?)\.xml$#', $file, $m);
	$series = $m[1];
	$epid = $m[2] + 0;
	
	$file = file_get_contents($file);
	# <p begin = "00:01:13.555" dur="00:00:05.00"><span tts:color="#fefe00" tts:textAlign="center"> I'm happy right now, thanks. </span><br/></p>
    # <p begin="00:00:54.578" end="00:00:57.198"><span tts:color="#ececec" tts:textAlign="left"> BIG BEN STRIKES </span><br/></p>
    # <p begin="00:02:22.008" end="00:02:27.628"><span tts:color="#ececec" tts:textAlign="center"> Dear Santa, thank you for the dolls </span><br/><span tts:color="#ececec" tts:textAlign="center"> and pencils and the fish. </span></p>
	preg_match_all('/<p begin ?= ?"([^"]*)"(?: dur="([^"]*)")?(?: end="([^"]*)")?>(.*?)<\/p>/', $file, $rows, PREG_SET_ORDER);
	$rowcount = 0;
	foreach ($rows as $row) {
		$begin = $row[1];
		$beginN = substr($begin, 0, 2) * 3600 + substr($begin, 3, 2) * 60 + substr($begin, 6);
		$duration = $row[2];
		if ($duration && $duration != '00:00:05.00') {
			print "NEW duration: $duration";
			exit;
		}
		$end = $row[3];
		# $text = preg_replace('#((<span[^>]*>).*?[^>])<br/>#', '$1</span><br/>$2', $row[4]);
		$text = $row[4];
		preg_match_all('/<span tts:color="([^"]*)" tts:textAlign="([^"]*)">(.*?)<\/span>/', $text, $spans, PREG_SET_ORDER);
        $lasttextarr = array();
		foreach ($spans as $span) {
			$color = $span[1];
			if (!$colors[$color]) {
				print "New colour: $color\n";
				exit;
			}
			$color = $colors[$color];
			$align = $span[2];
			$textarr = explode('<br/>', $span[3]);
			$safetextarr = $textarr;
			if (count($textarr) > count($lasttextarr)) {
				$same = 1;
				for ($c=0; $c<count($lasttextarr); $c++) {
					if ($lasttextarr[$c] != $textarr[$c]) $same = 0;
				}
				if ($same) {
					$textarr = array_slice($textarr, count($lasttextarr));
				}
			}
			foreach ($textarr as $text) {
				$text = trim($text);
				if (!$text) continue;
				$noise = 0;
				if ($text != 'I' && !preg_match('#[a-z!.]#', $text) && $color=='white') {
					$noise = 1;
				}
	
				$id = "$series-$epid-$rowcount";

				$doc = new XapianDocument();
				$indexer->set_document($doc);
				$doc->set_data($text);

				$doc->add_term("A$align");
				$doc->add_term("B$begin");
				$doc->add_term("C$color");
				$doc->add_term("E$epid");
				$doc->add_term("I$rowcount");
				$doc->add_term("N$noise");
				$doc->add_term("Q$id");
				$doc->add_term("S$series");

				$doc->add_value(0, Xapian::sortable_serialise($beginN));
				$doc->add_value(1, sprintf("%d%02d", $series, $epid));

				$indexer->index_text($text);

				$db->add_document($doc);
				$rowcount++;
			}
			$lasttextarr = $safetextarr;
		}
	}
}

$db = null;

