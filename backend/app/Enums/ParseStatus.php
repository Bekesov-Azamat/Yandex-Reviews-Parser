<?php

namespace App\Enums;

enum ParseStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Success = 'success';
    case Failed = 'failed';
    case Partial = 'partial';

    public function isFinished(): bool
    {
        return in_array($this, [
            self::Success,
            self::Failed,
            self::Partial,
        ], true);
    }
}
