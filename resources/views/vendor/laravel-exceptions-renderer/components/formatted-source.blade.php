@props(['frame', 'className' => ''])

<span {{ $attributes->merge(['class' => trim('font-mono text-sm text-neutral-700 dark:text-neutral-300 '.$className)]) }}>
    {{ $frame->class() ? $frame->class().'::' : '' }}{{ $frame->function() }}
</span>
