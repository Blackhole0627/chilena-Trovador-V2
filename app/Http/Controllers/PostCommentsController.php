<?php

namespace App\Http\Controllers;

use App\Helper;
use App\Models\PostLiveComments;
use App\Models\Updates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostCommentsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Guarda un comentario en vivo. El creador de la publicacion puede
     * adjuntar una nota de voz (MP3) en lugar de texto.
     */
    public function store(Request $request)
    {
        $post = Updates::findOrFail($request->updates_id);
        $isCreator = ($post->user_id == auth()->id());

        $rules = ['updates_id' => 'required|integer'];
        if ($request->hasFile('voice') && $isCreator) {
            $rules['voice'] = 'mimes:mp3,mpga,wav,ogg,m4a,webm|max:20480';
        } else {
            $rules['comment'] = 'required|max:100|min:1';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->getMessageBag()->toArray()]);
        }

        $c = new PostLiveComments();
        $c->user_id = auth()->id();
        $c->updates_id = $post->id;
        $c->comment = $request->comment ? trim(Helper::checkTextDb($request->comment)) : null;

        if ($request->hasFile('voice') && $isCreator) {
            $ext = $request->file('voice')->extension();
            $fileName = 'plc_' . strtolower(auth()->id() . time() . str_random(30)) . '.' . $ext;
            $request->file('voice')->storePubliclyAs(config('path.music'), $fileName);
            $c->media = $fileName;
        }

        $c->save();

        return response()->json(['success' => true]);
    }

    /**
     * Devuelve los ultimos comentarios de la publicacion (para el polling).
     * Limite reutiliza el ajuste existente number_comments_show.
     */
    public function fetch($id)
    {
        $post = Updates::findOrFail($id);

        $limit = (int) config('settings.number_comments_show', 6);
        if ($limit < 1) {
            $limit = 6;
        }

        $comments = PostLiveComments::where('updates_id', $id)
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get()
            ->reverse()
            ->values();

        $data = $comments->map(function ($c) use ($post) {
            $u = $c->user();
            return [
                'id' => $c->id,
                'name' => $u ? ($u->hide_name == 'yes' ? $u->username : $u->name) : '',
                'username' => $u ? $u->username : '',
                'avatar' => $u ? Helper::getFile(config('path.avatar') . $u->avatar) : '',
                'comment' => $c->comment,
                'media' => $c->media ? Helper::getFile(config('path.music') . $c->media) : null,
                'is_creator' => (bool) ($u && $c->user_id == $post->user_id),
            ];
        });

        return response()->json(['success' => true, 'comments' => $data]);
    }
}
