<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use App\Services\JsonSchemaValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

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
        // Basic file validation
        $request->validate([
            'resume_file' => 'required|file|mimes:json|max:2048',
        ], [
            'resume_file.mimes' => 'Please upload a valid JSON file.',
            'resume_file.max' => 'Resume file must be under 2MB.',
        ]);

        try {
            $file = $request->file('resume_file');
            $contents = file_get_contents($file->getRealPath());

            // Decode JSON for validation
            $jsonData = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            // 🔹 SCHEMA VALIDATION 🔹
            $validation = JsonSchemaValidator::validate(
                $jsonData,
                'json-resume-merged.json',
                autoFetchSchema: true
            );
  
            if (!$validation['valid']) {
                // 🔹 SAFEGUARD: Ensure errors are strings before imploding
                $errorMessages = array_filter(
                    $validation['errors'] ?? [],
                    fn($e) => is_string($e) && !empty($e)
                );

                $errorMessage = !empty($errorMessages)
                    ? 'Resume does not match required schema: ' . implode('; ', $errorMessages)
                    : 'Resume does not match required schema.';

                throw ValidationException::withMessages([
                    'resume_file' => $errorMessage
                ]);
            }

            // Optional: Additional business logic validation
            $this->validateBusinessRules($jsonData);

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
                ->with('success', 'Resume uploaded and validated successfully!');

        } catch (\JsonException $e) {
            return back()
                ->withInput()
                ->withErrors(['resume_file' => 'Invalid JSON format: ' . $e->getMessage()]);
        } catch (ValidationException $e) {
            // Re-throw validation exceptions to show user-friendly errors
            throw $e;
        } catch (\Exception $e) {
            Log::error('Resume upload failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'file' => $request->file('resume_file')?->getClientOriginalName(),
            ]);

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

    /**
     * Additional business rule validation (optional).
     */
    protected function validateBusinessRules(array $jsonData): void
    {
        // Example: Require at least name and email in basics
        $basics = $jsonData['basics'] ?? [];

        if (empty($basics['name'])) {
            throw ValidationException::withMessages([
                'resume_file' => 'Resume must include a name in the "basics" section.'
            ]);
        }

        if (!empty($basics['email']) && !filter_var($basics['email'], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'resume_file' => 'Invalid email format in "basics.email".'
            ]);
        }

        // Example: Require at least one work experience or education entry
        $hasWork = !empty($jsonData['work']) && count($jsonData['work']) > 0;
        $hasEducation = !empty($jsonData['education']) && count($jsonData['education']) > 0;

        if (!$hasWork && !$hasEducation) {
            throw ValidationException::withMessages([
                'resume_file' => 'Resume must include at least one work experience or education entry.'
            ]);
        }
    }

}