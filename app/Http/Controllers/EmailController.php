<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|array|min:1',
            'to.*' => 'email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // Max 10MB per file
        ]);

        $to = $request->input('to');
        $subject = $request->input('subject');
        $body = $request->input('body');
        $attachments = $request->file('attachments') ?? [];

        // Ensure attachments is always an array
        if (!is_array($attachments)) {
            $attachments = [$attachments];
        }

        try {
            Mail::to($to)->send(new SendEmail($subject, $body, $attachments));

            return response()->json(['message' => 'Email sent successfully.'], 200);
        } catch (\Exception $e) {
            \Log::error('Failed to send email', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to send email.', 'error' => $e->getMessage()], 500);
        }
    }
}
