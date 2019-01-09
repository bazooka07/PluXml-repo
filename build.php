#!/usr/bin/env php
<?php

const SITE_TITLE = 'Kazimentou';
const SITE_AUTHOR = 'Jean-Pierre Pourrez';
const SITE_DESCRIPTION = 'Plugins, thèmes, scripts pour le C.M.S. PluXml';

const WORKDIR = 'workdir/';
const ASSETS = WORKDIR.'assets/';
const LAST_RELEASES = WORKDIR.'latest/';
const RSS = WORKDIR.'rss/';
const REPO_XML = WORKDIR.'xml/';

define('VERSION', date('Y-m-d', filemtime(__FILE__)));

const INFOS_PATTERN = '@^[^/]+/infos\.xml$@';
const GIT_PATTERN = '@^\w+\s+(https?)://github.com/([\w-]+)/([^/]+)\.git\b@';
const JSON_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

const EXT_RSS = '.xml';

const BEGIN_REPO_XML = <<< EOT
<?xml version="1.0" encoding="UTF-8"?>
<document>\n
EOT;
const END_REPO_XML = <<< EOT
\n</document>\n
EOT;

$DEFAULT_IMGS = array(
	'plugins'	=> 'default-icon.png',
	'themes'	=> 'default-theme.png',
	'scripts'	=> 'default-icon.png'
);

function getHostnameFromGit() {
	// origin	https://github.com/bazooka07/pluxml-repository-static.git (fetch)
	//  https://bazooka07.github.io/pluxml-repository-static/
	if(!is_dir('.git')) { return array('', ''); }

	exec('git remote -v', $output, $result);
	if(
		$result == 0 and
		preg_match(GIT_PATTERN, $output[0], $matches)
	) {
		return array($matches[1].'://'.$matches[2].'.github.io', '/'.$matches[3].'/');
	}

	return array('', '');
}

/*
 * Recherche dans l'archive Zip, une entrée finissant par "/infos.xml"
 * */
function searchInfosFile(ZipArchive $zipFile) {
	for ($i=0; $i<$zipFile->numFiles; $i++) {
		$filename = $zipFile->getNameIndex($i);
		if(preg_match(INFOS_PATTERN, $filename)) {
			return $filename;
			break;
		}
	}

	return false;
}

/*
 * Recherche la déclaration d'une classe dans un fichier .php
 * dans l'archive Zip, pour connaitre le nom réel du plugin
 * */
function getPluginName(ZipArchive $zipFile) {
	$result = false;
	for ($i=0; $i<$zipFile->numFiles; $i++) {
		$filename = $zipFile->getNameIndex($i);
		if(substr($filename, -4) == '.php') {
			$content = $zipFile->getFromName($filename);
			if(preg_match('/\bclass\s+(\w+)\s+extends\s+plxPlugin\b/', $content, $matches) === 1) {
				$result = $matches[1];
				break;
			} else if(preg_match('/^([\w-]+)\/(?:article|static[^\.]*)\.php$/', $filename, $matches) === 1) {
				// archive zip d'un théme demandé par niqnutn
				$result = $matches[1];
				break;
			}
		}
	}
	return $result;
}

function getThemeName(ZipArchive $zipFile) {
	return preg_replace('@^(\w[^/]*).*@', '$1',$zipFile->getNameIndex(0));
}

