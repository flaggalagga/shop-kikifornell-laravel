<x-mail::message>
# Nouveau message

**De :** {{ $inquiry->name }} — {{ $inquiry->email }}

@if ($inquiry->piece)
**À propos de :** {{ $inquiry->piece->reference }} — {{ $inquiry->piece->title_fr }}
@endif

<x-mail::panel>
{{ $inquiry->message }}
</x-mail::panel>

Répondez directement à cet e-mail pour écrire à {{ $inquiry->name }}.
</x-mail::message>
