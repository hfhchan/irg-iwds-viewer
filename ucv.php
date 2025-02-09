<?
$compat = [];
$compat_a = array_map(function($line){
	$line = explode(';',$line);
	return [$line[0],$line[5]];
}, file('compat_mapping.txt'));

foreach ($compat_a as $pair) {
	$compat[$pair[0]] = $pair[1];
}

function toCompat($name) {
	global $compat;
	return 'u'.strtolower($compat[strtoupper(substr($name,1))]);
}

function nameToChar($name) {
	if (preg_match('@^u[0-9a-f]{4,5}$@', $name)) {
		return iconv('UTF-32BE', 'UTF-8', pack("H*", str_pad(substr($name, 1), 8, '0', STR_PAD_LEFT)));
	}
	throw new Exception('Invalid Input');
}
function html_safe($str) {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
function codepointToChar($codepoint) {
	if (preg_match('@^U\+[0-9A-F]{4,5}$@', $codepoint)) {
		return iconv('UTF-32BE', 'UTF-8', pack("H*", str_pad(substr($codepoint, 2), 8, '0', STR_PAD_LEFT)));
	}
	throw new Exception('Invalid Input');
}
function parseStringIntoCodepointArray($utf8) {
	$result = [];
	for ($i = 0; $i < strlen($utf8); $i++) {
		$char = $utf8[$i];
		$ascii = ord($char);
		if ($ascii < 128) {
			if ($char === '&') {
				$j = $i + 1;
				while (isset($utf8[$j]) && ord($utf8[$j]) < 128) {
					$j++;
				}
				$result[] = substr($utf8, $i, $j - $i);
				$i = $j - 1;
			} else {
				$result[] = $char;
			}
		} else if ($ascii < 192) {
		} else if ($ascii < 224) {
			$ascii1 = ord($utf8[$i+1]);
			if( (192 & $ascii1) === 128 ){
				$result[] = substr($utf8, $i, 2);
				$i++;
			}
		} else if ($ascii < 240) {
			$ascii1 = ord($utf8[$i+1]);
			$ascii2 = ord($utf8[$i+2]);
			
			if( (192 & $ascii1) === 128 ||
				(192 & $ascii2) === 128 ){
				$unicode = (15 & $ascii) * 4096 +
						   (63 & $ascii1) * 64 +
						   (63 & $ascii2);
				$result[] = 'U+'.strtoupper(dechex($unicode));
				$i += 2;
			}
		} else if ($ascii < 248) {
			$ascii1 = ord($utf8[$i+1]);
			$ascii2 = ord($utf8[$i+2]);
			$ascii3 = ord($utf8[$i+3]);
			
			if( (192 & $ascii1) === 128 ||
				(192 & $ascii2) === 128 ||
				(192 & $ascii3) === 128 ){
				$unicode = (15 & $ascii) * 262144 +
						   (63 & $ascii1) * 4096 +
						   (63 & $ascii2) * 64 +
						   (63 & $ascii3);
				$result[] = 'U+'.strtoupper(dechex($unicode));
				$i += 3;
			}
		}
	}
	return $result;
}
function html_esc($utf8){
	$result = [];
	for ($i = 0; $i < strlen($utf8); $i++) {
		$char = $utf8[$i];
		$ascii = ord($char);
		if ($ascii < 128) {
			$result[] = $char;
		} else if ($ascii < 192) {
		} else if ($ascii < 224) {
			$ascii1 = ord($utf8[$i+1]);
			if( (192 & $ascii1) === 128 ){
				$result[] = substr($utf8, $i, 2);
				$i++;
			}
		} else if ($ascii < 240) {
			$ascii1 = ord($utf8[$i+1]);
			$ascii2 = ord($utf8[$i+2]);
			
			if( (192 & $ascii1) === 128 ||
				(192 & $ascii2) === 128 ){
				$unicode = (15 & $ascii) * 4096 +
						   (63 & $ascii1) * 64 +
						   (63 & $ascii2);
				$result[] = 'u'.dechex($unicode);
				$i += 2;
			}
		} else if ($ascii < 248) {
			$ascii1 = ord($utf8[$i+1]);
			$ascii2 = ord($utf8[$i+2]);
			$ascii3 = ord($utf8[$i+3]);
			
			if( (192 & $ascii1) === 128 ||
				(192 & $ascii2) === 128 ||
				(192 & $ascii3) === 128 ){
				$unicode = (15 & $ascii) * 262144 +
						   (63 & $ascii1) * 4096 +
						   (63 & $ascii2) * 64 +
						   (63 & $ascii3);
				$result[] = 'u'.dechex($unicode);
				$i += 3;
			}
		}
	}
	return $result;
}
function getURL($url) {
	$ch = curl_init();
	if (!$ch) echo 'Failed to start cURL';
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 2);
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; rv:40.0) Gecko/20100101 Firefox/40.1");
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	@curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
	@curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 20);
	@curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 20);
	$data = curl_exec($ch);
	if (!$data) {
		$data = curl_exec($ch);
	}
	if (!$data) {
		echo 'Failed to fetch json:<br>';
		echo html_safe(curl_error($ch));
		echo '<hr>';
		echo html_safe($url);
		exit;
	}

	list($headers, $content) = explode("\r\n\r\n",$data,2);

	return $content;
}