function buildRSS($page, &$cache, $root) {
	$filename = WORKDIR.$page.'.json';
	$lastBuildDate = date('r', filemtime($filename));
	$sitename = SITE_TITLE;
	$href = $root.$page.EXT_RSS;
	$description = SITE_DESCRIPTION;
	$ver = VERSION;
	$header = <<< RSS_STARTS
<?xml version="1.0"  encoding="UTF-8" ?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>$sitename, playstore for Pluxml</title>
		<link>${root}index.html</link>
		<description>$description</description>
		<lastBuildDate>$lastBuildDate</lastBuildDate>
		<atom:link xmlns:atom="http://www.w3.org/2005/Atom" rel="self" type="application/rss+xml" href="$href" />
		<generator>Repository for Pluxml v3.0 ($ver)</generator>
		<skipDays>
			<day>Sunday</day>
		</skipDays>\n
RSS_STARTS;

	$temp = array();
	$output = array();
	if(is_array($cache)) {
		foreach($cache as $item=>$infos) {
			$lastVersion = array_keys($infos['versions'])[0];
			$lastRelease = $infos['versions'][$lastVersion];
			if(!empty($infos['img'])) { $lastRelease['img'] = $infos['img']; }
			$temp[$lastRelease['filedate'].'-'.$item] = $lastRelease;
		}

		if(!empty($temp)) {
			krsort($temp);
			$lastUpdates = array_slice($temp, 0, 10); // 10 items for the RSS feed
			foreach($lastUpdates as $item=>$infos) {
				$title = $infos['title'];
				$pubDate = date('r', strtotime($infos['filedate']));
				$description = htmlspecialchars($infos['description'], ENT_COMPAT | ENT_XML1);
				$author = htmlspecialchars($infos['author'], ENT_COMPAT | ENT_XML1);
				$guid = $page.'-'.$item;

				if(!empty($infos['img']) and file_exists($infos['img'])) {
					$length = filesize($infos['img']);
					$size = getimagesize($infos['img']);
					$enclosure = <<< ENCLOSURE
\n			<enclosure url="${root}${infos['img']}" length="$length" type="${size['mime']}" />
ENCLOSURE;
				} else {
					$enclosure = '';
				}

				$output[] = <<< ITEM
		<item>
			<title>$title</title>
			<link>${root}${infos['download']}</link>
			<description>$description</description>
			<dc:creator>$author</dc:creator>
			<pubDate>$pubDate</pubDate>$enclosure
			<guid>$guid</guid>
		</item>\n
ITEM;
			}
		}
	}
	$footer =  <<< RSS_ENDS
	</channel>
</rss>\n
RSS_ENDS;

	file_put_contents(RSS.$page.EXT_RSS, $header.implode("\n", $output).$footer);
}

function buildXML(&$datas) {

	$root = $datas['hostname'].$datas['urlBase'];
	$output = array();
	if(is_array($datas['items'])) {
		foreach($datas['items'] as $item=>$infos) {
			$img = (!empty($infos['img'])) ? $infos['img'] : 'assets/'.$GLOBALS['DEFAULT_IMGS'][$datas['page']];
			$output[] = <<< PLUGIN
	<plugin>
		<title>${infos['title']}</title>
		<author>${infos['author']}</author>
		<version>${infos['version']}</version>
		<date>${infos['date']}</date>
		<site>${infos['site']}</site>
		<description><![CDATA[${infos['description']}]]></description>
		<name>${item}</name>
		<file>${root}${infos['download']}</file>
		<icon>${root}${img}</icon>
	</plugin>
PLUGIN;
		}
	}
	$filename = REPO_XML.$datas['page'];
	file_put_contents($filename.'.xml', BEGIN_REPO_XML. implode("\n", $output) .END_REPO_XML);
	file_put_contents($filename.'.version', date('ymdH', filemtime($filename.'.xml')));
}

