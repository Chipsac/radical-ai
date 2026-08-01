<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class LandingController extends Controller
{
    /** The public front door. Signed-in users go straight to their workspace. */
    public function index(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('home');
        }

        return view('landing', [
            'topics' => ContactMessage::topics(),
        ]);
    }

    /**
     * "Get in touch" submissions.
     *
     * Two cheap spam defences instead of a third-party captcha: a honeypot
     * field bots fill in and humans never see, and a per-IP rate limit.
     */
    public function contact(Request $request)
    {
        // Honeypot — a real browser leaves this empty because it is hidden.
        if (filled($request->input('website'))) {
            // Pretend it worked so a bot learns nothing from the response.
            return back()->with('contact_sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'company' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'topic' => ['required', 'in:'.implode(',', array_keys(ContactMessage::topics()))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Please agree to us replying to your enquiry.',
            'message.min' => 'Please give us a little more detail so we can help.',
        ]);

        $this->guardAgainstFlooding($request);

        // `consent` is a form-only field — it gates submission but is not a
        // column, so it must not reach the insert.
        unset($data['consent']);

        $message = ContactMessage::create($data + [
            'ip_address' => $request->ip(),
        ]);

        Mail::to(config('mail.contact_to', 'hello@radical-ai.example'))
            ->send(new ContactMessageMail($message));

        return back()
            ->with('contact_sent', true)
            ->withFragment('contact');
    }

    /**
     * Allow a handful of enquiries per IP per hour — enough for a genuine
     * person who makes a mistake, not enough to be useful to a spammer.
     */
    private function guardAgainstFlooding(Request $request): void
    {
        $recent = ContactMessage::where('ip_address', $request->ip())
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recent >= 5) {
            throw ValidationException::withMessages([
                'message' => 'That is a lot of messages in a short time. Please email us directly instead.',
            ]);
        }
    }
}
