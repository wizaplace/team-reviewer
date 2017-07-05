<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types = 1);

$config = include 'config.php';

function getEtag($headers)
{
    foreach ($headers as $header) {
        if (strpos($header, 'ETag: "') === 0) {
            return substr($header, 7, -1);
        }
    }

    return '';
}

if (isset($config['auth']['token'])) {
    $authorization = 'token ' . $config['auth']['token'];
} elseif (isset($config['auth']['basic'])) {
    $authorization = 'Basic ' . base64_encode($config['auth']['basic']['username'].':'.$config['auth']['basic']['password']);
}

$authorizationHeader = "Authorization: $authorization\r\nUser-Agent: Wizaplace team reviewer";
$context = stream_context_create([
    'http' => [
        'header'  => $authorizationHeader,
    ]
]);

$repos = [];

$cachedData = [];
if (is_file('repos.dat')) {
    $cachedData = unserialize(file_get_contents('repos.dat'));
}

foreach ($config['repositories'] as $repository) {
    echo "Repo {$repository}:\n";
    $response = file_get_contents('https://api.github.com/repos/'.$repository.'/pulls?state=open&per_page=100', false, $context);
    $response = json_decode($response, true);

    // Put the PR IDs as array keys
    $response = array_combine(
        array_column($response, 'number'),
        $response
    );

    foreach ($response as &$pr) {
        echo "  #{$pr['number']} {$pr['title']}\n";

        $httpHeaderCache = '';
        if (!empty($cachedData[$repository][$pr['number']]['issue']['etag'])) {
            // If PR have been already fetched, query with cache
            echo '    Issue: CACHE TRY'.PHP_EOL;
            $httpHeaderCache = "\r\nIf-None-Match: \"{$cachedData[$repository][$pr['number']]['issue']['etag']}\"";
        }

        stream_context_set_option($context, ['http' => ['header' => $authorizationHeader.$httpHeaderCache]]);

        if ($issueData = json_decode(file_get_contents($pr['issue_url'], false, $context), true)) {
            echo '    Issue: CACHE MISS'.PHP_EOL;
            $pr['issue'] = $issueData;
            $pr['issue']['etag'] = getEtag($http_response_header);
        } else {
            echo '    Issue: CACHE HIT'.PHP_EOL;
            $pr['issue'] = $cachedData[$repository][$pr['number']]['issue'];
        }


        $httpHeaderCache = '';
        if (!empty($cachedData[$repository][$pr['number']]['status']['etag'])) {
            // If PR have been already fetched, query with cache
            echo '    Status: CACHE TRY'.PHP_EOL;
            $httpHeaderCache = "\r\nIf-None-Match: \"{$cachedData[$repository][$pr['number']]['status']['etag']}\"";
        }

        stream_context_set_option($context, ['http' => ['header' => $authorizationHeader.$httpHeaderCache]]);

        if ($statusData = json_decode(file_get_contents('https://api.github.com/repos/'.$repository.'/commits/'.$pr['head']['sha'].'/status', false, $context), true)) {
            echo '    Status: CACHE MISS'.PHP_EOL;
            $pr['status'] = $statusData;
            $pr['status']['etag'] = getEtag($http_response_header);
        } else {
            echo '    Status: CACHE HIT'.PHP_EOL;
            $pr['status'] = $cachedData[$repository][$pr['number']]['status'];
        }
    }

    $repos[$repository] = $response;
}

file_put_contents('repos.dat', serialize($repos));
