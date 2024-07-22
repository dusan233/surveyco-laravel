<?php

namespace App\Enums;

enum SurveyCategoryEnum: string
{
    case MARKET_RESEARCH = 'market_research';
    case ACADEMIC_RESEARCH = 'academic_research';
    case STUDENT_FEEDBACK = 'student_feedback';
    case EVENT_FEEDBACK = 'event_feedback';
    case CUSTOMER_FEEDBACK = 'customer_feedback';
}
