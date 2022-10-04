@extends('layouts.app')

@section('javascript')
    <script src="/js/confirm.js"></script>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between">メモ編集
            <form id="delete-form" action="{{ route('destory') }}" method="POST">
                @csrf
                <input type="hidden" name="memo_id" value="{{$edit_memo[0]['id']}}">
                <i class="fa-solid fa-trash" onClick="deleteHandle(event);"></i>
            </form>
        </div>
        <form class="card-body my-card-body" action="{{ route('update') }}" method="POST">
            @csrf
            <input type="hidden" name="memo_id" value="{{$edit_memo[0]['id']}}">
            <div class="form-group">
                <textarea class="form-control" name="content" rows="3" placeholder="ここにメモを入力">{{ $edit_memo[0]['content'] }}</textarea>
            </div>
            @error('content')
                <div class="alert alert-danger my-2">{{ $message }}</div>
            @enderror
            @foreach($tags as $t)
                <div class="form-check form-check-inline mb-3">
                    <input class="form-check-input" type="checkbox" name="tags[]" id="{{$t['id']}}" value="{{ $t['id'] }}" {{ in_array($t['id'], $include_tags) ? 'checked': '' }}>
                    <label class="form-check-label" for="{{$t['id']}}">{{ $t['name'] }}</label>
                </div>
            @endforeach
            <input type="text" class="form-control w-50 mt-3" name="new_tag" placeholder="新しいタグを追加">
            <button type="submit" class="btn btn-primary my-2">更新</button>
        </form>
    </div>
@endsection
