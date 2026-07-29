<?php

declare(strict_types=1);
namespace Koravik\Platform\UI;

use Koravik\Platform\Notifications\NotificationService;

final class AppShell
{
    public function apply(string $html,?array $account,string $path): string
    {
        if(!$account||!str_contains($html,'<body')) return $html;
        if(!str_contains($html,'app-shell.css'))$html=str_replace('</head>','<link rel="stylesheet" href="/assets/app-shell.css"></head>',$html);
        $primary=['/home'=>'Home','/journey'=>'Journey','/quests'=>'Quests','/chronicle'=>'Chronicle','/worlds'=>'Worlds','/companion'=>'Companion'];
        $primaryHtml='';foreach($primary as $href=>$label)$primaryHtml.=$this->link($href,$label,$path);
        $count=(new NotificationService(\database()))->unreadCount((string)$account['id']);
        $badge=$count?'<span class="notification-badge" aria-label="'.$count.' unread notifications">'.($count>9?'9+':$count).'</span>':'';
        $utilities=$this->link('/search','Search',$path).$this->link('/notifications','Notifications'.$badge,$path,false);
        $accountLinks=$this->link('/hearth','Hearth overview',$path).$this->link('/guide','Koravik guide',$path).$this->link('/settings','Settings',$path).$this->link('/privacy','Privacy',$path).$this->link('/audit','Audit activity',$path).$this->link('/settings/security','Security',$path).$this->link('/settings/data','Data controls',$path);
        $header='<header class="app-shell-header"><a class="brand" href="/home">Koravik</a><button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" onclick="const n=document.getElementById(\'primary-navigation\');const open=this.getAttribute(\'aria-expanded\')===\'true\';this.setAttribute(\'aria-expanded\',String(!open));n.hidden=open;">Menu</button><div id="primary-navigation" class="shell-navigation"><nav class="primary-nav" aria-label="Primary">'.$primaryHtml.'</nav><nav class="utility-nav" aria-label="Utilities">'.$utilities.'</nav><details class="account-menu"><summary>'.self::e((string)$account['display_name']).'</summary><div class="account-menu-panel">'.$accountLinks.'<form method="post" action="/logout"><input type="hidden" name="csrf" value="'.self::e(\Koravik\Platform\Security\Security::csrfToken()).'"><button type="submit">Sign out</button></form></div></details></div></header>';
        $html=preg_replace('#<header class="app-header">.*?</header>#s',$header,$html,1)??$html;
        if(!str_contains($html,'app-shell-header'))$html=preg_replace('#(<body[^>]*>)#','$1<a class="skip-link" href="#main">Skip to content</a>'.$header,$html,1)??$html;
        $html=str_replace('<main ','<main data-shell="unified" ',$html);
        return $html;
    }
    private function link(string $href,string $label,string $path,bool $escape=true):string{$active=$path===$href||($href!=='/home'&&str_starts_with($path,$href.'/'));return '<a href="'.$href.'"'.($active?' aria-current="page"':'').'>'.($escape?self::e($label):$label).'</a>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}