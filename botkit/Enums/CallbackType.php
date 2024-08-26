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
    
    // Смена семестра
    case ChangePeriod = 9;
    
    // Выбран новый семестр
    case SelectedPeriod = 10;

    // Смена типа аккаунта
    case ChangeAccountType = 17;
    
    #region После выбора групп
    case SelectedGroupForStudentRegister = 3;
    case SelectedGroupForStudentEdit = 8;
    case SelectedGroupForOtherRasp = 13;
    case SelectedGroupForNewAccountType = 19;
    #endregion

    #region После выбора даты
    case SelectedDateForCurrentStudentRasp = 11;
    case SelectedDateForCurrentTeacherRasp = 20;
    case SelectedDateForGroupRasp = 12;
    case SelectedDateForTeacherRasp = 16;
    case SelectedDateForCabinetRasp = 21;
    #endregion

    #region После выбора сотрудника
    case SelectedEmployeeForRasp = 15;
    case SelectedEmployeeForNewAccountType = 18;
    #endregion
    
    #region Пагинация
    // В списке групп
    case GroupSelectionPagination = 4;
    // В списке сотрудников
    case EmployeeSelectionPagination = 14;
    #endregion
}
