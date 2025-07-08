<?php

namespace App\Events;

use App\Models\TinNhan;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;

class TinNhanGuiEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $tinNhan;

    public function __construct(TinNhan $tinNhan)
    {
        $this->tinNhan = $tinNhan;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('tin-nhan.' . $this->tinNhan->nguoi_nhan);
    }

    public function broadcastWith()
    {
        return [
            'id_tin_nhan' => $this->tinNhan->id_tin_nhan,
            'nguoi_gui' => $this->tinNhan->nguoi_gui,
            'nguoi_nhan' => $this->tinNhan->nguoi_nhan,
            'bai_dang_lien_quan' => $this->tinNhan->bai_dang_lien_quan,
            'noi_dung' => $this->tinNhan->noi_dung,
            'thoi_gian_gui' => $this->tinNhan->thoi_gian_gui->toIso8601String(), // ✅
        ];
    }

    public function broadcastAs()
    {
        return 'TinNhanEvent';
    }
}
