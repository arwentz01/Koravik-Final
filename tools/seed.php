<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$email = env('SEED_EMAIL');
$password = env('SEED_PASSWORD');
$name = env('SEED_DISPLAY_NAME', 'Andrew') ?? 'Andrew';

if (!$email || !$password || strlen($password) < 8) {
    fwrite(STDERR, "Set SEED_EMAIL and a SEED_PASSWORD of at least 8 characters.\n");
    exit(1);
}

$accountId = '00000000-0000-4000-8000-000000000001';
$questId = '00000000-0000-4000-8000-000000000101';
$occurrenceId = '00000000-0000-4000-8000-000000000102';
$installationId = '00000000-0000-4000-8000-000000000201';
$now = gmdate('Y-m-d H:i:s');
$today = gmdate('Y-m-d');

$pdo = database()->pdo();
$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO platform_accounts (id, email, display_name, role, status, created_at, updated_at) VALUES (:id, :email, :display_name, "owner", "active", :created_at, :updated_at) ON DUPLICATE KEY UPDATE email = VALUES(email), display_name = VALUES(display_name), updated_at = VALUES(updated_at)')
        ->execute(['id'=>$accountId,'email'=>mb_strtolower(trim($email)),'display_name'=>$name,'created_at'=>$now,'updated_at'=>$now]);
    $pdo->prepare('INSERT INTO auth_credentials (account_id, password_hash, updated_at) VALUES (:account_id, :password_hash, :updated_at) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), updated_at = VALUES(updated_at)')
        ->execute(['account_id'=>$accountId,'password_hash'=>password_hash($password, PASSWORD_DEFAULT),'updated_at'=>$now]);
    $pdo->prepare('INSERT INTO quests (id, account_id, title, description, status, lifecycle_status, created_at, updated_at) VALUES (:id, :account_id, "Make one corner calmer", "Choose one small space and leave it a little easier to return to.", "active", "active", :created_at, :updated_at) ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), status = VALUES(status), lifecycle_status = "active", updated_at = VALUES(updated_at)')
        ->execute(['id'=>$questId,'account_id'=>$accountId,'created_at'=>$now,'updated_at'=>$now]);
    $pdo->prepare('INSERT INTO quest_occurrences (id, quest_id, account_id, scheduled_for, status, available_at, created_at, updated_at) VALUES (:id, :quest_id, :account_id, :scheduled_for, "available", :available_at, :created_at, :updated_at) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)')
        ->execute(['id'=>$occurrenceId,'quest_id'=>$questId,'account_id'=>$accountId,'scheduled_for'=>$today,'available_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);
    $pdo->prepare('INSERT INTO world_installations (id, account_id, world_key, status, installed_at) VALUES (:id, :account_id, "epic-ordinary", "active", :installed_at) ON DUPLICATE KEY UPDATE status = VALUES(status)')
        ->execute(['id'=>$installationId,'account_id'=>$accountId,'installed_at'=>$now]);
    $pdo->prepare('INSERT INTO world_relationships (installation_id, npc_key, trust_score, relationship_stage, updated_at) VALUES (:installation_id, "caretaker", 0, "new", :updated_at) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)')
        ->execute(['installation_id'=>$installationId,'updated_at'=>$now]);
    $pdo->prepare('INSERT INTO world_narrative_progress (installation_id, current_arc, current_chapter, current_scene, updated_at) VALUES (:installation_id, "coming-home", "the-first-light", "caretaker-welcome", :updated_at) ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)')
        ->execute(['installation_id'=>$installationId,'updated_at'=>$now]);

    $pdo->commit();
    echo "Seeded Build 005 account, Quest occurrence, and Epic Ordinary continuation.\n";
} catch (Throwable $throwable) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $throwable;
}
