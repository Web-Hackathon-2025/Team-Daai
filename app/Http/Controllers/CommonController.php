<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class CommonController extends Controller
{
    use ResponseAPI;
    public function common()
    {
        $camera_protocols = [
            "RTSP",
            "P2P",
            "RTMP",
            "IP",
            "HLS"
        ];
        $camera_status=[
            "active",
            "disabled",
            "online",
            "offline",
            "unstable"
        ];

        $data = [
            "camera_protocols" => $camera_protocols,
            "camera_status" => $camera_status
        ];

        return $this->success(ResponseMessage::FETCHED, $data, 200);
    }
}
