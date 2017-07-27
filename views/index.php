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
            font-size: 12px;
        }

        h4 {
            font-size: 1.4rem;
        }

        .panel > .list-group .list-group-item.updated {
            border-left: 3px solid #0366d6;
        }

        .col-md-3 {
            padding-right: 5px;
            padding-left: 5px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php foreach ($repos as $repository => $pullRequests): ?>
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php echo $repository; ?>
                        <span class="badge pull-right"><?php echo (string) count($pullRequests); ?></span>
                    </div>
                    <div class="list-group">
                        <?php foreach ($pullRequests as $pr):
                            $updated = !(!empty($_COOKIE['lastClick'][$pr['id']]) && $_COOKIE['lastClick'][$pr['id']] > strtotime($pr['updated_at']));
                            ?>
                            <a href="/<?php echo $pr['id']; ?>/<?php echo base64_encode($pr['html_url']); ?>" class="list-group-item <?php if ($updated): ?>updated<?php endif; ?> <?php if ($pr['reviews']['state'] == 'APPROVED'): ?>list-group-item-success<?php endif; ?>" target="_blank">
                                <div class="media">
                                    <div class="media-left">
                                        <img class="media-object img-rounded" src="<?php echo $pr['user']['avatar_url']; ?>" alt="<?php echo $pr['user']['login']; ?>" width="40">
                                    </div>
                                    <div class="media-body">
                                        <h4 class="media-heading">
                                            <?php echo $pr['title'] ?> <span class="glyphicon glyphicon-<?php echo status_icon($pr['status']['state']); ?>"></span>
                                            <small>
                                                <?php foreach ($pr['issue']['labels'] as $label): ?>
                                                    <span class="label" style="background-color:#<?php echo $label['color'] ?>; color: <?php echo font_color($label['color']) ?>"><?php echo $label['name'] ?></span>
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
