<?php
// Перечисление для состояний пользователей

namespace BotKit\Enums;

enum State: int {
	case Any = -1;
	case FirstInteraction = 0;
    case NoResponse = 1;
    case Hub = 2;
    case EnterJournalLogin = 3;
    case EnterJournalPassword = 4;
}