$iwdsVersions = [
	'IRGN2242' => [
		'date' => '2017.08.03',
		'url' => 'https://raw.githubusercontent.com/kawabata/iwds/4684ead859bdb8e2c1139ec3cbe81f9242861045/iwds.xml',
		'sha1' => 'ba5e143539794d4248f5d3cccaa2050ff22dbde4'
	],
	'IRGN2387' => [
		'date' => '2019.10.26',
		'url' => 'https://raw.githubusercontent.com/kawabata/iwds/bd8cb2d158799116a38bd1968ca03695caff97ac/iwds.xml',
		'sha1' => '84e7cd7d6256784459012974b6a9f344b060824f'
	],
	'IRGN2425' => [
		'date' => '2020.02.26',
		'url' => 'https://raw.githubusercontent.com/kawabata/iwds/abf0a971e9112a50f55d225c073608e4b89fd17d/iwds.xml',
		'sha1' => '29e5548430f97de68297abcb482af1a951a78246'
	],
	'IRGN2476' => [
		'date' => '2021.06.11',
		'url' => 'https://raw.githubusercontent.com/yi-bai/iwds/d5cf2b48a55b9f15adca0e0267dfe3633841dc70/iwds.xml',
		'sha1' => '1d2ff27b9937874459c0f0777fac7f262062237c'
	],
	'IRGN2514' => [
		'date' => '2021.09.30',
		'url' => 'https://raw.githubusercontent.com/yi-bai/iwds/43fd9ba0339b6690a981b9f4788f6f8cd67f04eb/iwds.xml',
		'sha1' => '6d773807624706d02cf32dd625f0bc4e62a11f64'
	],
	'IRGN2548' => [
		'date' => '2022.04.06',
		'url' => 'https://raw.githubusercontent.com/yi-bai/iwds/f41954aa2fe7aff62f551ae05a594f670efefb3a/iwds.xml',
		'sha1' => 'efacb09265d72453170a32e8d48fae6c399e7817'
	],
	'IRGN2584' => [
		'date' => '2022.10.29',
		'url' => 'https://raw.githubusercontent.com/yi-bai/iwds/ef1df69833db4aab531c3bf88e7d83bd7776f08a/iwds.xml',
		'sha1' => '5a873fcf248945de78333a4244e406bffc7cbcd4'
	],
	'IRGN2584' => [
		'date' => '2023.10.30',
		'url' => 'https://raw.githubusercontent.com/yi-bai/iwds/298a5cf8fc50ab97f06d53093eefed78cbafe909/iwds.xml',
		'sha1' => 'c65f12996492c8c7df15689ee90b321a563c47c9'
	],
	'n2680' => [
		'date' => '2024.03.30',
		'url' => 'https://raw.githubusercontent.com/yi-bai/iwds/a1953eb6830c8ae9a924598c8d7051b0802b1297/iwds.xml',
		'sha1' => '6a0209ba5651c8a755271736eefe4ada6a8a1e2d'
	],
];

$json = getURL('https://api.github.com/repos/yi-bai/iwds/contents/iwds.xml');
$data = json_decode($json);

