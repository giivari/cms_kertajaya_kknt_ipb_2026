<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;

class ContactController extends Controller
{
    public function show()
    {
        return view('public.contact');
    }

    public function store(StoreContactMessageRequest $request)
    {
        ContactMessage::create($request->only([
            'name',
            'contact_type',
            'contact_value',
            'subject',
            'message',
        ]));

        return redirect()->route('public.contact.show')
            ->with('success', 'Pesan Anda telah berhasil dikirim. Terima kasih.');
    }
}