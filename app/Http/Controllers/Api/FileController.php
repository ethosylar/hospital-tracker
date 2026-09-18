<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttachExistingFileRequest;
use App\Http\Requests\MoveTaskFileRequest;
use App\Http\Requests\StoreFileUploadRequest;
use App\Http\Resources\StoredFileResource;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\StoredFile;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\StoredFileAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class FileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PROJECT FILES
    |--------------------------------------------------------------------------
    */

    public function projectIndex(Project $project)
    {
        $files = $project
            ->files()
            ->where('dt_files.site_id', $project->site_id)
            ->with(['site:id,code,name,short_name',])
            ->select('dt_files.*')
            ->orderByDesc('dt_files.id')
            ->paginate(50);

        return StoredFileResource::collection($files);
    }

    public function projectUpload(StoreFileUploadRequest $request, Project $project)
    {
        $uploaded = $request->file('file');

        if (!$uploaded) {
            return ApiResponse::error(
                ApiErrorCode::FILE_UPLOAD_FAILED,
                'Missing file.',
                [],
                422
            );
        }

        if (!StoredFileAccess::canManageSite($request->user(), (int) $project->site_id)) {
            return ApiResponse::error(
                ApiErrorCode::FILE_SITE_ACCESS_DENIED,
                'You do not have management access to this Site.',
                [],
                403
            );
        }

        $disk = config('filesystems.default', 'local');
        $dir = 'uploads/files/' . $project->site_id . '/' . date('Y/m');
        $path = null;

        try {
            $path = $uploaded->store($dir, $disk);
            $checksum = hash_file('sha256', $uploaded->getRealPath());

            $file = DB::transaction(
                function () use (
                    $disk,
                    $path,
                    $uploaded,
                    $checksum,
                    $request,
                    $project
                ) {
                    $file =
                        StoredFile::create([
                            'site_id' => (int) $project->site_id,
                            'disk' => $disk,
                            'path' => $path,
                            'original_name' => $uploaded->getClientOriginalName(),
                            'mime_type' => $uploaded->getClientMimeType(),
                            'size' => $uploaded->getSize() ?? 0,
                            'checksum' => $checksum,
                            'uploaded_by_user_id' => $request->user()->id,
                        ]);

                    $project->files()->syncWithoutDetaching([$file->id,]);

                    return $file;
                }
            );

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE',
                (int) $file->id,
                'CREATE',
                [
                    'site_id' => (int) $project->site_id,
                    'context' => 'PROJECT',
                    'project_id' => (int) $project->id,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                    'checksum' => $file->checksum,
                    'disk' => $file->disk,
                    'path' => $file->path,
                ]
            );

            $file->load(['site:id,code,name,short_name',]);

            return (new StoredFileResource($file))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);

            if ($path && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            return ApiResponse::error(
                ApiErrorCode::FILE_UPLOAD_FAILED,
                'Failed to upload file.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function projectAttach(AttachExistingFileRequest $request, Project $project)
    {
        $fileId = (int) $request->validated('file_id');

        /*
        |--------------------------------------------------------------------------
        | Resolve file through Site visibility
        |--------------------------------------------------------------------------
        */

        $file = StoredFileAccess::visibleQuery($request->user())->find($fileId);

        if (!$file) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Same Site
        |--------------------------------------------------------------------------
        */

        if ((int) $file->site_id !== (int) $project->site_id) {
            return ApiResponse::error(
                ApiErrorCode::FILE_SITE_MISMATCH,
                'The file and Project must belong to the same Site.',
                [
                    'file_id' => (int) $file->id,
                    'file_site_id' => (int) $file->site_id,
                    'project_id' => (int) $project->id,
                    'project_site_id' => (int) $project->site_id,
                ],
                422
            );
        }

        if (!StoredFileAccess::canManage($request->user(), $file)) {
            return ApiResponse::error(
                ApiErrorCode::FILE_SITE_ACCESS_DENIED,
                'You do not have management access to this file Site.',
                [],
                403
            );
        }

        $project->files()->syncWithoutDetaching([$file->id,]);

        \App\Support\Audit::log(
            $request->user()->id,
            'FILE_LINK',
            (int) $file->id,
            'ATTACH',
            [
                'site_id' => (int) $project->site_id,
                'context' => 'PROJECT',
                'project_id' => (int) $project->id,
            ]
        );

        return response()->json(['ok' => true,]);
    }

    public function projectDetach(Request $request, Project $project, StoredFile $file)
    {
        if ((int) $file->site_id !== (int) $project->site_id) {
            abort(404);
        }

        $isLinked =
            $project
            ->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(
                ApiErrorCode::FILE_NOT_LINKED,
                'File is not linked to this project.',
                [],
                404
            );
        }

        try {
            $snapshot = [
                'site_id' => (int) $project->site_id,
                'context' => 'PROJECT',
                'project_id' => (int) $project->id,
                'file_id' => (int) $file->id,
                'original_name' => $file->original_name,
            ];

            $result =
                DB::transaction(
                    function () use (
                        $project,
                        $file
                    ) {
                        $project
                            ->files()
                            ->detach($file->id);

                        $stillReferenced =
                            $this
                            ->isFileReferenced($file->id);

                        if (!$stillReferenced) {
                            Storage::disk($file->disk)->delete($file->path);
                            $file->delete();

                            return [
                                'deleted' =>
                                true,
                            ];
                        }

                        return [
                            'deleted' =>
                            false,
                        ];
                    }
                );

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE_LINK',
                (int) $file->id,
                'DETACH',
                [
                    ...$snapshot,
                    'deleted' => (int) $result['deleted'],
                ],
                'API',
                (int) $project->site_id
            );

            return response()->json([
                'ok' => true,
                'deleted' => (bool) $result['deleted'],
            ]);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::FILE_DETACH_FAILED,
                'Failed to detach file.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function projectDownload(Project $project, StoredFile $file)
    {
        /*
        |--------------------------------------------------------------------------
        | File must belong to same Site
        |--------------------------------------------------------------------------
        */

        if ((int) $file->site_id !== (int) $project->site_id) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | File must actually be linked
        |--------------------------------------------------------------------------
        */

        $isLinked =
            $project
            ->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(
                ApiErrorCode::FILE_NOT_LINKED,
                'File is not linked to this project.',
                [],
                404
            );
        }

        if (!Storage::disk($file->disk)->exists($file->path)) {
            return ApiResponse::error(
                ApiErrorCode::FILE_PHYSICAL_MISSING,
                'Physical file not found.',
                [],
                404
            );
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /*
    |--------------------------------------------------------------------------
    | TASK FILES
    |--------------------------------------------------------------------------
    */

    public function taskIndex(ProjectTask $task)
    {
        $project =
            Project::query()
            ->findOrFail($task->project_id);

        $files =
            $task
            ->files()
            ->where('dt_files.site_id', $project->site_id)
            ->with(['site:id,code,name,short_name',])
            ->select('dt_files.*')
            ->orderByDesc('dt_files.id')
            ->paginate(50);

        return StoredFileResource::collection($files);
    }

    public function taskUpload(StoreFileUploadRequest $request, ProjectTask $task)
    {
        $project =
            Project::query()
            ->findOrFail($task->project_id);

        $uploaded = $request->file('file');

        if (!$uploaded) {
            return ApiResponse::error(
                ApiErrorCode::FILE_UPLOAD_FAILED,
                'Missing file.',
                [],
                422
            );
        }

        if (!StoredFileAccess::canManageSite($request->user(), (int) $project->site_id)) {
            return ApiResponse::error(
                ApiErrorCode::FILE_SITE_ACCESS_DENIED,
                'You do not have management access to this Site.',
                [],
                403
            );
        }

        $disk = config('filesystems.default', 'local');

        $dir =
            'uploads/files/'
            . $project->site_id
            . '/'
            . date('Y/m');

        $path = null;

        try {
            $path = $uploaded->store($dir, $disk);
            $checksum = hash_file('sha256', $uploaded->getRealPath());

            $file =
                DB::transaction(
                    function () use (
                        $disk,
                        $path,
                        $uploaded,
                        $checksum,
                        $request,
                        $task,
                        $project
                    ) {
                        $file =
                            StoredFile::create([
                                'site_id' => (int) $project->site_id,
                                'disk' => $disk,
                                'path' => $path,
                                'original_name' => $uploaded->getClientOriginalName(),
                                'mime_type' => $uploaded->getClientMimeType(),
                                'size' => $uploaded->getSize() ?? 0,
                                'checksum' => $checksum,
                                'uploaded_by_user_id' => $request->user()->id,
                            ]);

                        $task
                            ->files()
                            ->syncWithoutDetaching([$file->id,]);

                        return $file;
                    }
                );

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE',
                (int) $file->id,
                'CREATE',
                [
                    'site_id' => (int) $project->site_id,
                    'context' => 'TASK',
                    'task_id' => (int) $task->id,
                    'project_id' => (int) $task->project_id,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                    'checksum' => $file->checksum,
                    'disk' => $file->disk,
                    'path' => $file->path,
                ]
            );

            $file->load(['site:id,code,name,short_name',]);

            return (new StoredFileResource($file))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);

            if ($path && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }

            return ApiResponse::error(
                ApiErrorCode::FILE_UPLOAD_FAILED,
                'Failed to upload file.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function taskAttach(AttachExistingFileRequest $request, ProjectTask $task)
    {
        $project = Project::query()->findOrFail($task->project_id);
        $fileId = (int) $request->validated('file_id');
        $file = StoredFileAccess::visibleQuery($request->user())->find($fileId);

        if (!$file) {
            abort(404);
        }

        if ((int) $file->site_id !== (int) $project->site_id) {
            return ApiResponse::error(
                ApiErrorCode::FILE_SITE_MISMATCH,
                'The file and Task must belong to the same Site.',
                [
                    'file_id' => (int) $file->id,
                    'file_site_id' => (int) $file->site_id,
                    'task_id' => (int) $task->id,
                    'project_id' => (int) $project->id,
                    'project_site_id' => (int) $project->site_id,
                ],
                422
            );
        }

        if (!StoredFileAccess::canManage($request->user(), $file)) {
            return ApiResponse::error(
                ApiErrorCode::FILE_SITE_ACCESS_DENIED,
                'You do not have management access to this file Site.',
                [],
                403
            );
        }

        $task
            ->files()
            ->syncWithoutDetaching([$file->id,]);

        \App\Support\Audit::log(
            $request->user()->id,
            'FILE_LINK',
            (int) $file->id,
            'ATTACH',
            [
                'site_id' => (int) $project->site_id,
                'context' => 'TASK',
                'task_id' => (int) $task->id,
                'project_id' => (int) $project->id,
            ]
        );

        return response()->json(['ok' => true,]);
    }

    public function taskDetach(Request $request, ProjectTask $task, StoredFile $file)
    {
        $project = Project::query()->findOrFail($task->project_id);

        if ((int) $file->site_id !== (int) $project->site_id) {
            abort(404);
        }

        $isLinked =
            $task
            ->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(
                ApiErrorCode::FILE_NOT_LINKED,
                'File is not linked to this task.',
                [],
                404
            );
        }

        try {
            $snapshot = [
                'site_id' => (int) $project->site_id,
                'context' => 'TASK',
                'task_id' => (int) $task->id,
                'project_id' => (int) $project->id,
                'file_id' => (int) $file->id,
                'original_name' => $file->original_name,
            ];

            $result =
                DB::transaction(
                    function () use (
                        $task,
                        $file
                    ) {
                        $task
                            ->files()
                            ->detach($file->id);

                        $stillReferenced =
                            $this
                            ->isFileReferenced($file->id);

                        if (!$stillReferenced) {
                            Storage::disk($file->disk)->delete($file->path);
                            $file->delete();
                            return ['deleted' => true,];
                        }

                        return [
                            'deleted' =>
                            false,
                        ];
                    }
                );

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE_LINK',
                (int) $file->id,
                'DETACH',
                [
                    ...$snapshot,
                    'deleted' => (int) $result['deleted'],
                ],
                'API',
                (int) $project->site_id
            );

            return response()->json([
                'ok' => true,
                'deleted' => (bool) $result['deleted'],
            ]);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::FILE_DETACH_FAILED,
                'Failed to detach file.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function taskDownload(ProjectTask $task, StoredFile $file)
    {
        $project =
            Project::query()
            ->findOrFail($task->project_id);

        if ((int) $file->site_id !== (int) $project->site_id) {
            abort(404);
        }

        $isLinked =
            $task
            ->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(
                ApiErrorCode::FILE_NOT_LINKED,
                'File is not linked to this task.',
                [],
                404
            );
        }

        if (!Storage::disk($file->disk)->exists($file->path)) {
            return ApiResponse::error(
                ApiErrorCode::FILE_PHYSICAL_MISSING,
                'Physical file not found.',
                [],
                404
            );
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /*
    |--------------------------------------------------------------------------
    | Move Task File
    |--------------------------------------------------------------------------
    */

    public function taskMove(MoveTaskFileRequest $request, ProjectTask $task, StoredFile $file)
    {
        $data = $request->validated();
        $toTaskId = (int) $data['to_task_id'];
        $keep = (bool) ($data['keep_on_source'] ?? false);

        $sourceProject =
            Project::query()
            ->findOrFail($task->project_id);

        /*
        |--------------------------------------------------------------------------
        | File must belong to source Site
        |--------------------------------------------------------------------------
        */

        if ((int) $file->site_id !== (int) $sourceProject->site_id) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Must be linked to source Task
        |--------------------------------------------------------------------------
        */

        $isLinked =
            $task
            ->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(
                ApiErrorCode::FILE_NOT_LINKED,
                'File is not linked to source task.',
                [],
                404
            );
        }

        $toTask =
            ProjectTask::query()
            ->findOrFail($toTaskId);

        /*
        |--------------------------------------------------------------------------
        | Keep existing same-Project rule
        |--------------------------------------------------------------------------
        */

        if ((int) $toTask->project_id !== (int) $task->project_id) {
            return ApiResponse::error(
                ApiErrorCode::FILE_MOVE_FAILED,
                'Target task must be in the same Project.',
                [],
                422
            );
        }

        $targetProject =
            Project::query()
            ->findOrFail($toTask->project_id);

        /*
        |--------------------------------------------------------------------------
        | Defense-in-depth Site rule
        |--------------------------------------------------------------------------
        */

        if ((int) $targetProject->site_id !== (int) $file->site_id) {
            return ApiResponse::error(
                ApiErrorCode::FILE_SITE_MISMATCH,
                'The file and target Task must belong to the same Site.',
                [],
                422
            );
        }

        try {
            DB::transaction(
                function () use (
                    $task,
                    $toTask,
                    $file,
                    $keep
                ) {
                    $toTask
                        ->files()
                        ->syncWithoutDetaching([$file->id,]);

                    if (!$keep) {
                        $task
                            ->files()
                            ->detach($file->id);
                    }
                }
            );

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE_LINK',
                (int) $file->id,
                'MOVE',
                [
                    'site_id' => (int) $sourceProject->site_id,
                    'from_task_id' => (int) $task->id,
                    'to_task_id' => (int) $toTask->id,
                    'project_id' => (int) $task->project_id,
                    'keep_on_source' => (int) $keep,
                ]
            );

            return response()->json(['ok' => true,]);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::FILE_MOVE_FAILED,
                'Failed to move file.',
                $this->errorDetails($e),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function isFileReferenced(int $fileId): bool
    {
        return DB::table('dt_project_files')
            ->where('file_id', $fileId)
            ->exists()
            || DB::table('dt_task_files')
            ->where('file_id', $fileId)
            ->exists()
            || DB::table('dt_agreement_files')
            ->where('file_id', $fileId)
            ->exists();
    }

    private function errorDetails(Throwable $e): array
    {
        if (!config('app.debug')) {
            return [];
        }

        return [
            'exception' => $e->getMessage(),
            'exception_class' => get_class($e),
        ];
    }
}
