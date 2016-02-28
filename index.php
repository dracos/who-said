<?php

/*
 * Front end for Dr Who subtitles searching, graphing, tagclouding
 * Matthew Somerville, http://www.dracos.co.uk/
 * Version 1.5. Written at Mashed08 and soon after, at some stupid time, so excuse poor code quality ;)
 */

include_once 'search.php';

define('NUM_SERIES', 7);
$episodes = array(
    1 => 14,
    2 => 14,
    3 => 14,
    4 => 18,
    5 => 14,
    6 => 14,
    7 => 6,
);

# Construct query string from optional advanced search parameters
$query = isset($_GET['q']) ? $_GET['q'] : '';

if (preg_match('#date:(\d+)#', $query, $m)) {
	list($s, $e) = date_index($m[1]);
	if ($s && $e) {
		$query = preg_replace('#date:\d+#', "series:$s ep:$e", $query);
		header("Location: /?q=". urlencode($query));
		exit;
	}
}

if (isset($_GET['align']) && $_GET['align']) $query .= ' align:' . $_GET['align'];
if (isset($_GET['colour']) && $_GET['colour']) $query .= ' colour:' . strtolower($_GET['colour']);
if (isset($_GET['noise']) && $_GET['noise']) $query .= ' noise:' . $_GET['noise'];
if (isset($_GET['ep']) && $_GET['ep']) $query .= ' ep:' . $_GET['ep'];
if (isset($_GET['series']) && $_GET['series']) $query .= ' series:' . $_GET['series'];
if (isset($_GET['from']) && isset($_GET['to'])) {
	$query .= ' ' . ($_GET[from]*60) . '..' . ($_GET[to]*60);
}

$query = trim($query);
$h_query = htmlspecialchars($query);

# Header
?>
<!DOCTYPE html>
<html lang="en-gb">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php if ($query) echo "&lsquo;$h_query&rsquo; | "; ?>Who Said... Subtitle Search, by Matthew Somerville</title>
<link rel="stylesheet" type="text/css" href="style.css">
<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=Arvo">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
<script src="js.js"></script>
</head>
<body>
<div id="b">

<form action="/" method="get" id="search">
<label for="q">Search:</label> <input type="text" id="q" name="q" value="<?=$h_query?>" size="20">
<input type="submit" value="Search">
</form>

<h1><a href="/">Who Said&hellip; <small>&ndash; Subtitle Search<?php

if ($query) {
	echo "<br>&lsquo;$h_query&rsquo;";
}
echo '</small></a></h1>';

