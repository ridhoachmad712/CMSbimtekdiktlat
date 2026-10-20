@props(['content'])

@if (filled($content))
    <div {{ $attributes->class('rich-content') }}>
        {!! \Filament\Forms\Components\RichEditor\RichContentRenderer::make($content)->fileAttachmentsDisk("public")->fileAttachmentsVisibility("public")->toHtml() !!}
    </div>
@endif
