@component('mail::message')
# New enquiry

**{{ $contact->name }}** got in touch through the website.

@component('mail::table')
| | |
|:---|:---|
| **Topic** | {{ $topicLabel }} |
| **Email** | {{ $contact->email }} |
| **Company** | {{ $contact->company ?: '—' }} |
| **Phone** | {{ $contact->phone ?: '—' }} |
| **Received** | {{ $contact->created_at->format('j F Y, H:i') }} |
@endcomponent

**Message**

{{ $contact->message }}

@component('mail::button', ['url' => 'mailto:'.$contact->email])
Reply to {{ $contact->name }}
@endcomponent

Replying to this notification also reaches them directly.
@endcomponent
