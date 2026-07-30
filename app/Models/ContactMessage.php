<?php

namespace App\Models;

use App\Enums\ContactStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory, HasUuids, Auditable;

    protected $fillable = [
        'name',
        'contact_type',
        'contact_value',
        'subject',
        'message',
    ];

    protected $casts = [
        'status' => ContactStatus::class,
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function getAuditAllowlist(): array
    {
        return [
            'status',
            'read_at',
            'archived_at',
        ];
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->read_at = now();
        }

        if ($this->status === ContactStatus::NEW) {
            $this->status = ContactStatus::READ;
        }
        
        $this->save();
    }

    public function changeStatus(ContactStatus $status): void
    {
        if (is_null($this->read_at) && $this->status === ContactStatus::NEW && $status !== ContactStatus::NEW) {
            $this->read_at = now();
        }
        
        $this->status = $status;
        $this->save();
    }

    public function archive(): void
    {
        $this->archived_at = now();
        $this->save();
    }

    public function restoreFromArchive(): void
    {
        $this->archived_at = null;
        $this->save();
    }
}