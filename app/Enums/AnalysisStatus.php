<?php

declare(strict_types=1);

namespace App\Enums;

enum AnalysisStatus: string
{
    case NotStarted = 'not_started';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
}
