<?php

namespace App\Assistant;

/**
 * What came back from a turn: either an answer, or a change waiting on the
 * user's say-so.
 */
class AgentResult
{
    private function __construct(
        public readonly string $text,
        public readonly ?string $pendingToolUseId = null,
        public readonly ?string $pendingTool = null,
        public readonly ?string $pendingDescription = null,
    ) {}

    public static function answered(string $text): self
    {
        return new self($text);
    }

    public static function needsConfirmation(
        string $toolUseId,
        string $tool,
        string $description,
        string $text,
    ): self {
        return new self($text, $toolUseId, $tool, $description);
    }

    public function awaitingConfirmation(): bool
    {
        return $this->pendingToolUseId !== null;
    }
}
