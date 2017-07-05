<?php
/**
 * @author      Wizacha DevTeam <dev@wizacha.com>
 * @copyright   Copyright (c) Wizacha
 * @license     Proprietary
 */
declare(strict_types = 1);

function status_icon($state) {
    switch ($state) {
        case 'failure':
            return 'remove-sign text-danger';
        case 'pending':
            return 'question-sign text-warning';
        case 'success':
            return 'ok-sign text-success';
    }
}

if (!empty($_GET['id']) && !empty($_GET['redir'])) {
    setcookie('lastClick['.$_GET['id'].']', (string) time(), time() + 2678400);
    header('Location: '.$_GET['redir'], true, 302);
    exit();
}

$repos = [];

if (is_file('repos.dat')) {
    $repos = unserialize(file_get_contents('repos.dat'));
}

usort($pullRequests, function($a, $b) {
    $ta = strtotime($a['created_at']);
    $tb = strtotime($b['created_at']);

    if ($ta == $tb) {
        return 0;
    }

    return $ta < $tb ? -1 : 1;
});
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php if (isset($_GET['autorefresh'])): ?>
        <meta http-equiv="refresh" content="30">
        <?php endif; ?>

        <title>Pulls Requests</title>

        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <style>
            body {
                margin: 10px;
            }

            .panel > .list-group .list-group-item.updated {
                border-left: 3px solid #0366d6;
            }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <?php foreach ($repos as $repository => $pullRequests): ?>
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <?php echo $repository; ?>
                            <span class="badge pull-right"><?php echo (string) count($pullRequests); ?></span>
                        </div>
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
                                        <h4 class="media-heading">
                                            <?php echo $pr['title'] ?> <span class="glyphicon glyphicon-<?php echo status_icon($pr['status']['state']); ?>"></span>
                                            <small>
                                                <?php foreach ($pr['issue']['labels'] as $label): ?>
                                                <span class="label" style="background-color:#<?php echo $label['color'] ?>"><?php echo $label['name'] ?></span>
                                                <?php endforeach; ?>
                                            </small>
                                        </h4>
                                        <span class="text-muted">#<?php echo $pr['number'] ?> <span class="glyphicon glyphicon-time"></span> <?php echo date('d/m H:i', strtotime($pr['created_at'])); ?></span>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </body>
</html>
