<?php

declare(strict_types=1);

namespace Koravik\Platform\Hearth;

use Koravik\Platform\Database\Database;
use PDO;
use RuntimeException;

final class HearthLayoutService
{
    public const WIDGETS=['pillars'=>'Pillar support','chronicle'=>'Chronicle moment','world'=>'Active World continuation','organizations'=>'Organizations','households'=>'Households','trust'=>'Trust and recovery'];
    public function __construct(private readonly Database $database) {}

    public function get(string $accountId): array
    {
        $this->ensure($accountId);
        $s=$this->database->pdo()->prepare('SELECT widget_key,position,visible FROM hearth_layout_preferences WHERE account_id=:account_id ORDER BY position');
        $s->execute(['account_id'=>$accountId]);
        return $s->fetchAll();
    }

    public function save(string $accountId,array $input): void
    {
        $order=array_values(array_filter(array_map('strval',(array)($input['order']??[])),static fn(string $v): bool=>isset(self::WIDGETS[$v])));
        if(count($order)!==count(self::WIDGETS) || count(array_unique($order))!==count(self::WIDGETS)) throw new RuntimeException('Keep each Hearth section exactly once.');
        $move=(string)($input['move']??'');
        if($move!=='' && preg_match('/^('.implode('|',array_map('preg_quote',array_keys(self::WIDGETS))).'):(up|down)$/',$move,$m)) {
            $index=array_search($m[1],$order,true);
            $swap=$m[2]==='up'?$index-1:$index+1;
            if($index!==false && isset($order[$swap])) [$order[$index],$order[$swap]]=[$order[$swap],$order[$index]];
        }
        $visible=array_map('strval',(array)($input['visible']??[]));
        $this->database->transaction(function(PDO $pdo) use($accountId,$order,$visible): void {
            $pdo->prepare('UPDATE hearth_layout_preferences SET position=position+1000 WHERE account_id=:account_id')->execute(['account_id'=>$accountId]);
            foreach($order as $i=>$key) {
                $pdo->prepare('UPDATE hearth_layout_preferences SET position=:position,visible=:visible,updated_at=UTC_TIMESTAMP() WHERE account_id=:account_id AND widget_key=:widget_key')->execute(['account_id'=>$accountId,'widget_key'=>$key,'position'=>($i+1)*10,'visible'=>in_array($key,$visible,true)?1:0]);
            }
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"hearth.layout.updated","account",:subject_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject_id'=>$accountId]);
        });
    }

    public function reset(string $accountId): void
    {
        $this->database->transaction(function(PDO $pdo) use($accountId): void {
            $pdo->prepare('DELETE FROM hearth_layout_preferences WHERE account_id=:account_id')->execute(['account_id'=>$accountId]);
            $pdo->prepare('INSERT INTO audit_log (id,account_id,action,subject_type,subject_id,occurred_at) VALUES (:id,:account_id,"hearth.layout.reset","account",:subject_id,UTC_TIMESTAMP())')->execute(['id'=>self::uuid(),'account_id'=>$accountId,'subject_id'=>$accountId]);
        });
        $this->ensure($accountId);
    }

    public function apply(string $html,string $accountId): string
    {
        $layout=$this->get($accountId);
        $parts=[];
        if(preg_match('#<section><h2>What you have been supporting</h2>.*?</section>#s',$html,$m)) { $parts['pillars']=$m[0]; $html=str_replace($m[0],'',$html); }
        if(preg_match('#<section class="experience-grid">(.*?)</section>#s',$html,$m)) {
            $inside=$m[1];
            if(preg_match('#<article class="chronicle-card[^>]*>.*?</article>#s',$inside,$c)) $parts['chronicle']='<section class="experience-grid">'.$c[0].'</section>';
            if(preg_match('#<article class="world-card[^>]*>.*?</article>#s',$inside,$w)) $parts['world']='<section class="experience-grid">'.$w[0].'</section>';
            $html=str_replace($m[0],'',$html);
        }
        $optional='';
        foreach($layout as $row) if((bool)$row['visible'] && isset($parts[$row['widget_key']])) $optional.=$parts[$row['widget_key']];
        foreach($layout as $row) if((bool)$row['visible'] && !isset($parts[$row['widget_key']])) $optional.=$this->sourceWidget((string)$row['widget_key'],$accountId);
        $html=str_replace('</main>',$optional.'</main>',$html);
        $html=str_replace('<p>One meaningful next step is enough.</p>','<p>One meaningful next step is enough.</p><p><a class="quiet-link" href="/hearth/customize">Customize Hearth</a></p>',$html);
        return $html;
    }

    private function ensure(string $accountId): void
    {
        $pdo=$this->database->pdo();
        foreach(array_keys(self::WIDGETS) as $i=>$key) $pdo->prepare('INSERT INTO hearth_layout_preferences (account_id,widget_key,position,visible,updated_at) VALUES (:account_id,:widget_key,:position,1,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE widget_key=VALUES(widget_key)')->execute(['account_id'=>$accountId,'widget_key'=>$key,'position'=>($i+1)*10]);
    }
    private function sourceWidget(string $key,string $accountId): string
    {
        $pdo=$this->database->pdo();
        if($key==='organizations'){$s=$pdo->prepare('SELECT COUNT(*) FROM organization_memberships WHERE account_id=:account_id AND status="active"');$s->execute(['account_id'=>$accountId]);return '<section class="surface hearth-source-aware-widget"><h2>Organizations</h2><p>'.(int)$s->fetchColumn().' active Organization spaces. Hearth links to them without moving personal records into them.</p><a href="/organizations">Open Organizations</a></section>';}
        if($key==='households'){$s=$pdo->prepare('SELECT COUNT(*) FROM household_memberships WHERE account_id=:account_id AND status="active"');$s->execute(['account_id'=>$accountId]);return '<section class="surface hearth-source-aware-widget"><h2>Households</h2><p>'.(int)$s->fetchColumn().' active Household spaces. Household coordination stays private and separate from personal Quests unless accepted.</p><a href="/households">Open Households</a></section>';}
        if($key==='trust'){$health=(new \Koravik\Districts\Health\HealthService($this->database))->hearthSignal($accountId);return '<section class="surface hearth-source-aware-widget health-to-hearth-private-signal-summary"><h2>Trust and recovery</h2><p>Runtime Schema Compatibility Hardening, Notification Sync Safety Pass, Release Suite Runtime Regression Coverage, and Implementation Handoff / Migration Inventory Cleanup keep Hearth calm when the account is sparse or returning.</p><h3>Private Health summary</h3><p>'.self::e((string)$health['summary']).'</p><p class="local-actions"><a href="/privacy">Privacy</a><a href="/settings/data">Data controls</a><a href="/system/health">System health</a></p></section>';}
        return '';
    }
    private static function e(string $v): string { return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
    private static function uuid(): string { $b=random_bytes(16);$b[6]=chr((ord($b[6])&15)|64);$b[8]=chr((ord($b[8])&63)|128);return vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(bin2hex($b),4)); }
}
