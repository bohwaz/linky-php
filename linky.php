#!/usr/bin/env php
<?php
/**
 * Linky PHP
 * @author bohwaz <https://bohwaz.net/>
 * @license GNU Affero GPL v3.0
 */

namespace Linky;

define('Linky\CONFIG_HOME', $_ENV['XDG_CONFIG_HOME'] ?? (isset($_ENV['HOME']) ? $_ENV['HOME'] . '/.config' : '/tmp'));

const OAUTH_TOKENS_FILE = CONFIG_HOME . '/linky.json';

error_reporting(E_ALL);

function http_request(string $url, array $headers = []): string
{
	$headers = array_map(function ($v, $k) { return sprintf('%s: %s', $k, $v); }, $headers, array_keys($headers));
	$headers = implode("\r\n", $headers);

	$context = stream_context_create([
		'http' => [
			'header' => $headers,
			//'ignore_errors' => true,
		],
	]);

	$body = file_get_contents($url, false, $context);
	return $body;
}

function get_oauth(string $key = 'access'): ?string
{
	static $data = null;

	if (null === $data) {
		$data = json_decode(@file_get_contents(OAUTH_TOKENS_FILE));
	}

	return $data->$key ?? null;
}

function api_request(string $uri, array $params): ?\stdClass
{
	$headers = [
		'Authorization' => sprintf('Bearer %s', get_oauth()),
	];

	$uri .= '?' . http_build_query($params);

	return json_decode(http_request($uri, $headers));
}

$o = [];
$params = [];
$co = null;

$argv = array_slice($_SERVER['argv'], 1);

foreach ($argv as $a) {
	if (substr($a, 0, 2) == '--') {
		if ($co) {
			$o[$co] = null;
		}

		$co = substr($a, 2);
	}
	elseif (substr($a, 0, 1) == '-') {
		if ($co) {
			$o[$co] = null;
		}

		$co = substr($a, 1);
	}
	elseif ($co) {
		$o[$co] = $a;
		$co = null;
	}
	else {
		$params[] = $a;
	}
}

if ($co) {
	$o[$co] = null;
}

$cmd = array_shift($params) ?: 'daily';

if ($cmd === 'help' || array_key_exists('h', $o) || array_key_exists('help', $o)) {
	echo <<<EOF
Usage: linky COMMANDE [OPTIONS]

Pour pouvoir utiliser cette commande, il faut déjà :
- avoir un compte sur https://mon-compte.enedis.fr/
- créer un access token sur https://conso.vercel.app/
- exécuter la commande donnée par conso.vercel.app

Commandes :

	daily
		Affiche la consommation quotidienne en kWh

	weekly
		Affiche la consommation hebdomadaire en kWh

	monthly
		Affiche la consommation mensuelle en kWh

	auth
		Enregistre les informations d'authentification

	(Si aucune commande n'est passée, 'daily' sera utilisé.)

Options :

	--json
		Renvoie les données au format JSON au lieu de les afficher

	-s DATE
	--start DATE
		Définit la date de début pour afficher la consommation.
		Si aucune date de début n'est définie, alors c'est 30 jours avant la date
		d'aujourd'hui qui sera utilisée.

	-e DATE
	--end DATE
		Définit la date de fin.
		Si aucune date de fin n'est définie, alors la date du jour est utilisée.

	-w WEEKS
	--weeks WEEKS
		Définit le nombre de semaines à afficher à partir de la date de fin
		(remplace --start)

	-m MONTHS
	--months MONTHS
		Définit le nombre de mois à afficher à partir de la date de fin
		(remplace --start)

Chemins :

	~/.config/linky.json
		Enregistre les données d'authentification de l'API.
EOF;
}

if ($cmd === 'auth') {
	if (!isset($o['a'], $o['r'], $o['u'])) {
		echo "Invalid call\n";
		exit(1);
	}

	file_put_contents(OAUTH_TOKENS_FILE, json_encode(['access' => $o['a'], 'refresh' => $o['r'], 'upid' => $o['u']]));
}

if (!get_oauth()) {
	echo "No OAuth token was set. Please use https://conso.vercel.app/\n";
	exit(1);
}

$start = $o['s'] ?? ($o['start'] ?? '30 days ago');
$end = $o['e'] ?? ($o['end'] ?? 'now');

if ($o['w'] ?? $o['weeks'] ?? null) {
	$start = sprintf('%d weeks ago', $o['w'] ?? $o['week']);
}

if ($o['m'] ?? $o['months'] ?? null) {
	$start = sprintf('%d months ago', $o['m'] ?? $o['months']);
}

$e = strtotime($end);

if (!$e) {
	echo "Invalid end date: " . $end . "\n";
	exit(1);
}

$s = strtotime($start, $e);

if (!$s) {
	echo "Invalid start date: " . $start . "\n";
	exit(1);
}

/*
Error 500
$data = api_request('https://ext.hml.api.enedis.fr/daily_consumption', [
	'start'          => date('Y-m-d', $s),
	'end'            => date('Y-m-d', $e),
	'usage_point_id' => get_oauth('upid'),
]);
*/

$data = api_request('https://gw.prd.api.enedis.fr/v4/metering_data/daily_consumption', [
	'start'          => date('Y-m-d', $s),
	'end'            => date('Y-m-d', $e),
	'usage_point_id' => get_oauth('upid'),
]);

if (!isset($data->meter_reading->interval_reading[0])) {
	echo "No data found: \n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
	exit(1);
}

$table = [];

foreach ($data->meter_reading->interval_reading as $d) {
	$table[$d->date] = $d->value;
}

if ($cmd == 'weekly') {
	$new_table = [];

	foreach ($table as $day => $value) {
		$dt = strtotime($day);
		$week = date('\SW', $dt) . date(' (d/m-', strtotime('monday this week', $dt)) . date('d/m)', strtotime('sunday this week', $dt));

		if (!isset($new_table[$week])) {
			$new_table[$week] = 0;
		}

		$new_table[$week] += $value;
	}

	$table = $new_table;
}
elseif ($cmd == 'monthly') {
	$new_table = [];

	foreach ($table as $day => $value) {
		$month = date('m/Y', strtotime($day));

		if (!isset($new_table[$month])) {
			$new_table[$month] = 0;
		}

		$new_table[$month] += $value;
	}

	$table = $new_table;
}

ksort($table);

if (array_key_exists('json', $o)) {
	echo json_encode($table, JSON_PRETTY_PRINT);
	exit;
}

$max = max($table);
$max_kwh = round($max / 1000);

foreach ($table as $idx => $value) {
	$i = ($value / $max) * 40;
	$v = round($value / 1000);
	$v = str_repeat(' ', strlen($max_kwh) - strlen($v)) . $v . ' kWh';
	$idx = $cmd == 'daily' ? date('d/m', strtotime($idx)) : $idx;
	printf("%s %s %s\n", $idx, $v, str_repeat('▄', (int)$i));
}
