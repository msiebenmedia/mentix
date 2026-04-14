<?php

namespace App\Events;

use App\Models\Quiz;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizStreamUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Quiz $quiz,
        public array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('quiz.stream.' . $this->quiz->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'quiz.stream.updated';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}