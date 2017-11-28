<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Chat;

class ChatsController extends Controller
{
    public function show($id)
    {
        return (new Chat)->getAllByMeAnd($id);
    }

    public function store(Request $request)
    {
        if (!$request->file('audio')->getClientSize()) {
            return;
        }

        return (new Chat)->saveAudio($request->file('audio'), $request->input('receiver'));
    }
}
