{{-- <x-mail::message>
# Introduction

The body of your message.

<x-mail::button :url="''">
Button Text
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message> --}}

@component('mail::message')
    # New Note Created

    **Title:** {{ $note->title }}

    {{ Str::limit($note->body, 200) }}

    Thanks,
    {{ config('app.name') }}
@endcomponent
