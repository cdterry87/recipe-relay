<?php

namespace App\Mail;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Email a friend": a recipe card sent to an address a member typed in. The friend's address
 * is used for this one email and never stored, and the member's own address is not shared.
 */
class RecipeShared extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Recipe $recipe, public User $sender, public ?string $note = null)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->sender->name} shared a recipe with you: {$this->recipe->title}");
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.recipe-shared',
            text: 'mail.recipe-shared-text',
            with: ['recipe' => $this->recipe, 'sender' => $this->sender->name, 'note' => $this->note, 'url' => $this->recipe->shareUrl()],
        );
    }
}
