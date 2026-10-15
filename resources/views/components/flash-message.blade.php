@if (session('success'))
    <div class="alert-success" style="margin: 0 0 16px 0;">
        {{ session('success') }}
    </div>
    <input type="hidden" id="flash-toast-message" value="{{ session('success') }}">
@endif

@if (session('error'))
    <div class="alert-danger" style="margin: 0 0 16px 0;">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert-danger" style="margin: 0 0 16px 0;">
        <ul style="margin:0;padding-left:18px">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