if (isset($_GET['source'])) {
	echo '<ul><li><a href="#indexer">indexer.php</a>
<li><a href="#index">index.php</a>
<li><a href="#searchphp">search.php</a>
</ul>';
	echo '<h2><a name="indexer"></a>indexer.php</h2>';
	highlight_file('indexer.php');
	echo '<h2><a name="index"></a>index.php</h2>';
	highlight_file('index.php');
	echo '<h2><a name="searchphp"></a>search.php</h2>';
	highlight_file('search.php');
} elseif ($query) {
	# Let's do a search
	$data = search($query, 1000000);
	$estimate = $data['estimate'];
	if ($estimate==0) {
		print '<p id="error">No results, please try something else :)</p>';
		front_page();
		footer();
		exit;
	}
	$data = $data['data'];

	# First, collate stats from the data
	$graph = array();
	$max = 0;
	$next = array(); $prev = array(); $eps = array();
	foreach ($data as $row) {
		$series = $row['series'];
		$ep = $row['ep'];
		$time = time_index($series, $ep, $row['begin']);
		#if ($time>1) continue;
		$text = $row['text'];
		if (preg_match('#' . $query . '[\s,.!?]+([\w\']+)#i', $text, $m)) $next[strtolower($m[1])]++;
		if (preg_match('#([\w\']+)[\s,.!?]+' . $query. '#i', $text, $m)) $prev[strtolower($m[1])]++;
		$eps["$series-$ep"]++;
		$graph["$series-$ep"]++;
		if ($max < $graph["$series-$ep"]) $max = $graph["$series-$ep"];
	}	
	arsort($next); arsort($prev); arsort($eps);

	# Output either a tag cloud if it's an episode, or a line graph
	echo '<p align="center">';
	if (preg_match('#series:(\d+)#', $query, $mmm) && preg_match('#ep:(\d+)#', $query, $mmmm)) {
		$img = "images/$mmm[1]-$mmmm[1]L.png";
		if (file_exists($img)) {
			$size = getimagesize($img);
			echo "<img width='$size[0]' height='$size[1]' alt='' src='$img'>";
		}
	} else {
		echo '<img alt="" src="http://chart.apis.google.com/chart?chs=860x150&chds=0,' . $max . '&cht=ls&chd=t:';
		for ($s=1; $s<=NUM_SERIES; $s++) {
			for ($e=1; $e<=$episodes[$s]; $e++) {
				if ($s==3 && $e==5) continue;
				if ($s!=1 || $e>1) print ',';
				echo ($graph["$s-$e"] ? $graph["$s-$e"] : 0);
			}
		}
		echo '&chxt=y,x,x&chxl=0:|0|'.$max;
		$s = '|1:|1|2|3|4|5|6|7|8|9|10|11|12|13|X|1|2|3|4|5|6|7|8|9|10|11|12|13|X|1|2|3|4|6|7|8|9|10|11|12|13|X|1|2|3|4|5|6|7|8|9|10|11|12|13|X|X|X|X|X|1|2|3|4|5|6|7|8|9|10|11|12|13|X|1|2|3|4|5|6|7|8|9|10|11|12|13|X|1|2|3|4|5|X';
        $c1 = substr_count($s, '|');
        echo $s;
		$s = '|2:|||||||Series+1||||||||||||||Series+2||||||||||||||Series+3|||||||||||||Series+4||||||||||||||||||Series+5|||||||||||||||Series+6||||||||||Series+7||';
        $c2 = substr_count($s, '|');
        echo $s;
		echo '&chxs=1,666666,10,0|2,666666,10,0&chg=0,0&chco=005aaa&chg=1.087,0'; # Last num from trial and error! Was 1.923 for Series 1-4
		echo '">';
        if ($c1 != $c2) print "ERROR in | counts $c1 $c2";
	}
	echo '</p>';

	# Right hand column of stats/form/links
	echo '<div id="blurb">';
	echo '<h2 style="margin-top:0">Stats</h2>';

	if (!preg_match('#series:\d+#', $query) || !preg_match('#ep:\d+#', $query)) {
		list ($word, $num) = each($eps);
		print "<p>&lsquo;$h_query&rsquo; is mentioned most in the episode <strong>" . episode_lookup($word) . "</strong>, $num time" . ($num!=1?'s':'') . "</p>";
	}

	list ($word, $num) = each($next);
	if ($word) print "<p>The most common word following &lsquo;$h_query&rsquo; is <strong>$word</strong>, $num time" . ($num!=1?'s':'') . ".</p>";
	list ($word, $num) = each($prev);
	if ($word) print "<p>The most common word preceding &lsquo;$h_query&rsquo; is <strong>$word</strong>, $num time" . ($num!=1?'s':'') . ".</p>";

?>
<form method="get">
<h3>Advanced Search</h3>
<p>Words: <input type="text" name="q" value="" size="20">
<br>Series: <select name="series">
<?php for ($k=1; $k<=NUM_SERIES; $k++) { print "<option>$k"; } ?>
</select>
<br>Episode: <select name="ep">
<?php for ($k=1; $k<=14; $k++) { print "<option>$k"; } ?>
</select>
<br>Alignment: <select name="align"><option value="">- Any -<option value="left">Left<option value="center">Centred<option value="right">Right</select>
<br>Colour: <select name="colour"><option value="">- Any -<option>White<option>Cyan<option>Yellow<option>Green</select>
<br><input id="form_noise" type="checkbox" name="noise" value="1"> <label for="form_noise">Stage direction?</label>
<br>Between <input type="text" size="2" name="from" value='<?=htmlspecialchars($_GET['from'])?>'>
&ndash; <input type="text" size="2" name="to" value='<?=htmlspecialchars($_GET['to'])?>'> minutes in
<br><input type="submit" value="Search">
<p><small>You can also use quoted phrases or use boolean logic.</small></p>
</form>

<h2>Complete episodes</h2>

<div id="eps_side">

<?php
for ($s=1; $s<=NUM_SERIES; $s++) {
	echo "\n<h3>Series $s</h3> <ul>";
	for ($e=1; $e<=$episodes[$s]; $e++) {
		$title = episode_lookup("$s-$e");
		echo '<li>';
		if ($s!=3 || $e!=5) echo "<a href='/?q=series:$s+ep:$e'>";
		echo $title;
		if (file_exists("images/$s-{$e}S.png"))
			echo "<br><img alt='' src='images/$s-{$e}S.png'>";
		if ($s!=3 || $e!=5) echo '</a>';
	}
	echo '</ul>';
}
?>

</div>

</div> <!-- Blurb -->

<h2>Results</h2>

<ul id='searchresults'>
<?php
	foreach ($data as $row) {
		$text = $row['text'];
		$text = preg_replace("#$query#i", '<span class="hi">$0</span>', $text);
		$terms = $row['terms'];
		$pretty_time = episode_lookup("$row[series]-$row[ep]") . ' ' . prettify_time($row['begin']);

		$style = array();
		# Might not need colour on both <li> and <a> - <a> definitely needed for IE, and might be enough for others?
		if ($row['colour']=='yellow') $style[] = 'color:#ffff00';
		elseif ($row['colour']=='cyan') $style[] = 'color:#00ffff';
		elseif ($row['colour']=='green') $style[] = 'color:#00ff00';
		if ($row['align']=='center') $style[] = 'text-align:center';
		elseif ($row['align']=='right') $style[] = 'text-align:right';
		if ($row['noise']=='1') $style[] = 'font-style:italic';
		echo '<li';
		if ($style) echo ' style="' . join(';', $style) . '"';
		echo '><a name="' . $row['pos'] . '" href="/?q=series:' . $row['series'] . '+ep:' . $row['ep'] . '#' . $row['pos'] . '"';
		$style = array();
		if ($row['colour']=='yellow') $style[] = 'color:#ffff00';
		elseif ($row['colour']=='cyan') $style[] = 'color:#00ffff';
		elseif ($row['colour']=='green') $style[] = 'color:#00ff00';
		if ($style) echo ' style="' . join(';', $style) . '"';
		echo '>' . $text;
		echo '<span class="t">' . $pretty_time . '</span></a>';
		echo '</li>';
	}
	echo '</ul>';
} else {
	front_page();

}
footer();

