@props([
    'code' => '',
    'language' => null,
    'editor' => false,
    'startingLine' => 1,
    'highlightedLine' => null,
])

<pre {{ $attributes->merge(['class' => 'p-4 text-xs leading-6 whitespace-pre overflow-x-auto']) }}><code>{{ $code }}</code></pre>
