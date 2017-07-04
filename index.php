<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types = 1);

if (!empty($_GET['id']) && !empty($_GET['redir'])) {
    setcookie('lastClick['.$_GET['id'].']', (string) time(), time() + 2678400);
    header('Location: '.$_GET['redir'], true, 302);
    exit();
}

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
        <style>
            body {
                margin: 10px;
            }

            .updated {
                border-left: 3px solid #0366d6;
            }
        </style>
    </head>
    <body>
        <h1>Pull requests</h1>
        <div class="list-group">
            <?php foreach ($pullRequests as $pr):
                $updated = !(!empty($_COOKIE['lastClick'][$pr['id']]) && $_COOKIE['lastClick'][$pr['id']] > strtotime($pr['updated_at']));
            ?>
            <a href="?id=<?php echo $pr['id']; ?>&redir=<?php echo $pr['html_url']; ?>" class="list-group-item <?php if ($updated): ?>updated<?php endif; ?>" target="_blank">
                <div class="media">
                    <div class="media-left">
                        <img class="media-object img-circle" src="<?php echo $pr['user']['avatar_url']; ?>" alt="<?php echo $pr['user']['login']; ?>" width="40">
                    </div>
                    <div class="media-body">
                        <h4 class="media-heading"><?php echo $pr['title'] ?></h4>
                        <p class="text-muted">#<?php echo $pr['number'] ?> on <?php echo $pr['head']['repo']['full_name']; ?> at <?php echo date('d/m H:i', strtotime($pr['created_at'])); ?></p>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </body>
</html>
