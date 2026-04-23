<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required','string','max:120'],
            'email'   => ['required','email','max:180'],
            'phone'   => ['nullable','string','max:30'],
            'subject' => ['required','string','max:120'],
            'message' => ['required','string','max:4000'],
            'consent' => ['accepted'],
        ]);

        // ✅ change this email to your real inbox
        $to = config('mail.from.address');

        Mail::send('emails.contact', ['data' => $data], function ($m) use ($data, $to) {
            $m->to($to)
              ->replyTo($data['email'], $data['name'])
              ->subject("Contact RHS – ".$data['subject']);
        });

        return back()->with('success', 'Votre message a bien été envoyé. Nous vous recontactons rapidement.');
    }
}
