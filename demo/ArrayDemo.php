<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ray\Di\Injector;
use Ray\InputQuery\Attribute\Input;
use Ray\InputQuery\InputQuery;

// User input class
final class User
{
    public function __construct(
        #[Input]
        public readonly string $id,
        #[Input]
        public readonly string $name
    ) {
    }
}

// Controller with array handling
final class UserController
{
    public function listUsers(
        #[Input(item: User::class)]
        array $users
    ): void {
        echo "Array of users:\n";
        foreach ($users as $index => $user) {
            echo "  [$index] ID: {$user->id}, Name: {$user->name}\n";
        }
    }

    public function listUsersAsArrayObject(
        #[Input(item: User::class)]
        ArrayObject $users
    ): void {
        echo "\nArrayObject of users:\n";
        foreach ($users as $index => $user) {
            echo "  [$index] ID: {$user->id}, Name: {$user->name}\n";
        }
    }
}

// Demo
$injector = new Injector();
$inputQuery = new InputQuery($injector);

// Sample query data (like from $_POST)
$query = [
    'users' => [
        ['id' => '1', 'name' => 'jingu'],
        ['id' => '2', 'name' => 'horikawa'],
        ['id' => '3', 'name' => 'tanaka']
    ]
];

$controller = new UserController();

// Array example
$method = new ReflectionMethod($controller, 'listUsers');
$args = $inputQuery->getArguments($method, $query);
$controller->listUsers(...$args);

// ArrayObject example
$method = new ReflectionMethod($controller, 'listUsersAsArrayObject');
$args = $inputQuery->getArguments($method, $query);
$controller->listUsersAsArrayObject(...$args);
