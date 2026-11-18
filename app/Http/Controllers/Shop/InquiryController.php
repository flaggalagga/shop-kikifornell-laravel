<?php

namespace App\Http\Controllers\Shop;

use App\Mail\NewInquiryNotification;
use App\Models\Inquiry;
use App\Models\Piece;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** "Ask about this piece" form. */
class InquiryController
{
    public function store(Request $request, Piece $piece): RedirectResponse
    {
        // Honeypot: bots fill every field, people never see this one.
        if (filled($request->input('website'))) {
            return back()->with('notice', __('shop.inquiry.sent'));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'message' => ['required', 'string', 'min:5', 'max:4000'],
        ]);

        $inquiry = Inquiry::create([...$data, 'piece_id' => $piece->id, 'locale' => app()->getLocale()]);

        try {
            Mail::to(config('shop.contact_email'))->locale('fr')->send(new NewInquiryNotification($inquiry));
        } catch (Throwable $e) {
            Log::error('Inquiry email failed', ['inquiry' => $inquiry->id, 'error' => $e->getMessage()]);
        }

        return redirect(route('pieces.show', $piece).'#question')->with('notice', __('shop.inquiry.sent'));
    }
}
