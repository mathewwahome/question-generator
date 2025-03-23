<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class QuestionGeneratorController extends Controller
{
    /**
     * Generate questions from a PDF file
     */
    public function generateFromPDF(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf',
            'num_questions' => 'nullable|integer|min:1|max:20',
        ]);

        // Store the uploaded PDF file
        $pdfPath = $request->file('pdf_file')->store('pdfs');
        $fullPath = Storage::path($pdfPath);
        $numQuestions = $request->input('num_questions', 5);

        // Call Python script for question generation
        try {
            $process = new Process([
                '/var/www/html/ynet/question-generator/venv/bin/python',
                base_path('python/generate_questions.py'),
                '--pdf',
                $fullPath,
                '--num',
                $numQuestions
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = json_decode($process->getOutput(), true);

            // Store generated questions in database
            foreach ($output['questions'] as $question) {
                DB::table('generated_questions')->insert([
                    'question' => $question,
                    'source_type' => 'pdf',
                    'source_id' => $pdfPath,
                    'created_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'questions' => $output['questions'],
                'message' => 'Questions generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate questions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate questions from database notes
     */
    public function generateFromNotes(Request $request)
    {
        $request->validate([
            'note_ids' => 'required|array',
            'note_ids.*' => 'integer|exists:notes,id',
            'num_questions' => 'nullable|integer|min:1|max:20',
        ]);

        $noteIds = $request->input('note_ids');
        $numQuestions = $request->input('num_questions', 5);

        // Get notes content from database
        $notes = DB::table('notes')->whereIn('id', $noteIds)->get();
        $notesContent = $notes->pluck('content')->implode("\n\n");

        // Create temporary file with notes content
        $tempFile = tempnam(sys_get_temp_dir(), 'notes_');
        file_put_contents($tempFile, $notesContent);

        try {
            // Call Python script
            $process = new Process([
                '/var/www/html/ynet/question-generator/venv/bin/python',
                base_path('python/generate_questions.py'),
                '--text',
                $tempFile,
                '--num',
                $numQuestions
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = json_decode($process->getOutput(), true);

            // Store generated questions
            foreach ($output['questions'] as $question) {
                DB::table('generated_questions')->insert([
                    'question' => $question,
                    'source_type' => 'notes',
                    'source_id' => json_encode($noteIds),
                    'created_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'questions' => $output['questions'],
                'message' => 'Questions generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate questions: ' . $e->getMessage()
            ], 500);
        } finally {
            // Clean up temporary file
            @unlink($tempFile);
        }
    }
}
