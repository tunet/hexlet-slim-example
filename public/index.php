<?php

use App\UserRepository;
use App\Validator;
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\Views\PhpRenderer;

use function Symfony\Component\String\s;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$container = new Container();
$container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
$container->set('flash', fn() => new Messages());
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();
$repo = new UserRepository();

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
})->setName('home');

$app->get('/users', function ($request, $response) use ($repo) {
    $users = $repo->all();
    $term = $request->getQueryParam('term');
    $result = collect($users)->filter(
        fn($user) => empty($term) ?: s($user['nickname'])->ignoreCase()->startsWith($term)
    );
    $messages = $this->get('flash')->getMessages();

    $params = [
        'users'    => $result,
        'term'     => $term,
        'messages' => $messages,
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user'   => [
            'email'    => '',
            'nickname' => '',
        ],
        'errors' => [],
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('newUser');

$app->get('/users/{id}', function ($request, $response, $args) use ($repo) {
    $user = $repo->find($args['id']);

    if (!$user) {
        return $response->withStatus(404)->write('404 Not Found');
    }

    $params = [
        'user' => $user,
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->post('/users', function ($request, $response) use ($router, $repo) {
    $user = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $repo->save($user);

        $this->get('flash')->addMessage('success', 'Пользователь успешно добавлен');

        return $response->withRedirect($router->urlFor('users'), 302);
    }

    $params = [
        'user'   => $user,
        'errors' => $errors,
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('addUser');

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($repo) {
    $user = $repo->find($args['id']);

    if (!$user) {
        return $response->withStatus(404)->write('404 Not Found');
    }

    $params = [
        'user' => $user,
    ];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');

$app->patch('/users/{id}', function ($request, $response, $args) use ($router, $repo) {
    $user = $repo->find($args['id']);

    if (!$user) {
        return $response->withStatus(404)->write('404 Not Found');
    }

    $data = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $user['email'] = $data['email'];
        $user['nickname'] = $data['nickname'];

        $repo->save($user);

        $this->get('flash')->addMessage('success', 'Пользователь успешно обновлён');

        return $response->withRedirect($router->urlFor('editUser', ['id' => $user['id']]));
    }

    $params = [
        'user'   => $user,
        'errors' => $errors,
    ];

    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('updateUser');

$app->run();
