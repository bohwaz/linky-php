<?php

namespace Linky;

const API_BASE_URL = 'https://gw.prd.api.enedis.fr/';
const TOKENS_STORAGE = '';

error_reporting(E_ALL);

function http_request(string $url, array $headers = []): string
{
	$headers = array_map(function ($v, $k) { return sprintf('%s: %s', $k, $v); }, $headers);

	$context = stream_context_create([
		'header' => $headers,
		'ignore_errors' => true,
	]);

	$body = file_get_contents($url, false, $context);
	return $body;
}

function get_oauth_headers(): array
{

}

function api_request(string $uri, array $params): string
{
	$headers = array_merge(get_oauth_headers(), [
		'Content-Type' => 'application/x-www-form-urlencoded',
		'Accept' => 'application/json',
	]);

	return json_decode(http_request(API_BASE_URL . $uri), $headers);
}

