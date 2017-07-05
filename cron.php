<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types = 1);

$config = include 'config.php';

if (isset($config['auth']['token'])) {
    $authorization = 'token ' . $config['auth']['token'];
} elseif (isset($auth['basic'])) {
    $authorization = 'Basic ' . base64_encode($config['auth']['basic']['username'].':'.$config['auth']['basic']['password']);
}

$context = stream_context_create([
    'http' => [
        'header'  => 'Authorization: ' .$authorization. "\r\n" .
                     'User-Agent: Wizaplace team reviewer'
    ]
]);

$repos = [];

foreach ($config['repositories'] as $repository) {
    echo "Repo {$repository}:\n";
    $response = file_get_contents('https://api.github.com/repos/'.$repository.'/pulls?state=open&per_page=100', false, $context);
    $response = json_decode($response, true);

    foreach ($response as &$pr) {
        echo "  #{$pr['number']} {$pr['title']}\n";
        $pr['issue'] = json_decode(file_get_contents($pr['issue_url'], false, $context), true);
        $pr['status'] = json_decode(file_get_contents('https://api.github.com/repos/'.$repository.'/commits/'.$pr['head']['sha'].'/status', false, $context), true);
    }

    $repos[$repository] = $response;
}

file_put_contents('repos.dat', serialize($repos));
