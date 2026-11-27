<?php

namespace App\Models;

use App\Support\PostHtml;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $table = 'posts';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    /** The body as safe HTML for display (also cleans posts saved before bodies were sanitized). */
    public function safeBody(): string
    {
        return PostHtml::clean($this->body);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
