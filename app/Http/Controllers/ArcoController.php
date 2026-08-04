<?php

namespace App\Http\Controllers;

use App\Models\ArcoRequest;
use Illuminate\Http\Request;

class ArcoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * El usuario envia una solicitud ARCO+ (Ley 21.719).
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:access,rectification,deletion,opposition,portability',
            'details' => 'nullable|string|max:2000',
        ]);

        ArcoRequest::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'details' => $request->details,
            'status' => 'pending',
        ]);

        return back()->with('arco_success', __('general.arco_request_sent'));
    }

    /**
     * Panel admin: listado de solicitudes ARCO+.
     */
    public function adminIndex()
    {
        $requests = ArcoRequest::orderBy('id', 'desc')->paginate(30);

        return view('admin.arco-requests', ['requests' => $requests]);
    }

    /**
     * Panel admin: actualiza el estado de una solicitud.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,rejected',
        ]);

        $arco = ArcoRequest::findOrFail($id);
        $arco->status = $request->status;
        $arco->save();

        return back()->with('success_message', __('general.arco_status_updated'));
    }
}
