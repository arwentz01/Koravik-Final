<?php

declare(strict_types=1);

namespace Koravik\Platform\Settings;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class AccessibilityService
{
    private const TEXT_SCALES = ['standard', 'large', 'larger'];
    private const TYPEFACES = ['system', 'readable'];
    private const SPACING = ['standard', 'relaxed'];
    private const WIDTHS = ['standard', 'narrow'];

    public function __construct(private readonly Database $database) {}

    public function get(string $accountId): array
    {
        $this->database->pdo()->prepare(
            'INSERT INTO account_settings (account_id,updated_at) VALUES (:account_id,UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE account_id=VALUES(account_id)'
        )->execute(['account_id' => $accountId]);
        $statement = $this->database->pdo()->prepare(
            'SELECT reduced_motion,high_contrast,text_scale,typeface,content_spacing,
                    emphasize_links,enhanced_focus,reading_width
             FROM account_settings WHERE account_id=:account_id LIMIT 1'
        );
        $statement->execute(['account_id' => $accountId]);
        $row = $statement->fetch();
        if (!$row) throw new RuntimeException('Accessibility preferences are unavailable.');
        return $row;
    }

    public function save(string $accountId, array $input): void
    {
        $textScale = (string)($input['text_scale'] ?? 'standard');
        $typeface = (string)($input['typeface'] ?? 'system');
        $spacing = (string)($input['content_spacing'] ?? 'standard');
        $width = (string)($input['reading_width'] ?? 'standard');
        if (!in_array($textScale, self::TEXT_SCALES, true)) throw new RuntimeException('Choose a valid text size.');
        if (!in_array($typeface, self::TYPEFACES, true)) throw new RuntimeException('Choose a valid typeface.');
        if (!in_array($spacing, self::SPACING, true)) throw new RuntimeException('Choose a valid spacing preference.');
        if (!in_array($width, self::WIDTHS, true)) throw new RuntimeException('Choose a valid reading width.');

        $values = [
            'account_id' => $accountId,
            'reduced_motion' => isset($input['reduced_motion']) ? 1 : 0,
            'high_contrast' => isset($input['high_contrast']) ? 1 : 0,
            'text_scale' => $textScale,
            'typeface' => $typeface,
            'content_spacing' => $spacing,
            'emphasize_links' => isset($input['emphasize_links']) ? 1 : 0,
            'enhanced_focus' => isset($input['enhanced_focus']) ? 1 : 0,
            'reading_width' => $width,
        ];
        $this->database->transaction(function (PDO $pdo) use ($values, $accountId): void {
            $pdo->prepare(
                'INSERT INTO account_settings (account_id,updated_at) VALUES (:account_id,UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE account_id=VALUES(account_id)'
            )->execute(['account_id' => $accountId]);
            $pdo->prepare(
                'UPDATE account_settings SET reduced_motion=:reduced_motion,high_contrast=:high_contrast,
                 text_scale=:text_scale,typeface=:typeface,content_spacing=:content_spacing,
                 emphasize_links=:emphasize_links,enhanced_focus=:enhanced_focus,
                 reading_width=:reading_width,updated_at=UTC_TIMESTAMP() WHERE account_id=:account_id'
            )->execute($values);
            $pdo->prepare(
                'INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at)
                 VALUES (:id,:account_id,"accessibility.updated","account",:subject_id,UTC_TIMESTAMP())'
            )->execute(['id' => self::uuid(), 'account_id' => $accountId, 'subject_id' => $accountId]);
        });
    }

    public function reset(string $accountId): void
    {
        $this->save($accountId, []);
    }

    private static function uuid(): string
    {
        $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&0x0f)|0x40);$bytes[8]=chr((ord($bytes[8])&0x3f)|0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    }
}
