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

            $upProject = Project::where('code', $code)->first();

            if (!$upProject) {
                return response()->json([
                    'message' => 'Project not found'
                ], 404);
            }
            
            if ($upProject->file) {
                $oldFileName = $upProject->file;
                $oldFileExtension = pathinfo($oldFileName, PATHINFO_EXTENSION);
                $oldFilePath = "public/project-files/" . $oldFileExtension . '/' . $oldFileName;
    
                if (Storage::exists($oldFilePath)) {
                    Storage::delete($oldFilePath);
                }
            }

            if($request->hasFile('file')) {
                $dateNow = date('Y-m');
                $fileWithExt = $request->file('file')->getClientOriginalName();
                $fileWithoutExt = pathinfo($fileWithExt, PATHINFO_FILENAME);
                $fileExt = $request->file('file')->getClientOriginalExtension();
                $newFile = $fileWithoutExt . '_' . $dateNow . '.' . $fileExt;

                $folderPath = "public/project-files/" . $fileExt . '/';
                if (!Storage::exists($folderPath)) {
                    Storage::makeDirectory($folderPath);
                }

                $request->file('file')->storeAs($folderPath, $newFile);
                $validated['file'] = $newFile;
            }

            $upProject->update($validated);

            return response()->json([
                'message' => 'Project Updated Successfully',
                'file_url' => Storage::url($folderPath . $newFile)
            ],200);

            
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
    public function destroy(String $code)
    {
        try {

            $delProject = Project::where('code', $code)->first();

            if (!$delProject) {
                return response()->json([
                    'message' => 'Project not found'
                ], 404);
            }
            
            if ($delProject->file) {
                $oldFileName = $delProject->file;
            } else {
                return response()->json([
                    'message' => 'Project file not found'
                ], 404);
            }

            $oldFileExtension = pathinfo($oldFileName, PATHINFO_EXTENSION);
            $oldFilePath = "public/project-files/" . $oldFileExtension . '/' . $oldFileName;

            if (Storage::exists($oldFilePath)) {
                Storage::delete($oldFilePath);
            }

            $delProject->delete();

            return response()->json([
                'message' => 'Project Deleted Successfully!'
            ], 200);

            
        } catch (\Throwable $e) {
            Log::error('Project deleteion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while deleting the project.',
            ], 500);
        }
    }

}
