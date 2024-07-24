<?php

namespace App\Enums;

enum QuestionTypeEnum: string
{
    case SINGLE_CHOICE = 'single_choice';
    case CHECKBOX = 'checkbox';
    case DROPDOWN = 'dropdown';
    case TEXTBOX = 'textbox';
}
