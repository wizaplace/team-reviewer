<?php

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Templating
$app['templating'] = new class() {
    public function render($path, array $data = [])
    {
        extract($data);
        ob_start();
        eval('; ?>'.file_get_contents(__DIR__.'/../views/'.$path).'<?php ;');

        return ob_get_clean();
    }
};

require_once __DIR__.'/../functions.php';
$config = include __DIR__.'/../config.php';

$guzzleOptions = [];

$stack = HandlerStack::create();
$stack->push(
    new CacheMiddleware(
        new PrivateCacheStrategy(
            new DoctrineCacheStorage(
                new FilesystemCache('/tmp/team-reviewer')
            )
        )
    ),
    'cache'
);

$guzzleOptions['handler'] = $stack;

if (isset($config['auth']['token'])) {
    $guzzleOptions['headers']['Authorization'] = 'token '.$config['auth']['token'];
} elseif (isset($config['auth']['basic'])) {
    $guzzleOptions['auth'] = [$config['auth']['basic']['username'], $config['auth']['basic']['password']];
}
$app['http.client'] = new \GuzzleHttp\Client($guzzleOptions);

$app->get('/', function (Request $request) use ($app) {
    if (!empty($_GET['id']) && !empty($_GET['redir'])) {

    }

    $repos = [];
    $labels = [];

    if (is_file(__DIR__.'/../repos.dat')) {
        $repos = unserialize(file_get_contents(__DIR__.'/../repos.dat'));
    }

    if (isset($repos['labels'])) {
        $labels = $repos['labels'];
        unset($repos['labels']);
    }

    return $app['templating']->render('index.php', [
        'repos' => $repos,
        'labels' => $labels,
        'selectedLabels' => $request->query->get('label', []),
        'selectedStatus' => $request->query->get('status'),
        'autorefresh' => $request->query->getBoolean('autorefresh'),
    ]);
});

$app->get('/{id}/{redir}', function ($id, $redir) use ($app) {
    $response = $app->redirect(base64_decode($redir));
    $response->headers->setCookie(new Cookie('lastClick['.$id.']', (string) time(), time() + 2678400));

    return $response;
});

$app->get('/update', function () use ($app, $config) {
    $repos = [];
    $labelNames = [];

    foreach ($config['repositories'] as $repository) {
        $response = $app['http.client']->get('https://api.github.com/repos/' . $repository . '/pulls?state=open&per_page=100');

        $response = json_decode($response->getBody()->getContents(), true);

        foreach ($response as &$pr) {
            echo "  #{$pr['number']} {$pr['title']}\n";

            $pr['issue'] = json_decode(
                $app['http.client']->get($pr['issue_url'])->getBody()->getContents(),
                true
            );

            $pr['comments'] = json_decode(
                $app['http.client']->get($pr['comments_url'].'?per_page=100')->getBody()->getContents(),
                true
            );

            $pr['reviews'] = [
                'comments' => json_decode($app['http.client']->get('https://api.github.com/repos/'.$repository.'/pulls/'.$pr['number'].'/reviews')->getBody()->getContents(), true),
                'state' => null,
            ];

            foreach ($pr['reviews']['comments'] as $review) {
                if (null === $pr['reviews']['state']) {
                    $pr['reviews']['state'] = $review['state'];
                }

                if ('APPROVED' == $review['state'] && 'REQUEST_CHANGES' != $pr['reviews']['state']) {
                    $pr['reviews']['state'] = $review['state'];
                }

                if ('REQUEST_CHANGES' == $review['state']) {
                    $pr['reviews']['state'] = $review['state'];
                }
            }

            $pr['status'] = json_decode(
                $app['http.client']->get('https://api.github.com/repos/'.$repository.'/commits/'.$pr['head']['sha'].'/status')->getBody()->getContents(),
                true
            );
        }

        $labels = json_decode(
            $app['http.client']->get("https://api.github.com/repos/{$repository}/labels")->getBody()->getContents(),
            true
        );

        $repos[$repository] = $response;
        // Fusion de la liste de tous labels des repos
        $labelNames = array_merge($labelNames, array_column($labels, 'name'));
    }

    $repos['labels'] = array_unique($labelNames);

    file_put_contents(__DIR__.'/../repos.dat', serialize($repos));

    return 'ok';
});

$app->run();
