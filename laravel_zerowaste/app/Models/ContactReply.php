<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $contact_message_id
 * @property string $sender
 * @property string $mensaje
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ContactReply where(string $column, mixed $value)
 * @method static \Illuminate\Database\Eloquent\Builder|ContactReply orderBy(string $column, string $direction = 'asc')
 * @method \Illuminate\Database\Eloquent\Model belongsTo(string $related, string|null $foreignKey = null, string|null $ownerKey = null)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class ContactReply extends Model
{
    protected $table = 'contact_replies';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'contact_message_id',
        'sender',
        'mensaje',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the parent contact message.
     */
    public function contactMessage()
    {
        return $this->belongsTo(ContactMessage::class);
    }
}
