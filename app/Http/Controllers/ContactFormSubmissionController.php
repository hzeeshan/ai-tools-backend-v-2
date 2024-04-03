<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\ContactFormSubmission;
use App\Mail\ContactFormSubmittedMail;

class ContactFormSubmissionController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'data.name' => 'required|string',
            'data.email' => 'required|email',
            'data.subject' => 'sometimes|string',
            'data.message' => 'required|string',
        ]);

        try {
            $this->createSubmissionAndSendMail($validatedData['data']);

            return response()->json(['success' => true, 'message' => 'Sent successfully']);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    private function createSubmissionAndSendMail($data)
    {
        ContactFormSubmission::create($data);

        Mail::to('hafizzeeshan619@gmail.com')->send(new ContactFormSubmittedMail($data));
    }
}
