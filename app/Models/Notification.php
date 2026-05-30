<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'tipe',
        'is_read',
        'link',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Methods
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }
}
