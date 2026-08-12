<?php

namespace App\Services;

use App\Models\Escalation;
use App\Models\Team;
use App\Models\TelegramConversation;

class TelegramBotOrchestrator
{
    public function __construct(
        private readonly OllamaService $ollama,
        private readonly KnowledgeBaseService $knowledgeBase,
        private readonly TelegramService $telegram,
    ) {}

    public function handleIncomingMessage(string $chatId, string $messageText, ?string $customerName = null): void
    {
        $conversation = TelegramConversation::firstOrCreate(
            ['telegram_chat_id' => $chatId],
            ['status' => 'active', 'customer_name' => $customerName, 'last_message_at' => now()]
        );

        $conversation->messages()->create(['role' => 'customer', 'content' => $messageText]);
        $conversation->update(['last_message_at' => now()]);

        if ($this->requestsHuman($messageText)) {
            $this->escalate($conversation, 'El cliente solicitó hablar con un agente.');

            return;
        }

        $context = $this->knowledgeBase->findRelevantContext($messageText);
        $classification = $this->ollama->classifyConfidence($messageText, $context);

        if (! $classification['can_answer'] || ($classification['confidence'] ?? 0) < 70) {
            $this->escalate($conversation, 'El bot no tuvo suficiente confianza para responder.');

            return;
        }

        $history = $conversation->messages()
            ->latest()
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role === 'customer' ? 'user' : 'assistant', 'content' => $m->content])
            ->values()
            ->all();

        $reply = $this->ollama->chat($history, $context);

        $conversation->messages()->create(['role' => 'bot', 'content' => $reply]);
        $this->telegram->sendMessage($chatId, $reply);
    }

    private function requestsHuman(string $message): bool
    {
        $triggers = ['agente', 'humano', 'persona', 'hablar con alguien', 'no me sirve'];

        return collect($triggers)->contains(fn ($t) => str_contains(mb_strtolower($message), $t));
    }

    private function escalate(TelegramConversation $conversation, string $reason): void
    {
        $conversation->update(['status' => 'escalated']);

        $history = $conversation->messages()->orderBy('created_at')->get()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content, 'at' => $m->created_at])
            ->toArray();

        Escalation::create([
            'team_id' => Team::where('slug', 'helpdesk')->value('id'),
            'contact_channel_id' => $conversation->telegram_chat_id, // considera renombrar la columna, ver nota abajo
            'customer_name' => $conversation->customer_name,
            'conversation_history' => $history,
            'ai_summary' => $reason,
            'status' => 'pending',
            'priority' => 'medium',
            'escalated_at' => now(),
        ]);

        $this->telegram->sendMessage(
            $conversation->telegram_chat_id,
            'Tu caso fue escalado a un agente especializado. En breve te contactarán.'
        );
    }
}
