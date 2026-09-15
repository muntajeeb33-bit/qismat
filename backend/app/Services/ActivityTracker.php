<?php
namespace App\Services;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
class ActivityTracker { public function record(Request $request,string $event,?string $entityType=null,?int $entityId=null,array $metadata=[]):void { ActivityLog::create(['user_id'=>$request->user()?->id,'event_type'=>$event,'entity_type'=>$entityType,'entity_id'=>$entityId,'ip_address'=>$request->ip(),'platform'=>$request->header('X-Qismat-Platform','web'),'app_version'=>$request->header('X-Qismat-App-Version'),'device'=>$request->userAgent(),'metadata'=>$metadata,'created_at'=>now()]); } }
