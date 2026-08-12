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

        // Mismas reglas que los comentarios base: respeta el ajuste del creador
        // y los bloqueos entre usuarios.
        if (! $post->creator->allow_comments) {
            return response()->json([
                'success' => false,
                'errors' => ['error' => __('general.comments_disabled')],
            ]);
        }

        if (! $isCreator && auth()->user()->checkRestriction($post->user_id)) {
            return response()->json([
                'success' => false,
                'errors' => ['error' => __('general.error')],
            ]);
        }

        $rules = ['updates_id' => 'required|integer'];
        if ($request->hasFile('voice') && $isCreator) {
            // mp4/aac cubren la nota de voz de iPhone (MediaRecorder graba audio/mp4).
            $rules['voice'] = 'mimes:mp3,mpga,wav,ogg,m4a,mp4,aac,webm|max:20480';
        } elseif ($request->filled('sticker')) {
            $rules['sticker'] = 'string|max:500|starts_with:http';
        } elseif ($request->filled('gif_image')) {
            $rules['gif_image'] = 'string|max:500|starts_with:http';
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
        $c->sticker = $request->filled('sticker') ? $request->sticker : null;
        $c->gif_image = $request->filled('gif_image') ? $request->gif_image : null;

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
    public function fetch(Request $request, $id)
    {
        $post = Updates::findOrFail($id);

        // Hilo plano en vivo sin limite total: se cargan por paginas con "Ver mas".
        $perPage = 10;

        $total = PostLiveComments::where('updates_id', $id)->count();

        $query = PostLiveComments::where('updates_id', $id)->orderBy('id', 'desc');

        if ($request->filled('before')) {
            $query->where('id', '<', (int) $request->before);
        }

        $comments = $query->take($perPage)
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
                'sticker' => $c->sticker,
                'gif_image' => $c->gif_image,
                'is_creator' => (bool) ($u && $c->user_id == $post->user_id),
            ];
        });

        return response()->json([
            'success' => true,
            'comments' => $data,
            'total' => $total,
            'total_label' => trans_choice('general.comment_comments', $total, ['total' => number_format($total)]),
        ]);
    }
}
