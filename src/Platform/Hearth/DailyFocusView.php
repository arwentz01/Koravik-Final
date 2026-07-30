<?php

declare(strict_types=1);

namespace Koravik\Platform\Hearth;

final class DailyFocusView
{
    public function homePanel(array $dashboard): string
    {
        $focus=$dashboard['focus'];$date=(string)$dashboard['date'];
        if(!$focus){
            return '<section class="daily-focus surface state-panel state-empty" aria-labelledby="daily-focus-title"><div><p class="eyebrow">Today · '.self::dateLabel($date).'</p><h2 id="daily-focus-title">What would make today feel meaningful?</h2><p>Choose up to three existing Quests. This changes only your Hearth view—not the Quests themselves.</p></div><a class="button" href="/hearth/focus">Choose today’s focus</a></section>';
        }
        $entries='';foreach($focus['entries'] as $index=>$entry){$entries.='<li><span class="focus-number" aria-hidden="true">'.($index+1).'</span><div><p class="ownership-label">Quest</p><h3><a href="/quests/'.self::e((string)$entry['quest_id']).'">'.self::e((string)$entry['title']).'</a></h3>'.($entry['next_step']?'<p>'.self::e((string)$entry['next_step']).'</p>':'').'<p class="meta">'.self::scheduleLabel((string)$entry['scheduled_for'],$date).'</p></div></li>';}
        $intention=(string)($focus['intention']??'');
        return '<section class="daily-focus surface" aria-labelledby="daily-focus-title"><header><div><p class="eyebrow">Today · '.self::dateLabel($date).'</p><h2 id="daily-focus-title">'.($intention!==''?self::e($intention):'Your focus for today').'</h2></div><a class="quiet-link" href="/hearth/focus">Adjust focus</a></header>'.($entries!==''?'<ol class="daily-focus-list">'.$entries.'</ol>':'<div class="state-panel state-empty"><p>No Quests are pinned. Your intention can stand on its own.</p></div>').'</section>';
    }

    public function editor(array $dashboard,array $values=[],array $errors=[]): string
    {
        $focus=$dashboard['focus'];$selected=$values['priorities']??array_column((array)($focus['entries']??[]),'quest_occurrence_id');
        $intention=array_key_exists('intention',$values)?(string)$values['intention']:(string)($focus['intention']??'');
        $options='<option value="">No Quest in this position</option>';
        foreach($dashboard['candidates'] as $candidate){$label=(string)$candidate['title'].' · '.self::scheduleLabel((string)$candidate['scheduled_for'],(string)$dashboard['date']);$options.='<option value="'.self::e((string)$candidate['occurrence_id']).'">'.self::e($label).'</option>';}
        $selects='';for($i=0;$i<3;$i++){$value=(string)($selected[$i]??'');$selects.='<label for="priority_'.($i+1).'">Priority '.($i+1).'<select id="priority_'.($i+1).'" name="priorities[]">'.str_replace('value="'.self::e($value).'"','value="'.self::e($value).'" selected',$options).'</select></label>';}
        return '<section class="page-heading"><div><p class="eyebrow">Hearth · Today</p><h1>Choose what matters now.</h1><p>A small plan can create orientation without turning your day into a scorecard.</p></div><a href="/hearth">Back to Hearth</a></section>'
            .\Koravik\Platform\Resilience\FormErrors::summary($errors)
            .'<form class="surface surface-editor daily-focus-editor" method="post" action="/hearth/focus"><input type="hidden" name="csrf" value="'.self::e(\Koravik\Platform\Security\Security::csrfToken()).'"><fieldset><legend>Today’s intention</legend><label for="intention">A short phrase for the day <span class="optional">Optional when a Quest is selected</span><input id="intention" name="intention" maxlength="180" value="'.self::e($intention).'" aria-describedby="intention-help"></label><p id="intention-help" class="meta">For example: “Make room for what matters.”</p></fieldset><fieldset><legend>Up to three Quest priorities</legend><p>Selecting a Quest adds a reference to Hearth. Its schedule, completion, and history remain owned by Quests.</p>'.$selects.'</fieldset><div class="form-actions"><button class="button" type="submit">Save today’s focus</button><a class="button secondary" href="/quests/create">Create a new Quest</a></div></form>'
            .($focus?'<form method="post" action="/hearth/focus/clear"><input type="hidden" name="csrf" value="'.self::e(\Koravik\Platform\Security\Security::csrfToken()).'"><button class="quiet-button" type="submit">Clear today’s focus</button></form>':'');
    }

    private static function scheduleLabel(string $scheduled,string $today):string{return $scheduled<$today?'Available from '.self::dateLabel($scheduled):($scheduled===$today?'Available today':'Available '.self::dateLabel($scheduled));}
    private static function dateLabel(string $date):string{$value=\DateTimeImmutable::createFromFormat('Y-m-d',$date);return $value?$value->format('M j'):$date;}
    private static function e(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
