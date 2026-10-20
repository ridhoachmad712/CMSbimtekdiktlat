@php($shareUrl = url()->current())
<div class="flex flex-wrap items-center gap-2 text-sm">
    <span class="text-slate-500">Bagikan:</span>
    <a href="https://wa.me/?text={{ rawurlencode($title.' '.$shareUrl) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-full bg-slate-100 px-4 hover:bg-slate-200">WhatsApp</a>
    <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($shareUrl) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-full bg-slate-100 px-4 hover:bg-slate-200">Facebook</a>
    <a href="https://twitter.com/intent/tweet?url={{ rawurlencode($shareUrl) }}&text={{ rawurlencode($title) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-full bg-slate-100 px-4 hover:bg-slate-200">X</a>
</div>
