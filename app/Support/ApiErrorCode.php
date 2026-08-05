<?php
	
	namespace App\Support;
	
	class ApiErrorCode
	{
		// ============================================================
		// Generic / Global
		// ============================================================
		public const VALIDATION_FAILED = 'VALIDATION_FAILED';
		public const UNAUTHORIZED      = 'UNAUTHORIZED';
		public const FORBIDDEN         = 'FORBIDDEN';
		public const NOT_FOUND         = 'NOT_FOUND';
		public const DB_ERROR          = 'DB_ERROR';
		public const SERVER_ERROR      = 'SERVER_ERROR';
		
		// Optional generic domain errors (useful for 409/422)
		public const CONFLICT          = 'CONFLICT';
		public const BAD_REQUEST       = 'BAD_REQUEST';
		
		// ============================================================
		// Severity (st_severities)
		// ============================================================
		public const SEVERITY_CREATE_FAILED = 'SEVERITY_CREATE_FAILED';
		public const SEVERITY_UPDATE_FAILED = 'SEVERITY_UPDATE_FAILED';
		public const SEVERITY_DELETE_FAILED = 'SEVERITY_DELETE_FAILED';
		
		// ============================================================
		// Department (lt_departments)
		// ============================================================
		public const DEPARTMENT_CREATE_FAILED = 'DEPARTMENT_CREATE_FAILED';
		public const DEPARTMENT_UPDATE_FAILED = 'DEPARTMENT_UPDATE_FAILED';
		public const DEPARTMENT_DELETE_FAILED = 'DEPARTMENT_DELETE_FAILED';
		
		// ============================================================
		// Project Status (st_project_statuses)
		// ============================================================
		public const PROJECT_STATUS_CREATE_FAILED = 'PROJECT_STATUS_CREATE_FAILED';
		public const PROJECT_STATUS_UPDATE_FAILED = 'PROJECT_STATUS_UPDATE_FAILED';
		public const PROJECT_STATUS_DELETE_FAILED = 'PROJECT_STATUS_DELETE_FAILED';
		
		// ============================================================
		// Priority (lt_priorities)
		// ============================================================
		public const PRIORITY_CREATE_FAILED = 'PRIORITY_CREATE_FAILED';
		public const PRIORITY_UPDATE_FAILED = 'PRIORITY_UPDATE_FAILED';
		public const PRIORITY_DELETE_FAILED = 'PRIORITY_DELETE_FAILED';
		
		// ============================================================
		// Task Status (st_task_statuses)
		// ============================================================
		public const TASK_STATUS_CREATE_FAILED = 'TASK_STATUS_CREATE_FAILED';
		public const TASK_STATUS_UPDATE_FAILED = 'TASK_STATUS_UPDATE_FAILED';
		public const TASK_STATUS_DELETE_FAILED = 'TASK_STATUS_DELETE_FAILED';
		
		// ============================================================
		// Role (lt_roles)
		// ============================================================
		public const ROLE_CREATE_FAILED = 'ROLE_CREATE_FAILED';
		public const ROLE_UPDATE_FAILED = 'ROLE_UPDATE_FAILED';
		public const ROLE_DELETE_FAILED = 'ROLE_DELETE_FAILED';
		
		// ============================================================
		// Risk Issue Status (st_risk_issue_statuses)
		// ============================================================
		public const RISK_STATUS_CREATE_FAILED = 'RISK_STATUS_CREATE_FAILED';
		public const RISK_STATUS_UPDATE_FAILED = 'RISK_STATUS_UPDATE_FAILED';
		public const RISK_STATUS_DELETE_FAILED = 'RISK_STATUS_DELETE_FAILED';
		
		// ============================================================
		// Risk Issue Type (lt_risk_issue_types)
		// ============================================================
		public const RISK_ISSUE_TYPE_CREATE_FAILED = 'RISK_ISSUE_TYPE_CREATE_FAILED';
		public const RISK_ISSUE_TYPE_UPDATE_FAILED = 'RISK_ISSUE_TYPE_UPDATE_FAILED';
		public const RISK_ISSUE_TYPE_DELETE_FAILED = 'RISK_ISSUE_TYPE_DELETE_FAILED';
		
		// ============================================================
		// Project (dt_projects)
		// ============================================================
		public const PROJECT_CREATE_FAILED = 'PROJECT_CREATE_FAILED';
		public const PROJECT_UPDATE_FAILED = 'PROJECT_UPDATE_FAILED';
		public const PROJECT_DELETE_FAILED = 'PROJECT_DELETE_FAILED';
		
		// ============================================================
		// Project Task (dt_project_tasks)
		// ============================================================
		public const PROJECT_TASK_CREATE_FAILED = 'PROJECT_TASK_CREATE_FAILED';
		public const PROJECT_TASK_UPDATE_FAILED = 'PROJECT_TASK_UPDATE_FAILED';
		public const PROJECT_TASK_DELETE_FAILED = 'PROJECT_TASK_DELETE_FAILED';
		
		// Rule-level (your controller already returns 422 for these)
		public const PROJECT_TASK_INVALID_PARENT_TASK = 'PROJECT_TASK_INVALID_PARENT_TASK';
		public const PROJECT_TASK_INVALID_DEPENDENCY  = 'PROJECT_TASK_INVALID_DEPENDENCY';
		
		// ============================================================
		// Project Milestone (dt_project_milestones)
		// ============================================================
		public const PROJECT_MILESTONE_CREATE_FAILED = 'PROJECT_MILESTONE_CREATE_FAILED';
		public const PROJECT_MILESTONE_UPDATE_FAILED = 'PROJECT_MILESTONE_UPDATE_FAILED';
		public const PROJECT_MILESTONE_DELETE_FAILED = 'PROJECT_MILESTONE_DELETE_FAILED';
		
		public const PROJECT_MILESTONE_INVALID_STATUS = 'PROJECT_MILESTONE_INVALID_STATUS';
		public const PROJECT_NOT_FOUND                = 'PROJECT_NOT_FOUND'; // if you want a more specific 404 than NOT_FOUND
		
		// ============================================================
		// External Source (lt_external_sources)
		// ============================================================
		public const EXTERNAL_SOURCE_CREATE_FAILED = 'EXTERNAL_SOURCE_CREATE_FAILED';
		public const EXTERNAL_SOURCE_UPDATE_FAILED = 'EXTERNAL_SOURCE_UPDATE_FAILED';
		public const EXTERNAL_SOURCE_DELETE_FAILED = 'EXTERNAL_SOURCE_DELETE_FAILED';
		
		// ============================================================
		// External Risk Issue (dt_external_risk_issues, dt_external_risk_issue_links)
		// ============================================================
		public const EXTERNAL_RISK_ISSUE_CREATE_FAILED = 'EXTERNAL_RISK_ISSUE_CREATE_FAILED';
		public const EXTERNAL_RISK_ISSUE_UPDATE_FAILED = 'EXTERNAL_RISK_ISSUE_UPDATE_FAILED';
		public const EXTERNAL_RISK_ISSUE_DELETE_FAILED = 'EXTERNAL_RISK_ISSUE_DELETE_FAILED';
		public const EXTERNAL_RISK_ISSUE_DUPLICATE_EXTERNAL_ID = 'EXTERNAL_RISK_ISSUE_DUPLICATE_EXTERNAL_ID';
		public const EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD   = 'EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD';
		public const EXTERNAL_RISK_ISSUE_DUPLICATE_LINK = 'EXTERNAL_RISK_ISSUE_DUPLICATE_LINK';
		public const EXTERNAL_RISK_ISSUE_LINK_FAILED = 'EXTERNAL_RISK_ISSUE_LINK_FAILED';
		public const EXTERNAL_RISK_ISSUE_UNLINK_FAILED = 'EXTERNAL_RISK_ISSUE_UNLINK_FAILED';
		public const EXTERNAL_RISK_ISSUE_LINK_NOT_FOUND = 'EXTERNAL_RISK_ISSUE_LINK_NOT_FOUND';
		
		// ============================================================
		// Dashboard (optional; only if you start wrapping overview() with try/catch)
		// ============================================================
		public const DASHBOARD_LOAD_FAILED = 'DASHBOARD_LOAD_FAILED';
		
		// ============================================================
		// User (users) + User Roles (dt_user_roles)
		// ============================================================
		public const USER_CREATE_FAILED = 'USER_CREATE_FAILED';
		public const USER_UPDATE_FAILED = 'USER_UPDATE_FAILED';
		public const USER_DELETE_FAILED = 'USER_DELETE_FAILED';
		
		public const USER_ROLE_SYNC_FAILED = 'USER_ROLE_SYNC_FAILED';
		
		// ============================================================
		// FileUpload (File) (dt_files)
		// ============================================================		
		public const FILE_UPLOAD_FAILED = 'FILE_UPLOAD_FAILED';
		public const FILE_ATTACH_FAILED = 'FILE_ATTACH_FAILED';
		public const FILE_DETACH_FAILED = 'FILE_DETACH_FAILED';
		public const FILE_MOVE_FAILED = 'FILE_MOVE_FAILED';
		public const FILE_NOT_LINKED = 'FILE_NOT_LINKED';
		public const FILE_PHYSICAL_MISSING = 'FILE_PHYSICAL_MISSING';
		
		// ============================================================
		// Project Budget Line (File) (dt_project_budget_line)
		// ============================================================	
		public const PROJECT_BUDGET_LINE_CREATE_FAILED = 'PROJECT_BUDGET_LINE_CREATE_FAILED';
		public const PROJECT_BUDGET_LINE_UPDATE_FAILED = 'PROJECT_BUDGET_LINE_UPDATE_FAILED';
		public const PROJECT_BUDGET_LINE_DELETE_FAILED = 'PROJECT_BUDGET_LINE_DELETE_FAILED';
		public const PROJECT_BUDGET_LINE_DUPLICATE_CODE = 'PROJECT_BUDGET_LINE_DUPLICATE_CODE';
		
		// ============================================================
		// Project Category (lt_project_categories)
		// ============================================================	
		public const PROJECT_CATEGORY_CREATE_FAILED = 'PROJECT_CATEGORY_CREATE_FAILED';
		public const PROJECT_CATEGORY_UPDATE_FAILED = 'PROJECT_CATEGORY_UPDATE_FAILED';
		public const PROJECT_CATEGORY_DELETE_FAILED = 'PROJECT_CATEGORY_DELETE_FAILED';
		
		// ============================================================
		// Budget Allocations for Task & Milestones (dt_project_budget_allocations)
		// ============================================================	
		public const PROJECT_BUDGET_ALLOC_CREATE_FAILED = 'PROJECT_BUDGET_ALLOC_CREATE_FAILED';
		public const PROJECT_BUDGET_ALLOC_UPDATE_FAILED = 'PROJECT_BUDGET_ALLOC_UPDATE_FAILED';
		public const PROJECT_BUDGET_ALLOC_DELETE_FAILED = 'PROJECT_BUDGET_ALLOC_DELETE_FAILED';
		
		public const PROJECT_BUDGET_ALLOC_INVALID_TARGET = 'PROJECT_BUDGET_ALLOC_INVALID_TARGET';
		public const PROJECT_BUDGET_ALLOC_LINE_NOT_IN_PROJECT = 'PROJECT_BUDGET_ALLOC_LINE_NOT_IN_PROJECT';
		public const PROJECT_BUDGET_ALLOC_TASK_NOT_IN_PROJECT = 'PROJECT_BUDGET_ALLOC_TASK_NOT_IN_PROJECT';
		public const PROJECT_BUDGET_ALLOC_MILESTONE_NOT_IN_PROJECT = 'PROJECT_BUDGET_ALLOC_MILESTONE_NOT_IN_PROJECT';
		
		public const PROJECT_BUDGET_ALLOC_EXCEEDS_LINE_TOTAL = 'PROJECT_BUDGET_ALLOC_EXCEEDS_LINE_TOTAL';
		
		// ============================================================
		// External Source i.e. ePTW Integration (dt_integration_sync_runs, dt_project_permit_links, dt_external_permits)
		// ============================================================
		public const EPTW_SOURCE_NOT_CONFIGURED ='EPTW_SOURCE_NOT_CONFIGURED';
		public const EPTW_IMPORT_FAILED ='EPTW_IMPORT_FAILED';
		public const EPTW_PERMIT_LINK_CREATE_FAILED ='EPTW_PERMIT_LINK_CREATE_FAILED';
		public const EPTW_PERMIT_LINK_DELETE_FAILED ='EPTW_PERMIT_LINK_DELETE_FAILED';
		public const EPTW_PERMIT_LINKED_TO_ANOTHER_PROJECT ='EPTW_PERMIT_LINKED_TO_ANOTHER_PROJECT';
		public const EPTW_TASK_PROJECT_MISMATCH ='EPTW_TASK_PROJECT_MISMATCH';
		public const EPTW_PERMIT_SOURCE_DELETED ='EPTW_PERMIT_SOURCE_DELETED';
		public const EPTW_SYNC_FAILED = 'EPTW_SYNC_FAILED';
		public const EPTW_API_REQUEST_FAILED = 'EPTW_API_REQUEST_FAILED';
		public const EPTW_SYNC_ALREADY_RUNNING = 'EPTW_SYNC_ALREADY_RUNNING';
		
		// ============================================================
		// Permission Based Role Access (lt_role_permissions, lt_permissions)
		// ============================================================		
		public const UNAUTHENTICATED = 'UNAUTHENTICATED';
		
		
		// ============================================================
		// Agreement Status (st_agreement_statuses)
		// ============================================================
		public const AGREEMENT_STATUS_CREATE_FAILED = 'AGREEMENT_STATUS_CREATE_FAILED';
		public const AGREEMENT_STATUS_UPDATE_FAILED = 'AGREEMENT_STATUS_UPDATE_FAILED';
		public const AGREEMENT_STATUS_DELETE_FAILED = 'AGREEMENT_STATUS_DELETE_FAILED';
		public const AGREEMENT_STATUS_PROTECTED = 'AGREEMENT_STATUS_PROTECTED';
		
		// ============================================================
		// Agreement Category (lt_agreement_categories)
		// ============================================================
		public const AGREEMENT_CATEGORY_CREATE_FAILED = 'AGREEMENT_CATEGORY_CREATE_FAILED';
		public const AGREEMENT_CATEGORY_UPDATE_FAILED = 'AGREEMENT_CATEGORY_UPDATE_FAILED';
		public const AGREEMENT_CATEGORY_DELETE_FAILED = 'AGREEMENT_CATEGORY_DELETE_FAILED';
		public const AGREEMENT_CATEGORY_PROTECTED = 'AGREEMENT_CATEGORY_PROTECTED';
		public const AGREEMENT_CATEGORY_IN_USE = 'AGREEMENT_CATEGORY_IN_USE';
		
		// ============================================================
		// Agreement Type (lt_agreement_types)
		// ============================================================
		public const AGREEMENT_TYPE_CREATE_FAILED = 'AGREEMENT_TYPE_CREATE_FAILED';
		public const AGREEMENT_TYPE_UPDATE_FAILED = 'AGREEMENT_TYPE_UPDATE_FAILED';
		public const AGREEMENT_TYPE_DELETE_FAILED = 'AGREEMENT_TYPE_DELETE_FAILED';
		public const AGREEMENT_TYPE_PROTECTED = 'AGREEMENT_TYPE_PROTECTED';
		
		// ============================================================
		// Agreement Counterparty (dt_counterparties)
		// ============================================================
		public const COUNTERPARTY_DUPLICATE = 'COUNTERPARTY_DUPLICATE';
		public const COUNTERPARTY_CREATE_FAILED = 'COUNTERPARTY_CREATE_FAILED';
		public const COUNTERPARTY_UPDATE_FAILED = 'COUNTERPARTY_UPDATE_FAILED';
		public const COUNTERPARTY_DELETE_FAILED = 'COUNTERPARTY_DELETE_FAILED';
		
		// ============================================================
		// Agreement (dt_counterparties)
		// ============================================================
		public const AGREEMENT_ACCESS_DENIED = 'AGREEMENT_ACCESS_DENIED';
		public const AGREEMENT_CREATE_FAILED = 'AGREEMENT_CREATE_FAILED';
		public const AGREEMENT_UPDATE_FAILED = 'AGREEMENT_UPDATE_FAILED';
		public const AGREEMENT_NOT_EDITABLE = 'AGREEMENT_NOT_EDITABLE';
		public const AGREEMENT_INVALID_TRANSITION = 'AGREEMENT_INVALID_TRANSITION';
		public const AGREEMENT_INVALID_DATES = 'AGREEMENT_INVALID_DATES';
		public const AGREEMENT_TYPE_CATEGORY_MISMATCH = 'AGREEMENT_TYPE_CATEGORY_MISMATCH';
		public const AGREEMENT_REVIEW_FAILED = 'AGREEMENT_REVIEW_FAILED';
		public const AGREEMENT_SUBMIT_FAILED = 'AGREEMENT_SUBMIT_FAILED';
		public const AGREEMENT_APPROVE_FAILED = 'AGREEMENT_APPROVE_FAILED';
		public const AGREEMENT_ACTIVATE_FAILED = 'AGREEMENT_ACTIVATE_FAILED';
		public const AGREEMENT_AMEND_FAILED = 'AGREEMENT_AMEND_FAILED';
		public const AGREEMENT_RENEW_FAILED = 'AGREEMENT_RENEW_FAILED';
		public const AGREEMENT_TERMINATE_FAILED = 'AGREEMENT_TERMINATE_FAILED';
		public const AGREEMENT_ARCHIVE_FAILED = 'AGREEMENT_ARCHIVE_FAILED';
		public const AGREEMENT_CANCEL_FAILED = 'AGREEMENT_CANCEL_FAILED';
		public const AGREEMENT_PROJECT_ALREADY_LINKED = 'AGREEMENT_PROJECT_ALREADY_LINKED';
		public const AGREEMENT_PROJECT_LINK_NOT_FOUND = 'AGREEMENT_PROJECT_LINK_NOT_FOUND';
		public const AGREEMENT_PROJECT_LINK_FAILED = 'AGREEMENT_PROJECT_LINK_FAILED';
		public const AGREEMENT_PROJECT_UNLINK_FAILED = 'AGREEMENT_PROJECT_UNLINK_FAILED';
		
		// ============================================================
		// Agreement Document Type (lt_agreement_document_types)
		// ============================================================
		public const AGREEMENT_DOCUMENT_TYPE_CREATE_FAILED = 'AGREEMENT_DOCUMENT_TYPE_CREATE_FAILED';
		public const AGREEMENT_DOCUMENT_TYPE_UPDATE_FAILED = 'AGREEMENT_DOCUMENT_TYPE_UPDATE_FAILED';
		public const AGREEMENT_DOCUMENT_TYPE_DELETE_FAILED = 'AGREEMENT_DOCUMENT_TYPE_DELETE_FAILED';
		public const AGREEMENT_DOCUMENT_TYPE_PROTECTED = 'AGREEMENT_DOCUMENT_TYPE_PROTECTED';
		
		// ============================================================
		// Agreement Document File (dt_agreement_files)
		// ============================================================
		public const AGREEMENT_FILE_NOT_LINKED = 'AGREEMENT_FILE_NOT_LINKED';
		public const AGREEMENT_FILE_UPLOAD_FAILED = 'AGREEMENT_FILE_UPLOAD_FAILED';
		public const AGREEMENT_FILE_UPDATE_FAILED = 'AGREEMENT_FILE_UPDATE_FAILED';
		public const AGREEMENT_FILE_DETACH_FAILED = 'AGREEMENT_FILE_DETACH_FAILED';
		public const AGREEMENT_FILE_PHYSICAL_MISSING = 'AGREEMENT_FILE_PHYSICAL_MISSING';
		public const AGREEMENT_FILE_INVALID_SUPERSEDES = 'AGREEMENT_FILE_INVALID_SUPERSEDES';
		
		
		// ============================================================
		// Agreement Document OCR (dt_file_text_extractions)
		// ============================================================
		public const AGREEMENT_OCR_DISABLED = 'AGREEMENT_OCR_DISABLED';
		public const AGREEMENT_OCR_NOT_ELIGIBLE = 'AGREEMENT_OCR_NOT_ELIGIBLE';
		public const AGREEMENT_OCR_ALREADY_PROCESSING = 'AGREEMENT_OCR_ALREADY_PROCESSING';
		public const AGREEMENT_OCR_REQUEST_FAILED = 'AGREEMENT_OCR_REQUEST_FAILED';
		
		// ============================================================
		// Project Budget Allocation (dt_project_budget_allocations)
		// ============================================================
		public const PROJECT_BUDGET_ALLOCATION_NOT_FOUND = 'PROJECT_BUDGET_ALLOCATION_NOT_FOUND';
		public const PROJECT_BUDGET_ALLOCATION_CREATE_FAILED = 'PROJECT_BUDGET_ALLOCATION_CREATE_FAILED';
		public const PROJECT_BUDGET_ALLOCATION_UPDATE_FAILED = 'PROJECT_BUDGET_ALLOCATION_UPDATE_FAILED';
		public const PROJECT_BUDGET_ALLOCATION_DELETE_FAILED = 'PROJECT_BUDGET_ALLOCATION_DELETE_FAILED';
		
		
	}