$failed_to_check_new_version = false;
if (isset($data->message) && strpos($data->message, 'API rate limit exceeded') !== false) {
	$failed_to_check_new_version = true;
} else {
	$failed_to_check_new_version = false;
	$sha = $data->sha;
	$checksums = array_map(function($entry) { return $entry['sha1']; }, $iwdsVersions);
	if (!in_array($sha, $checksums)) {
		$iwdsVersions[$data->sha] = [
			'date' => 'New',
			'url' => 'https://raw.githubusercontent.com/yi-bai/iwds/master/iwds.xml'
		];
	}
}

if (!function_exists('array_key_last')) {
	function array_key_last($arr) {
		end($arr);
		return key($arr);
	}
}

$selectedVersion = isset($_GET['version']) && isset($iwdsVersions[$_GET['version']]) ? $_GET['version'] : array_key_last($iwdsVersions);

$iwds = getURL($iwdsVersions[$selectedVersion]['url']);
$iwds = simplexml_load_string($iwds);


?>


<!doctype html>
<html>
<meta charset=utf-8>
<title>IWDS (<?=$iwdsVersions[$selectedVersion]['date']?>)</title>
<style>
body{font-family:Arial,sans-serif;margin:0;background:#eee}
h1,h2,h3{margin:10px 0}
table{border-collapse:collapse;border:1px solid #ccc;width:100%}
th,td{padding:10px;text-align:left;border:1px solid #ccc;vertical-align:top}

div.table{border:1px solid #ccc}
div.tr{display:flex}
div.tr:not(:first-child){border-top:1px solid #ccc}
div.th{padding:10px;text-align:left;border-right:1px solid #ccc;flex:0 0 200px}
div.td{padding:10px;text-align:left;flex:1 1 auto}

pre{margin:10px 0;white-space:pre-wrap}
img{background:#fff}
.main{width:1280px;margin:20px auto;background:#fff;padding:40px}
.toc-group{margin:10px 0;display:flex;flex-wrap:wrap}
.toc-group a{text-decoration:none;position:relative;color:#000;flex:none;width:210px;border:1px solid #ccc;padding:10px;margin:2px}
.toc-group a:hover{background:#eee}
.toc-group img{width:40px;height:40px}
.glyph{border:1px solid #ccc}

h3{font-size:18px;margin:10px 0 4px}
.small-char{vertical-align:text-bottom}
.supercjk{max-width:100%}
.ucs-2017{max-width:100%}
.disunified{table-layout:fixed}
ul{margin:10px 10px 10px 30px;padding:0}
li{margin:10px 0}

@media(max-width:1200px) {
	.main{width:960px;margin:10px auto;background:#fff;padding:10px}
	h1{font-size:20px}
	ul{margin:10px 10px 10px 20px}
	.toc-group a{width:164px;padding:5px;margin:1px}
	.toc-group img{width:32px;height:32px}
}

.kind-not-unifiable::before{position:absolute;top:0;left:0;right:0;content:"";border-top:3px solid #f00}
</style>
<div class="main">
	<div>IWDS source: <a href="https://github.com/yi-bai/iwds" target=_blank>https://github.com/yi-bai/iwds</a></div>
<?
if ($failed_to_check_new_version) {
?>
	<div style="color:red;font-size:13px;margin:5px 0">(Failed to check for new versions)</div>
<?
}
?>
<?
foreach (array_reverse($iwdsVersions) as $key => $version) {
?>
<a href="?version=<?=$key?>"><?=$key?> - <?=$version['date']?></a><br>
<?
}
?>
</div>
<div class="main">
	<h1 style="margin:0 0 4px">IWDS (<?=$iwdsVersions[$selectedVersion]['date']?>)</h1>
	<div style="font-size:13px;margin:0 0 20px;overflow:auto"><?=$iwdsVersions[$selectedVersion]['url']?></div>
	<h2>Table of Contents</h2>
<?
echo '<ul>';
foreach ($iwds->group as $section) {
	echo '<li><a href="#ucv_'.$section['id'].'">' . $section['en'] . '</a>';
	
	if ($section->subgroup) {
		echo '<ul>';
		foreach ($section->subgroup as $subgroup) {
			echo '<li><a href="#ucv_'.$subgroup['id'].'">' . $subgroup['en'] . '</a>';
			parseGroupSimp($subgroup, $section['id'], $subgroup['id']);
			echo '</li>';
		}
		echo '</ul>';
	} else {
		parseGroupSimp($section, $section['id']);
	}
	echo '</li>';
}
echo '</ul>';
?>
</div>



<div class="main">
<?


function parseGroupSimp($group, $id, $id2 = '') {
	if (count($group->entry) != count($group)) {
		echo '<div>Warning Additional Items!</div>';
	}
	echo '<div class=toc-group>';
	foreach ($group->entry as $entry) {
		echo '<a href="?show='.html_safe($entry['id']).'#entry-'.html_safe($entry['id']).'" class="kind-' . htmlspecialchars($entry['kind']) . '"><b>'.$entry['id'].'</b> &middot; '.$entry['kind'];
		echo ' ';
		$components = parseStringIntoCodepointArray('' . $entry->components);
		foreach ($components as $component) {
			if (strlen($component) >= 6 && $component[0] === 'U') {
				$usv = hexdec(substr($component, 2));
				if ($usv > 0xE000 && $usv < 0xFFFF) {
					//$cdp = $usv + 0x834E - 0xF000;
					//$glyph = 'cdp-' . dechex($usv) . '.png';
					//echo '<img src="https://raw.githack.com/kawabata/iwds/master/glyphs/'.$glyph.'" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
				} else {
					echo codepointToChar($component);
				}
			} else {
				echo htmlspecialchars($component);
			}
		}
		echo '<br>';
		$glyphs = explode(',', $entry->glyphs);
		foreach ($glyphs as $glyph) {
			if (strpos($glyph, 'u2ff') === 0 || true) {
				// echo '<img src="https://cdn.jsdelivr.net/gh/yi-bai/iwds@master/glyphs/'.html_safe($glyph).'.png" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
				// echo '<img src="https://raw.githack.com/kawabata/iwds/master/glyphs/'.html_safe($glyph).'.png" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
				echo '<img src="https://raw.githack.com/yi-bai/iwds/master/glyphs/'.html_safe($glyph).'.png" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
			} else {
				// echo '<img src="https://cdn.jsdelivr.net/gh/yi-bai/iwds@master/glyphs/'.html_safe($glyph).'.svg" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
				// echo '<img src="https://raw.githack.com/kawabata/iwds/master/glyphs/'.html_safe($glyph).'.svg" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
				echo '<img src="https://raw.githack.com/yi-bai/iwds/master/glyphs/'.html_safe($glyph).'.svg" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
			}
		}
		echo '</a>';
	}
	echo '</div>'."\r\n";
}

function displayList($list, $tag = 'div', $compat = false, $replaceImage = false) {
	foreach ($list as $i => $char) {
		if ($char === '*' || $char === '#' || $char === '$') {
			continue;
		}
		echo "\t\t\t\t\t".'<'.$tag.'>';
		echo nameToChar($char);
		echo '<a href="https://zi.tools/zi/' . urlencode(nameToChar($char)) . '" target=_blank><img src="http://en.glyphwiki.org/glyph/hkcs_m' . html_safe(substr($char, 1)) . '.svg" width=20 height=20 class=small-char loading=lazy></a>';
		echo ' U+' . strtoupper(substr($char,1));
		if ($compat) {
			echo ' = ';
			echo nameToChar(toCompat($char));
			echo ' U+' . strtoupper(substr(toCompat($char),1));
		}
		echo '<br>';
		if ($replaceImage) {
			$paddedUSV = sprintf('%05s',strtoupper(substr($char,1)));
			$folder = ltrim(substr($paddedUSV, 0, 3), '0');
			$usv = ltrim($paddedUSV, '0');
			$imageSrc = 'https://hc.jsecs.org/Code%20Charts/UCSv13/Excerpt/'.$folder.'/U+'.$usv.'.png';
			echo '<img srcset="'.html_safe($imageSrc).' 1.2x" loading=lazy>';
		} else if (isset($list[$i + 1]) && $list[$i + 1] === '$') {
			echo '<img srcset="https://raw.githack.com/kawabata/iwds/master/ucs2014/'.html_safe(sprintf('%05s',strtoupper(substr($char,1)))).'.png 5x" class=ucs-2017 loading=lazy>';
		} else if (isset($list[$i + 1]) && $list[$i + 1] === '*') {
			echo '<img src="https://raw.githack.com/kawabata/iwds/master/ucs2003/'.html_safe(sprintf('%05s',strtoupper(substr($char,1)))).'.png" class=ucs-2013 loading=lazy>';
		} else if (isset($list[$i + 1]) && $list[$i + 1] === '#') {
			echo '<img srcset="https://raw.githack.com/kawabata/iwds/master/supercjk/'.html_safe(sprintf('%05s',strtoupper(substr($char,1)))).'.png 8x" class=supercjk loading=lazy>';
		} else {
			echo '<img srcset="https://raw.githack.com/kawabata/iwds/master/ucs2017/'.html_safe(sprintf('%05s',strtoupper(substr($char,1)))).'.png 5x" class=ucs-2017 loading=lazy>';
		}
		echo '</'.$tag.'>'."\r\n";
		
	}
}
function printEntry($entry) {
	echo "\t\t" . '<div class=table>' . "\r\n";
	
	$positive = 0;
	$negative = 0;
	
	echo "\t\t\t" . '<div class=tr id="entry-' . html_safe($entry['id']) . '">' . "\r\n";
	echo "\t\t\t" . '<div class=th>' . $entry['id'] . '<br>' . $entry['kind'] . '</div>' . "\r\n";
	echo "\t\t\t" . '<div class=td>' . "\r\n";
	$glyphs = explode(',', $entry->glyphs);
	foreach ($glyphs as $glyph) {
		echo "\t\t\t\t";
		if (strpos($glyph, 'u2ff') === 0 || true) {
			// echo '<img src="https://cdn.jsdelivr.net/gh/yi-bai/iwds@master/glyphs/'.html_safe($glyph).'.png" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
			echo '<img src="https://raw.githack.com/yi-bai/iwds/master/glyphs/'.html_safe($glyph).'.png" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
		} else {
			// echo '<img src="https://cdn.jsdelivr.net/gh/yi-bai/iwds@master/glyphs/'.html_safe($glyph).'.svg" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
			echo '<img src="https://raw.githack.com/yi-bai/iwds/master/glyphs/'.html_safe($glyph).'.svg" width=50 height=50 class=glyph title="'.html_safe($glyph).'" loading=lazy>';
		}
		echo "\r\n";
	}
	if (isset($entry->components)) {
		echo '<div>'.html_safe($entry->components . '').'</div>'."\r\n";
	}
	if (isset($entry->jis)) {
		echo '<h3>JIS</h3>' . "\r\n";
		array_map(function($jis) {
			echo '<div><img src="https://raw.githack.com/kawabata/iwds/master/fig/jis.'.html_safe($jis . '').'.gif" loading=lazy></div>';
		}, explode(',',$entry->jis));
		echo "\r\n";
	}
	if (isset($entry->hydcd)) {
		echo '<h3>HYDCD</h3><div><img src="https://raw.githack.com/kawabata/iwds/master/fig/xinjiu'.html_safe($entry->hydcd . '').'.png"></div>'."\r\n";
	}

	if (isset($entry->unified)) {
		$unified = html_esc($entry->unified . '');
		if (isset($unified[count($unified) - 1]) && $unified[count($unified) - 1] === 'u2026') {
			array_pop($unified);
			echo '<h3>Unified Ideographs (Examples)</h3>' . "\r\n";
		} else {
			echo '<h3>Unified Ideographs</h3>' . "\r\n";
		}
		echo "<div>\r\n";
		displayList($unified);
		echo "</div>\r\n";
		$positive += count($unified);
	}

	if (isset($entry->compatibles)) {
		echo '<h3>Compatibility Ideographs</h3>' . "\r\n";
		$compatibles = html_esc($entry->compatibles . '');
		displayList($compatibles, 'div', true);
		$positive += count($compatibles);
	}

	if (isset($entry->SourceCodeSeparation)) {
		echo '<h3>Source Code Separations</h3>' . "\r\n";
		echo '<table class=disunified>';
		$scs = explode(',',$entry->SourceCodeSeparation . '');
		foreach ($scs as $pair) {
			echo '<tr>';
			$pair = html_esc($pair);
			displayList($pair, 'td');
			echo '</tr>';
		}
		echo '</table>'."\r\n";
		$positive += count($scs);
	}

	if (isset($entry->disunified)) {
		echo '<h3>Disunified Ideographs</h3>' . "\r\n";
		echo '<table class=disunified>'."\r\n";
		$disunified = explode(',',$entry->disunified . '');
		foreach ($disunified as $pair) {
			echo '<tr>'."\r\n";
			$pair = html_esc($pair);
			displayList($pair, 'td');
			echo '</tr>'."\r\n";
		}
		echo '</table>'."\r\n";
		$negative += count($disunified);
	}

	if (isset($entry->single)) {
		echo '<h3>Single</h3>'."\r\n";
		echo "<table class=single><tr>\r\n";
		foreach ($entry->single as $tag) {
			echo "<th>" . $tag->attributes()->char . "</th>\r\n";
		}
		echo "</tr><tr>\r\n";
		foreach ($entry->single as $tag) {
			echo "<td>\r\n";
			$single = html_esc($tag . '');
			displayList($single, 'div', false, true);
			echo "</td>\r\n";
			$negative += count($single);
		}
		echo "</tr></table>\r\n";
	}

	if (isset($entry->note)) {
		echo '<h3>Notes</h3>' . "\r\n";
		echo '<p>' . html_safe($entry->note . '') . '</p>' . "\r\n";
	}

	if (isset($entry->ReviewSystem)) {
		echo '<h3>Discussion</h3>' . "\r\n";
		$link = $entry->ReviewSystem . '';
		echo '<p><a href="' . html_safe($link) . '" target=_blank>' . html_safe($link) . '</a></p>' . "\r\n";
	}

	if (isset($entry->annexs)) {
		echo '<h3>Annex</h3>' . "\r\n";
		echo '<p>' . html_safe($entry->annexs . '') . '</p>' . "\r\n";
	}
	
	if ($positive > 0 || $negative > 0) {
		$rate = $positive / ($positive + $negative);
		if ($rate > 0.8)
			echo '<p><b>Rule Application Rate</b>: ' . number_format( $rate * 100, 2). '</p>'."\r\n";
		else
			echo '<p><b>Rule Application Rate</b>: <span style="color:red">' . number_format( $rate * 100, 2). '</span></p>'."\r\n";
	} else {
		echo '<p><b>Rule Application Rate</b>: N/A</p>'."\r\n";
	}
	unset($entry->glyphs);
	unset($entry->jis);
	unset($entry->hydcd);
	unset($entry->unified);
	unset($entry->compatibles);
	unset($entry->components);
	unset($entry->SourceCodeSeparation);
	unset($entry->disunified);
	unset($entry->single);
	unset($entry->note);
	unset($entry->ReviewSystem);
	unset($entry->annexs);
	if (count($entry)) {
		echo '<div><b>Additional Attributes:</b></div>';
		echo '<pre>';
		var_dump($entry);
		echo '</pre>'."\r\n";
	}
	echo '</div>';
	echo '</div>'."\r\n";
	echo '</div>'."\r\n";
}

$show = isset($_GET['show']) ? $_GET['show'] : '';

function inGroup($id, $group) {
	foreach ($group->entry as $entry) {
		if ($entry['id'] == $id) {
			return true;
		}
	}
	return false;
}

foreach ($iwds->group as $section) {
	$inGroup = false;
	if ($section->subgroup) {
		foreach ($section->subgroup as $subgroup) {
			foreach ($subgroup->entry as $entry) {
				if ($entry['id'] . "" === $show || $show === 'all') {
					echo "\t" . '<h1 id="ucv_'.$section['id'].'">' . $section['en'] . '</h1>' . "\r\n";
					echo "\t" . '<h2 id="ucv_'.$subgroup['id'].'">' . $subgroup['en'] . '</h2>' . "\r\n";
					printEntry($entry);
				}
			}
		}
	} else {
		foreach ($section->entry as $entry) {
			if ($entry['id'] . "" === $show || $show === 'all') {
				echo "\t" . '<h1 id="ucv_'.$section['id'].'">' . $section['en'] . '</h1>' . "\r\n";
				printEntry($entry);
			}			
		}
	}
}

?>
</div>
