<?php

namespace App;

use const JSON_PRETTY_PRINT;

class UserRepository
{
    private const USER_STORAGE_PATH = __DIR__ . '/../storage/users.json';

    public function find($id): ?array
    {
        $users = $this->all();

        return collect($users)->firstWhere('id', $id);
    }

    public function all(): array
    {
        if (!file_exists(static::USER_STORAGE_PATH)) {
            return [];
        }

        return json_decode(file_get_contents(static::USER_STORAGE_PATH), true);
    }

    public function save(array $user): void
    {
        $users = $this->all();

        if (isset($user['id'])) {
            $index = collect($users)->search(fn($item) => $item['id'] === $user['id']);
            $users[$index] = $user;
        } else {
            $maxId = collect($users)->max('id');
            $users[] = array_merge($user, ['id' => $maxId + 1]);
        }

        $this->saveUsersToStorage($users);
    }

    public function destroy($id): void
    {
        $users = $this->all();
        $index = collect($users)->search(fn($item) => $item['id'] === $id);

        if (false === $index) {
            return;
        }

        unset($users[$index]);

        $this->saveUsersToStorage($users);
    }

    private function saveUsersToStorage(array $users): void
    {
        $users = array_values($users);
        $jsonUsers = json_encode($users, JSON_PRETTY_PRINT);
        file_put_contents(static::USER_STORAGE_PATH, $jsonUsers);
    }
}
