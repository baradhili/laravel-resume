<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ResumeController extends Controller
{
    /**
     * Show upload form
     */
    public function create()
    {
        return view('resumes.upload');
    }

    /**
     * Handle JSON resume upload
     */
    public function store(Request $request)
    {
        $request->validate([
            'resume_file' => 'required|file|mimes:json|max:2048', // 2MB max
        ], [
            'resume_file.mimes' => 'Please upload a valid JSON file.',
            'resume_file.max' => 'Resume file must be under 2MB.',
        ]);

        try {
            $file = $request->file('resume_file');
            $contents = file_get_contents($file->getRealPath());
            $jsonData = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            // Basic JSON Resume schema validation
            if (!isset($jsonData['basics']) || !is_array($jsonData['basics'])) {
                throw ValidationException::withMessages([
                    'resume_file' => 'Invalid JSON Resume format. Missing required "basics" section.'
                ]);
            }

            // Store file
            $filename = 'resumes/' . Auth::id() . '_' . time() . '_' . uniqid() . '.json';
            Storage::put($filename, $contents);

            // Save to database
            $resume = Resume::create([
                'user_id' => Auth::id(),
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'parsed_data' => $jsonData,
                'json_resume_version' => $jsonData['$schema'] ?? null,
                'uploaded_at' => now(),
            ]);

            return redirect()
                ->route('resumes.show', $resume)
                ->with('success', 'Resume uploaded successfully!');

        } catch (\JsonException $e) {
            return back()
                ->withInput()
                ->withErrors(['resume_file' => 'Invalid JSON format: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['resume_file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Display uploaded resume
     */
    public function show(Resume $resume)
    {
        // Authorize: user can only view their own resumes
        if ($resume->user_id !== Auth::id()) {
            abort(403);
        }

        return view('resumes.show', compact('resume'));
    }

    /**
     * List user's resumes
     */
    public function index()
    {
        $resumes = Resume::where('user_id', Auth::id())
            ->latest('uploaded_at')
            ->paginate(10);

        return view('resumes.index', compact('resumes'));
    }

    /**
     * Delete resume
     */
    public function destroy(Resume $resume)
    {
        if ($resume->user_id !== Auth::id()) {
            abort(403);
        }

        // Delete file from storage
        if (Storage::exists($resume->filename)) {
            Storage::delete($resume->filename);
        }

        $resume->delete();

        return redirect()
            ->route('resumes.index')
            ->with('success', 'Resume deleted successfully.');
    }
}