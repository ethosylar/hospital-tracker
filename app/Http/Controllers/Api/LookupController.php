<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgreementCategory;
use App\Models\AgreementDocumentType;
use App\Models\AgreementStatus;
use App\Models\AgreementType;
use App\Models\Counterparty;
use App\Models\ExternalSource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\SiteAccess;
use App\Support\ProjectAccess;

class LookupController extends Controller
{
	public function index(Request $request)
	{
		return response()->json([
			'departments' => $this->visibleDepartments($request),

			'priorities' => DB::table('lt_priorities')
				->where('is_active', 1)
				->orderBy('sort_order')
				->get([
					'id',
					'code',
					'name',
					'sort_order',
				]),

			'project_statuses' => DB::table('st_project_statuses')
				->where('is_active', 1)
				->orderBy('sort_order')
				->get([
					'id',
					'code',
					'name',
					'sort_order',
				]),

			'task_statuses' => DB::table('st_task_statuses')
				->where('is_active', 1)
				->orderBy('sort_order')
				->get([
					'id',
					'code',
					'name',
					'sort_order',
				]),

			'risk_issue_statuses' => DB::table('st_risk_issue_statuses')
				->where('is_active', 1)
				->orderBy('sort_order')
				->get([
					'id',
					'code',
					'name',
					'sort_order',
				]),

			'severities' => DB::table('st_severities')
				->where('is_active', 1)
				->orderBy('sort_order')
				->get([
					'id',
					'code',
					'name',
					'sort_order',
				]),

			'risk_issue_types' => DB::table('lt_risk_issue_types')
				->where('is_active', 1)
				->orderBy('id')
				->get([
					'id',
					'code',
					'name',
				]),

			'project_categories' => DB::table('lt_project_categories')
				->where('is_active', 1)
				->orderBy('sort_order')
				->orderBy('name')
				->get([
					'id',
					'code',
					'name',
				]),
		]);
	}

	public function departments(Request $request)
	{
		return $this->lookupResponse(
			$this->visibleDepartments(
				$request
			)
		);
	}

	public function users(Request $request)
	{
		$query = User::query()
			->select([
				'id',
				'name',
				'department_id',
			])
			->orderBy('name');

		if ($request->filled('site_id')) {
			$siteId = (int) $request->input('site_id');

			if (!SiteAccess::canViewSite($request->user(), $siteId)) {
				return $this->lookupResponse(collect());
			}

			$query->whereHas(
				'siteAccesses',
				fn($site) =>
				$site
					->where('site_id', $siteId)
					->where('is_active', true)
			);
		} elseif (!$request->user()->hasPermission('system.all')) {
			$siteIds = SiteAccess::viewSiteIds($request->user());

			if (empty($siteIds)) {
				$query->whereRaw('1 = 0');
			} else {
				$query->whereHas(
					'siteAccesses',
					fn($site) => $site
						->whereIn('site_id', $siteIds)
						->where('is_active', true)
				);
			}
		}

		return $this->lookupResponse($query->get());
	}

	public function priorities()
	{
		return $this->lookupResponse($this->priorityRows());
	}

	public function projectStatuses()
	{
		return $this->lookupResponse($this->projectStatusRows());
	}

	public function taskStatuses()
	{
		return $this->lookupResponse($this->taskStatusRows());
	}

	public function riskIssueStatuses()
	{
		return $this->lookupResponse($this->riskIssueStatusRows());
	}

	public function severities()
	{
		return $this->lookupResponse($this->severityRows());
	}

	public function riskIssueTypes()
	{
		return $this->lookupResponse($this->riskIssueTypeRows());
	}

	public function projectCategories()
	{
		return $this->lookupResponse($this->projectCategoryRows());
	}

	public function externalSources()
	{
		$rows = ExternalSource::query()
			->where('is_active', true)
			->orderBy('name')
			->get(['id', 'code', 'name', 'base_url',]);

		return $this->lookupResponse($rows);
	}

	public function projects(Request $request) {
		$query = ProjectAccess::visibleQuery($request->user());

		if ($request->filled('site_id')) {
			$query->where('dt_projects.site_id',(int) $request->input('site_id'));
		}

		$rows = $query
			->orderBy('code')
			->orderBy('name')
			->get([
				'id',
				'site_id',
				'code',
				'name',
			]);

		return $this->lookupResponse($rows);
	}

	public function agreementStatuses()
	{
		$rows = AgreementStatus::query()
			->where('is_active', true)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name', 'is_terminal',]);

		return $this->lookupResponse($rows);
	}

	public function agreementCategories()
	{
		$rows = AgreementCategory::query()
			->where('is_active', true)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name',]);

		return $this->lookupResponse($rows);
	}

