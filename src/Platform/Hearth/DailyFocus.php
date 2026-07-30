<?php

declare(strict_types=1);

namespace Koravik\Platform\Hearth;

use RuntimeException;

final class DailyFocus
{
    public static function normalize(string $intention,array $occurrenceIds): array
    {
        $intention=trim($intention);
        if(mb_strlen($intention)>180) throw new RuntimeException('Keep today’s intention to 180 characters or fewer.');
        $ids=array_values(array_filter(array_map('strval',$occurrenceIds),static fn(string $id):bool=>$id!==''));
        if(count($ids)>3) throw new RuntimeException('Choose no more than three priorities for today.');
        if(count($ids)!==count(array_unique($ids))) throw new RuntimeException('Choose each Quest only once.');
        foreach($ids as $id)if(!preg_match('/^[a-f0-9-]{36}$/',$id))throw new RuntimeException('One selected Quest is unavailable.');
        if($intention===''&&$ids===[])throw new RuntimeException('Add an intention or choose at least one Quest.');
        return ['intention'=>$intention,'occurrence_ids'=>$ids];
    }
}
