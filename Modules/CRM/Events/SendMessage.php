<?php

namespace Modules\CRM\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;

use Illuminate\Broadcasting\InteractsWithSockets;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Auth;

class SendMessage implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    private $participants;
    private $message;
    private $ofUserId;
    private $channelListenCrm;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($participants, $message, $ofUserId, $conversationId)
    {
        $this->participants = $participants;
        $this->message  = $message;
        $this->ofUserId  = $ofUserId;

        $appCodeUnique = env('VITE_APP_CODE', 'ARACODE');

        $this->channelListenCrm = "message-notification-" . $appCodeUnique;
        //dd($this->channelListenCrm);
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel($this->channelListenCrm);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'MessageNotification';
    }
    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastWith()
    {
        return [
            'message' => $this->message,
            'participants' => $this->participants,
            'ofUserId' => $this->ofUserId
        ];
    }
}