# ---

function footer() { ?>
<div style="clear:both"></div>
</div>
<p id="footer">
Subtitle data loaded into a <a href="http://www.xapian.org/">Xapian</a> database,
graphs plotted with <a href="http://code.google.com/apis/chart/">Google Charts API</a>,
<br>and tag clouds drawn by <a href="http://wordle.net/">Wordle</a>.
Everything else (<a href="https://github.com/dracos/who-said">source</a>) by <a href="http://www.dracos.co.uk/">Matthew Somerville</a>
(<a href="http://twitter.com/dracos">@dracos</a>)</p>
</body>
</html>
<?php
}

# Utility functions

#function prettify_date($d) {
#	return date('jS F', strtotime(substr($d,1,4).'-'.substr($d,5,2).'-'.substr($d,7,2)));
#}

function prettify_time($t) {
	if (substr($t, 0, 2)=='00')
		return substr($t, 3, 5);
	return substr($t, 0, 8);
}

# Index for line graph generation
function date_index($d) {
	if ($d=='20080402') return array(2,14);
	if ($d=='20080403') return array(3,14);
	if ($d=='20080405') return array(4,1);
	if ($d=='20080412') return array(4,2);
	if ($d=='20080419') return array(4,3);
	if ($d=='20080426') return array(4,4);
	if ($d=='20080503') return array(4,5);
	if ($d=='20080510') return array(4,6);
	if ($d=='20080517') return array(4,7);
	if ($d=='20080531') return array(4,8);
	if ($d=='20080607') return array(4,9);
	if ($d=='20080614') return array(4,10);
	if ($d=='20080621') return array(4,11);
	return array(0,0);
}

