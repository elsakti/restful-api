<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class ProjectController extends Controller
{

    private function getCode()
    {
        $uuid = Uuid::uuid4();
        $code = substr($uuid->toString(), 0, 6);
        $code = Str::upper($code);
        return $code;
        // @dd($code);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $project = Project::latest()->paginate(10);
        return new ProjectResource(true, 'Project Data List', $project);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(),[
                'name' => 'required|string|max:255',
                'file' => 'required|max:10000|mimes:png,jpg,svg,pdf,zip,docx,docs',
                'description' => 'nullable|string',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date',
                'status' => 'required|in:pending,in_progress,completed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->messages(),
                ], 422);
            }

            $validated = $validator->validated();

            if($request->hasFile('file')) {
                $dateNow = date('Y-m');
                $fileWithExt = $request->file('file')->getClientOriginalName();
                $fileWithoutExt = pathinfo($fileWithExt, PATHINFO_FILENAME);
                $fileExt = $request->file('file')->getClientOriginalExtension();
                $file = $fileWithoutExt . '_' . $dateNow . '.' . $fileExt;

                $folderPath = "public/project-files/" . $fileExt . '/';
                if (!Storage::exists($folderPath)) {
                    Storage::makeDirectory($folderPath);
                }

                $request->file('file')->storeAs($folderPath, $file);
                $validated['file'] = $file;
            }

            do {

                $code = $this->getCode();

            } while (Project::where('code', $code)->first());

            $validated['code'] = $code;

            $project = Project::create($validated);
            return response()->json([
                'message' => 'Project created successfully!',
                'data' => $project,
            ], 201);


        } catch (\Throwable $e) {
            Log::error('Project creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while creating the project.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(String $code)
    {
        $project = Project::where('code', $code)->first();

        if (!$project) {
            return response()->json([
                'message' => 'Project not found',
            ], 404);
        }

        return response()->json($project, 200);
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, string $code)
    {
        $project = Project::where('code', $code)->first();
        $oldFileName = $project->file;
        $oldFileExtension = $oldFileName->getClientOriginalExtension();
        @dd($oldFileExtension);
        $oldFilePath = "public/storage/project-files/" . $oldFileExtension . "/" . pathinfo($project->file, PATHINFO_FILENAME);
        if ($oldFilePath && Storage::exists($oldFilePath)) {
            Storage::delete($oldFilePath);
        }
        try {

            if (!$project) {
                return response()->json([
                    'message' => 'Project not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date',
                'status' => 'nullable|in:pending,in_progress,completed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->messages(),
                ], 422);
            }


            $validated = $validator->validated();

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                $fileName = $file->hashName();
                $filePath = $file->storeAs("public/project-files/$extension", $file->hashName());

                if (!$filePath) {
                    return response()->json([
                        'message' => 'Error uploading file.',
                    ], 500);
                }


                $validated['file'] = $fileName;
            }

            $project->update($validated);

            return response()->json([
                'message' => 'Project updated successfully!',
                'data' => $project,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Project update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while updating the project.',
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($code)
    {
        try {
            // Validasi ID proyek
            $project = Project::where('code', $code)->first();

            if (!$project) {
                return response()->json([
                    'message' => 'Project not found.',
                ], 404);
            }

            // Hapus file terkait jika ada
            if ($project->file) {
                dd($project->file);
                $filePath = "public/project-files/" . pathinfo($project->file, PATHINFO_EXTENSION) . '/' . $project->file;
                // dd($filePath);
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
            }

            // Hapus entri proyek dari database
            $project->delete();

            return response()->json([
                'message' => 'Project deleted successfully!',
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Project deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while deleting the project.',
            ], 500);
        }
    }

}
