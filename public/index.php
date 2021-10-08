<?php

use App\Validator;
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';

const USER_STORAGE_PATH = __DIR__ . '/../storage/users.json';

function getUsers(): array
{
    if (!file_exists(USER_STORAGE_PATH)) {
        return [];
    }

    return json_decode(file_get_contents(USER_STORAGE_PATH), true);
}

function saveUsers(array $users): void
{
    $jsonUsers = json_encode($users, JSON_PRETTY_PRINT);
    file_put_contents(USER_STORAGE_PATH, $jsonUsers);
}

function addUser(array $user): void
{
    $users = getUsers();
    $maxId = collect($users)->max('id');
    $users[] = array_merge($user, ['id' => $maxId + 1]);
    saveUsers($users);
}

$container = new Container();
$container->set('renderer', fn() => new PhpRenderer(__DIR__ . '/../templates'));
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

$app->get('/users', function ($request, $response) {
    $users = getUsers();
    $term = $request->getQueryParam('term');
    $result = collect($users)->filter(
        fn($user) => empty($term) ?: s($user['nickname'])->ignoreCase()->startsWith($term)
    );
    $params = [
        'users' => $result,
        'term'  => $term,
    ];

    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user'   => [
            'email'    => '',
            'nickname' => '',
        ],
        'errors' => [],
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $users = getUsers();
    $user = collect($users)->firstWhere('id', $args['id']);

    if (!$user) {
        return $response->withStatus(404)->write('404 Not Found');
    }

    $params = [
        'user' => $user,
    ];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        addUser($user);

        return $response->withRedirect('/users', 302);
    }

    $params = [
        'user'   => $user,
        'errors' => $errors,
    ];

    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->run();