# Times from manually looking
function time_index($s, $e, $t) {
	$t = substr($t, 0, 2) * 3600 + substr($t, 3, 2) * 60 + substr($t, 6);
	if ($s==2 && $e==14) return $t / 60/60;
	if ($s==3 && $e==14) return $t / 75/60;
	if ($s==4 && ($e==1 || $e==2)) return $t / 48/60;
	if ($s==4 && ($e==7 || $e==10)) return $t / 44/60;
	if ($s==4 && $e==11) return $t / 49/60;
	if ($s==2 && $e==9) return $t / 49/60;
	if ($s==2 && $e==13) return $t / 46/60;
	if ($s==3 && $e==11) return $t / 46/60;
	if ($s==3 && $e==12) return $t / 46/60;
	if ($s==3 && $e==13) return $t / 51/60;
	if ($s==4 && $e==13) return $t / 63/60;
	return $t / 45/60;
}

# For most common use display
function episode_lookup($n) {
	$eps = array(
		'1-1' => 'Rose',
		'1-2' => 'The End of the World',
		'1-3' => 'The Unquiet Dead',
		'1-4' => 'Aliens of London',
		'1-5' => 'World War Three',
		'1-6' => 'Dalek',
		'1-7' => 'The Long Game',
		'1-8' => 'Father&rsquo;s Day',
		'1-9' => 'The Empty Child',
		'1-10' => 'The Doctor Dances',
		'1-11' => 'Boom Town',
		'1-12' => 'Bad Wolf',
		'1-13' => 'The Parting of the Ways',
		'1-14' => 'Christmas Invasion',
		'2-1' => 'New Earth',
		'2-2' => 'Tooth and Claw',
		'2-3' => 'School Reunion',
		'2-4' => 'The Girl in the Fireplace',
		'2-5' => 'Rise of the Cybermen',
		'2-6' => 'The Age of Steel',
		'2-7' => 'The Idiot&rsquo;s Lantern',
		'2-8' => 'The Impossible Planet',
		'2-9' => 'The Satan Pit',
		'2-10' => 'Love and Monsters',
		'2-11' => 'Fear Her',
		'2-12' => 'Army of Ghosts',
		'2-13' => 'Doomsday',
		'2-14' => 'Runaway Bride',
		'3-1' => 'Smith and Jones',
		'3-2' => 'The Shakespeare Code',
		'3-3' => 'Gridlock',
		'3-4' => 'Daleks in Manhattan',
		'3-5' => 'Evolution of the Daleks',
		'3-6' => 'The Lazarus Experiment',
		'3-7' => '42',
		'3-8' => 'Human Nature',
		'3-9' => 'The Family of Blood',
		'3-10' => 'Blink',
		'3-11' => 'Utopia',
		'3-12' => 'The Sound of Drums',
		'3-13' => 'Last of the Time Lords',
		'3-14' => 'Voyage of the Damned',
		'4-1' => 'Partners in Crime',
		'4-3' => 'Planet of the Ood',
		'4-5' => 'The Poison Sky',
		'4-7' => 'The Unicorn and the Wasp',
		'4-9' => 'Forest of the Dead',
		'4-11' => 'Turn Left',
		'4-2' => 'The Fires of Pompeii',
		'4-4' => 'The Sontaran Stratagem',
		'4-6' => 'The Doctor&rsquo;s Daughter',
		'4-8' => 'Silence in the Library',
		'4-10' => 'Midnight',
		'4-12' => 'The Stolen Earth',
		'4-13' => "Journey's End",
        '4-14' => 'The Next Doctor',
        '4-15' => 'Planet of the Dead',
        '4-16' => 'The Waters of Mars',
        '4-17' => 'The End of Time (1)',
        '4-18' => 'The End of Time (2)',
        '5-1' => 'The Eleventh Hour',
        '5-2' => 'The Beast Below',
        '5-3' => 'Victory of the Daleks',
        '5-4' => 'The Time of Angels',
        '5-5' => 'Flesh and Stone',
        '5-6' => 'The Vampires of Venice',
        '5-7' => 'Amy&rsquo;s Choice',
        '5-8' => 'The Hungry Earth',
        '5-9' => 'Cold Blood',
        '5-10' => 'Vincent and the Doctor',
        '5-11' => 'The Lodger',
        '5-12' => 'The Pandorica Opens',
        '5-13' => 'The Big Bang',
        '5-14' => 'A Christmas Carol',
        '6-1' => 'The Impossible Astronaut',
        '6-2' => 'Day of the Moon',
        '6-3' => 'The Curse of the Black Spot',
        '6-4' => 'The Doctor&rsquo;s Wife',
        '6-5' => 'The Rebel Flesh',
        '6-6' => 'The Almost People',
        '6-7' => 'A Good Man Goes to War',
        '6-8' => 'Let&rsquo;s Kill Hitler',
        '6-9' => 'Night Terrors',
        '6-10' => 'The Girl Who Waited',
        '6-11' => 'The God Complex',
        '6-12' => 'Closing Time',
        '6-13' => 'The Wedding of River Song',
        '6-14' => 'The Doctor, the Widow and the Wardrobe',
        '7-1' => 'Asylum of the Daleks',
        '7-2' => 'Dinosaurs on a Spaceship',
        '7-3' => 'A Town Called Mercy',
        '7-4' => 'The Power of Three',
        '7-5' => 'The Angels Take Manhattan',
        '7-6' => 'The Snowmen',
	);
	return $eps[$n];
}

