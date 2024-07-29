<?php

namespace App\Enums;

enum SurveyStatusEnum: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case CLOSED = 'closed';
}
