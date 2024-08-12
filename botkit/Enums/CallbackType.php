<?php
// Перечисление для типов обратного вызова

namespace BotKit\Enums;

enum CallbackType: int {
    // Показать условия использования
    case ShowTos = 0;
    
    // Выбрать тип аккаунта
    case SelectedAccountType = 1;
    
    // Выбран курс группы
    case SelectedGroupNum = 2;
    
    // Переход к вводу логина АВЕРС
    case EnterJournalLogin = 5;
    
    // Пропуск ввода логина и пароля
    case SkipCredentials = 6;
    
    // Смена группы
    case ChangeGroup = 7;
    
    #region После выбора групп
    case SelectedGroupForStudentRegister = 3;
    case SelectedGroupForStudentEdit = 8;
    #endregion
    
    #region Пагинация
    // В списке групп
    case GroupSelectionPagination = 4;
    #endregion
}