function front_page() {
    global $episodes;
?>

<div id="examples">
Some example searches:
<a href="/?q=rose">Rose</a> /
<a href="/?q=martha">Martha</a> /
<a href="/?q=donna">Donna</a> /
<a href="/?q=amy">Amy</a> /
<a href="/?q=rory">Rory</a>,
<a href="/?q=tardis">TARDIS</a>,
<a href="/?q=tyler+-rose">Tyler&nbsp;-Rose</a>,
<a href="/?q=sonic">sonic</a>,
<a href="/?q=silence">silence</a>,
<a href="/?q='very+clever'">"very clever"</a>,
<a href="/?noise=1"><i>all stage directions</i></a>,
<a href="/?q=doctor+colour:cyan"><i>all cyan subtitles with &ldquo;doctor&rdquo;</i></a>
</div>

<p>This site takes the
subtitles from Doctor Who and makes them searchable; note that there are currently a few
<a id="missing_link" href="#missing">missing areas</a>.
You can search by word, phrase, stage direction-ness, subtitle colour or
position, series, episode, or time within episode. Episodes in series 1 to 4 have
a representative tag cloud, and search results have a line graph showing usage
throughout the series. All subtitles on search results are clickable to go to
that point in the full episode list of subtitles.
</p>

<p>
<a href="http://twitter.com/dracos" class="twitter-follow-button" data-show-count="false">Follow @dracos</a>
<script src="http://platform.twitter.com/widgets.js" type="text/javascript"></script>
</p>

<ul id="front_eps">
<?php

for ($s=1; $s<=NUM_SERIES; $s++) {
	for ($e=1; $e<=$episodes[$s]; $e++) {
		if ($s==3 && $e==5) continue;
		echo '<li>';
		if (!file_exists("images/$s-{$e}S.png")) {
			#echo '<small><i>pic coming soon</i></small><br><br>';
            echo '<br><br>';
        }
		if ($s!=3 || $e!=5) echo '<a href="/?q=series:', $s, '+ep:', $e, '">';
		if (file_exists("images/$s-{$e}S.png"))
			echo '<img alt="" src="images/', $s, '-', $e, 'S.png"><br>';
		echo episode_lookup("$s-$e");
		if ($s!=3 || $e!=5) echo '</a>';
	}
}

?>
</ul>

<div id="missing">
<p>Here&rsquo;s the precise details of what I have:</p>

<ul>
<li>Series 1: All of episodes 1, 4, 5, 7, 9, and 13; almost all of episode 10; around 30 minutes of episodes 3 and 12; around 20 minutes of episodes 2 and 6; 13 minutes of episodes 8 and 11.
<li>20 minutes of Christmas Invasion
<li>Series 2: All of episodes 3, 4, 9, 10, 13; around 35 minutes of episodes 6, 7, and 8; around 20&ndash;25 minutes of episodes 5 and 11; and around 15 minutes for episodes 1, 2, and 12.
<li>All of The Runaway Bride
<li>Series 3: All of episodes 1, 3, 6, 7, 9, 10, 11, 12, 13; around half an hour of episodes 2 and 8; 13 minutes of episode 4; none of episode 5.
<li>13.5 minutes of Voyage of the Damned
<li>Series 4: All of episodes 1, 2, 4, 7, 10, 11, 12, and 13; 37.5 minutes of episode 9; only 10&ndash;13 minutes of episodes 3, 5, 6, and 8.
<li>Specials between series 4 and 5: All of them.
<li>Series 5-7: All episodes.
</ul>
</div>

<?php
}
