<?php
// Тип кэша изображений

namespace BotKit\Enums;

enum ImageCacheType: int {
    case Grades             = 0;
    case GroupSchedule      = 1;
    case TeacherSchedule    = 2;
    case OccupancySchedule  = 3;
}