	public function agreementTypes(Request $request)
	{
		$query = AgreementType::query()
			->where('is_active', true);

		if ($request->filled('agreement_category_id')) {
			$query->where('agreement_category_id', (int) $request->input('agreement_category_id'));
		}

		$rows = $query
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'agreement_category_id', 'code', 'name',]);

		return $this->lookupResponse($rows);
	}

	public function counterparties()
	{
		$rows = Counterparty::query()
			->where('is_active', true)
			->orderBy('legal_name')
			->get(['id', 'code', 'counterparty_type', 'legal_name', 'trading_name',]);

		return $this->lookupResponse($rows);
	}

	public function agreementDocumentTypes()
	{
		$rows = AgreementDocumentType::query()
			->where('is_active', true)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name', 'ocr_eligible',]);

		return $this->lookupResponse($rows);
	}

	public function userManagement(Request $request)
	{
		$departments = $this->visibleDepartments($request);
		$permissions = DB::table('lt_permissions')
			->where('is_active', 1)
			->orderBy('module')
			->orderBy('sort_order')
			->orderBy('name')
			->get([
				'id',
				'code',
				'name',
				'module',
				'description',
				'sort_order',
			]);

		$roles = DB::table('lt_roles')
			->where('is_active', 1)
			->orderBy('name')
			->get([
				'id',
				'code',
				'name',
			]);

		$rolePermissions = DB::table('lt_role_permissions as rp')
			->join('lt_roles as r', 'r.id', '=', 'rp.role_id')
			->join('lt_permissions as p', 'p.id', '=', 'rp.permission_id')
			->where('r.is_active', 1)
			->where('p.is_active', 1)
			->orderBy('r.name')
			->orderBy('p.module')
			->orderBy('p.sort_order')
			->orderBy('p.name')
			->get([
				'rp.role_id',
				'rp.permission_id',
				'p.code as permission_code',
				'p.name as permission_name',
				'p.module as permission_module',
			]);

		$permissionsByRole = $rolePermissions
			->groupBy('role_id')
			->map(function ($items) {
				return $items->map(function ($item) {
					return [
						'id' => (int) $item->permission_id,
						'code' => $item->permission_code,
						'name' => $item->permission_name,
						'module' => $item->permission_module,
					];
				})->values();
			});

		$rolesWithPermissions = $roles->map(
			function ($role) use ($permissionsByRole) {
				return [
					'id' => (int) $role->id,
					'code' => $role->code,
					'name' => $role->name,
					'permissions' => $permissionsByRole
						->get($role->id, collect())
						->values(),
				];
			}
		);

		$permissionModules = $permissions
			->pluck('module')
			->filter()
			->unique()
			->values();

		return response()->json([
			'departments' => $departments,
			'roles' => $rolesWithPermissions,
			'permissions' => $permissions,
			'permission_modules' => $permissionModules,
		]);
	}

	private function visibleDepartments(Request $request)
	{
		$query = DB::table('lt_departments as d')
			->join('lt_sites as s', 's.id', '=', 'd.site_id')
			->where('d.is_active', 1)
			->where('s.is_active', 1);

		if (!$request->user()->hasPermission('system.all')) {
			$siteIds = SiteAccess::viewSiteIds($request->user());

			if (empty($siteIds)) {
				$query->whereRaw('1 = 0');
			} else {
				$query->whereIn('d.site_id', $siteIds);
			}
		}

		if ($request->filled('site_id')) {
			$query->where('d.site_id', (int) $request->input('site_id'));
		}

		return $query
			->orderBy('s.name')
			->orderBy('d.name')
			->get([
				'd.id',
				'd.site_id',
				'd.code',
				'd.name',
				's.code as site_code',
				's.name as site_name',
			]);
	}

	private function priorityRows()
	{
		return DB::table('lt_priorities')
			->where('is_active', 1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name', 'sort_order',]);
	}


	private function projectStatusRows()
	{
		return DB::table('st_project_statuses')
			->where('is_active', 1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name', 'sort_order',]);
	}


	private function taskStatusRows()
	{
		return DB::table('st_task_statuses')
			->where('is_active', 1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name', 'sort_order',]);
	}


	private function riskIssueStatusRows()
	{
		return DB::table('st_risk_issue_statuses')
			->where('is_active', 1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name', 'sort_order',]);
	}


	private function severityRows()
	{
		return DB::table('st_severities')
			->where('is_active', 1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name', 'sort_order',]);
	}


	private function riskIssueTypeRows()
	{
		return DB::table('lt_risk_issue_types')
			->where('is_active', 1)
			->orderBy('name')
			->get(['id', 'code', 'name',]);
	}


	private function projectCategoryRows()
	{
		return DB::table('lt_project_categories')
			->where('is_active', 1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name',]);
	}


	private function lookupResponse($rows)
	{
		return response()->json(['data' => $rows->values(),]);
	}
}
