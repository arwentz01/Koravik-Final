<?php

declare(strict_types=1);

namespace Koravik\Districts\Quests;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class LivingQuestController
{
    public function __construct(private readonly Database $database)
    {
    }

    public function handle(string $method, string $path): bool
    {
        if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/focus$#', $path, $matches)) {
            $this->requireCsrf();
            $account = Security::requireAccount();
            try {
                (new QuestService($this->database))->updateFocus($matches[1], (string) $account['id'], (string) ($_POST['purpose'] ?? ''), (string) ($_POST['next_step'] ?? ''));
                $_SESSION['flash'] = 'Quest focus updated.';
            } catch (RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            $this->redirect('/quests/' . $matches[1]);
        }

        if ($method === 'POST' && preg_match('#^/quests/([a-f0-9-]{36})/resolve$#', $path, $matches)) {
            $this->requireCsrf();
            $account = Security::requireAccount();
            try {
                (new QuestService($this->database))->resolve($matches[1], (string) $account['id'], (string) ($_POST['outcome'] ?? ''), (string) ($_POST['reflection'] ?? ''));
                $_SESSION['flash'] = 'The Quest outcome was recorded without erasing the journey.';
            } catch (RuntimeException $exception) {
                $_SESSION['flash'] = $exception->getMessage();
            }
            $this->redirect('/quests/' . $matches[1]);
        }

        return false;
    }

    private function requireCsrf(): void
    {
        if (!hash_equals(Security::csrfToken(), (string) ($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Your session changed. Please try again.');
        }
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }
}
