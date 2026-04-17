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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * =========================
     * Project Files
     * =========================
     */
    public function projectIndex(Project $project)
    {
        $files = $project->files()
            ->select('dt_files.*')
            ->orderByDesc('dt_files.id')
            ->paginate(50);

        return StoredFileResource::collection($files);
    }

    public function projectUpload(StoreFileUploadRequest $request, Project $project)
    {
        $uploaded = $request->file('file');
        if (!$uploaded) {
            return ApiResponse::error(ApiErrorCode::FILE_UPLOAD_FAILED, 'Missing file', 422);
        }

        try {
            $disk = config('filesystems.default', 'local');
            $dir = 'uploads/files/' . date('Y/m');
            $path = $uploaded->store($dir, $disk);

            $checksum = hash_file('sha256', $uploaded->getRealPath());

            $file = DB::transaction(function () use ($disk, $path, $uploaded, $checksum, $request, $project) {
                $f = StoredFile::create([
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $uploaded->getClientOriginalName(),
                    'mime_type' => $uploaded->getClientMimeType(),
                    'size' => $uploaded->getSize() ?? 0,
                    'checksum' => $checksum,
                    'uploaded_by_user_id' => $request->user()->id,
                ]);

                $project->files()->syncWithoutDetaching([$f->id]);

                return $f;
            });

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE',
                (int)$file->id,
                'CREATE',
                [
                    'context' => 'PROJECT',
                    'project_id' => (int)$project->id,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                    'checksum' => $file->checksum,
                    'disk' => $file->disk,
                    'path' => $file->path,
                ]
            );

            return (new StoredFileResource($file))->response()->setStatusCode(201);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(ApiErrorCode::FILE_UPLOAD_FAILED, 'Failed to upload file', 500);
        }
    }

    public function projectAttach(AttachExistingFileRequest $request, Project $project)
    {
        $fileId = (int)$request->validated('file_id');
        $file = StoredFile::findOrFail($fileId);

        $project->files()->syncWithoutDetaching([$file->id]);

        \App\Support\Audit::log(
            $request->user()->id,
            'FILE_LINK',
            (int)$file->id,
            'ATTACH',
            ['context' => 'PROJECT', 'project_id' => (int)$project->id]
        );

        return response()->json(['ok' => true]);
    }

    public function projectDetach(Request $request, Project $project, StoredFile $file)
    {
        $isLinked = $project->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(ApiErrorCode::FILE_NOT_LINKED, 'File is not linked to this project', 404);
        }

        try {
            $result = DB::transaction(function () use ($project, $file) {
                $project->files()->detach($file->id);

                $stillReferenced =
                    DB::table('dt_project_files')->where('file_id', $file->id)->exists()
                    || DB::table('dt_task_files')->where('file_id', $file->id)->exists();

                if (!$stillReferenced) {
                    // delete physical + record
                    Storage::disk($file->disk)->delete($file->path);
                    $file->delete();
                    return ['deleted' => true];
                }

                return ['deleted' => false];
            });

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE_LINK',
                (int)$file->id,
                'DETACH',
                ['context' => 'PROJECT', 'project_id' => (int)$project->id, 'deleted' => (int)$result['deleted']]
            );

            return response()->json(['ok' => true, 'deleted' => (bool)$result['deleted']]);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(ApiErrorCode::FILE_DETACH_FAILED, 'Failed to detach file', 500);
        }
    }

    public function projectDownload(Project $project, StoredFile $file)
    {
        $isLinked = $project->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(ApiErrorCode::FILE_NOT_LINKED, 'File is not linked to this project', 404);
        }

        if (!Storage::disk($file->disk)->exists($file->path)) {
            return ApiResponse::error(ApiErrorCode::FILE_PHYSICAL_MISSING, 'Physical file not found', 404);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * =========================
     * Task Files
     * =========================
     */
    public function taskIndex(ProjectTask $task)
    {
        $files = $task->files()
            ->select('dt_files.*')
            ->orderByDesc('dt_files.id')
            ->paginate(50);

        return StoredFileResource::collection($files);
    }

    public function taskUpload(StoreFileUploadRequest $request, ProjectTask $task)
    {
        $uploaded = $request->file('file');
        if (!$uploaded) {
            return ApiResponse::error(ApiErrorCode::FILE_UPLOAD_FAILED, 'Missing file', 422);
        }

        try {
            $disk = config('filesystems.default', 'local');
            $dir = 'uploads/files/' . date('Y/m');
            $path = $uploaded->store($dir, $disk);
            $checksum = hash_file('sha256', $uploaded->getRealPath());

            $file = DB::transaction(function () use ($disk, $path, $uploaded, $checksum, $request, $task) {
                $f = StoredFile::create([
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $uploaded->getClientOriginalName(),
                    'mime_type' => $uploaded->getClientMimeType(),
                    'size' => $uploaded->getSize() ?? 0,
                    'checksum' => $checksum,
                    'uploaded_by_user_id' => $request->user()->id,
                ]);

                $task->files()->syncWithoutDetaching([$f->id]);

                return $f;
            });

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE',
                (int)$file->id,
                'CREATE',
                [
                    'context' => 'TASK',
                    'task_id' => (int)$task->id,
                    'project_id' => (int)$task->project_id,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                    'checksum' => $file->checksum,
                    'disk' => $file->disk,
                    'path' => $file->path,
                ]
            );

            return (new StoredFileResource($file))->response()->setStatusCode(201);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(ApiErrorCode::FILE_UPLOAD_FAILED, 'Failed to upload file', 500);
        }
    }

    public function taskAttach(AttachExistingFileRequest $request, ProjectTask $task)
    {
        $fileId = (int)$request->validated('file_id');
        $file = StoredFile::findOrFail($fileId);

        $task->files()->syncWithoutDetaching([$file->id]);

        \App\Support\Audit::log(
            $request->user()->id,
            'FILE_LINK',
            (int)$file->id,
            'ATTACH',
            ['context' => 'TASK', 'task_id' => (int)$task->id, 'project_id' => (int)$task->project_id]
        );

        return response()->json(['ok' => true]);
    }

    public function taskDetach(Request $request, ProjectTask $task, StoredFile $file)
    {
        $isLinked = $task->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(ApiErrorCode::FILE_NOT_LINKED, 'File is not linked to this task', 404);
        }

        try {
            $result = DB::transaction(function () use ($task, $file) {
                $task->files()->detach($file->id);

                $stillReferenced =
                    DB::table('dt_project_files')->where('file_id', $file->id)->exists()
                    || DB::table('dt_task_files')->where('file_id', $file->id)->exists();

                if (!$stillReferenced) {
                    Storage::disk($file->disk)->delete($file->path);
                    $file->delete();
                    return ['deleted' => true];
                }

                return ['deleted' => false];
            });

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE_LINK',
                (int)$file->id,
                'DETACH',
                ['context' => 'TASK', 'task_id' => (int)$task->id, 'deleted' => (int)$result['deleted']]
            );

            return response()->json(['ok' => true, 'deleted' => (bool)$result['deleted']]);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(ApiErrorCode::FILE_DETACH_FAILED, 'Failed to detach file', 500);
        }
    }

    public function taskDownload(ProjectTask $task, StoredFile $file)
    {
        $isLinked = $task->files()
            ->where('dt_files.id', $file->id)
            ->exists();

        if (!$isLinked) {
            return ApiResponse::error(ApiErrorCode::FILE_NOT_LINKED, 'File is not linked to this task', 404);
        }

        if (!Storage::disk($file->disk)->exists($file->path)) {
            return ApiResponse::error(ApiErrorCode::FILE_PHYSICAL_MISSING, 'Physical file not found', 404);
        }

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * Move file link from one task to another task (same project recommended)
     * POST /tasks/{task}/files/{file}/move
     */
    public function taskMove(MoveTaskFileRequest $request, ProjectTask $task, StoredFile $file)
    {
        $data = $request->validated();
        $toTaskId = (int)$data['to_task_id'];
        $keep = (bool)($data['keep_on_source'] ?? false);

        // must be linked on source task
        $isLinked = $task->files()->where('dt_files.id', $file->id)->exists();
        if (!$isLinked) {
            return ApiResponse::error(ApiErrorCode::FILE_NOT_LINKED, 'File is not linked to source task', 404);
        }

        $toTask = ProjectTask::findOrFail($toTaskId);

        // (optional but recommended) ensure same project
        if ((int)$toTask->project_id !== (int)$task->project_id) {
            return ApiResponse::error(ApiErrorCode::FILE_MOVE_FAILED, 'Target task must be in same project', 422);
        }

        try {
            DB::transaction(function () use ($task, $toTask, $file, $keep) {
                $toTask->files()->syncWithoutDetaching([$file->id]);

                if (!$keep) {
                    $task->files()->detach($file->id);
                }
            });

            \App\Support\Audit::log(
                $request->user()->id,
                'FILE_LINK',
                (int)$file->id,
                'MOVE',
                [
                    'from_task_id' => (int)$task->id,
                    'to_task_id' => (int)$toTask->id,
                    'project_id' => (int)$task->project_id,
                    'keep_on_source' => (int)$keep,
                ]
            );

            return response()->json(['ok' => true]);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(ApiErrorCode::FILE_MOVE_FAILED, 'Failed to move file', 500);
        }
    }
}