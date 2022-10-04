<?php
// メモ
//             // dump die の略 -> メソッドの引数の取った値を展開して止める =>　データの確認  デバッグ
//             dd($posts);


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Memo;
use App\Models\Tag;
use App\Models\MemoTag;
use DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $tags = Tag::where('user_id', '=', \Auth::id())-> whereNull('deleted_at')->orderBy('id', 'DESC')->get();
        return view('create', compact( 'tags'));
    }

    public function store(Request $request)
    {
        $posts = $request->all();
        $request->validate(['content' => 'required']);

        // トランザクション
        DB::transaction(function() use($posts) {
            $memo_id = Memo::insertGetId(['content' => $posts['content'] , 'user_id' => \Auth::id()]);
            $stag_exists = Tag::where('user_id', '=' , \Auth::id())->where('name', '=', $posts['new_tag'])->exists();
            if( !empty($posts['new_tag']) && !$stag_exists ) {
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag_id]);
            }

            //既存タグが紐づけられた場合 memo_tagsにインサート
            if(!empty($posts['tags'])) {
                foreach($posts['tags'] as $tag ) {
                    MemoTag::insert(['memo_id' => $memo_id, 'tag_id' => $tag]);
                }
            }


        });



        return redirect( route('home') );

    }

    public function edit($id)
    {
        $edit_memo = Memo::select('memos.*', 'tags.id AS tag_id')
                    ->leftJoin('memo_tags','memo_tags.memo_id', '=', 'memos.id')
                    ->leftJoin('tags', 'memo_tags.tag_id', '=', 'tags.id')
                    ->where('memos.user_id', '=', \Auth::id())
                    ->where('memos.id', '=', $id)
                    ->whereNull('memos.deleted_at')
                    ->get();

        $include_tags = [];
        foreach($edit_memo as $memo) {
            array_push($include_tags, $memo["tag_id"]);
        }

        $tags = Tag::where('user_id', '=', \Auth::id())-> whereNull('deleted_at')->orderBy('id', 'DESC')->get();


        return view('edit', compact('edit_memo', 'include_tags', 'tags'));
    }

    public function update(Request $request)
    {
        $posts = $request->all();
        $request->validate(['content' => 'required']);

        // トランザクション
        DB::transaction(function() use($posts) {
            Memo::where('id',$posts['memo_id'])->update(['content' => $posts['content']]);
            // 一旦メモとタグの紐付けを削除
            MemoTag::where('memo_id', '=', $posts['memo_id'])->delete();

            foreach($posts['tags'] as $tag) {
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag]);
            }
            $stag_exists = Tag::where('user_id', '=' , \Auth::id())->where('name', '=', $posts['new_tag'])->exists();
            if( !empty($posts['new_tag']) && !$stag_exists ) {
                $tag_id = Tag::insertGetId(['user_id' => \Auth::id(), 'name' => $posts['new_tag']]);
                MemoTag::insert(['memo_id' => $posts['memo_id'], 'tag_id' => $tag_id]);
            }
        });

        return redirect( route('home') );

    }


    public function destory(Request $request)
    {
        $posts = $request->all();

        // 今回物理削除ではなく、論理削除なので、 deleted_atに時間を追加する
        Memo::where('id',$posts['memo_id'])->update(['deleted_at' => date('Y-m-d H:i:s', time())]);
        return redirect( route('home') );

    }
}
