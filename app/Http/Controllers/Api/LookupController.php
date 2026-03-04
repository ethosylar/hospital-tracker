<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LookupController extends Controller
{
    public function index()
    {
        return response()->json([
            'departments' => DB::table('lt_departments')->where('is_active',1)->orderBy('name')->get(['id','code','name']),
            'priorities' => DB::table('lt_priorities')->where('is_active',1)->orderBy('sort_order')->get(['id','code','name','sort_order']),
            'project_statuses' => DB::table('st_project_statuses')->where('is_active',1)->orderBy('sort_order')->get(['id','code','name','sort_order']),
            'task_statuses' => DB::table('st_task_statuses')->where('is_active',1)->orderBy('sort_order')->get(['id','code','name','sort_order']),
            'risk_issue_statuses' => DB::table('st_risk_issue_statuses')->where('is_active',1)->orderBy('sort_order')->get(['id','code','name','sort_order']),
            'severities' => DB::table('st_severities')->where('is_active',1)->orderBy('sort_order')->get(['id','code','name','sort_order']),
            'risk_issue_types' => DB::table('lt_risk_issue_types')->where('is_active',1)->orderBy('id')->get(['id','code','name']),
            'roles' => DB::table('lt_roles')->where('is_active',1)->orderBy('name')->get(['id','code','name']),
        ]);
    }
}