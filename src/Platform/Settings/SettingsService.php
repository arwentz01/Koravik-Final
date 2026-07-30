<?php

declare(strict_types=1);

namespace Koravik\Platform\Settings;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class SettingsService
{
    private const APPEARANCE = ['system','light','dark'];
    private const DATE_FORMATS = ['month_day_year','year_month_day','day_month_year'];
    private const TIMEZONES = ['America/New_York','America/Chicago','America/Denver','America/Los_Angeles','UTC'];

    public function __construct(private readonly Database $database) {}

    public function get(string $accountId): array
    {
        $pdo=$this->database->pdo();
        $pdo->prepare('INSERT INTO account_settings (account_id,updated_at) VALUES (:account_id,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE account_id=VALUES(account_id)')->execute(['account_id'=>$accountId]);
        $statement=$pdo->prepare('SELECT a.display_name,s.appearance,s.reduced_motion,s.high_contrast,s.text_scale,s.typeface,s.content_spacing,s.emphasize_links,s.enhanced_focus,s.reading_width,s.timezone,s.date_format,s.updated_at FROM platform_accounts a JOIN account_settings s ON s.account_id=a.id WHERE a.id=:account_id LIMIT 1');
        $statement->execute(['account_id'=>$accountId]);
        $row=$statement->fetch();
        if(!$row) throw new RuntimeException('Account settings are unavailable.');
        return $row;
    }

    public function save(string $accountId,array $input): void
    {
        $displayName=trim((string)($input['display_name']??''));
        $appearance=(string)($input['appearance']??'system');
        $timezone=(string)($input['timezone']??'America/New_York');
        $dateFormat=(string)($input['date_format']??'month_day_year');
        if($displayName==='' || mb_strlen($displayName)>100) throw new RuntimeException('Display name must be between 1 and 100 characters.');
        if(!in_array($appearance,self::APPEARANCE,true)) throw new RuntimeException('Choose a valid appearance preference.');
        if(!in_array($timezone,self::TIMEZONES,true)) throw new RuntimeException('Choose a supported time zone.');
        if(!in_array($dateFormat,self::DATE_FORMATS,true)) throw new RuntimeException('Choose a valid date format.');
        $reducedMotion=isset($input['reduced_motion'])?1:0;
        $highContrast=isset($input['high_contrast'])?1:0;
        $this->database->transaction(function(PDO $pdo) use($accountId,$displayName,$appearance,$timezone,$dateFormat,$reducedMotion,$highContrast): void {
            $pdo->prepare('UPDATE platform_accounts SET display_name=:display_name,updated_at=UTC_TIMESTAMP() WHERE id=:account_id')->execute(['display_name'=>$displayName,'account_id'=>$accountId]);
            $pdo->prepare('INSERT INTO account_settings (account_id,appearance,reduced_motion,high_contrast,timezone,date_format,updated_at) VALUES (:account_id,:appearance,:reduced_motion,:high_contrast,:timezone,:date_format,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE appearance=VALUES(appearance),reduced_motion=VALUES(reduced_motion),high_contrast=VALUES(high_contrast),timezone=VALUES(timezone),date_format=VALUES(date_format),updated_at=UTC_TIMESTAMP()')->execute(['account_id'=>$accountId,'appearance'=>$appearance,'reduced_motion'=>$reducedMotion,'high_contrast'=>$highContrast,'timezone'=>$timezone,'date_format'=>$dateFormat]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"settings.updated","account",:subject_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject_id'=>$accountId]);
        });
    }

    private static function uuid(): string
    {
        $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&0x0f)|0x40);$bytes[8]=chr((ord($bytes[8])&0x3f)|0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($bytes),4));
    }
}
