@props(['title', 'updated'])

@include('legal.layout', ['title' => $title, 'updated' => $updated, 'slot' => $slot])
