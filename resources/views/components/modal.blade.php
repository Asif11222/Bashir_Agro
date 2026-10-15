@props([
    'id' => 'modal',
    'title' => '',
    'saveText' => 'সংরক্ষণ করুন',
])

<div class="modal">
    <div class="modal-head">
        <div>
            <b style="font-size: 19px">{{ $title }}</b>
        </div>
        <button type="button" class="btn" data-action="close">✕</button>
    </div>
    <div class="modal-body">
        {{ $slot }}
    </div>
    @if(isset($footer))
        <div class="modal-foot">
            {{ $footer }}
        </div>
    @endif
</div>