function buildCatalog($page) {
	echo "Building $page catalog\n";

	$cache = array();
	$imgsFolder = ASSETS.$page;

	$result = true;
	$zip = new ZipArchive();
	foreach(glob($page.'/*.zip') as $filename) {
		if($res = $zip->open($filename)) {
			if($infoFile = searchInfosFile($zip)) {
				$infosXML = $zip->getFromName($infoFile);
				try {
					$filedate = filemtime($filename);
					$infos = array(
						'download' => $filename,
						'filedate' => date('Y-m-d', $filedate)
					);
					# On parse le fichier infos.xml
					@$doc = new SimpleXMLElement($infosXML);
					foreach($doc as $key=>$value) {
						$infos[$key] = $value->__toString();
					}

					switch($page) {
						case 'plugins':
							$keyName = getPluginName($zip);
							$imgPattern = '@^'.$keyName.'/icon\.(jpe?g|png|gif)$@i';
							break;
						case 'themes':
							$keyName = getThemeName($zip);
							$imgPattern = '@^'.$keyName.'/preview\.(jpe?g|png|gif)$@i';
							break;
						case 'scripts':
							$keyName = preg_replace('@[\d\._-]+$@', '', basename($filename, '.zip'));
							$imgPattern = false;
							break;
						default:
					}

					// look for an image
					$imgPath = false;
					if(!empty($imgPattern)) {
						for ($i=0; $i<$zip->numFiles; $i++) {
							$filename = $zip->getNameIndex($i);
							if(!empty($imgPattern)) {
								if(preg_match($imgPattern, $filename, $matches)) {
									$img = "$imgsFolder/${keyName}.$matches[1]";
									if(!file_exists($img) or filemtime($img) < $filedate) {
										if($zip->extractTo($imgsFolder, $filename)) {
											$from = $imgsFolder.'/'.$filename;
											rename($from, $img);
											touch($img, $filedate);
										}
									}
									if(empty($imgPath)) {
										$imgPath = $img;
									}
									break;
								}
							}
						}
					}

					if(!empty($keyName)) {
						// on vérifie si le plugin est déjà référencé dans le cache
						if(!array_key_exists($keyName, $cache)) {
							$cache[$keyName] = array('versions'	=> array());
						}
						$cache[$keyName]['versions'][$infos['version']] = $infos;
						if(!empty($imgPath)) {
							$cache[$keyName]['img'] = $imgPath;
						}
					}

				} catch (Exception $e) {
					error_log(date('Y-m-d H:i').' - fichier infos.xml incorrect pour le plugin '.$f.' - Ligne n°'.$e->getLine().': '.$e->getMessage()."\n", 3, dirname(__FILE__).'/errors.log');
					$result = false;
				}
			}
			$zip->close();
		}
	}

	// supprime les dossiers créés par l'extraction des iĉones ou des previews
	foreach(glob($imgsFolder.'/*', GLOB_ONLYDIR) as $folder) {
		rmdir($folder);
	}

	// tri et sauvegarde du cache sur le disque dur
	$lastReleases = array();
	if(!empty($cache)) {
		ksort($cache);

		// tri décroissant des versions
		foreach(array_keys($cache) as $k) {
			uksort($cache[$k]['versions'], function($a, $b) {
				return -version_compare($a, $b);
			});
			$lastVersion = array_keys($cache[$k]['versions'])[0];
			$lastReleases[$k] = $cache[$k]['versions'][$lastVersion];
			if(!empty($cache[$k]['img'])) {
				$lastReleases[$k]['img'] = $cache[$k]['img'];
			}
		}
	}
	// encodage et sauvegarde au format JSON
	$filename = WORKDIR.$page.'.json';
	if(file_put_contents($filename, json_encode($cache, JSON_OPTIONS), LOCK_EX) === false) {
		$error = "No rights for writing in the $filename file.\nFeel free for calling the webmaster.";
	}

	list($hostname, $urlBase) = getHostnameFromGit();
	$callbacks = array(
		'hostname'		=> $hostname,
		'urlBase'		=> $urlBase,
		'page'			=> $page,
		'lastUpdate'	=> date('c', filemtime($filename)),
		'items'			=> (!empty($lastReleases)) ? $lastReleases : false
	);
	$filename = LAST_RELEASES.$page.'.json';
	if(file_put_contents($filename, json_encode($callbacks, JSON_OPTIONS), LOCK_EX) === false) {
		$error = "No rights for writing in the $filename file.\nFeel free for calling the webmaster.";
	}

	buildRSS($page, $cache, $hostname.$urlBase);
	buildXML($callbacks);
}
function init() {
	if(!class_exists('ZipArchive')) {
		$message = 'Class ZipArchive is missing in PHP library';
		if(!empty($_SERVER['SERVER_ADDR'])) {
			header('HTTP/1.0 500 '.$message);
			header('Content-type: text/plain; charset=utf-8');
			header('Content-Length: '.strlen($message));
		} else {
			echo "No IP address for this server\n\n";
		}
		// print_r($_SERVER);
		echo "$message\n";
		exit;
	}

	foreach(array(WORKDIR, ASSETS, LAST_RELEASES, RSS, REPO_XML) as $folder) {
		if(!is_dir($folder)) { mkdir($folder); }
	}
	foreach(array('plugins', 'themes', 'scripts') as $items) {
		$folder = ASSETS.$items;
		if(!is_dir($folder)) { mkdir($folder); }
	}
}

init();
array_map('buildCatalog', array('plugins', 'themes', 'scripts'));
?>
