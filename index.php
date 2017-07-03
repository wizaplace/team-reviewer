<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types = 1);

$config = include 'config.php';

$context = stream_context_create([
    'http' => [
        'header'  => 'Authorization: Basic ' . base64_encode($config['auth']['username'].':'.$config['auth']['password']) . "\r\n" .
                     'User-Agent: Wizaplace team reviewer'
    ]
]);

$pullRequests = [];

foreach ($config['repositories'] as $repository) {
    $response = file_get_contents('https://api.github.com/repos/'.$repository.'/pulls?state=open&per_page=100', false, $context);
    $response = json_decode($response, true);

    $pullRequests = array_merge($pullRequests, $response);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="refresh" content="30">

        <title>Pulls Requests</title>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    </head>
    <body>
        <h1>Pull requests</h1>
        <div class="list-group">
            <?php foreach ($pullRequests as $pr): ?>
            <a href="<?php echo $pr['html_url'] ?>" class="list-group-item" target="_blank">
                <h4 class="list-group-item-heading">
                    <img class="img-circle" src="<?php echo $pr['user']['avatar_url']; ?>" width="32" />
                    <?php echo $pr['title'] ?>
                    <span class="pull-right">
                        <strong>
                            <?php echo $pr['head']['repo']['full_name']; ?>
                        </strong>
                        &nbsp;-&nbsp;
                        <?php echo date('d/m H:i', strtotime($pr['created_at'])); ?>
                    </span>
                </h4>
            </a>
            <?php endforeach; ?>
        </div>
    </body>
</html>
