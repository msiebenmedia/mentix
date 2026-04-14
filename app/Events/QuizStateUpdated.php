<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStateUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Quiz $quiz,
        public array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('quiz.' . $this->quiz->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'quiz.state.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}