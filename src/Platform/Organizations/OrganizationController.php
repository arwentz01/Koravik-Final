<?php

declare(strict_types=1);

namespace Koravik\Platform\Organizations;

use Koravik\Platform\Database\Database;
use Koravik\Platform\Security\Security;
use RuntimeException;

final class OrganizationController
{
    public function __construct(private readonly Database $database) {}

    public function handle(string $method,string $path): bool
    {
        if($method==='GET'&&$path==='/organizations'){$this->index();return true;}
        if($method==='POST'&&$path==='/organizations'){$this->create();return true;}
        if($method==='GET'&&preg_match('#^/organizations/invitations/([A-Za-z0-9_-]+)$#',$path,$m)){$this->accept($m[1]);return true;}
        if($method==='GET'&&preg_match('#^/organizations/([a-f0-9-]{36})$#',$path,$m)){$this->show($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/organizations/([a-f0-9-]{36})/invitations$#',$path,$m)){$this->invite($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/organizations/([a-f0-9-]{36})/members/([a-f0-9-]{36})/role$#',$path,$m)){$this->role($m[1],$m[2]);return true;}
        if($method==='POST'&&preg_match('#^/organizations/([a-f0-9-]{36})/members/([a-f0-9-]{36})/remove$#',$path,$m)){$this->remove($m[1],$m[2]);return true;}
        if($method==='POST'&&preg_match('#^/organizations/([a-f0-9-]{36})/ownership/([a-f0-9-]{36})$#',$path,$m)){$this->transfer($m[1],$m[2]);return true;}
        if($method==='POST'&&preg_match('#^/organizations/([a-f0-9-]{36})/events$#',$path,$m)){$this->event($m[1]);return true;}
        if($method==='POST'&&preg_match('#^/organizations/([a-f0-9-]{36})/links$#',$path,$m)){$this->link($m[1]);return true;}
        return false;
    }

    private function index():void
    {
        $a=Security::requireAccount();$service=new OrganizationService($this->database);$cards='';foreach($service->memberships((string)$a['id']) as $o){$cards.='<article class="surface"><p class="eyebrow">'.self::e(ucfirst((string)$o['membership_role'])).'</p><h2>'.self::e((string)$o['name']).'</h2><p>'.self::e((string)($o['summary']??'')).'</p><a class="button" href="/organizations/'.self::e((string)$o['id']).'">Open organization</a></article>';}
        $body='<section class="page-heading"><div><p class="eyebrow">Organizations</p><h1>Shared work without losing personal ownership.</h1><p>Organizations are optional operating spaces for groups, nonprofits, and communities. Your personal Koravik remains independent.</p></div></section><section class="surface"><h2>Create an organization</h2><form method="post" action="/organizations" class="form-grid"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Name<input name="name" required maxlength="180"></label><label>Primary timezone<input name="timezone" value="America/New_York"></label><label class="full">Summary<textarea name="summary"></textarea></label><button class="button" type="submit">Create organization</button></form></section><section><h2>Your organizations</h2><div class="grid">'.($cards?:'<article class="empty-state"><h3>No organizations yet.</h3><p>You can still use every personal Koravik feature without one.</p></article>').'</div></section>';$this->page('Organizations',$body);
    }

    private function show(string $id):void
    {
        $a=Security::requireAccount();$service=new OrganizationService($this->database);try{$data=$service->dashboard((string)$a['id'],$id);}catch(RuntimeException){$this->notFound();return;}$o=$data['organization'];$members='';foreach($data['members'] as $m){$controls='';if($service->can((string)$a['id'],$id,'manage_members')&&$m['role']!=='owner'){$controls='<form method="post" action="/organizations/'.$id.'/members/'.self::e((string)$m['id']).'/role"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><select name="role"><option>admin</option><option>creator</option><option>member</option></select><button class="button secondary">Change role</button></form><form method="post" action="/organizations/'.$id.'/members/'.self::e((string)$m['id']).'/remove"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><button class="button secondary">Remove</button></form>';}$members.='<article class="surface"><h3>'.self::e((string)$m['display_name']).'</h3><p>'.self::e((string)$m['email']).' · '.self::e(ucfirst((string)$m['role'])).'</p>'.$controls.'</article>';}
        $events='';foreach($data['events'] as $e)$events.='<li><a href="/gather/events/'.self::e((string)$e['id']).'">'.self::e((string)$e['title']).'</a> · '.self::e((string)$e['starts_at']).'</li>';
        $links='';foreach($data['links'] as $l)$links.='<li>'.self::e((string)($l['hostname']??'krvk.nl')).'/'.self::e((string)$l['slug']).' · '.self::e((string)$l['label']).'</li>';
        $activity='';foreach($data['activity'] as $x)$activity.='<li>'.self::e((string)$x['action']).' · '.self::e((string)$x['created_at']).' UTC</li>';
        $admin=$service->can((string)$a['id'],$id,'manage_members')?'<section class="surface"><h2>Invite a member</h2><form method="post" action="/organizations/'.$id.'/invitations"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Email<input type="email" name="email" required></label><label>Role<select name="role"><option value="member">Member</option><option value="creator">Creator</option><option value="admin">Admin</option></select></label><button class="button">Create invitation</button></form></section>':'';
        $creator=$service->can((string)$a['id'],$id,'create_content')?'<section class="grid"><article class="surface"><h2>Create organization event</h2><form method="post" action="/organizations/'.$id.'/events"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Title<input name="title" required></label><label>Starts<input type="datetime-local" name="starts_at" required></label><label>Venue<input name="venue"></label><label>Description<textarea name="description"></textarea></label><input type="hidden" name="guest_registration_enabled" value="1"><input type="hidden" name="waitlist_enabled" value="1"><button class="button">Create event</button></form></article><article class="surface"><h2>Create organization link</h2><form method="post" action="/organizations/'.$id.'/links"><input type="hidden" name="csrf" value="'.self::e(Security::csrfToken()).'"><label>Label<input name="label" required></label><label>Destination<input type="url" name="destination" required></label><button class="button">Create link</button></form></article></section>':'';
        $body='<section class="page-heading"><div><p class="eyebrow">Organization · '.self::e(ucfirst((string)$o['membership_role'])).'</p><h1>'.self::e((string)$o['name']).'</h1><p>'.self::e((string)($o['summary']??'')).'</p></div><a href="/organizations">All organizations</a></section><section class="grid"><article class="surface"><h2>Upcoming and recent events</h2><ul>'.($events?:'<li>No organization events yet.</li>').'</ul></article><article class="surface"><h2>Beacon links</h2><ul>'.($links?:'<li>No organization links yet.</li>').'</ul></article></section>'.$creator.$admin.'<section><h2>Members</h2><div class="grid">'.$members.'</div></section><section class="surface"><h2>Recent activity</h2><ul>'.($activity?:'<li>No activity yet.</li>').'</ul></section>';$this->page((string)$o['name'],$body);
    }

    private function create():void{$this->csrf();$a=Security::requireAccount();try{$id=(new OrganizationService($this->database))->create((string)$a['id'],$_POST);$_SESSION['flash']='Organization created.';$this->go('/organizations/'.$id);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();$this->go('/organizations');}}
    private function invite(string $id):void{$this->csrf();$a=Security::requireAccount();try{$token=(new OrganizationService($this->database))->invite((string)$a['id'],$id,(string)($_POST['email']??''),(string)($_POST['role']??'member'));$_SESSION['flash']='Invitation created. Share this acceptance path securely: /organizations/invitations/'.$token;}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/organizations/'.$id);}
    private function accept(string $token):void{$a=Security::requireAccount();try{$id=(new OrganizationService($this->database))->acceptInvitation((string)$a['id'],$token);$_SESSION['flash']='Organization invitation accepted.';$this->go('/organizations/'.$id);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();$this->go('/organizations');}}
    private function role(string $id,string $member):void{$this->csrf();$a=Security::requireAccount();try{(new OrganizationService($this->database))->changeRole((string)$a['id'],$id,$member,(string)($_POST['role']??''));$_SESSION['flash']='Membership role updated.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/organizations/'.$id);}
    private function remove(string $id,string $member):void{$this->csrf();$a=Security::requireAccount();try{(new OrganizationService($this->database))->removeMember((string)$a['id'],$id,$member);$_SESSION['flash']='Member removed.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/organizations/'.$id);}
    private function transfer(string $id,string $member):void{$this->csrf();$a=Security::requireAccount();try{(new OrganizationService($this->database))->transferOwnership((string)$a['id'],$id,$member);$_SESSION['flash']='Organization ownership transferred.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/organizations/'.$id);}
    private function event(string $id):void{$this->csrf();$a=Security::requireAccount();try{$event=(new OrganizationService($this->database))->createOrganizationEvent((string)$a['id'],$id,$_POST);$_SESSION['flash']='Organization event created.';$this->go('/gather/events/'.$event);}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();$this->go('/organizations/'.$id);}}
    private function link(string $id):void{$this->csrf();$a=Security::requireAccount();try{(new OrganizationService($this->database))->createOrganizationLink((string)$a['id'],$id,(string)($_POST['label']??''),(string)($_POST['destination']??''));$_SESSION['flash']='Organization Beacon link created.';}catch(RuntimeException $e){$_SESSION['flash']=$e->getMessage();}$this->go('/organizations/'.$id);}
    private function csrf():void{if(!Security::verifyCsrf((string)($_POST['csrf']??'')))throw new RuntimeException('Your session token expired.');}
    private function go(string $to):never{header('Location: '.$to,true,303);exit;}
    private function notFound():void{http_response_code(404);$this->page('Organization not found','<section class="empty-state"><h1>That organization is unavailable.</h1></section>');}
    private function page(string $title,string $body):void{$flash='';if(isset($_SESSION['flash'])){$flash='<div class="notice" role="status">'.self::e((string)$_SESSION['flash']).'</div>';unset($_SESSION['flash']);}echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.self::e($title).' · Koravik</title><link rel="stylesheet" href="/assets/app.css"><link rel="stylesheet" href="/assets/journey.css"></head><body><main id="main" class="page">'.$flash.$body.'</main><footer>Koravik · Organizations</footer></body></html>';}
    private static function e(string $v):string{return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
